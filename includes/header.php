<?php
/* ============================================================
   includes/header.php — Navbar global DINAMIS untuk halaman publik
   Menampilkan menu sesuai status login (Login vs Dashboard+nama).
   Variabel opsional sebelum include:
     $pageTitle  -> judul <title>
     $activePage -> 'index' | 'tentang' | 'keunggulan'
   ============================================================ */
require_once __DIR__ . '/functions.php';

$pageTitle  = $pageTitle  ?? 'Bimbel Ayo Mengaji 📖✨';
$activePage = $activePage ?? '';
$base       = baseUrl();
$isLogin    = !empty($_SESSION['user_id']);
$namaUser   = $_SESSION['nama_lengkap'] ?? '';
$roleUser   = $_SESSION['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e(getSetting('deskripsi', 'Bimbel Ayo Mengaji - Belajar Al-Quran untuk anak & remaja.')) ?>">

    <link rel="stylesheet" href="<?= $base ?>css/global.css">
    <link rel="stylesheet" href="<?= $base ?>css/main-page.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
</head>
<body class="page-fade">

    <!-- BACKGROUND EMOJI POP-ART -->
    <div class="bg-emojis" aria-hidden="true">
        <span style="--x:6%;  --y:14%; --d:0s;   --s:1.4rem;">📚</span>
        <span style="--x:18%; --y:72%; --d:1.2s; --s:1.1rem;">✨</span>
        <span style="--x:30%; --y:30%; --d:2.4s; --s:1.6rem;">🚀</span>
        <span style="--x:42%; --y:84%; --d:0.6s; --s:1.2rem;">🧠</span>
        <span style="--x:54%; --y:20%; --d:1.8s; --s:1.5rem;">🎯</span>
        <span style="--x:66%; --y:66%; --d:3s;   --s:1.3rem;">⭐</span>
        <span style="--x:78%; --y:26%; --d:0.9s; --s:1.6rem;">🗣️</span>
        <span style="--x:90%; --y:60%; --d:2.1s; --s:1.4rem;">📖</span>
        <span style="--x:12%; --y:46%; --d:1.5s; --s:1.2rem;">⭐</span>
        <span style="--x:48%; --y:54%; --d:2.7s; --s:1.1rem;">✨</span>
        <span style="--x:72%; --y:88%; --d:0.3s; --s:1.5rem;">📚</span>
        <span style="--x:84%; --y:12%; --d:3.3s; --s:1.3rem;">🧠</span>
    </div>

    <!-- NAVBAR -->
    <header class="navbar" id="navbar">
        <div class="container nav-container">
            <a href="<?= $base ?>index.php" class="logo">📖 Ayo <span>Mengaji</span></a>
            <nav class="nav-menu" id="nav-menu">
                <ul class="nav-list">
                    <li><a href="<?= $base ?>index.php"           class="nav-link <?= $activePage==='index'?'active':'' ?>">🏠 Beranda</a></li>
                    <li><a href="<?= $base ?>tentang-kami.php"     class="nav-link <?= $activePage==='tentang'?'active':'' ?>">✨ Tentang</a></li>
                    <li><a href="<?= $base ?>slide-keunggulan.php" class="nav-link <?= $activePage==='keunggulan'?'active':'' ?>">⭐ Keunggulan</a></li>
                </ul>
            </nav>
            <div class="nav-actions">
                <button class="theme-toggle" id="theme-toggle" aria-label="Ganti Tema">☀️</button>
                <?php if ($isLogin): ?>
                    <a href="<?= dashboardUrl($roleUser) ?>" class="btn btn-primary btn-nav-cta">🧭 Dashboard</a>
                    <a href="<?= $base ?>logout.php" class="btn btn-outline btn-nav-cta">Keluar</a>
                <?php else: ?>
                    <a href="<?= $base ?>login.php" class="btn btn-outline btn-nav-cta">Masuk</a>
                    <a href="<?= $base ?>register.php" class="btn btn-primary btn-nav-cta">Daftar 🚀</a>
                <?php endif; ?>
                <button class="hamburger" id="hamburger" aria-label="Buka Menu">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </header>
