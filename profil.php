<?php
/* ============================================================
   profil.php — Profil mandiri untuk SEMUA role
   Edit data diri, ganti foto profil (upload), ubah password.
   Untuk role pengajar: foto & nama otomatis tersinkron ke
   tabel pengajar sehingga profil publik ikut terbarui.
   ============================================================ */
require_once __DIR__ . '/includes/functions.php';
cekLogin();

$base = baseUrl();
$uid  = (int)$_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';

$me = currentUser();
if (!$me) { redirect($base . 'logout.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $action = $_POST['action'] ?? '';

    /* ---- Update data diri + foto ---- */
    if ($action === 'update_profil') {
        $nama   = trim($_POST['nama_lengkap'] ?? '');
        $noHp   = trim($_POST['no_hp'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        if ($nama === '') {
            flashMessage('error', 'Nama lengkap wajib diisi.');
            redirect($base . 'profil.php');
        }
        $foto = $me['foto_profil'];
        if (!empty($_FILES['foto']['name'])) {
            $up = uploadFile($_FILES['foto'], 'assets/uploads');
            if ($up) {
                $foto = $up;
            } else {
                flashMessage('error', 'Foto gagal diupload (harus jpg/png, maks 2MB).');
                redirect($base . 'profil.php');
            }
        }
        $pdo->prepare('UPDATE users SET nama_lengkap=?, no_hp=?, alamat=?, foto_profil=? WHERE id=?')
            ->execute([$nama, $noHp, $alamat, $foto, $uid]);
        // Sinkron ke tabel turunan agar tampilan lain konsisten
        if ($role === 'pengajar') {
            $pdo->prepare('UPDATE pengajar SET nama_pengajar=?, foto_pengajar=? WHERE user_id=?')
                ->execute([$nama, $foto, $uid]);
        } elseif ($role === 'client') {
            $pdo->prepare('UPDATE clients SET nama_client=?, no_hp=?, alamat=? WHERE user_id=?')
                ->execute([$nama, $noHp, $alamat, $uid]);
        }
        $_SESSION['nama_lengkap'] = $nama;
        logAktivitas($uid, $role, 'update_profil', 'Memperbarui profil sendiri');
        flashMessage('success', 'Profil berhasil diperbarui. ✅');
        redirect($base . 'profil.php');
    }

    /* ---- Ganti password ---- */
    elseif ($action === 'ganti_password') {
        $lama = $_POST['password_lama'] ?? '';
        $baru = $_POST['password_baru'] ?? '';
        $baru2= $_POST['password_baru2'] ?? '';
        if (!password_verify($lama, $me['password_hash'])) {
            flashMessage('error', 'Password lama salah. ❌');
        } elseif (strlen($baru) < 6) {
            flashMessage('error', 'Password baru minimal 6 karakter.');
        } elseif ($baru !== $baru2) {
            flashMessage('error', 'Konfirmasi password baru tidak cocok.');
        } else {
            $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')
                ->execute([password_hash($baru, PASSWORD_DEFAULT), $uid]);
            logAktivitas($uid, $role, 'ganti_password', 'Mengganti password sendiri');
            flashMessage('success', 'Password berhasil diganti. 🔐');
        }
        redirect($base . 'profil.php');
    }
}

// Reload data terbaru untuk tampilan
$st = $pdo->prepare('SELECT * FROM users WHERE id=?'); $st->execute([$uid]); $me = $st->fetch();
$fotoView = $me['foto_profil'] && !str_contains($me['foto_profil'], 'default-avatar')
    ? $base . rawurlencode_path($me['foto_profil'])
    : $base . 'assets/default-avatar.png';
$roleLabel = ['super_admin'=>'Super Admin','manager'=>'Manager','pengajar'=>'Pengajar','client'=>'Client'][$role] ?? 'User';

$panelTitle = 'Profil Saya';
$activeMenu = 'profil';
require_once __DIR__ . '/includes/admin_header.php';
?>
<div class="page-head">
    <h2>Profil Saya 👤</h2>
    <p>Kelola data diri dan foto profil Anda agar tampilan lebih personal.</p>
</div>

<div class="profil-wrap">
    <!-- Kartu foto -->
    <div class="panel profil-photo-card">
        <img src="<?= $fotoView ?>" class="profil-photo" alt="Foto profil"
             onerror="this.src='<?= $base ?>assets/default-avatar.png'">
        <h3><?= e($me['nama_lengkap']) ?></h3>
        <span class="profil-role-badge"><?= e($roleLabel) ?></span>
        <p style="color:var(--text-secondary);margin-top:10px;font-size:.9rem">@<?= e($me['username']) ?></p>
        <p style="color:var(--text-secondary);font-size:.85rem"><?= e($me['email']) ?></p>
    </div>

    <!-- Form edit -->
    <div>
        <form method="post" enctype="multipart/form-data" class="panel">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="update_profil">
            <h3 class="panel-title">Data Diri ✏️</h3>
            <div class="form-row">
                <div class="form-col"><label class="form-label">Nama Lengkap *</label>
                    <input type="text" name="nama_lengkap" class="form-input" value="<?= e($me['nama_lengkap']) ?>" required></div>
                <div class="form-col"><label class="form-label">No. WhatsApp</label>
                    <input type="text" name="no_hp" class="form-input" value="<?= e($me['no_hp']) ?>"></div>
            </div>
            <label class="form-label">Alamat</label>
            <textarea name="alamat" class="form-input" rows="2"><?= e($me['alamat']) ?></textarea>
            <label class="form-label">Ganti Foto Profil (jpg/png, maks 2MB)</label>
            <input type="file" name="foto" class="form-input" accept=".jpg,.jpeg,.png">
            <p class="form-hint">Kosongkan jika tidak ingin mengganti foto.
                <?= $role==='pengajar' ? 'Foto ini juga akan tampil di halaman publik "Tentang Kami".' : '' ?></p>
            <button type="submit" class="btn btn-primary btn-lg" style="margin-top:14px">Simpan Profil 💾</button>
        </form>

        <form method="post" class="panel">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="ganti_password">
            <h3 class="panel-title">Ganti Password 🔐</h3>
            <label class="form-label">Password Lama</label>
            <input type="password" name="password_lama" class="form-input" required>
            <div class="form-row">
                <div class="form-col"><label class="form-label">Password Baru (min. 6)</label>
                    <input type="password" name="password_baru" class="form-input" required></div>
                <div class="form-col"><label class="form-label">Ulangi Password Baru</label>
                    <input type="password" name="password_baru2" class="form-input" required></div>
            </div>
            <button type="submit" class="btn btn-warning btn-lg" style="margin-top:14px">Ubah Password 🔑</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
