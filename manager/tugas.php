<?php
/* ============================================================
   manager/tugas.php — Beri tugas/arahan ke pengajar
   ============================================================ */
require_once __DIR__ . '/../includes/functions.php';
cekRole(['manager']);
$base = baseUrl();
$mid  = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $pid   = (int)($_POST['pengajar_id'] ?? 0);
        $eid   = $_POST['entitas_id'] !== '' ? (int)$_POST['entitas_id'] : null;
        $judul = trim($_POST['judul_tugas'] ?? '');
        $desk  = trim($_POST['deskripsi'] ?? '');
        if ($pid && $judul !== '') {
            $pdo->prepare('INSERT INTO tugas_pengajar (pengajar_id,entitas_id,manager_id,judul_tugas,deskripsi) VALUES (?,?,?,?,?)')
                ->execute([$pid,$eid,$mid,$judul,$desk]);
            logAktivitas($mid, 'manager', 'beri_tugas', "Tugas '$judul' ke pengajar #$pid");

            // 🔔 Notifikasi ke pengajar: ada tugas baru dari manager
            $pg = $pdo->prepare('SELECT nama_pengajar, user_id FROM pengajar WHERE id=?');
            $pg->execute([$pid]);
            if ($pgData = $pg->fetch()) {
                buatNotifikasi((int)$pgData['user_id'],
                    "Tugas baru dari manager: \"$judul\" 📋",
                    'pengajar/dashboard.php');
            }

            flashMessage('success', 'Tugas berhasil dibuat. 📋');
        } else {
            flashMessage('error', 'Pengajar & judul tugas wajib diisi.');
        }
    } elseif ($action === 'status') {
        $tid = (int)($_POST['tugas_id'] ?? 0);
        $stt = in_array($_POST['status'] ?? '', ['assigned','in_progress','completed'], true) ? $_POST['status'] : 'assigned';
        $pdo->prepare('UPDATE tugas_pengajar SET status=? WHERE id=?')->execute([$stt,$tid]);
        flashMessage('success', 'Status tugas diperbarui. 🔄');
    } elseif ($action === 'delete') {
        $tid = (int)($_POST['tugas_id'] ?? 0);
        $pdo->prepare('DELETE FROM tugas_pengajar WHERE id=?')->execute([$tid]);
        flashMessage('success', 'Tugas dihapus. 🗑️');
    }
    redirect($base . 'manager/tugas.php');
}

$pengajarOpts = $pdo->query("SELECT id, nama_pengajar FROM pengajar WHERE status_aktif=1 ORDER BY nama_pengajar")->fetchAll();
$entitasOpts  = $pdo->query("SELECT e.id, e.nama_entitas, e.pengajar_id FROM entitas e WHERE e.pengajar_id IS NOT NULL ORDER BY e.nama_entitas")->fetchAll();

$ff = $_GET['fp'] ?? ''; $fs = $_GET['fs'] ?? '';
$where = ''; $params = [];
$cond = [];
if ($ff !== '') { $cond[] = 't.pengajar_id=?'; $params[] = (int)$ff; }
if (in_array($fs, ['assigned','in_progress','completed'], true)) { $cond[] = 't.status=?'; $params[] = $fs; }
if ($cond) $where = 'WHERE ' . implode(' AND ', $cond);

$sql = "SELECT t.*, p.nama_pengajar, e.nama_entitas FROM tugas_pengajar t
        JOIN pengajar p ON t.pengajar_id=p.id
        LEFT JOIN entitas e ON t.entitas_id=e.id
        $where ORDER BY t.created_at DESC";
$st = $pdo->prepare($sql); $st->execute($params); $tugasList = $st->fetchAll();

$panelTitle = 'Beri Tugas';
$activeMenu = 'tugas';
require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="page-head">
    <h2>Tugas Pengajar 📋</h2>
    <p>Berikan arahan/tugas mengajar kepada pengajar dan pantau statusnya.</p>
</div>

<div class="page-actions">
    <button class="btn btn-primary" data-modal-target="m-add-tugas">➕ Buat Tugas Baru</button>
</div>

