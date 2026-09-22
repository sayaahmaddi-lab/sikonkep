<?php
// =============================================================
// Autentikasi & perlindungan untuk halaman ADMIN.
//
//   require_admin()       → panggil di ATAU-index admin yang BUTUH login
//   csrf_token()          → ambil token untuk dimasukkan ke form
//   csrf_verify()         → cek token sebelum memproses POST
//   attempt_login()       → verifikasi POST login + proteksi brute-force
//   logout_admin()        → akhiri sesi
//
// =============================================================

// ------------------------------------------------------------------
// Helper: set header keamanan dasar (panggil di semua halaman admin)
// ------------------------------------------------------------------
function keamanan_header(): void {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
}

// ------------------------------------------------------------------
// Sesi dengan masa berlaku
// ------------------------------------------------------------------
function mulai_sesi_aman(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;

    session_set_cookie_params([
        'lifetime' => (int)ADMIN_SESSION_LIFETIME, // 0 = sampai browser ditutup
        'path'     => '/',
        'httponly' => true,      // cookie tidak bisa dibaca JavaScript
        'samesite' => 'Lax',     // kurangi risiko CSRF
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_name('SKADMSESS');
    session_start();

    // Menolak sesi terlalu tua (bila lifetime > 0)
    if (ADMIN_SESSION_LIFETIME > 0 && isset($_SESSION['login_at'])) {
        if (time() - (int)$_SESSION['login_at'] > ADMIN_SESSION_LIFETIME) {
            session_unset();
            session_destroy();
            return; // sesi sudah dihancurkan; pemanggil akan melihat belum login
        }
    }

    // Regenerasi ID sesi berkala (cegah session fixation)
    if (!isset($_SESSION['regenerasi_pada'])) {
        $_SESSION['regenerasi_pada'] = time();
    } elseif (time() - (int)$_SESSION['regenerasi_pada'] > 300) { // tiap 5 menit
        session_regenerate_id(true);
        $_SESSION['regenerasi_pada'] = time();
    }
}

// ------------------------------------------------------------------
// Cek apakah sudah login sebagai admin
// ------------------------------------------------------------------
function sudah_login(): bool {
    return !empty($_SESSION['admin_ok']) && $_SESSION['admin_ok'] === true;
}

// ------------------------------------------------------------------
// Proteksi brute-force sederhana (berbasis IP)
// ------------------------------------------------------------------
function kunci_file(): string {
    $dir = sys_get_temp_dir();
    $ip  = preg_replace('/[^A-Za-z0-9]/', '_', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    return $dir . '/sikonkep_kunci_' . $ip . '.lock';
}

function catat_gagal(): int {
    $f = kunci_file();
    $data = file_exists($f) ? (array)json_decode((string)file_get_contents($f), true) : [];
    $data['gagal'] = (int)($data['gagal'] ?? 0) + 1;
    $data['sampai'] = time() + 60; // window 60 detik
    @file_put_contents($f, json_encode($data), LOCK_EX);
    return (int)$data['gagal'];
}

function terkunci(): int {
    $f = kunci_file();
    if (!file_exists($f)) return 0;
    $data = (array)json_decode((string)file_get_contents($f), true);
    if (time() >= (int)($data['sampai'] ?? 0)) {
        @unlink($f);
        return 0;
    }
    return (int)($data['gagal'] ?? 0);
}

function reset_gagal(): void {
    @unlink(kunci_file());
}

// ------------------------------------------------------------------
// Proses login
// ------------------------------------------------------------------
function attempt_login(string $user, string $pass): array {
    // Sudah melampaui batas percobaan?
    $gagal = terkunci();
    if ($gagal >= (int)ADMIN_MAX_ATTEMPTS) {
        return [false, 'Terlalu banyak percobaan. Coba lagi dalam 1 menit.'];
    }

    $cocokUser = hash_equals(ADMIN_USER, $user);
    // Menerima password polos maupun hash bcrypt (diawali $2y$)
    $cocokPass = strncmp(ADMIN_PASS, '$2y$', 4) === 0
        ? password_verify($pass, ADMIN_PASS)
        : hash_equals(ADMIN_PASS, $pass);

    if ($cocokUser && $cocokPass) {
        reset_gagal();
        session_regenerate_id(true);
        $_SESSION['admin_ok'] = true;
        $_SESSION['login_at'] = time();
        return [true, 'Login berhasil.'];
    }

    $sisa = (int)ADMIN_MAX_ATTEMPTS - catat_gagal();
    $sisa = max(0, $sisa);
    return [false, 'Username atau password salah. Sisa percobaan: ' . $sisa];
}

function logout_admin(): void {
    reset_gagal();
    session_unset();
    session_destroy();
}

// ------------------------------------------------------------------
// CSRF token
// ------------------------------------------------------------------
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_verify(?string $token): bool {
    return is_string($token) && hash_equals(csrf_token(), $token);
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

// ------------------------------------------------------------------
// Gerbang utama: wajib login untuk halaman admin
// ------------------------------------------------------------------
function require_admin(): void {
    mulai_sesi_aman();
    if (sudah_login()) return; // aman, lanjutkan

    // Belum login → arahkan ke halaman login
    $diri = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
    header('Location: login.php?next=' . rawurlencode($diri));
    exit;
}
