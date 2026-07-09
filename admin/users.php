<?php
/* ============================================================
   admin/users.php — CRUD semua user (super admin)
   Tambah, edit (role/status/data), hapus. Filter & pagination.
   Catatan privasi: edit akun manager/pengajar/super_admin
   memerlukan verifikasi domain @bimbelayomengaji.my.id.
   ============================================================ */
require_once __DIR__ . '/../includes/functions.php';
cekRole(['super_admin']);
$base = baseUrl();
$me   = (int)$_SESSION['user_id'];
$VERIF_DOMAIN = getSetting('domain_verif', 'bimbelayomengaji.my.id');

$roles = ['client','manager','pengajar','super_admin'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $nama=trim($_POST['nama_lengkap']??''); $user=trim($_POST['username']??'');
        $email=trim($_POST['email']??''); $pass=$_POST['password']??'';
        $role=in_array($_POST['role']??'',$roles,true)?$_POST['role']:'client';
        $noHp=trim($_POST['no_hp']??'');
        if ($nama===''||$user===''||$email===''||$pass==='') { flashMessage('error','Field wajib belum lengkap.'); }
        elseif (!filter_var($email,FILTER_VALIDATE_EMAIL)) { flashMessage('error','Email tidak valid.'); }
        else {
            $chk=$pdo->prepare('SELECT id FROM users WHERE username=? OR email=?'); $chk->execute([$user,$email]);
            if ($chk->fetch()) { flashMessage('error','Username/email sudah dipakai.'); }
            else {
                try {
                    $pdo->beginTransaction();
                    $pdo->prepare('INSERT INTO users (username,email,password_hash,role,nama_lengkap,no_hp) VALUES (?,?,?,?,?,?)')
                        ->execute([$user,$email,password_hash($pass,PASSWORD_DEFAULT),$role,$nama,$noHp]);
                    $nid=(int)$pdo->lastInsertId();
                    if ($role==='pengajar') {
                        $pdo->prepare('INSERT INTO pengajar (user_id,nama_pengajar) VALUES (?,?)')->execute([$nid,$nama]);
                    } elseif ($role==='client') {
                        $pdo->prepare('INSERT INTO clients (user_id,nama_client,no_hp) VALUES (?,?,?)')->execute([$nid,$nama,$noHp]);
                    }
                    $pdo->commit();
                    logAktivitas($me,'super_admin','tambah_user',"User $user ($role)");
                    flashMessage('success',"User $user ditambahkan. 🎉");
                } catch (PDOException $ex){ if($pdo->inTransaction())$pdo->rollBack(); flashMessage('error','Gagal menambah user.'); }
            }
        }
    }
    elseif ($action === 'edit') {
        $uid=(int)($_POST['user_id']??0);
        $nama=trim($_POST['nama_lengkap']??''); $noHp=trim($_POST['no_hp']??'');
        $role=in_array($_POST['role']??'',$roles,true)?$_POST['role']:'client';
        $status=(int)($_POST['status_aktif']??1);
        $newPass=$_POST['password']??'';
        $verif=trim($_POST['verif_email']??'');

        $r=$pdo->prepare('SELECT * FROM users WHERE id=?'); $r->execute([$uid]); $target=$r->fetch();
        if (!$target) { flashMessage('error','User tidak ditemukan.'); }
        else {
            // Privasi: mengedit akun staf perlu verifikasi domain
            $isStaff = in_array($target['role'], ['manager','pengajar','super_admin'], true);
            $needVerif = $isStaff;
            $domainOK = str_ends_with(strtolower($verif), '@'.strtolower($VERIF_DOMAIN));
            if ($needVerif && !$domainOK) {
                flashMessage('error', "Edit akun staf butuh verifikasi email berdomain @$VERIF_DOMAIN.");
            } else {
                if ($newPass !== '') {
                    $pdo->prepare('UPDATE users SET nama_lengkap=?,no_hp=?,role=?,status_aktif=?,password_hash=? WHERE id=?')
                        ->execute([$nama,$noHp,$role,$status,password_hash($newPass,PASSWORD_DEFAULT),$uid]);
                } else {
                    $pdo->prepare('UPDATE users SET nama_lengkap=?,no_hp=?,role=?,status_aktif=? WHERE id=?')
                        ->execute([$nama,$noHp,$role,$status,$uid]);
                }
                // Jika role berubah ke pengajar & belum ada detail, buat
                if ($role==='pengajar') {
                    $c=$pdo->prepare('SELECT id FROM pengajar WHERE user_id=?'); $c->execute([$uid]);
                    if(!$c->fetch()) $pdo->prepare('INSERT INTO pengajar (user_id,nama_pengajar) VALUES (?,?)')->execute([$uid,$nama]);
                }
                logAktivitas($me,'super_admin','edit_user',"Edit user #$uid");
                flashMessage('success','User diperbarui. ✅');
            }
        }
    }
    elseif ($action === 'delete') {
        $uid=(int)($_POST['user_id']??0);
        if ($uid === $me) { flashMessage('error','Tidak bisa menghapus akun sendiri.'); }
        else {
            $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$uid]);
            logAktivitas($me,'super_admin','hapus_user',"Hapus user #$uid");
            flashMessage('success','User dihapus. 🗑️');
        }
    }
    redirect($base.'admin/users.php');
}

