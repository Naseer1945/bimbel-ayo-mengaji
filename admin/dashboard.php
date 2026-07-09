<?php
/* ============================================================
   admin/dashboard.php — Statistik keseluruhan (super admin)
   ============================================================ */
require_once __DIR__ . '/../includes/functions.php';
cekRole(['super_admin']);

// Hitung user per role
$roleCounts = ['client'=>0,'manager'=>0,'pengajar'=>0,'super_admin'=>0];
foreach ($pdo->query("SELECT role, COUNT(*) c FROM users GROUP BY role") as $r) {
    $roleCounts[$r['role']] = (int)$r['c'];
}
$stats = $pdo->query('SELECT * FROM view_dashboard_stats')->fetch() ?: [];

// Aktivitas 7 hari (log_aktivitas)
$chart = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $c = $pdo->prepare("SELECT COUNT(*) FROM log_aktivitas WHERE DATE(created_at)=?");
    $c->execute([$d]);
    $chart[] = ['label'=>date('d/m', strtotime($d)), 'val'=>(int)$c->fetchColumn()];
}
$maxChart = max(1, max(array_map(fn($x)=>$x['val'], $chart)));

$panelTitle = 'Dashboard Admin';
$activeMenu = 'dashboard';
require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="page-head">
    <h2>Dashboard Super Admin 📊</h2>
    <p>Kontrol penuh dan statistik keseluruhan sistem Bimbel Ayo Mengaji.</p>
</div>

<div class="stat-grid">
    <div class="stat-card"><div class="stat-ico">🧑‍🤝‍🧑</div><div class="stat-meta"><div class="num"><?= $roleCounts['client'] ?></div><div class="lbl">Client</div></div></div>
    <div class="stat-card s-info"><div class="stat-ico">🧑‍💼</div><div class="stat-meta"><div class="num"><?= $roleCounts['manager'] ?></div><div class="lbl">Manager</div></div></div>
    <div class="stat-card s-green"><div class="stat-ico">👨‍🏫</div><div class="stat-meta"><div class="num"><?= $roleCounts['pengajar'] ?></div><div class="lbl">Pengajar</div></div></div>
    <div class="stat-card s-pink"><div class="stat-ico">🛡️</div><div class="stat-meta"><div class="num"><?= $roleCounts['super_admin'] ?></div><div class="lbl">Super Admin</div></div></div>
</div>

<div class="stat-grid">
    <div class="stat-card s-green"><div class="stat-ico">📈</div><div class="stat-meta"><div class="num"><?= (int)($stats['total_santri_aktif'] ?? 0) ?></div><div class="lbl">Santri Aktif</div></div></div>
    <div class="stat-card s-amber"><div class="stat-ico">🆕</div><div class="stat-meta"><div class="num"><?= (int)($stats['total_santri_baru'] ?? 0) ?></div><div class="lbl">Santri Baru</div></div></div>
    <div class="stat-card s-info"><div class="stat-ico">👨‍🏫</div><div class="stat-meta"><div class="num"><?= (int)($stats['total_pengajar'] ?? 0) ?></div><div class="lbl">Pengajar Aktif</div></div></div>
</div>

<div class="panel">
    <h3 class="panel-title">Aktivitas Sistem — 7 Hari Terakhir 📅</h3>
    <div class="bar-chart">
        <?php foreach ($chart as $c): ?>
            <div class="bar-col">
                <span class="bar-val"><?= $c['val'] ?></span>
                <div class="bar" style="height: <?= max(4, round($c['val']/$maxChart*180)) ?>px"></div>
                <span class="bar-label"><?= e($c['label']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="page-actions">
    <a href="<?= baseUrl() ?>admin/users.php" class="btn btn-primary">👥 Kelola User</a>
    <a href="<?= baseUrl() ?>admin/log.php" class="btn btn-info">📜 Lihat Log</a>
    <a href="<?= baseUrl() ?>admin/settings.php" class="btn btn-success">⚙️ Pengaturan</a>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
