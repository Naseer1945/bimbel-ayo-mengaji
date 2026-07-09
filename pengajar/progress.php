<?php
/* ============================================================
   pengajar/progress.php — Update level & status belajar santri
   ============================================================ */
require_once __DIR__ . '/../includes/functions.php';
cekRole(['pengajar']);

$uid = (int)$_SESSION['user_id'];
$pstmt = $pdo->prepare('SELECT id FROM pengajar WHERE user_id = ? LIMIT 1');
$pstmt->execute([$uid]);
$pid = (int)($pstmt->fetchColumn() ?: 0);

$levels  = ['Pemula','Iqra 1','Iqra 2','Iqra 3','Iqra 4','Iqra 5','Iqra 6','Tajwid','Hafalan'];
$statuses= ['baru','aktif','lulus','nonaktif'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $eid    = (int)($_POST['entitas_id'] ?? 0);
    $level  = in_array($_POST['level'] ?? '', $levels, true) ? $_POST['level'] : 'Pemula';
    $status = in_array($_POST['status'] ?? '', $statuses, true) ? $_POST['status'] : 'baru';
    $catatan= trim($_POST['catatan'] ?? '');

    // Pastikan santri ini memang milik pengajar
    $chk = $pdo->prepare('SELECT id, nama_entitas FROM entitas WHERE id=? AND pengajar_id=?');
    $chk->execute([$eid, $pid]);
    $ent = $chk->fetch();
    if ($ent) {
        $pdo->prepare('UPDATE entitas SET level_saat_ini=?, status_belajar=?, catatan_progress=? WHERE id=?')
            ->execute([$level, $status, $catatan, $eid]);
        logAktivitas($uid, 'pengajar', 'update_progress', "Progress {$ent['nama_entitas']} -> $level / $status");
        flashMessage('success', "Progress {$ent['nama_entitas']} berhasil diperbarui. ✅");
    } else {
        flashMessage('error', 'Santri tidak ditemukan / bukan santri Anda.');
    }
    redirect(baseUrl() . 'pengajar/progress.php');
}

$rows = [];
if ($pid) {
    $st = $pdo->prepare('SELECT e.*, c.nama_client FROM entitas e JOIN clients c ON e.client_id=c.id
                         WHERE e.pengajar_id=? ORDER BY e.nama_entitas ASC');
    $st->execute([$pid]); $rows = $st->fetchAll();
}

$panelTitle = 'Update Progress';
$activeMenu = 'progress';
require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="page-head">
    <h2>Update Progress Santri 📈</h2>
    <p>Perbarui level dan status belajar tiap santri. Perubahan tercatat di log.</p>
</div>

<div class="panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Nama</th><th>Wali</th><th>Level</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if ($rows): foreach ($rows as $e): ?>
                <tr>
                    <td><?= e($e['nama_entitas']) ?></td>
                    <td><?= e($e['nama_client']) ?></td>
                    <td><?= e($e['level_saat_ini']) ?></td>
                    <td><?= getStatusBadge($e['status_belajar']) ?></td>
                    <td><button class="btn btn-info btn-sm" data-modal-target="m-prog-<?= $e['id'] ?>">✏️ Update</button></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="5" class="empty-row">Belum ada santri untuk diperbarui. 🧒</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal per santri -->
<?php foreach ($rows as $e): ?>
<div class="modal-overlay" id="m-prog-<?= $e['id'] ?>">
    <div class="modal">
        <div class="modal-head"><h3>Update: <?= e($e['nama_entitas']) ?></h3><button class="modal-close" data-modal-close>&times;</button></div>
        <form method="post">
            <?= csrfField() ?>
            <input type="hidden" name="entitas_id" value="<?= $e['id'] ?>">
            <label class="form-label">Level Saat Ini</label>
            <select name="level" class="form-input">
                <?php foreach ($levels as $lv): ?>
                    <option value="<?= $lv ?>" <?= $e['level_saat_ini']===$lv?'selected':'' ?>><?= $lv ?></option>
                <?php endforeach; ?>
            </select>
            <label class="form-label">Status Belajar</label>
            <select name="status" class="form-input">
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= $s ?>" <?= $e['status_belajar']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <label class="form-label">Catatan Progress</label>
            <textarea name="catatan" class="form-input" rows="3"><?= e($e['catatan_progress']) ?></textarea>
            <button type="submit" class="btn btn-primary" style="margin-top:14px">Simpan Perubahan 💾</button>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