// Filter + pagination
$fr=$_GET['role']??''; $fst=$_GET['status']??'';
$page=max(1,(int)($_GET['page']??1)); $perPage=15; $offset=($page-1)*$perPage;
$cond=[]; $params=[];
if (in_array($fr,$roles,true)){ $cond[]='role=?'; $params[]=$fr; }
if ($fst!=='' && in_array($fst,['0','1'],true)){ $cond[]='status_aktif=?'; $params[]=(int)$fst; }
$where=$cond?('WHERE '.implode(' AND ',$cond)):'';

$cnt=$pdo->prepare("SELECT COUNT(*) FROM users $where"); $cnt->execute($params); $total=(int)$cnt->fetchColumn();
$st=$pdo->prepare("SELECT * FROM users $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$st->execute($params); $users=$st->fetchAll();

$panelTitle='Kelola User'; $activeMenu='users';
require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="page-head">
    <h2>Kelola User 👥</h2>
    <p>Tambah, edit, dan hapus semua akun. Edit akun staf butuh verifikasi domain @<?= e($VERIF_DOMAIN) ?>.</p>
</div>

<div class="page-actions">
    <button class="btn btn-primary" data-modal-target="m-add-user">➕ Tambah User</button>
</div>

<form method="get" class="filter-bar">
    <select name="role" class="form-input" onchange="this.form.submit()">
        <option value="">Semua Role</option>
        <?php foreach ($roles as $r): ?><option value="<?= $r ?>" <?= $fr===$r?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$r)) ?></option><?php endforeach; ?>
    </select>
    <select name="status" class="form-input" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        <option value="1" <?= $fst==='1'?'selected':'' ?>>Aktif</option>
        <option value="0" <?= $fst==='0'?'selected':'' ?>>Nonaktif</option>
    </select>
</form>

<div class="panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Nama</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if ($users): foreach ($users as $u): ?>
                <tr>
                    <td><?= e($u['nama_lengkap']) ?></td>
                    <td><?= e($u['username']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><span class="badge badge-info"><?= ucfirst(str_replace('_',' ',$u['role'])) ?></span></td>
                    <td><?= $u['status_aktif']?'<span class="badge badge-success">Aktif</span>':'<span class="badge badge-muted">Nonaktif</span>' ?></td>
                    <td>
                        <div class="action-group">
                            <button class="btn btn-info btn-sm" data-modal-target="m-edit-<?= $u['id'] ?>">✏️</button>
                            <?php if ((int)$u['id'] !== $me): ?>
                            <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" value="<?= $u['id'] ?>"><button class="btn btn-danger btn-sm" data-confirm="Hapus user ini permanen?">🗑️</button></form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="6" class="empty-row">Tidak ada user untuk filter ini.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?= pagination($total,$perPage,$page,$base.'admin/users.php?role='.urlencode($fr).'&status='.urlencode($fst)) ?>
</div>

<!-- Modal Tambah -->
<div class="modal-overlay" id="m-add-user">
    <div class="modal">
        <div class="modal-head"><h3>Tambah User Baru ➕</h3><button class="modal-close" data-modal-close>&times;</button></div>
        <form method="post">
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
            <div class="form-row">
                <div class="form-col"><label class="form-label">Password *</label><input type="password" name="password" class="form-input" required></div>
                <div class="form-col"><label class="form-label">Role *</label>
                    <select name="role" class="form-input">
                        <?php foreach ($roles as $r): ?><option value="<?= $r ?>"><?= ucfirst(str_replace('_',' ',$r)) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg" style="margin-top:14px">Simpan User 💾</button>
        </form>
    </div>
</div>

<!-- Modal Edit per user -->
<?php foreach ($users as $u): $isStaff=in_array($u['role'],['manager','pengajar','super_admin'],true); ?>
<div class="modal-overlay" id="m-edit-<?= $u['id'] ?>">
    <div class="modal">
        <div class="modal-head"><h3>Edit: <?= e($u['nama_lengkap']) ?></h3><button class="modal-close" data-modal-close>&times;</button></div>
        <form method="post">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <div class="form-row">
                <div class="form-col"><label class="form-label">Nama Lengkap</label><input type="text" name="nama_lengkap" class="form-input" value="<?= e($u['nama_lengkap']) ?>" required></div>
                <div class="form-col"><label class="form-label">No. HP</label><input type="text" name="no_hp" class="form-input" value="<?= e($u['no_hp']) ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-col"><label class="form-label">Role</label>
                    <select name="role" class="form-input">
                        <?php foreach ($roles as $r): ?><option value="<?= $r ?>" <?= $u['role']===$r?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$r)) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-col"><label class="form-label">Status</label>
                    <select name="status_aktif" class="form-input">
                        <option value="1" <?= $u['status_aktif']?'selected':'' ?>>Aktif</option>
                        <option value="0" <?= !$u['status_aktif']?'selected':'' ?>>Nonaktif</option>
                    </select>
                </div>
            </div>
            <label class="form-label">Password Baru (kosongkan jika tidak diubah)</label>
            <input type="password" name="password" class="form-input">
            <?php if ($isStaff): ?>
            <label class="form-label">🔐 Verifikasi Email (@<?= e($VERIF_DOMAIN) ?>)</label>
            <input type="email" name="verif_email" class="form-input" placeholder="dev@<?= e($VERIF_DOMAIN) ?>" required>
            <p class="form-hint">Wajib untuk mengedit akun staf (privasi developer).</p>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary" style="margin-top:14px">Simpan Perubahan 💾</button>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
