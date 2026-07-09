<?php
/* ============================================================
   admin/promosi.php — Kelola Promosi & Event (super admin)
   Promo/diskon/event tampil otomatis di beranda (index.php)
   selama status aktif & dalam rentang tanggal.
   ============================================================ */
require_once __DIR__ . '/../includes/functions.php';
cekRole(['super_admin']);
$base = baseUrl();
$me   = (int)$_SESSION['user_id'];

$labels = ['PROMO','DISKON','EVENT','INFO'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $judul = trim($_POST['judul'] ?? '');
        $desk  = trim($_POST['deskripsi'] ?? '');
        $label = in_array($_POST['label'] ?? '', $labels, true) ? $_POST['label'] : 'PROMO';
        $mulai = $_POST['tanggal_mulai'] ?: null;
        $sampai= $_POST['tanggal_selesai'] ?: null;
        if ($judul === '') {
            flashMessage('error', 'Judul promo wajib diisi.');
        } elseif ($action === 'add') {
            $pdo->prepare('INSERT INTO promosi (judul,deskripsi,label,tanggal_mulai,tanggal_selesai,dibuat_oleh) VALUES (?,?,?,?,?,?)')
                ->execute([$judul,$desk,$label,$mulai,$sampai,$me]);
            logAktivitas($me,'super_admin','tambah_promosi',"Promo baru: $judul");
            flashMessage('success','Promosi berhasil dibuat! 🎉');
        } else {
            $pid = (int)($_POST['promo_id'] ?? 0);
            $pdo->prepare('UPDATE promosi SET judul=?,deskripsi=?,label=?,tanggal_mulai=?,tanggal_selesai=? WHERE id=?')
                ->execute([$judul,$desk,$label,$mulai,$sampai,$pid]);
            logAktivitas($me,'super_admin','edit_promosi',"Edit promo #$pid");
            flashMessage('success','Promosi diperbarui. ✅');
        }
    }
    elseif ($action === 'toggle') {
        $pid = (int)($_POST['promo_id'] ?? 0);
        $pdo->prepare('UPDATE promosi SET status_aktif = NOT status_aktif WHERE id=?')->execute([$pid]);
        flashMessage('success','Status promo diubah. 🔄');
    }
    elseif ($action === 'delete') {
        $pid = (int)($_POST['promo_id'] ?? 0);
        $pdo->prepare('DELETE FROM promosi WHERE id=?')->execute([$pid]);
        logAktivitas($me,'super_admin','hapus_promosi',"Hapus promo #$pid");
        flashMessage('success','Promo dihapus. 🗑️');
    }
    redirect($base . 'admin/promosi.php');
}

$list = $pdo->query('SELECT p.*, u.nama_lengkap AS pembuat FROM promosi p LEFT JOIN users u ON p.dibuat_oleh=u.id ORDER BY p.created_at DESC')->fetchAll();

$panelTitle = 'Promosi & Event';
$activeMenu = 'promosi';
require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="page-head">
    <h2>Promosi & Event 🎉</h2>
    <p>Buat promo, diskon, atau event — tampil otomatis di beranda selama aktif &amp; dalam rentang tanggal.</p>
    <?php if (!fiturAktif('promosi')): ?>
        <p><span class="badge badge-warning">⚠️ Fitur promosi sedang NONAKTIF (lihat Pengaturan & Fitur) — promo tidak tampil di beranda.</span></p>
    <?php endif; ?>
</div>

<div class="page-actions">
    <button class="btn btn-primary" data-modal-target="m-add-promo">➕ Buat Promo/Event Baru</button>
</div>

