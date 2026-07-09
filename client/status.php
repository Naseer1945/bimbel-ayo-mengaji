<?php
/* ============================================================
   client/status.php — Monitoring status entitas + pengajar
   ============================================================ */
require_once __DIR__ . '/../includes/functions.php';
cekRole(['client']);

$uid = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT * FROM clients WHERE user_id = ? LIMIT 1');
$stmt->execute([$uid]);
$client = $stmt->fetch();

$entitasList = [];
if ($client) {
    $q = $pdo->prepare('SELECT e.*, p.nama_pengajar FROM entitas e
                        LEFT JOIN pengajar p ON e.pengajar_id = p.id
                        WHERE e.client_id = ? ORDER BY e.created_at DESC');
    $q->execute([$client['id']]);
    $entitasList = $q->fetchAll();
}

$panelTitle = 'Status & Monitoring';
$activeMenu = 'status';
require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="page-head">
    <h2>Status Pendaftaran & Belajar 🔎</h2>
    <p>Detail status setiap santri serta pengajar yang ditugaskan.</p>
</div>

<?php if ($client && $client['status_pendaftaran'] === 'rejected' && !empty($client['catatan_manager'])): ?>
    <div class="panel" style="border-left:5px solid var(--admin-danger)">
        <strong>Catatan dari Manager:</strong> <?= e($client['catatan_manager']) ?>
    </div>
<?php endif; ?>

<div class="panel">
    <h3 class="panel-title">Status Pendaftaran Akun: <?= $client ? getStatusBadge($client['status_pendaftaran']) : '<span class="badge badge-muted">Belum daftar</span>' ?></h3>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Nama Santri</th><th>Usia</th><th>JK</th><th>Status Belajar</th><th>Pengajar</th><th>Jadwal</th><th>Catatan Progress</th></tr></thead>
            <tbody>
            <?php if ($entitasList): foreach ($entitasList as $e): ?>
                <tr>
                    <td><?= e($e['nama_entitas']) ?></td>
                    <td><?= e((string)$e['usia']) ?></td>
                    <td><?= $e['jenis_kelamin'] === 'P' ? 'Perempuan' : 'Laki-laki' ?></td>
                    <td><?= getStatusBadge($e['status_belajar']) ?> <small>(<?= e($e['level_saat_ini']) ?>)</small></td>
                    <td><?= $e['nama_pengajar'] ? e($e['nama_pengajar']) : '<span class="badge badge-muted">Belum</span>' ?></td>
                    <td><?= e($e['jadwal_hari']) ?> <?= $e['jadwal_jam'] ? e(substr($e['jadwal_jam'],0,5)) : '' ?></td>
                    <td><?= $e['catatan_progress'] ? e($e['catatan_progress']) : '-' ?></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="7" class="empty-row">Belum ada santri terdaftar. <a href="<?= baseUrl() ?>client/daftar.php">Daftar sekarang →</a></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
