<?php
// =============================================================
// Keluar (logout) dari sesi admin
// =============================================================

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';

mulai_sesi_aman();
logout_admin();

header('Location: login.php?keluar=1');
exit;
