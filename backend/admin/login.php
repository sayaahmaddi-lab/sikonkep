<?php
// =============================================================
// Halaman login admin
// =============================================================

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';

keamanan_header();
mulai_sesi_aman();

// Jika sudah login, langsung masuk
if (sudah_login()) {
    header('Location: index.php');
    exit;
}

$pesan = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    // Cek CSRF
    if (!csrf_verify($_POST['csrf'] ?? null)) {
        http_response_code(403);
        $pesan = 'Token keamanan tidak valid. Silakan coba lagi.';
    } else {
        $user = trim($_POST['username'] ?? '');
        $pass = (string)($_POST['password'] ?? '');
        [$ok, $msg] = attempt_login($user, $pass);
        if ($ok) {
            header('Location: index.php');
            exit;
        }
        $pesan = $msg;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Admin Formulir Kepentingan Pribadi</title>
<style>
  :root { --biru:#0f2438; --biru2:#1c3a57; }
  * { box-sizing:border-box; }
  body {
    margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
    background:linear-gradient(135deg,#0f2438 0%,#1c3a57 60%,#2a4e73 100%);
    font-family:"Segoe UI",Arial,sans-serif;
  }
  .kartu {
    width:340px; background:#fff; border-radius:10px; padding:30px 28px;
    box-shadow:0 12px 40px rgba(0,0,0,.35);
  }
  .kartu h1 { margin:0 0 4px; font-size:18px; color:var(--biru); }
  .kartu .sub { margin:0 0 22px; font-size:12.5px; color:#677; }
  label { display:block; font-size:12.5px; font-weight:600; color:#334; margin:12px 0 5px; }
  input[type=text], input[type=password] {
    width:100%; padding:10px 12px; border:1px solid #c4cdd6; border-radius:7px;
    font:inherit; font-size:14px;
  }
  input:focus { outline:2px solid #2a4e73; outline-offset:1px; border-color:#2a4e73; }
  button {
    width:100%; margin-top:20px; padding:11px; border:none; border-radius:7px;
    background:var(--biru2); color:#fff; font:inherit; font-size:14px; font-weight:600; cursor:pointer;
  }
  button:hover { background:var(--biru); }
  .pesan {
    margin:14px 0 0; padding:9px 12px; border-radius:7px; font-size:12.5px;
    background:#fdeaea; color:#a33; border:1px solid #f3c6c6;
  }
  .kembali { display:block; text-align:center; margin-top:16px; font-size:12px; color:#2a4e73; text-decoration:none; }
</style>
</head>
<body>
  <div class="kartu">
    <h1>Admin SiKonKep</h1>
    <p class="sub">Formulir Daftar Kepentingan Pribadi</p>
    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required autofocus>
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
      <button type="submit">Masuk</button>
    </form>
    <?php if ($pesan): ?><div class="pesan"><?= htmlspecialchars($pesan) ?></div><?php endif; ?>
    <a class="kembali" href="index.php">← Kembali ke halaman daftar</a>
  </div>
</body>
</html>
