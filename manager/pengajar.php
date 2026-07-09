<?php
/* ============================================================
   manager/pengajar.php — Tambah/Edit/Nonaktifkan pengajar
   INSERT users(role=pengajar) + pengajar. Upload foto.
   Dampak: index.php & tentang-kami.php otomatis berubah.
   ============================================================ */
require_once __DIR__ . '/../includes/functions.php';
cekRole(['manager','super_admin']);
$base = baseUrl();
$mid  = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $action = $_POST['action'] ?? '';

    /* ---- TAMBAH PENGAJAR ---- */
    if ($action === 'add') {
        $nama  = trim($_POST['nama_lengkap'] ?? '');
        $user  = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $noHp  = trim($_POST['no_hp'] ?? '');
        $pend  = trim($_POST['pendidikan'] ?? '');
        $peng  = trim($_POST['pengalaman'] ?? '');
        $bio   = trim($_POST['bio'] ?? '');

        if ($nama==='' || $user==='' || $email==='' || $pass==='') {
            flashMessage('error', 'Nama, username, email, password wajib diisi.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flashMessage('error', 'Email tidak valid.');
        } else {
            $chk = $pdo->prepare('SELECT id FROM users WHERE username=? OR email=?');
            $chk->execute([$user,$email]);
            if ($chk->fetch()) {
                flashMessage('error', 'Username/email sudah dipakai.');
            } else {
                $foto = 'assets/default-pengajar.png';
                if (!empty($_FILES['foto']['name'])) {
                    $up = uploadFile($_FILES['foto'], 'assets/uploads');
                    if ($up) $foto = $up; else flashMessage('error', 'Foto gagal diupload (cek format jpg/png & ukuran < 2MB).');
                }
                try {
                    $pdo->beginTransaction();
                    $ins = $pdo->prepare('INSERT INTO users (username,email,password_hash,role,nama_lengkap,no_hp,foto_profil) VALUES (?,?,?,"pengajar",?,?,?)');
                    $ins->execute([$user,$email,password_hash($pass,PASSWORD_DEFAULT),$nama,$noHp,$foto]);
                    $newUid = (int)$pdo->lastInsertId();
                    $pdo->prepare('INSERT INTO pengajar (user_id,nama_pengajar,pendidikan,pengalaman,foto_pengajar,bio) VALUES (?,?,?,?,?,?)')
                        ->execute([$newUid,$nama,$pend,$peng,$foto,$bio]);
                    $pdo->commit();
                    logAktivitas($mid, $_SESSION['role'], 'tambah_pengajar', "Pengajar baru: $nama");
                    flashMessage('success', "Pengajar $nama berhasil ditambahkan! 🎉");
                } catch (PDOException $ex) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    flashMessage('error', 'Gagal menambah pengajar.');
                }
            }
        }
    }

    /* ---- EDIT PENGAJAR ---- */
    elseif ($action === 'edit') {
        $pgid = (int)($_POST['pengajar_id'] ?? 0);
        $nama = trim($_POST['nama_lengkap'] ?? '');
        $pend = trim($_POST['pendidikan'] ?? '');
        $peng = trim($_POST['pengalaman'] ?? '');
        $bio  = trim($_POST['bio'] ?? '');
        $row  = $pdo->prepare('SELECT * FROM pengajar WHERE id=?'); $row->execute([$pgid]); $pg = $row->fetch();
        if ($pg) {
            $foto = $pg['foto_pengajar'];
            if (!empty($_FILES['foto']['name'])) {
                $up = uploadFile($_FILES['foto'], 'assets/uploads');
                if ($up) $foto = $up;
            }
            $pdo->prepare('UPDATE pengajar SET nama_pengajar=?, pendidikan=?, pengalaman=?, bio=?, foto_pengajar=? WHERE id=?')
                ->execute([$nama,$pend,$peng,$bio,$foto,$pgid]);
            $pdo->prepare('UPDATE users SET nama_lengkap=?, foto_profil=? WHERE id=?')
                ->execute([$nama,$foto,$pg['user_id']]);
            logAktivitas($mid, $_SESSION['role'], 'edit_pengajar', "Edit pengajar #$pgid");
            flashMessage('success', 'Data pengajar diperbarui. ✅');
        }
    }

    /* ---- TOGGLE STATUS ---- */
    elseif ($action === 'toggle') {
        $pgid = (int)($_POST['pengajar_id'] ?? 0);
        $pdo->prepare('UPDATE pengajar SET status_aktif = NOT status_aktif WHERE id=?')->execute([$pgid]);
        // sinkron user status
        $pdo->prepare('UPDATE users u JOIN pengajar p ON u.id=p.user_id SET u.status_aktif=p.status_aktif WHERE p.id=?')->execute([$pgid]);
        logAktivitas($mid, $_SESSION['role'], 'toggle_pengajar', "Toggle status pengajar #$pgid");
        flashMessage('success', 'Status pengajar diubah. 🔄');
    }

    /* ---- UBAH URUTAN TAMPIL (rotasi pengajar di web publik) ---- */
    elseif ($action === 'reorder') {
        $pgid = (int)($_POST['pengajar_id'] ?? 0);
        $ur   = (int)($_POST['urutan_tampil'] ?? 0);
        $pdo->prepare('UPDATE pengajar SET urutan_tampil=? WHERE id=?')->execute([$ur, $pgid]);
        logAktivitas($mid, $_SESSION['role'], 'reorder_pengajar', "Urutan pengajar #$pgid -> $ur");
        flashMessage('success', 'Urutan tampil pengajar diperbarui. ↕️');
    }
    redirect($base . 'manager/pengajar.php');
}

