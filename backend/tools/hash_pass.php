<?php
// =============================================================
// Alat bantu: membuat hash bcrypt untuk password admin.
//
//   Pemakaian (dari terminal / CMD):
//     php backend/tools/hash_pass.php "password-anda"
//
//   Atau buka lewat browser:
//     http://localhost/sikonkep/backend/tools/hash_pass.php?p=password-anda
//     (sebaiknya hapus/hapus file ini setelah dipakai di lingkungan produksi)
//
//   Hasilnya berupa string berawalan $2y$ — tempel ke ADMIN_PASS di config.php
// =============================================================

$pass = null;

// Bila dipanggil via CLI
if (PHP_SAPI === 'cli') {
    $pass = $argv[1] ?? null;
} else {
    // Dipanggil via web
    $pass = $_GET['p'] ?? null;
}

if ($pass === null || $pass === '') {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Pemakaian: php hash_pass.php \"password-anda\"\n");
    } else {
        echo 'Tambahkan parameter ?p=password-anda pada URL.';
    }
    exit(1);
}

$hash = password_hash($pass, PASSWORD_BCRYPT);

if (PHP_SAPI === 'cli') {
    echo "Hash bcrypt: " . $hash . "\n\n";
    echo "Tempel baris ini ke config.php:\n";
    echo "define('ADMIN_PASS', '" . $hash . "');\n";
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Hash bcrypt:\n" . $hash . "\n\n";
    echo "Tempel baris ini ke config.php:\n";
    echo "define('ADMIN_PASS', '" . $hash . "');\n";
}
