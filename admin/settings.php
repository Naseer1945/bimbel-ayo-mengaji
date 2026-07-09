<?php
/* ============================================================
   admin/settings.php — Pengaturan sistem (super admin)
   Ubah nama web/kontak/deskripsi, backup DB, reset statistik.
   ============================================================ */
require_once __DIR__ . '/../includes/functions.php';
cekRole(['super_admin']);
$base = baseUrl();
$me   = (int)$_SESSION['user_id'];

/* ---- BACKUP DATABASE (download .sql) — harus sebelum output HTML ---- */
if (($_GET['action'] ?? '') === 'backup') {
    $filename = 'backup_bimbel_' . date('Ymd_His') . '.sql';
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "-- Backup database bimbel_ayo_mengaji\n-- " . date('Y-m-d H:i:s') . "\nSET FOREIGN_KEY_CHECKS=0;\n\n";
    foreach ($tables as $tbl) {
        $create = $pdo->query("SHOW CREATE TABLE `$tbl`")->fetch(PDO::FETCH_NUM);
        echo "DROP TABLE IF EXISTS `$tbl`;\n" . ($create[1] ?? '') . ";\n\n";
        $rows = $pdo->query("SELECT * FROM `$tbl`");
        foreach ($rows as $row) {
            $cols = array_map(fn($c) => "`$c`", array_keys($row));
            $vals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote($v), array_values($row));
            echo "INSERT INTO `$tbl` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n";
        }
        echo "\n";
    }
    echo "SET FOREIGN_KEY_CHECKS=1;\n";
    logAktivitas($me, 'super_admin', 'backup_db', 'Download backup database');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $fields = ['nama_web','kontak_wa','email_admin','deskripsi','domain_verif'];
        $up = $pdo->prepare('INSERT INTO pengaturan (nama_key,nilai) VALUES (?,?) ON DUPLICATE KEY UPDATE nilai=VALUES(nilai)');
        foreach ($fields as $f) {
            $up->execute([$f, trim($_POST[$f] ?? '')]);
        }
        logAktivitas($me,'super_admin','update_settings','Pengaturan sistem diperbarui');
        flashMessage('success','Pengaturan disimpan. ⚙️');
        redirect($base.'admin/settings.php');
    }
    elseif ($action === 'save_fitur') {
        // Toggle fitur webapp (checkbox: ada = 1, tidak ada = 0)
        $up = $pdo->prepare('INSERT INTO pengaturan (nama_key,nilai) VALUES (?,?) ON DUPLICATE KEY UPDATE nilai=VALUES(nilai)');
        foreach (['fitur_youtube','fitur_promosi'] as $f) {
            $up->execute([$f, isset($_POST[$f]) ? '1' : '0']);
        }
        logAktivitas($me,'super_admin','toggle_fitur','Mengubah status fitur webapp');
        flashMessage('success','Status fitur diperbarui. 🎛️');
        redirect($base.'admin/settings.php');
    }
    elseif ($action === 'reset_stats') {
        $pdo->query('DELETE FROM log_aktivitas');
        logAktivitas($me,'super_admin','reset_stats','Reset log aktivitas');
        flashMessage('success','Log aktivitas direset. 🧹');
        redirect($base.'admin/settings.php');
    }
}

// reload cache settings fresh (getSetting menyimpan static cache; baca langsung)
$cfg = [];
foreach ($pdo->query('SELECT nama_key,nilai FROM pengaturan') as $r) $cfg[$r['nama_key']]=$r['nilai'];

$panelTitle='Pengaturan'; $activeMenu='settings';
require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="page-head">
    <h2>Pengaturan Sistem ⚙️</h2>
    <p>Kelola informasi website, backup database, dan pemeliharaan.</p>
</div>

<form method="post" class="panel">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="save">
    <h3 class="panel-title">Informasi Website 🌐</h3>
    <div class="form-row">
        <div class="form-col"><label class="form-label">Nama Web</label><input type="text" name="nama_web" class="form-input" value="<?= e($cfg['nama_web']??'') ?>"></div>
        <div class="form-col"><label class="form-label">Kontak WhatsApp</label><input type="text" name="kontak_wa" class="form-input" value="<?= e($cfg['kontak_wa']??'') ?>"></div>
    </div>
    <div class="form-row">
        <div class="form-col"><label class="form-label">Email Admin</label><input type="email" name="email_admin" class="form-input" value="<?= e($cfg['email_admin']??'') ?>"></div>
        <div class="form-col"><label class="form-label">Domain Verifikasi Dev</label><input type="text" name="domain_verif" class="form-input" value="<?= e($cfg['domain_verif']??'') ?>"></div>
    </div>
    <label class="form-label">Deskripsi Default</label>
    <textarea name="deskripsi" class="form-input" rows="3"><?= e($cfg['deskripsi']??'') ?></textarea>
    <button type="submit" class="btn btn-primary btn-lg" style="margin-top:14px">Simpan Pengaturan 💾</button>
</form>

<form method="post" class="panel">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="save_fitur">
    <h3 class="panel-title">Kontrol Fitur Webapp 🎛️</h3>
    <p class="form-hint" style="margin-bottom:12px">Nonaktifkan fitur untuk menyembunyikannya dari web publik tanpa menghapus datanya. Bisa diaktifkan kembali kapan saja.</p>
    <label class="form-label" style="display:flex;align-items:center;gap:10px;cursor:pointer">
        <input type="checkbox" name="fitur_youtube" <?= ($cfg['fitur_youtube'] ?? '1')==='1' ? 'checked' : '' ?> style="width:20px;height:20px">
        ▶️ Link YouTube (kartu keunggulan, nilai kami, tombol channel)
    </label>
    <label class="form-label" style="display:flex;align-items:center;gap:10px;cursor:pointer">
        <input type="checkbox" name="fitur_promosi" <?= ($cfg['fitur_promosi'] ?? '1')==='1' ? 'checked' : '' ?> style="width:20px;height:20px">
        🎉 Promosi & Event (banner promo di beranda)
    </label>
    <button type="submit" class="btn btn-primary" style="margin-top:14px">Simpan Status Fitur 💾</button>
</form>

<div class="panel">
    <h3 class="panel-title">Pemeliharaan 🛠️</h3>
    <div class="page-actions">
        <a href="<?= $base ?>admin/settings.php?action=backup" class="btn btn-success">⬇️ Backup Database (.sql)</a>
        <form method="post" style="display:inline">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="reset_stats">
            <button class="btn btn-danger" data-confirm="Yakin reset semua log aktivitas? Tindakan ini tidak bisa dibatalkan.">🧹 Reset Log Aktivitas</button>
        </form>
    </div>
    <p class="form-hint">Backup mengunduh seluruh struktur + data sebagai file SQL. Reset menghapus seluruh log aktivitas.</p>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
