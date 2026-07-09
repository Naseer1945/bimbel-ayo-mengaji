<?php
/* ============================================================
   manager/dashboard.php — Ringkasan operasional manager
   ============================================================ */
require_once __DIR__ . '/../includes/functions.php';
cekRole(['manager']);

$stats = ['total_client'=>0,'total_pengajar'=>0,'total_santri_aktif'=>0,'total_santri_baru'=>0];
$row = $pdo->query('SELECT * FROM view_dashboard_stats')->fetch();
if ($row) $stats = $row;

$pendingClients = (int)$pdo->query("SELECT COUNT(*) FROM clients WHERE status_pendaftaran='pending'")->fetchColumn();
$totalEntitas   = (int)$pdo->query("SELECT COUNT(*) FROM entitas")->fetchColumn();
$unassigned     = (int)$pdo->query("SELECT COUNT(*) FROM entitas WHERE pengajar_id IS NULL")->fetchColumn();

// Pendaftaran 7 hari terakhir (clients)
$chart = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $c = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE DATE(created_at)=?");
    $c->execute([$d]);
    $chart[] = ['label' => date('d/m', strtotime($d)), 'val' => (int)$c->fetchColumn()];
}
$maxChart = max(1, max(array_map(fn($x)=>$x['val'], $chart)));

$panelTitle = 'Dashboard Manager';
$activeMenu = 'dashboard';
require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="page-head">
    <h2>Dashboard Manager 📊</h2>
    <p>Ringkasan client, pengajar, dan santri Bimbel Ayo Mengaji.</p>
</div>

<div class="stat-grid">
    <div class="stat-card"><div class="stat-ico">🧑‍🤝‍🧑</div><div class="stat-meta"><div class="num"><?= (int)$stats['total_client'] ?></div><div class="lbl">Total Client</div></div></div>
    <div class="stat-card s-info"><div class="stat-ico">👨‍🏫</div><div class="stat-meta"><div class="num"><?= (int)$stats['total_pengajar'] ?></div><div class="lbl">Pengajar Aktif</div></div></div>
    <div class="stat-card s-green"><div class="stat-ico">📈</div><div class="stat-meta"><div class="num"><?= (int)$stats['total_santri_aktif'] ?></div><div class="lbl">Santri Aktif</div></div></div>
    <div class="stat-card s-amber"><div class="stat-ico">⏳</div><div class="stat-meta"><div class="num"><?= $pendingClients ?></div><div class="lbl">Client Pending</div></div></div>
</div>

<div class="stat-grid">
    <div class="stat-card s-info"><div class="stat-ico">🧒</div><div class="stat-meta"><div class="num"><?= $totalEntitas ?></div><div class="lbl">Total Santri</div></div></div>
    <div class="stat-card s-pink"><div class="stat-ico">🆕</div><div class="stat-meta"><div class="num"><?= (int)$stats['total_santri_baru'] ?></div><div class="lbl">Santri Baru</div></div></div>
    <div class="stat-card s-amber"><div class="stat-ico">🔗</div><div class="stat-meta"><div class="num"><?= $unassigned ?></div><div class="lbl">Belum Ada Pengajar</div></div></div>
</div>

<div class="panel">
    <h3 class="panel-title">Pendaftaran Client — 7 Hari Terakhir 📅</h3>
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
    <a href="<?= baseUrl() ?>manager/clients.php" class="btn btn-primary">✅ Approve Client</a>
    <a href="<?= baseUrl() ?>manager/entitas.php" class="btn btn-info">🔗 Assign Pengajar</a>
    <a href="<?= baseUrl() ?>manager/pengajar.php" class="btn btn-success">👨‍🏫 Tambah Pengajar</a>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