<div class="panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Label</th><th>Judul</th><th>Periode</th><th>Status</th><th>Dibuat</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if ($list): foreach ($list as $p):
                $expired = $p['tanggal_selesai'] && strtotime($p['tanggal_selesai']) < strtotime(date('Y-m-d')); ?>
                <tr>
                    <td><span class="badge badge-warning"><?= e($p['label']) ?></span></td>
                    <td><strong><?= e($p['judul']) ?></strong><br><small style="color:var(--text-secondary)"><?= e(mb_strimwidth($p['deskripsi']??'',0,60,'…')) ?></small></td>
                    <td>
                        <?= $p['tanggal_mulai'] ? formatTanggal($p['tanggal_mulai']) : '∞' ?> —
                        <?= $p['tanggal_selesai'] ? formatTanggal($p['tanggal_selesai']) : '∞' ?>
                        <?= $expired ? '<br><span class="badge badge-muted">Kedaluwarsa</span>' : '' ?>
                    </td>
                    <td><?= $p['status_aktif'] ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-muted">Nonaktif</span>' ?></td>
                    <td><?= e($p['pembuat'] ?? '-') ?></td>
                    <td>
                        <div class="action-group">
                            <button class="btn btn-info btn-sm" data-modal-target="m-edit-promo-<?= $p['id'] ?>">✏️</button>
                            <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="promo_id" value="<?= $p['id'] ?>"><button class="btn <?= $p['status_aktif']?'btn-warning':'btn-success' ?> btn-sm"><?= $p['status_aktif']?'🚫':'✅' ?></button></form>
                            <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="promo_id" value="<?= $p['id'] ?>"><button class="btn btn-danger btn-sm" data-confirm="Hapus promo ini permanen?">🗑️</button></form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="6" class="empty-row">Belum ada promo. Buat yang pertama biar web makin hidup! 🎉</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal-overlay" id="m-add-promo">
    <div class="modal">
        <div class="modal-head"><h3>Buat Promo/Event 🎉</h3><button class="modal-close" data-modal-close>&times;</button></div>
        <form method="post">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add">
            <label class="form-label">Label</label>
            <select name="label" class="form-input">
                <?php foreach ($labels as $l): ?><option value="<?= $l ?>"><?= $l ?></option><?php endforeach; ?>
            </select>
            <label class="form-label">Judul *</label>
            <input type="text" name="judul" class="form-input" placeholder="Diskon 20% Pendaftaran Ramadhan" required>
            <label class="form-label">Deskripsi</label>
            <textarea name="deskripsi" class="form-input" rows="3" placeholder="Detail promo/event..."></textarea>
            <div class="form-row">
                <div class="form-col"><label class="form-label">Mulai (opsional)</label><input type="date" name="tanggal_mulai" class="form-input"></div>
                <div class="form-col"><label class="form-label">Selesai (opsional)</label><input type="date" name="tanggal_selesai" class="form-input"></div>
            </div>
            <p class="form-hint">Kosongkan tanggal agar promo berlaku tanpa batas waktu.</p>
            <button type="submit" class="btn btn-primary btn-lg" style="margin-top:14px">Terbitkan 🚀</button>
        </form>
    </div>
</div>

<!-- Modal Edit per promo -->
<?php foreach ($list as $p): ?>
<div class="modal-overlay" id="m-edit-promo-<?= $p['id'] ?>">
    <div class="modal">
        <div class="modal-head"><h3>Edit: <?= e($p['judul']) ?></h3><button class="modal-close" data-modal-close>&times;</button></div>
        <form method="post">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="promo_id" value="<?= $p['id'] ?>">
            <label class="form-label">Label</label>
            <select name="label" class="form-input">
                <?php foreach ($labels as $l): ?><option value="<?= $l ?>" <?= $p['label']===$l?'selected':'' ?>><?= $l ?></option><?php endforeach; ?>
            </select>
            <label class="form-label">Judul *</label>
            <input type="text" name="judul" class="form-input" value="<?= e($p['judul']) ?>" required>
            <label class="form-label">Deskripsi</label>
            <textarea name="deskripsi" class="form-input" rows="3"><?= e($p['deskripsi']) ?></textarea>
            <div class="form-row">
                <div class="form-col"><label class="form-label">Mulai</label><input type="date" name="tanggal_mulai" class="form-input" value="<?= e($p['tanggal_mulai']) ?>"></div>
                <div class="form-col"><label class="form-label">Selesai</label><input type="date" name="tanggal_selesai" class="form-input" value="<?= e($p['tanggal_selesai']) ?>"></div>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top:14px">Simpan 💾</button>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