$list = $pdo->query("SELECT p.*, u.email, u.username FROM pengajar p JOIN users u ON p.user_id=u.id ORDER BY p.status_aktif DESC, p.urutan_tampil ASC, p.nama_pengajar ASC")->fetchAll();

$panelTitle = 'Kelola Pengajar';
$activeMenu = 'pengajar';
require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="page-head">
    <h2>Kelola Pengajar 👨‍🏫</h2>
    <p>Tambah pengajar baru — otomatis tampil di halaman publik. Edit & atur status keaktifan.</p>
</div>

<div class="page-actions">
    <button class="btn btn-primary" data-modal-target="m-add-pengajar">➕ Tambah Pengajar Baru</button>
</div>

<div class="panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Foto</th><th>Nama</th><th>Username</th><th>Email</th><th>Urutan</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if ($list): foreach ($list as $p): ?>
                <tr>
                    <td><img src="<?= $base . rawurlencode_path($p['foto_pengajar']) ?>" class="thumb" onerror="this.src='<?= $base ?>assets/default-pengajar.png'"></td>
                    <td><?= e($p['nama_pengajar']) ?></td>
                    <td><?= e($p['username']) ?></td>
                    <td><?= e($p['email']) ?></td>
                    <td>
                        <form method="post" style="display:flex;gap:6px;align-items:center">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="reorder">
                            <input type="hidden" name="pengajar_id" value="<?= $p['id'] ?>">
                            <input type="number" name="urutan_tampil" class="form-input" value="<?= (int)($p['urutan_tampil'] ?? 0) ?>" style="width:70px" title="Angka kecil tampil lebih dulu">
                            <button class="btn btn-info btn-sm" title="Simpan urutan">↕️</button>
                        </form>
                    </td>
                    <td><?= $p['status_aktif'] ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-muted">Nonaktif</span>' ?></td>
                    <td>
                        <div class="action-group">
                            <button class="btn btn-info btn-sm" data-modal-target="m-edit-<?= $p['id'] ?>">✏️ Edit</button>
                            <form method="post" style="display:inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="pengajar_id" value="<?= $p['id'] ?>">
                                <button class="btn <?= $p['status_aktif']?'btn-warning':'btn-success' ?> btn-sm" data-confirm="Ubah status pengajar ini?"><?= $p['status_aktif']?'🚫 Nonaktif':'✅ Aktifkan' ?></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="7" class="empty-row">Belum ada pengajar. Tambahkan yang pertama! 👨‍🏫</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal-overlay" id="m-add-pengajar">
    <div class="modal modal-wide">
        <div class="modal-head"><h3>Tambah Pengajar Baru 👨‍🏫</h3><button class="modal-close" data-modal-close>&times;</button></div>
        <form method="post" enctype="multipart/form-data">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add">
            <div class="form-row">
                <div class="form-col"><label class="form-label">Nama Lengkap *</label><input type="text" name="nama_lengkap" class="form-input" required></div>
                <div class="form-col"><label class="form-label">No. HP</label><input type="text" name="no_hp" class="form-input"></div>
            </div>
            <div class="form-row">
                <div class="form-col"><label class="form-label">Username *</label><input type="text" name="username" class="form-input" required></div>
                <div class="form-col"><label class="form-label">Email *</label><input type="email" name="email" class="form-input" required></div>
            </div>
            <label class="form-label">Password *</label><input type="password" name="password" class="form-input" required>
            <label class="form-label">Pendidikan (pisahkan dengan ; )</label><textarea name="pendidikan" class="form-input" rows="2"></textarea>
            <label class="form-label">Pengalaman (pisahkan dengan ; )</label><textarea name="pengalaman" class="form-input" rows="2"></textarea>
            <label class="form-label">Bio</label><textarea name="bio" class="form-input" rows="2"></textarea>
            <label class="form-label">Foto Pengajar (jpg/png, max 2MB)</label><input type="file" name="foto" class="form-input" accept=".jpg,.jpeg,.png">
            <button type="submit" class="btn btn-primary btn-lg" style="margin-top:14px">Simpan Pengajar 💾</button>
        </form>
    </div>
</div>

<!-- Modal Edit per pengajar -->
<?php foreach ($list as $p): ?>
<div class="modal-overlay" id="m-edit-<?= $p['id'] ?>">
    <div class="modal modal-wide">
        <div class="modal-head"><h3>Edit: <?= e($p['nama_pengajar']) ?></h3><button class="modal-close" data-modal-close>&times;</button></div>
        <form method="post" enctype="multipart/form-data">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="pengajar_id" value="<?= $p['id'] ?>">
            <label class="form-label">Nama Lengkap</label><input type="text" name="nama_lengkap" class="form-input" value="<?= e($p['nama_pengajar']) ?>" required>
            <p class="form-hint">Username (<?= e($p['username']) ?>) tidak dapat diubah.</p>
            <label class="form-label">Pendidikan</label><textarea name="pendidikan" class="form-input" rows="2"><?= e($p['pendidikan']) ?></textarea>
            <label class="form-label">Pengalaman</label><textarea name="pengalaman" class="form-input" rows="2"><?= e($p['pengalaman']) ?></textarea>
            <label class="form-label">Bio</label><textarea name="bio" class="form-input" rows="2"><?= e($p['bio']) ?></textarea>
            <label class="form-label">Ganti Foto (opsional)</label><input type="file" name="foto" class="form-input" accept=".jpg,.jpeg,.png">
            <button type="submit" class="btn btn-primary" style="margin-top:14px">Simpan Perubahan 💾</button>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
