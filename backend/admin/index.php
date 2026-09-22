<?php
// =============================================================
// Halaman admin: daftar seluruh pengisian formulir
// =============================================================

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

keamanan_header();
require_admin();   // hanya admin yang sudah login yang boleh melihat daftar

$pdo = db();

// ---- Export CSV ----
if (($_GET['export'] ?? '') === 'csv') {
    $st = $pdo->query('SELECT * FROM pengisian ORDER BY created_at DESC, id DESC');
    $rows = $st->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="rekap-pengisian-' . date('Ymd-His') . '.csv"');
    $out = fopen('php://output', 'w');
    // BOM agar Excel membaca utf-8
    fwrite($out, "\xEF\xBB\xBF");

    fputcsv($out, ['ID','Nama','NIP','Pangkat/Gol','Jabatan','Perangkat Daerah','Unit Kerja','Tanggal Isi',
                   'Bagian A','Bagian B','Bagian C','Bagian D','Bagian E','Bagian F',
                   'Ada Tanda Tangan','Nama Ttd','NIP Ttd','Status','Dibuat pada']);

    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'],
            $r['nama'],
            $r['nip'],
            $r['pangkat'],
            $r['jabatan'],
            $r['perangkat'],
            $r['unit_kerja'],
            $r['tanggal_isi'],
            $r['bagian_a'],
            $r['bagian_b'],
            $r['bagian_c'],
            $r['bagian_d'],
            $r['bagian_e'],
            $r['bagian_f'],
            (!empty($r['ttd']) ? 'YA' : 'TIDAK'),
            $r['ttd_nama'],
            $r['ttd_nip'],
            $r['status'],
            $r['created_at'],
        ]);
    }
    fclose($out);
    exit;
}

