<?php
// =============================================================
// Koneksi database via PDO (dipakai bersama simpan.php & admin/*.php)
// =============================================================

require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok'      => false,
            'message' => 'Gagal terhubung ke database: ' . $e->getMessage(),
        ]);
        exit;
    }
    return $pdo;
}

// Ambil isi body POST (form JSON) dengan aman
function baca_body_json(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) $data = $_POST; // fallback ke form-urlencoded
    return $data;
}
