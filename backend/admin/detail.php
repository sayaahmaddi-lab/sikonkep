<?php
// =============================================================
// Halaman detail: melihat satu pengisian lengkap + ubah status
// =============================================================

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

keamanan_header();
require_admin();   // hanya admin yang sudah login

$pdo = db();

// ---- Update status ----
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify($_POST['csrf'] ?? null)) {
        http_response_code(403);
        echo 'Token keamanan tidak valid.';
        exit;
    }
    $id     = (int)($_POST['id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    if ($id && $status) {
        $st = $pdo->prepare('UPDATE pengisian SET status = ? WHERE id = ?');
        $st->execute([$status, $id]);
    }
    header('Location: detail.php?id=' . $id . '&saved=1');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo 'ID tidak valid.'; exit; }

$st = $pdo->prepare('SELECT * FROM pengisian WHERE id = ?');
$st->execute([$id]);
$r = $st->fetch();

if (!$r) { echo 'Data tidak ditemukan.'; exit; }

$statusList = ['BARU', 'DIPROSES', 'VERIFIKASI', 'DITERIMA', 'DITOLAK'];

function tab_lihat($namaBagian, $json, $kolom)
{
    $data = is_string($json) ? json_decode($json, true) : $json;
    if (!is_array($data) || !$data) {
        echo '<p style="color:#999">Bagian ini kosong (tidak diisi).</p>';
        return;
    }
    // Ambil nomor baris yang ada
    $baris = [];
    foreach ($data as $k => $v) if (preg_match('/^.*?(\d+|dst)_0$/', $k, $m)) $baris[] = $m[1];
    $baris = array_values(array_unique($baris));

    echo '<table><thead><tr><th style="width:36px">No.</th>';
    foreach ($kolom as $judul) echo '<th>' . htmlspecialchars($judul) . '</th>';
    echo '</tr></thead><tbody>';
    $no = 1;
    foreach ($baris as $idx => $label) {
        // cari prefix umum (label bisa '1','2','3','dst')
        // bangun nilai tiap kolom
        $nilaiKolom = [];
        for ($j = 0; $j < count($kolom); $j++) {
            // coba semua prefix pendek (a,b,c,d) lalu label
            foreach (['a', 'b', 'c', 'd'] as $p) {
                $kunci = $p . $label . '_' . $j;
                if (array_key_exists($kunci, $data)) { $nilaiKolom[$j] = $data[$kunci]; break; }
            }
        }
        $terisi = false;
        foreach ($nilaiKolom as $v) if (is_string($v) && trim($v) !== '') { $terisi = true; break; }
        if (!$terisi) continue;

        echo '<tr><td>' . $no . '</td>';
        for ($j = 0; $j < count($kolom); $j++) {
            echo '<td>' . nl2br(htmlspecialchars($nilaiKolom[$j] ?? '')) . '</td>';
        }
        echo '</tr>';
        $no++;
    }
    echo '</tbody></table>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detail Pengisian #<?= (int)$r['id'] ?></title>
<style>
  :root { --biru:#0f2438; --biru2:#1c3a57; --abu:#eef1f5; --hijau:#1c7c3c; }
  * { box-sizing:border-box; }
  body { margin:0; font-family:"Segoe UI",Arial,sans-serif; background:#eef2f6; color:#111; }
  .bar { background:var(--biru); color:#fff; padding:12px 20px; display:flex; gap:14px; align-items:center; }
  .bar h1 { font-size:16px; margin:0; }
  .bar a { color:#bcd0e6; font-size:13px; text-decoration:none; }
  .bar .logout { margin-left:auto; color:#eab7b7; border:1px solid rgba(255,255,255,.3); padding:5px 12px; border-radius:6px; }
  .bar .logout:hover { background:#7f2b2b; color:#fff; }
  .wrap { padding:20px; max-width:980px; margin:0 auto; }
  .kartu { background:#fff; border:1px solid #d7dee6; border-radius:8px; padding:18px; margin-bottom:18px; }
  .kartu h2 { font-size:14px; margin:0 0 12px; text-transform:uppercase; border-bottom:2px solid var(--abu); padding-bottom:6px; }
  .grid { display:grid; grid-template-columns:180px 1fr; gap:6px 12px; font-size:13.5px; }
  .grid .lbl { font-weight:600; color:#444; }
  table { width:100%; border-collapse:collapse; font-size:12.5px; margin-top:8px; }
  th,td { border:1px solid #d7dee6; padding:6px 8px; vertical-align:top; }
  th { background:var(--abu); font-size:12px; }
  .badge { display:inline-block; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:700; background:#e7f3ec; color:var(--hijau); }
  .ttd-box { border:1px solid #ccc; border-radius:6px; padding:8px; background:#fff; display:inline-block; }
  .ttd-box img { max-width:420px; max-height:150px; }
  label { font-weight:600; font-size:13px; }
  select, .btn { font:inherit; font-size:13px; padding:7px 12px; border-radius:6px; border:1px solid #c4cdd6; }
  .btn { background:var(--biru2); color:#fff; cursor:pointer; border-color:var(--biru2); }
  .ok { background:#e7f3ec; color:#1c7c3c; padding:8px 12px; border-radius:6px; font-size:13px; margin-bottom:14px; }
  .teks-jawaban { font-size:13.5px; line-height:1.6; }
  .sub { margin:4px 0 0 22px; padding-left:14px; border-left:2px solid #c7d2de; font-size:13px; color:#333; }
</style>
</head>
<body>
  <div class="bar">
    <h1>Detail Pengisian #<?= (int)$r['id'] ?></h1>
    <a href="index.php">← Kembali ke daftar</a>
    <a class="logout" href="logout.php">Keluar</a>
  </div>
  <div class="wrap">
    <?php if (($_GET['saved'] ?? '') === '1'): ?><div class="ok">✓ Status berhasil diperbarui.</div><?php endif; ?>

    <div class="kartu">
      <h2>Identitas Pemohon</h2>
      <div class="grid">
        <span class="lbl">Nama</span><span><?= htmlspecialchars($r['nama']) ?></span>
        <span class="lbl">NIP</span><span><?= htmlspecialchars($r['nip']) ?></span>
        <span class="lbl">Pangkat/Gol.</span><span><?= htmlspecialchars($r['pangkat'] ?: '-') ?></span>
        <span class="lbl">Jabatan</span><span><?= htmlspecialchars($r['jabatan'] ?: '-') ?></span>
        <span class="lbl">Perangkat Daerah</span><span><?= htmlspecialchars($r['perangkat'] ?: '-') ?></span>
        <span class="lbl">Unit Kerja</span><span><?= htmlspecialchars($r['unit_kerja'] ?: '-') ?></span>
        <span class="lbl">Tanggal Pengisian</span><span><?= htmlspecialchars($r['tanggal_isi'] ?: '-') ?></span>
        <span class="lbl">Dikirim pada</span><span><?= date('d/m/Y H:i:s', strtotime($r['created_at'])) ?></span>
      </div>
    </div>

    <div class="kartu">
      <h2>A. Hubungan Keluarga dan Kerabat</h2>
      <?php tab_lihat('A', $r['bagian_a'], ['Nama','Hubungan','Pekerjaan/Jabatan & Instansi','Situasi Konflik Kepentingan']); ?>
    </div>

    <div class="kartu">
      <h2>B. Hubungan Bisnis dan Finansial</h2>
      <?php tab_lihat('B', $r['bagian_b'], ['Bentuk Kepemilikan','Nilai/Prosentase','Nama Badan Usaha/Lokasi Aset','Situasi Konflik Kepentingan']); ?>
    </div>

    <div class="kartu">
      <h2>C. Pekerjaan Lain di Luar Pekerjaan Pokok</h2>
      <?php tab_lihat('C', $r['bagian_c'], ['Bentuk Pekerjaan','Jabatan/Fungsi','Nama Perusahaan/Institusi','Situasi Konflik Kepentingan']); ?>
    </div>

    <div class="kartu">
      <h2>D. Jabatan Publik Lain (Rangkap Jabatan)</h2>
      <?php tab_lihat('D', $r['bagian_d'], ['Jabatan','Perangkat Daerah/Unit Kerja/Institusi','Situasi Konflik Kepentingan']); ?>
    </div>

    <div class="kartu">
      <h2>E. Hubungan atau Afiliasi Lainnya</h2>
      <?php
        $e = json_decode($r['bagian_e'] ?? '[]', true);
        if (!is_array($e) || !$e) { echo '<p style="color:#999">Tidak diisi.</p>'; }
        else {
          foreach ([1,2,3] as $n) {
            $jawab = isset($e['e'.$n.'_ya']) ? ($e['e'.$n.'_ya'] ? 'Ya' : ($e['e'.$n.'_tidak'] ? 'Tidak' : '-')) : '-';
            echo '<div class="teks-jawaban" style="margin-bottom:10px">';
            echo '<b>' . $n . '.</b> Jawaban: <b>' . $jawab . '</b>';
            if ($jawab === 'Ya') {
              echo '<div class="sub">';
              echo 'Nama Organisasi/Institusi : ' . nl2br(htmlspecialchars($e['e'.$n.'_nama'] ?? $e['e'.$n.'_institusi'] ?? '-')) . '<br>';
              echo 'Posisi/Jabatan : ' . nl2br(htmlspecialchars($e['e'.$n.'_posisi'] ?? '-')) . '<br>';
              if (isset($e['e'.$n.'_situasi'])) echo 'Situasi : ' . nl2br(htmlspecialchars($e['e'.$n.'_situasi'])) . '<br>';
              echo '</div>';
            }
            echo '</div>';
          }
        }
      ?>
    </div>

    <div class="kartu">
      <h2>F. Rencana Pasca Pensiun / Pengunduran Diri</h2>
      <?php
        $f = json_decode($r['bagian_f'] ?? '[]', true);
        if (!is_array($f) || !$f) { echo '<p style="color:#999">Tidak diisi.</p>'; }
        else {
          $fYa = !empty($f['ya']);
          echo '<div class="teks-jawaban">Jawaban memiliki rencana: <b>' . ($fYa ? 'Ya' : (!empty($f['tidak']) ? 'Tidak' : '-')) . '</b></div>';
          if ($fYa) {
            echo '<div class="sub">';
            echo 'a. Nama perusahaan/jenis usaha : ' . nl2br(htmlspecialchars($f['perusahaan'] ?? '-')) . '<br>';
            echo '&nbsp;&nbsp;&nbsp;Posisi/Jabatan : ' . nl2br(htmlspecialchars($f['posisi1'] ?? '-')) . '<br>';
            echo '&nbsp;&nbsp;&nbsp;Situasi : ' . nl2br(htmlspecialchars($f['situasi1'] ?? '-')) . '<br>';
            echo 'b. Nama organisasi : ' . nl2br(htmlspecialchars($f['organisasi'] ?? '-')) . '<br>';
            echo '&nbsp;&nbsp;&nbsp;Posisi/Jabatan : ' . nl2br(htmlspecialchars($f['posisi2'] ?? '-')) . '<br>';
            echo '&nbsp;&nbsp;&nbsp;Situasi : ' . nl2br(htmlspecialchars($f['situasi2'] ?? '-')) . '<br>';
            echo '</div>';
          }
        }
      ?>
    </div>

    <div class="kartu">
      <h2>Tanda Tangan & Pengelolaan</h2>
      <div class="grid">
        <span class="lbl">Tanda Tangan</span>
        <span class="ttd-box">
          <?php if (!empty($r['ttd']) && strpos($r['ttd'], 'data:image/') === 0): ?>
            <img src="<?= htmlspecialchars($r['ttd']) ?>" alt="Ttd">
          <?php else: ?><span style="color:#aaa">Tidak ada tanda tangan</span><?php endif; ?>
        </span>
        <span class="lbl">Nama Ttd</span><span><?= htmlspecialchars($r['ttd_nama'] ?: '-') ?></span>
        <span class="lbl">NIP Ttd</span><span><?= htmlspecialchars($r['ttd_nip'] ?: '-') ?></span>
        <span class="lbl">Status</span><span><span class="badge"><?= htmlspecialchars($r['status']) ?></span></span>
      </div>
      <form method="post" style="margin-top:14px; display:flex; gap:10px; align-items:center;">
        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
        <?= csrf_field() ?>
        <label for="status">Ubah status:</label>
        <select name="status" id="status">
          <?php foreach ($statusList as $s): ?>
            <option value="<?= htmlspecialchars($s) ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn">Simpan Status</button>
      </form>
    </div>
  </div>
</body>
</html>
