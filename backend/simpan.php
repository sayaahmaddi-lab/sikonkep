<?php
// =============================================================
// Endpoint: menerima data formulir dari halaman HTML lalu menyimpannya
// ke tabel `pengisian`.
// Dikirim sebagai JSON via POST oleh formulir.
// =============================================================

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

// Hanya menerima POST
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Metode tidak diizinkan.']);
    exit;
}

// Baca & bersihkan input
$d = [];
foreach (baca_body_json() as $k => $v) {
    $k = preg_replace('/[^A-Za-z0-9_]/', '', (string)$k);
    $d[$k] = is_string($v) ? trim($v) : $v;
}

// ====== Validasi identitas ======
$wajib = ['nama', 'nip'];
$kurangWajib = [];
foreach ($wajib as $f) {
    if (!isset($d[$f]) || $d[$f] === '') {
        $kurangWajib[] = $f;
    }
}
if ($kurangWajib) {
    http_response_code(422);
    echo json_encode([
        'ok'      => false,
        'message' => 'Kolom wajib belum diisi: ' . implode(', ', $kurangWajib),
    ]);
    exit;
}

// Ornamen tanda tangan (data URL PNG). Batasi tipe agar aman.
function bersihkan_ttd(?string $ttd): ?string {
    if ($ttd === null || $ttd === '') return null;
    if (mb_strlen($ttd) > 5 * 1024 * 1024) return null; // maks 5 MB agar database tidak membengkak
    if (!preg_match('#^data:image/(png|jpeg|jpg);base64,[A-Za-z0-9+/=]+$#', $ttd)) return null;
    return $ttd;
}

$nilai = [
    'nama'        => mb_substr($d['nama'] ?? '', 0, 150),
    'nip'         => mb_substr($d['nip'] ?? '', 0, 50),
    'pangkat'     => mb_substr($d['pangkat'] ?? '', 0, 100),
    'jabatan'     => mb_substr($d['jabatan'] ?? '', 0, 150),
    'perangkat'   => mb_substr($d['perangkat'] ?? '', 0, 150),
    'unit_kerja'  => mb_substr($d['unit_kerja'] ?? '', 0, 150),
    'tanggal_isi' => mb_substr($d['tanggal_isi'] ?? '', 0, 50),
    'bagian_a'    => $d['bagian_a'] ?? null,
    'bagian_b'    => $d['bagian_b'] ?? null,
    'bagian_c'    => $d['bagian_c'] ?? null,
    'bagian_d'    => $d['bagian_d'] ?? null,
    'bagian_e'    => $d['bagian_e'] ?? null,
    'bagian_f'    => $d['bagian_f'] ?? null,
    'ttd'         => bersihkan_ttd($d['ttd'] ?? null),
    'ttd_nama'    => mb_substr($d['ttd_nama'] ?? '', 0, 150),
    'ttd_nip'     => mb_substr($d['ttd_nip'] ?? '', 0, 50),
];

// Bagian A-F dijamin berupa JSON (kosongkan bila bukan JSON valid)
foreach (['bagian_a', 'bagian_b', 'bagian_c', 'bagian_d', 'bagian_e', 'bagian_f'] as $b) {
    if ($nilai[$b] !== null && $nilai[$b] !== '') {
        json_decode($nilai[$b]);
        if (json_last_error() !== JSON_ERROR_NONE) $nilai[$b] = null;
    }
}

$sql = 'INSERT INTO pengisian
            (nama, nip, pangkat, jabatan, perangkat, unit_kerja, tanggal_isi,
             bagian_a, bagian_b, bagian_c, bagian_d, bagian_e, bagian_f,
             ttd, ttd_nama, ttd_nip)
        VALUES
            (:nama, :nip, :pangkat, :jabatan, :perangkat, :unit_kerja, :tanggal_isi,
             :bagian_a, :bagian_b, :bagian_c, :bagian_d, :bagian_e, :bagian_f,
             :ttd, :ttd_nama, :ttd_nip)';

try {
    $pdo = db();
    $st  = $pdo->prepare($sql);
    $st->execute($nilai);
    $id = (int)$pdo->lastInsertId();

    echo json_encode([
        'ok'      => true,
        'message' => 'Data berhasil disimpan.',
        'id'      => $id,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'ok'      => false,
        'message' => 'Gagal menyimpan: ' . $e->getMessage(),
    ]);
}
