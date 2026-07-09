<?php
/* ============================================================
   includes/admin_header.php — Layout panel (sidebar + topbar)
   Dipakai oleh admin/, manager/, pengajar/, client/.
   Variabel sebelum include:
     $panelTitle  -> judul halaman & <title>
     $activeMenu  -> key menu aktif
   Menu dirender otomatis sesuai $_SESSION['role'].
   ============================================================ */
require_once __DIR__ . '/functions.php';
cekLogin();

$base       = baseUrl();
$role       = $_SESSION['role'] ?? '';
$namaUser   = $_SESSION['nama_lengkap'] ?? 'Pengguna';
$panelTitle = $panelTitle ?? 'Dashboard';
$activeMenu = $activeMenu ?? '';

// ---- Definisi menu per role: key => [label, icon, path] ----
$menus = [
    'super_admin' => [
        'dashboard' => ['Dashboard', '📊', 'admin/dashboard.php'],
        'users'     => ['Kelola User', '👥', 'admin/users.php'],
        'promosi'   => ['Promosi & Event', '🎉', 'admin/promosi.php'],
        'galeri'    => ['Aset Galeri', '🖼️', 'manager/galeri.php'],
        'log'       => ['Log Aktivitas', '📜', 'admin/log.php'],
        'settings'  => ['Pengaturan & Fitur', '⚙️', 'admin/settings.php'],
    ],
    'manager' => [
        'dashboard' => ['Dashboard', '📊', 'manager/dashboard.php'],
        'clients'   => ['Data Client', '🧑‍🤝‍🧑', 'manager/clients.php'],
        'entitas'   => ['Santri & Assign', '🧒', 'manager/entitas.php'],
        'pengajar'  => ['Kelola Pengajar', '👨‍🏫', 'manager/pengajar.php'],
        'galeri'    => ['Galeri Foto', '🖼️', 'manager/galeri.php'],
        'youtube'   => ['Link YouTube', '▶️', 'manager/youtube.php'],
        'tugas'     => ['Beri Tugas', '📋', 'manager/tugas.php'],
    ],
    'pengajar' => [
        'dashboard' => ['Dashboard', '📊', 'pengajar/dashboard.php'],
        'jadwal'    => ['Jadwal Mengajar', '🗓️', 'pengajar/jadwal.php'],
        'santri'    => ['Daftar Santri', '🧒', 'pengajar/santri.php'],
        'progress'  => ['Update Progress', '📈', 'pengajar/progress.php'],
    ],
    'client' => [
        'dashboard' => ['Dashboard', '📊', 'client/dashboard.php'],
        'daftar'    => ['Daftar Santri', '📝', 'client/daftar.php'],
        'status'    => ['Status & Monitoring', '🔎', 'client/status.php'],
    ],
];
$roleLabel = [
    'super_admin' => 'Super Admin', 'manager' => 'Manager',
    'pengajar' => 'Pengajar', 'client' => 'Client',
][$role] ?? 'User';
$myMenu = $menus[$role] ?? [];
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($panelTitle) ?> | Panel <?= e($roleLabel) ?></title>
    <link rel="stylesheet" href="<?= $base ?>css/global.css">
    <link rel="stylesheet" href="<?= $base ?>css/admin-dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
</head>
<body class="admin-body">

<!-- SIDEBAR -->
<aside class="admin-sidebar" id="admin-sidebar">
    <div class="sidebar-brand">
        <a href="<?= $base ?>index.php" class="sidebar-logo">📖 Ayo <span>Mengaji</span></a>
        <span class="sidebar-role"><?= e($roleLabel) ?></span>
    </div>
    <nav class="sidebar-nav">
        <?php foreach ($myMenu as $key => $item): ?>
            <a href="<?= $base . $item[2] ?>" class="sidebar-link <?= $activeMenu===$key?'active':'' ?>">
                <span class="sidebar-icon"><?= $item[1] ?></span>
                <span class="sidebar-text"><?= e($item[0]) ?></span>
            </a>
        <?php endforeach; ?>
        <a href="<?= $base ?>profil.php" class="sidebar-link <?= $activeMenu==='profil'?'active':'' ?>">
            <span class="sidebar-icon">👤</span><span class="sidebar-text">Profil Saya</span>
        </a>
        <a href="<?= $base ?>index.php" class="sidebar-link">
            <span class="sidebar-icon">🌐</span><span class="sidebar-text">Lihat Website</span>
        </a>
        <a href="<?= $base ?>logout.php" class="sidebar-link sidebar-logout">
            <span class="sidebar-icon">🚪</span><span class="sidebar-text">Keluar</span>
        </a>
    </nav>
</aside>
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<!-- MAIN AREA -->
<div class="admin-main">
    <!-- TOPBAR -->
    <header class="admin-topbar">
        <button class="sidebar-toggle" id="sidebar-toggle" aria-label="Menu">☰</button>
        <h1 class="topbar-title"><?= e($panelTitle) ?></h1>
        <div class="topbar-right">
            <?php $__notif = hitungNotifBelumDibaca((int)$_SESSION['user_id']); ?>
            <a href="<?= $base ?>notifikasi.php" class="notif-bell" title="Notifikasi">
                🔔
                <?php if ($__notif > 0): ?>
                    <span class="notif-count"><?= $__notif > 99 ? '99+' : $__notif ?></span>
                <?php endif; ?>
            </a>
            <button class="theme-toggle" id="theme-toggle" aria-label="Ganti Tema">☀️</button>
            <?php
            $__me = currentUser();
            $__foto = $__me['foto_profil'] ?? '';
            $__hasFoto = $__foto && !str_contains($__foto, 'default-avatar');
            ?>
            <a href="<?= $base ?>profil.php" class="topbar-user" title="Profil Saya">
                <?php if ($__hasFoto): ?>
                    <img src="<?= $base . rawurlencode_path($__foto) ?>" class="user-avatar-img" alt="Foto profil"
                         onerror="this.outerHTML='<span class=\'user-avatar\'><?= strtoupper(substr($namaUser,0,1)) ?></span>'">
                <?php else: ?>
                    <span class="user-avatar"><?= strtoupper(substr($namaUser, 0, 1)) ?></span>
                <?php endif; ?>
                <span class="user-name"><?= e($namaUser) ?></span>
            </a>
        </div>
    </header>

    <!-- CONTENT -->
    <main class="admin-content">
        <?= showFlash() ?>
