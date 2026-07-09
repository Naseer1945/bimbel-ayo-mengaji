<?php
/* ============================================================
   client/dashboard.php — Ringkasan entitas yang didaftarkan
   ============================================================ */
require_once __DIR__ . '/../includes/functions.php';
cekRole(['client']);

$uid = (int)$_SESSION['user_id'];

// Ambil client row milik user ini
$stmt = $pdo->prepare('SELECT * FROM clients WHERE user_id = ? ORDER BY id ASC LIMIT 1');
$stmt->execute([$uid]);
$client = $stmt->fetch();

$totalEntitas = $aktif = $baru = $lulus = 0;
$entitasList  = [];
if ($client) {
    $q = $pdo->prepare('SELECT e.*, p.nama_pengajar FROM entitas e
                        LEFT JOIN pengajar p ON e.pengajar_id = p.id
                        WHERE e.client_id = ? ORDER BY e.created_at DESC');
    $q->execute([$client['id']]);
    $entitasList = $q->fetchAll();
    $totalEntitas = count($entitasList);
    foreach ($entitasList as $e) {
        if ($e['status_belajar'] === 'aktif') $aktif++;
        elseif ($e['status_belajar'] === 'baru') $baru++;
        elseif ($e['status_belajar'] === 'lulus') $lulus++;
    }
}

$panelTitle = 'Dashboard Client';
$activeMenu = 'dashboard';
require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="page-head">
    <h2>Halo, <?= e($_SESSION['nama_lengkap']) ?>! 👋</h2>
    <p>Pantau perkembangan pendaftaran &amp; belajar santri Anda di sini.</p>
</div>

<div class="stat-grid">
    <div class="stat-card"><div class="stat-ico">🧒</div><div class="stat-meta"><div class="num"><?= $totalEntitas ?></div><div class="lbl">Total Santri Didaftarkan</div></div></div>
    <div class="stat-card s-info"><div class="stat-ico">🆕</div><div class="stat-meta"><div class="num"><?= $baru ?></div><div class="lbl">Santri Baru</div></div></div>
    <div class="stat-card s-green"><div class="stat-ico">📈</div><div class="stat-meta"><div class="num"><?= $aktif ?></div><div class="lbl">Sedang Aktif Belajar</div></div></div>
    <div class="stat-card s-amber"><div class="stat-ico">🎓</div><div class="stat-meta"><div class="num"><?= $lulus ?></div><div class="lbl">Sudah Lulus</div></div></div>
</div>

<?php if (!$client || $totalEntitas === 0): ?>
    <div class="panel" style="text-align:center">
        <p class="panel-title">Belum ada santri terdaftar 📝</p>
        <p style="color:var(--text-secondary);margin-bottom:16px">Yuk daftarkan putra-putri Anda untuk mulai belajar mengaji!</p>
        <a href="<?= baseUrl() ?>client/daftar.php" class="btn btn-primary">➕ Daftarkan Santri Sekarang</a>
    </div>
<?php else: ?>
    <div class="panel">
        <div class="page-actions" style="justify-content:space-between">
            <h3 class="panel-title" style="margin:0">Status Pendaftaran: <?= getStatusBadge($client['status_pendaftaran']) ?></h3>
            <a href="<?= baseUrl() ?>client/daftar.php" class="btn btn-primary btn-sm">➕ Tambah Santri</a>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Nama Santri</th><th>Usia</th><th>Level</th><th>Status Belajar</th><th>Pengajar</th><th>Jadwal</th></tr></thead>
                <tbody>
                <?php foreach ($entitasList as $e): ?>
                    <tr>
                        <td><?= e($e['nama_entitas']) ?></td>
                        <td><?= e((string)$e['usia']) ?></td>
                        <td><?= e($e['level_saat_ini']) ?></td>
                        <td><?= getStatusBadge($e['status_belajar']) ?></td>
                        <td><?= $e['nama_pengajar'] ? e($e['nama_pengajar']) : '<span class="badge badge-muted">Belum di-assign</span>' ?></td>
                        <td><?= e($e['jadwal_hari']) ?> <?= $e['jadwal_jam'] ? e(substr($e['jadwal_jam'],0,5)) : '' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
