<?php
/* ============================================================
   logout.php — Hapus session & redirect ke beranda
   ============================================================ */
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['user_id'])) {
    logAktivitas((int)$_SESSION['user_id'], $_SESSION['role'] ?? null, 'logout', 'Logout');
}
$base = baseUrl();
session_unset();
session_destroy();

// Mulai session baru hanya untuk flash message
session_start();
flashMessage('success', 'Anda telah keluar. Sampai jumpa lagi! 👋');
redirect($base . 'index.php');
