<?php
// =============================================================
// KONFIGURASI KONEKSI MYSQL - Silakan sesuaikan dengan server Anda
// =============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'sikonkep');
define('DB_USER', 'root');
define('DB_PASS', '');

// =============================================================
// KEAMANAN HALAMAN ADMIN (LOGIN)
// =============================================================

// Username untuk masuk ke halaman admin.
define('ADMIN_USER', 'admin');

// Password admin. Boleh ditulis polos ("rahasia123").
// Lebih aman: simpan dalam bentuk hash bcrypt. Cara:
//   1) jalankan:  php backend/tools/hash_pass.php "password-anda"
//   2) tempel hasilnya di sini menggantikan teks di bawah.
define('ADMIN_PASS', 'ganti-password-anda');

// Masa berlaku sesi login (detik). 0 = sampai browser ditutup.
define('ADMIN_SESSION_LIFETIME', 28800);   // 8 jam

// Maksimal percobaan login salah sebelum dikunci sementara (anti brute-force).
define('ADMIN_MAX_ATTEMPTS', 5);

// Boleh tidak diubah.
date_default_timezone_set('Asia/Jakarta');
