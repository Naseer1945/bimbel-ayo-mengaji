<?php
/* ============================================================
   pengajar/dashboard.php — Ringkasan santri, tugas, jadwal
   ============================================================ */
require_once __DIR__ . '/../includes/functions.php';
cekRole(['pengajar']);

$uid = (int)$_SESSION['user_id'];
// pengajar.id dari user_id
$pstmt = $pdo->prepare('SELECT * FROM pengajar WHERE user_id = ? LIMIT 1');
$pstmt->execute([$uid]);
$pengajar = $pstmt->fetch();
$pid = $pengajar ? (int)$pengajar['id'] : 0;

$totalSantri = $tugasPending = $tugasDone = 0;
$jadwalHariIni = [];
$levelDist = [];
$hariIni = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'][date('l')];

if ($pid) {
    $totalSantri = (int)$pdo->query("SELECT COUNT(*) FROM entitas WHERE pengajar_id=$pid AND status_belajar='aktif'")->fetchColumn();
    $t = $pdo->prepare("SELECT status, COUNT(*) c FROM tugas_pengajar WHERE pengajar_id=? GROUP BY status");
    $t->execute([$pid]);
    foreach ($t as $row) {
        if ($row['status'] === 'completed') $tugasDone = (int)$row['c'];
        else $tugasPending += (int)$row['c'];
    }
    // Jadwal hari ini (entitas yang jadwal_hari mengandung hari ini)
    $j = $pdo->prepare("SELECT e.*, c.nama_client FROM entitas e JOIN clients c ON e.client_id=c.id
                        WHERE e.pengajar_id=? AND e.jadwal_hari LIKE ? ORDER BY e.jadwal_jam ASC");
    $j->execute([$pid, '%' . $hariIni . '%']);
    $jadwalHariIni = $j->fetchAll();
    // Distribusi level
    $l = $pdo->prepare("SELECT level_saat_ini, COUNT(*) c FROM entitas WHERE pengajar_id=? GROUP BY level_saat_ini ORDER BY c DESC");
    $l->execute([$pid]);
    $levelDist = $l->fetchAll();
}
$maxLvl = $levelDist ? max(array_map(fn($x)=>(int)$x['c'], $levelDist)) : 1;

$panelTitle = 'Dashboard Pengajar';
$activeMenu = 'dashboard';
require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="page-head">
    <h2>Assalamualaikum, <?= e($_SESSION['nama_lengkap']) ?> 👨‍🏫</h2>
    <p>Ringkasan tugas mengajar Anda hari <?= e($hariIni) ?>, <?= formatTanggal(date('Y-m-d')) ?>.</p>
</div>

<?php if (!$pengajar): ?>
    <div class="panel"><p>Data profil pengajar Anda belum lengkap. Hubungi manager. ⚠️</p></div>
<?php else: ?>
<div class="stat-grid">
    <div class="stat-card"><div class="stat-ico">🧒</div><div class="stat-meta"><div class="num"><?= $totalSantri ?></div><div class="lbl">Santri Aktif</div></div></div>
    <div class="stat-card s-amber"><div class="stat-ico">📋</div><div class="stat-meta"><div class="num"><?= $tugasPending ?></div><div class="lbl">Tugas Pending</div></div></div>
    <div class="stat-card s-green"><div class="stat-ico">✅</div><div class="stat-meta"><div class="num"><?= $tugasDone ?></div><div class="lbl">Tugas Selesai</div></div></div>
    <div class="stat-card s-info"><div class="stat-ico">🗓️</div><div class="stat-meta"><div class="num"><?= count($jadwalHariIni) ?></div><div class="lbl">Jadwal Hari Ini</div></div></div>
</div>

<div class="panel">
    <h3 class="panel-title">Jadwal Mengajar Hari Ini (<?= e($hariIni) ?>) 🗓️</h3>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Jam</th><th>Santri</th><th>Usia</th><th>Wali</th><th>Level</th></tr></thead>
            <tbody>
            <?php if ($jadwalHariIni): foreach ($jadwalHariIni as $e): ?>
                <tr>
                    <td><?= $e['jadwal_jam'] ? e(substr($e['jadwal_jam'],0,5)) : '-' ?></td>
                    <td><?= e($e['nama_entitas']) ?></td>
                    <td><?= e((string)$e['usia']) ?></td>
                    <td><?= e($e['nama_client']) ?></td>
                    <td><?= e($e['level_saat_ini']) ?></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="5" class="empty-row">Tidak ada jadwal mengajar hari ini. 🌤️</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="panel">
    <h3 class="panel-title">Distribusi Santri per Level 📊</h3>
    <?php if ($levelDist): foreach ($levelDist as $lv): ?>
        <div class="lvl-row">
            <span class="lvl-name"><?= e($lv['level_saat_ini']) ?></span>
            <div class="lvl-track"><div class="lvl-fill" style="width: <?= round((int)$lv['c']/$maxLvl*100) ?>%"></div></div>
            <span class="lvl-num"><?= (int)$lv['c'] ?></span>
        </div>
    <?php endforeach; else: ?>
        <p style="color:var(--text-secondary)">Belum ada santri yang ditugaskan.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
