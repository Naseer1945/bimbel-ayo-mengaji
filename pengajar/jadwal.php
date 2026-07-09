<?php
/* ============================================================
   pengajar/jadwal.php — Tabel jadwal mengajar (filter per hari)
   ============================================================ */
require_once __DIR__ . '/../includes/functions.php';
cekRole(['pengajar']);

$uid = (int)$_SESSION['user_id'];
$pstmt = $pdo->prepare('SELECT id FROM pengajar WHERE user_id = ? LIMIT 1');
$pstmt->execute([$uid]);
$pid = (int)($pstmt->fetchColumn() ?: 0);

$hari = $_GET['hari'] ?? '';
$hariValid = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];

$rows = [];
if ($pid) {
    $sql = "SELECT e.*, c.nama_client, c.alamat FROM entitas e JOIN clients c ON e.client_id=c.id
            WHERE e.pengajar_id = ?";
    $params = [$pid];
    if (in_array($hari, $hariValid, true)) {
        $sql .= " AND e.jadwal_hari LIKE ?";
        $params[] = '%' . $hari . '%';
    }
    $sql .= " ORDER BY e.jadwal_jam ASC";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();
}

$panelTitle = 'Jadwal Mengajar';
$activeMenu = 'jadwal';
require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="page-head">
    <h2>Jadwal Mengajar 🗓️</h2>
    <p>Daftar jadwal mengajar santri Anda. Saring berdasarkan hari.</p>
</div>

<form method="get" class="filter-bar">
    <label class="form-label" style="margin:0">Filter Hari:</label>
    <select name="hari" class="form-input" onchange="this.form.submit()">
        <option value="">— Semua Hari —</option>
        <?php foreach ($hariValid as $h): ?>
            <option value="<?= $h ?>" <?= $hari===$h?'selected':'' ?>><?= $h ?></option>
        <?php endforeach; ?>
    </select>
</form>

<div class="panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Hari</th><th>Jam</th><th>Santri</th><th>Usia</th><th>Wali</th><th>Alamat</th><th>Level</th><th>Status</th></tr></thead>
            <tbody>
            <?php if ($rows): foreach ($rows as $e): ?>
                <tr>
                    <td><?= e($e['jadwal_hari']) ?></td>
                    <td><?= $e['jadwal_jam'] ? e(substr($e['jadwal_jam'],0,5)) : '-' ?></td>
                    <td><?= e($e['nama_entitas']) ?></td>
                    <td><?= e((string)$e['usia']) ?></td>
                    <td><?= e($e['nama_client']) ?></td>
                    <td><?= e(mb_strimwidth($e['alamat'] ?? '-', 0, 40, '…')) ?></td>
                    <td><?= e($e['level_saat_ini']) ?></td>
                    <td><?= getStatusBadge($e['status_belajar']) ?></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="8" class="empty-row">Belum ada jadwal untuk filter ini. 🗓️</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