<form method="get" class="filter-bar">
    <label class="form-label" style="margin:0">Pengajar:</label>
    <select name="fp" class="form-input" onchange="this.form.submit()">
        <option value="">Semua</option>
        <?php foreach ($pengajarOpts as $p): ?><option value="<?= $p['id'] ?>" <?= (string)$ff===(string)$p['id']?'selected':'' ?>><?= e($p['nama_pengajar']) ?></option><?php endforeach; ?>
    </select>
    <label class="form-label" style="margin:0">Status:</label>
    <select name="fs" class="form-input" onchange="this.form.submit()">
        <option value="">Semua</option>
        <option value="assigned" <?= $fs==='assigned'?'selected':'' ?>>Ditugaskan</option>
        <option value="in_progress" <?= $fs==='in_progress'?'selected':'' ?>>Dikerjakan</option>
        <option value="completed" <?= $fs==='completed'?'selected':'' ?>>Selesai</option>
    </select>
</form>

<div class="panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Judul</th><th>Pengajar</th><th>Santri</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if ($tugasList): foreach ($tugasList as $t): ?>
                <tr>
                    <td><strong><?= e($t['judul_tugas']) ?></strong><br><small style="color:var(--text-secondary)"><?= e(mb_strimwidth($t['deskripsi']??'',0,50,'…')) ?></small></td>
                    <td><?= e($t['nama_pengajar']) ?></td>
                    <td><?= $t['nama_entitas'] ? e($t['nama_entitas']) : '<span class="badge badge-muted">Umum</span>' ?></td>
                    <td><?= getStatusBadge($t['status']) ?></td>
                    <td><?= formatTanggal($t['created_at']) ?></td>
                    <td>
                        <div class="action-group">
                            <form method="post">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="status">
                                <input type="hidden" name="tugas_id" value="<?= $t['id'] ?>">
                                <select name="status" class="form-input" onchange="this.form.submit()" style="width:auto">
                                    <option value="assigned" <?= $t['status']==='assigned'?'selected':'' ?>>Ditugaskan</option>
                                    <option value="in_progress" <?= $t['status']==='in_progress'?'selected':'' ?>>Dikerjakan</option>
                                    <option value="completed" <?= $t['status']==='completed'?'selected':'' ?>>Selesai</option>
                                </select>
                            </form>
                            <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="tugas_id" value="<?= $t['id'] ?>"><button class="btn btn-danger btn-sm" data-confirm="Hapus tugas ini?">🗑️</button></form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="6" class="empty-row">Belum ada tugas. Buat yang pertama! 📋</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Tugas -->
<div class="modal-overlay" id="m-add-tugas">
    <div class="modal">
        <div class="modal-head"><h3>Buat Tugas Baru 📋</h3><button class="modal-close" data-modal-close>&times;</button></div>
        <form method="post">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add">
            <label class="form-label">Pengajar *</label>
            <select name="pengajar_id" id="tugas-pengajar" class="form-input" required>
                <option value="">— Pilih Pengajar —</option>
                <?php foreach ($pengajarOpts as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['nama_pengajar']) ?></option><?php endforeach; ?>
            </select>
            <label class="form-label">Santri (opsional)</label>
            <select name="entitas_id" id="tugas-entitas" class="form-input">
                <option value="">— Tugas Umum —</option>
                <?php foreach ($entitasOpts as $en): ?><option value="<?= $en['id'] ?>" data-pid="<?= $en['pengajar_id'] ?>"><?= e($en['nama_entitas']) ?></option><?php endforeach; ?>
            </select>
            <label class="form-label">Judul Tugas *</label><input type="text" name="judul_tugas" class="form-input" required>
            <label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-input" rows="3"></textarea>
            <button type="submit" class="btn btn-primary btn-lg" style="margin-top:14px">Kirim Tugas 🚀</button>
        </form>
    </div>
</div>

<script>
/* Filter dropdown santri sesuai pengajar terpilih (UX kecil) */
(function(){
    var sp = document.getElementById('tugas-pengajar');
    var se = document.getElementById('tugas-entitas');
    if (sp && se) {
        var opts = Array.prototype.slice.call(se.querySelectorAll('option[data-pid]'));
        sp.addEventListener('change', function(){
            var pid = sp.value;
            se.value = '';
            opts.forEach(function(o){
                o.hidden = pid !== '' && o.getAttribute('data-pid') !== pid;
            });
        });
    }
})();
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
