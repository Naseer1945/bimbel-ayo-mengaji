<?php
/* ============================================================
   manager/galeri.php — Kelola aset galeri (foto web)
   Upload, hapus, aktif/nonaktif, ubah urutan tampil.
   Foto rolling di halaman publik sesuai urutan_tampil.
   Diakses oleh manager & super_admin.
   ============================================================ */
require_once __DIR__ . '/../includes/functions.php';
cekRole(['manager','super_admin']);
$base = baseUrl();
$uid  = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $action = $_POST['action'] ?? '';

    if ($action === 'upload') {
        $nama     = trim($_POST['nama_file'] ?? '');
        $kategori = in_array($_POST['kategori'] ?? '', ['hero','galeri','dokumentasi','pengajar'], true) ? $_POST['kategori'] : 'galeri';
        $urutan   = (int)($_POST['urutan_tampil'] ?? 0);
        if ($nama === '' || empty($_FILES['file']['name'])) {
            flashMessage('error', 'Nama foto & file wajib diisi.');
        } else {
            $path = uploadFile($_FILES['file'], 'assets/images');
            if ($path) {
                $pdo->prepare('INSERT INTO assets_galeri (nama_file,path_file,kategori,urutan_tampil,diupload_oleh) VALUES (?,?,?,?,?)')
                    ->execute([$nama,$path,$kategori,$urutan,$uid]);
                logAktivitas($uid, $_SESSION['role'], 'upload_galeri', "Upload: $nama ($kategori)");
                flashMessage('success', 'Foto berhasil diupload! 🖼️');
            } else {
                flashMessage('error', 'Upload gagal. Pastikan jpg/png & < 2MB.');
            }
        }
    }
    elseif ($action === 'delete') {
        $gid = (int)($_POST['id'] ?? 0);
        $r = $pdo->prepare('SELECT path_file FROM assets_galeri WHERE id=?'); $r->execute([$gid]); $g = $r->fetch();
        if ($g) {
            $pdo->prepare('DELETE FROM assets_galeri WHERE id=?')->execute([$gid]);
            // Hapus file fisik HANYA jika berada di folder upload (jangan hapus aset asli/seed)
            $abs = __DIR__ . '/../' . $g['path_file'];
            if (is_file($abs) && (str_contains($g['path_file'],'assets/images/') || str_contains($g['path_file'],'assets/uploads/'))) {
                @unlink($abs);
            }
            logAktivitas($uid, $_SESSION['role'], 'hapus_galeri', "Hapus galeri #$gid");
            flashMessage('success', 'Foto dihapus. 🗑️');
        }
    }
    elseif ($action === 'toggle') {
        $gid = (int)($_POST['id'] ?? 0);
        $pdo->prepare('UPDATE assets_galeri SET status_aktif = NOT status_aktif WHERE id=?')->execute([$gid]);
        flashMessage('success', 'Status foto diubah. 🔄');
    }
    elseif ($action === 'reorder') {
        $gid = (int)($_POST['id'] ?? 0);
        $ur  = (int)($_POST['urutan_tampil'] ?? 0);
        $pdo->prepare('UPDATE assets_galeri SET urutan_tampil=? WHERE id=?')->execute([$ur,$gid]);
        flashMessage('success', 'Urutan tampil diperbarui. ↕️');
    }
    redirect($base . 'manager/galeri.php');
}

$items = $pdo->query("SELECT * FROM assets_galeri ORDER BY kategori, urutan_tampil ASC")->fetchAll();

$panelTitle = 'Galeri Foto';
$activeMenu = 'galeri';
require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="page-head">
    <h2>Kelola Aset Galeri 🖼️</h2>
    <p>Ganti foto di website kapan saja. Foto tampil berurutan (rolling) sesuai "Urutan".</p>
</div>

<div class="page-actions">
    <button class="btn btn-primary" data-modal-target="m-upload">⬆️ Upload Foto Baru</button>
</div>

<div class="panel">
    <h3 class="panel-title">Semua Foto (<?= count($items) ?>)</h3>
    <div class="galeri-admin-grid">
        <?php if ($items): foreach ($items as $g): ?>
            <div class="galeri-admin-item">
                <img src="<?= $base . rawurlencode_path($g['path_file']) ?>" alt="<?= e($g['nama_file']) ?>" onerror="this.src='<?= $base ?>assets/default-pengajar.png'">
                <div class="galeri-admin-body">
                    <h4><?= e($g['nama_file']) ?></h4>
                    <div class="galeri-admin-meta">
                        <span class="badge badge-info"><?= e($g['kategori']) ?></span>
                        <?= $g['status_aktif'] ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-muted">Nonaktif</span>' ?>
                    </div>
                    <form method="post" class="filter-bar" style="margin-bottom:8px">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="reorder">
                        <input type="hidden" name="id" value="<?= $g['id'] ?>">
                        <input type="number" name="urutan_tampil" class="form-input" value="<?= (int)$g['urutan_tampil'] ?>" style="width:80px" title="Urutan tampil">
                        <button class="btn btn-info btn-sm">↕️</button>
                    </form>
                    <div class="action-group">
                        <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $g['id'] ?>"><button class="btn <?= $g['status_aktif']?'btn-warning':'btn-success' ?> btn-sm"><?= $g['status_aktif']?'🚫':'✅' ?></button></form>
                        <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $g['id'] ?>"><button class="btn btn-danger btn-sm" data-confirm="Hapus foto ini permanen?">🗑️</button></form>
                    </div>
                </div>
            </div>
        <?php endforeach; else: ?>
            <p style="color:var(--text-secondary)">Belum ada foto. Upload yang pertama! 🖼️</p>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Upload -->
<div class="modal-overlay" id="m-upload">
    <div class="modal">
        <div class="modal-head"><h3>Upload Foto Baru ⬆️</h3><button class="modal-close" data-modal-close>&times;</button></div>
        <form method="post" enctype="multipart/form-data">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="upload">
            <label class="form-label">Nama Foto *</label><input type="text" name="nama_file" class="form-input" required>
            <label class="form-label">Kategori *</label>
            <select name="kategori" class="form-input">
                <option value="dokumentasi">Dokumentasi</option>
                <option value="galeri">Galeri</option>
                <option value="hero">Hero</option>
                <option value="pengajar">Pengajar</option>
            </select>
            <label class="form-label">Urutan Tampil</label><input type="number" name="urutan_tampil" class="form-input" value="0">
            <p class="form-hint">Angka kecil tampil lebih dulu (rolling sesuai urutan).</p>
            <label class="form-label">File (jpg/png, max 2MB) *</label><input type="file" name="file" class="form-input" accept=".jpg,.jpeg,.png" required>
            <button type="submit" class="btn btn-primary btn-lg" style="margin-top:14px">Upload Sekarang 🚀</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