$st = $pdo->query('SELECT * FROM pengisian ORDER BY created_at DESC, id DESC');
$rows = $st->fetchAll();
$total = count($rows);
$belum = 0;
foreach ($rows as $r) if ($r['status'] === 'BARU') $belum++;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Daftar Pengisian Formulir</title>
<style>
  :root { --biru:#0f2438; --biru2:#1c3a57; --hijau:#1c7c3c; --abu:#eef1f5; }
  * { box-sizing:border-box; }
  body { margin:0; font-family:"Segoe UI",Arial,sans-serif; background:#eef2f6; color:#111; }
  .bar { background:var(--biru); color:#fff; padding:12px 20px; display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
  .bar h1 { font-size:16px; margin:0; font-weight:600; }
  .bar .stat { margin-left:auto; font-size:12.5px; background:#132f4c; padding:5px 10px; border-radius:6px; }
  .wrap { padding:20px; max-width:1300px; margin:0 auto; }
  .kotak-info { background:#fff; border:1px solid #d7dee6; border-radius:8px; padding:12px 16px; margin-bottom:16px; font-size:13px; display:flex; gap:24px; flex-wrap:wrap; }
  .kotak-info b { display:block; font-size:20px; }
  table { width:100%; border-collapse:collapse; background:#fff; font-size:12.5px; box-shadow:0 1px 4px rgba(0,0,0,.06); }
  th,td { border:1px solid #d7dee6; padding:7px 9px; vertical-align:top; text-align:left; }
  th { background:var(--abu); font-size:12px; }
  tr:hover td { background:#f8fafc; }
  .badge { display:inline-block; padding:2px 9px; border-radius:999px; font-size:11px; font-weight:600; }
  .badge.baru { background:#e7f3ec; color:#1c7c3c; }
  .badge.lain { background:#eee; color:#555; }
  .btn { display:inline-block; padding:6px 12px; border-radius:6px; font-size:12.5px; text-decoration:none; color:#fff; background:var(--biru2); border:1px solid transparent; cursor:pointer; }
  .btn.hijau { background:var(--hijau); }
  .btn:hover { filter:brightness(1.1); }
  .bar .logout {
    color:#eab7b7; text-decoration:none; font-size:12.5px;
    border:1px solid rgba(255,255,255,.3); padding:5px 12px; border-radius:6px;
  }
  .bar .logout:hover { background:#7f2b2b; color:#fff; }
  .kecil-tabel td { font-size:11.5px; }
  .jempol { width:150px; }
  .jempol img { max-width:150px; max-height:80px; border:1px solid #ccc; border-radius:4px; background:#fff; }
  .kosong { padding:30px; text-align:center; color:#888; }
  code { background:#eef1f5; padding:1px 5px; border-radius:4px; font-size:11px; }
</style>
</head>
<body>
  <div class="bar">
    <h1>Admin — Daftar Pengisian Formulir Kepentingan Pribadi</h1>
    <span class="stat">Total: <b><?= $total ?></b> &nbsp;•&nbsp; Belum ditindak: <b><?= $belum ?></b></span>
    <a class="logout" href="logout.php">Keluar</a>
  </div>
  <div class="wrap">
    <div class="kotak-info">
      <span><b><?= $total ?></b> Total pengisian</span>
      <span><b><?= $belum ?></b> Berstatus BARU</span>
      <span style="margin-left:auto">
        <a class="btn" href="index.php?export=csv">⬇ Export CSV</a>
      </span>
    </div>

    <?php if (!$rows): ?>
      <div class="kosong">Belum ada data. Kirim data dari halaman formulir terlebih dahulu.</div>
    <?php else: ?>
      <div style="overflow-x:auto;">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nama / NIP</th>
            <th>Jabatan</th>
            <th>Perangkat Daerah / Unit Kerja</th>
            <th>Tanggal Isi</th>
            <th>A</th><th>B</th><th>C</th><th>D</th>
            <th>E (Ya di pil.)</th><th>F (Ya)</th>
            <th>Ttd</th>
            <th>Status</th>
            <th>Dikirim pada</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
          <?php
            $e = json_decode($r['bagian_e'] ?? '[]', true);
            $f = json_decode($r['bagian_f'] ?? '[]', true);
            $eYa = 0;
            if (is_array($e)) {
                foreach ([1, 2, 3] as $n) {
                    if (!empty($e['e' . $n . '_ya'])) $eYa++;
                }
            }
            $fYa = is_array($f) && !empty($f['ya']) ? 'Ya' : (is_array($f) && !empty($f['tidak']) ? 'Tidak' : '-');
            $jA = json_decode($r['bagian_a'] ?? '[]', true);
            $jB = json_decode($r['bagian_b'] ?? '[]', true);
            $jC = json_decode($r['bagian_c'] ?? '[]', true);
            $jD = json_decode($r['bagian_d'] ?? '[]', true);
            $ada = function ($arr, $prefix) {
              if (!is_array($arr)) return '-';
              $n = 0;
              foreach ($arr as $k => $v) {
                if (strpos($k, $prefix) === 0 && isset($v[0]) && $v[0] !== '') $n++;
              }
              return $n ?: '-';
            };
          ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td>
              <b><?= htmlspecialchars($r['nama']) ?></b><br>
              <span style="color:#666"><?= htmlspecialchars($r['nip']) ?></span>
            </td>
            <td><?= htmlspecialchars($r['jabatan'] ?: '-') ?></td>
            <td>
              <?= htmlspecialchars($r['perangkat'] ?: '-') ?>
              <?php if ($r['unit_kerja']): ?><br><span style="color:#666"><?= htmlspecialchars($r['unit_kerja']) ?></span><?php endif; ?>
            </td>
            <td><?= htmlspecialchars($r['tanggal_isi'] ?: '-') ?></td>
            <td><?= $ada($jA, 'a1_') ?></td>
            <td><?= $ada($jB, 'b1_') ?></td>
            <td><?= $ada($jC, 'c1_') ?></td>
            <td><?= $ada($jD, 'd1_') ?></td>
            <td><?= $eYa ? $eYa . ' item' : '-' ?></td>
            <td><?= $fYa ?></td>
            <td class="jempol">
              <?php if (!empty($r['ttd']) && strpos($r['ttd'], 'data:image/') === 0): ?>
                <img src="<?= htmlspecialchars($r['ttd']) ?>" alt="Tanda tangan">
              <?php else: ?>
                <span style="color:#aaa">-</span>
              <?php endif; ?>
            </td>
            <td><span class="badge <?= $r['status'] === 'BARU' ? 'baru' : 'lain' ?>"><?= htmlspecialchars($r['status']) ?></span></td>
            <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
            <td><a class="btn" href="detail.php?id=<?= (int)$r['id'] ?>">Lihat</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
