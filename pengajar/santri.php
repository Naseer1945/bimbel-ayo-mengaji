<?php
/* ============================================================
   pengajar/santri.php — Daftar santri yang ditugaskan
   ============================================================ */
require_once __DIR__ . '/../includes/functions.php';
cekRole(['pengajar']);

$uid = (int)$_SESSION['user_id'];
$pstmt = $pdo->prepare('SELECT id FROM pengajar WHERE user_id = ? LIMIT 1');
$pstmt->execute([$uid]);
$pid = (int)($pstmt->fetchColumn() ?: 0);

$filter = $_GET['status'] ?? '';
$statusValid = ['baru','aktif','lulus','nonaktif'];

$rows = [];
if ($pid) {
    $sql = "SELECT e.*, c.nama_client, c.no_hp FROM entitas e JOIN clients c ON e.client_id=c.id WHERE e.pengajar_id=?";
    $params = [$pid];
    if (in_array($filter, $statusValid, true)) { $sql .= " AND e.status_belajar=?"; $params[] = $filter; }
    $sql .= " ORDER BY e.nama_entitas ASC";
    $st = $pdo->prepare($sql); $st->execute($params); $rows = $st->fetchAll();
}

$panelTitle = 'Daftar Santri';
$activeMenu = 'santri';
require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="page-head">
    <h2>Santri yang Saya Ajar 🧒</h2>
    <p>Daftar santri yang ditugaskan kepada Anda oleh manager.</p>
</div>

<form method="get" class="filter-bar">
    <label class="form-label" style="margin:0">Filter Status:</label>
    <select name="status" class="form-input" onchange="this.form.submit()">
        <option value="">— Semua —</option>
        <?php foreach ($statusValid as $s): ?>
            <option value="<?= $s ?>" <?= $filter===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
    <a href="<?= baseUrl() ?>pengajar/progress.php" class="btn btn-primary btn-sm">📈 Update Progress</a>
</form>

<div class="panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Nama</th><th>Usia</th><th>JK</th><th>Wali</th><th>Kontak</th><th>Level</th><th>Status</th><th>Jadwal</th></tr></thead>
            <tbody>
            <?php if ($rows): foreach ($rows as $e): ?>
                <tr>
                    <td><?= e($e['nama_entitas']) ?></td>
                    <td><?= e((string)$e['usia']) ?></td>
                    <td><?= $e['jenis_kelamin']==='P'?'P':'L' ?></td>
                    <td><?= e($e['nama_client']) ?></td>
                    <td><?= e($e['no_hp']) ?></td>
                    <td><?= e($e['level_saat_ini']) ?></td>
                    <td><?= getStatusBadge($e['status_belajar']) ?></td>
                    <td><?= e($e['jadwal_hari']) ?> <?= $e['jadwal_jam'] ? e(substr($e['jadwal_jam'],0,5)) : '' ?></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="8" class="empty-row">Belum ada santri ditugaskan. 🧒</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
