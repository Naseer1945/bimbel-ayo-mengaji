<?php
/* ============================================================
   notifikasi.php — Kotak notifikasi untuk SEMUA role
   Menampilkan reminder/pemberitahuan milik akun yang login.
   Dibuka = semua otomatis ditandai terbaca. Bisa hapus semua.
   ============================================================ */
require_once __DIR__ . '/includes/functions.php';
cekLogin();

$base = baseUrl();
$uid  = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    if (($_POST['action'] ?? '') === 'hapus_semua') {
        $pdo->prepare('DELETE FROM notifikasi WHERE user_id=?')->execute([$uid]);
        flashMessage('success', 'Semua notifikasi dihapus. 🧹');
    }
    redirect($base . 'notifikasi.php');
}

// Ambil notifikasi milik user (terbaru dulu), lalu tandai terbaca
$st = $pdo->prepare('SELECT * FROM notifikasi WHERE user_id=? ORDER BY created_at DESC LIMIT 100');
$st->execute([$uid]);
$notifs = $st->fetchAll();
$pdo->prepare('UPDATE notifikasi SET is_read=1 WHERE user_id=? AND is_read=0')->execute([$uid]);

$panelTitle = 'Notifikasi';
$activeMenu = 'notifikasi';
require_once __DIR__ . '/includes/admin_header.php';
?>
<div class="page-head">
    <h2>Notifikasi 🔔</h2>
    <p>Pemberitahuan dan reminder terbaru untuk akun Anda.</p>
</div>

<div class="panel">
    <div class="page-actions" style="justify-content:space-between">
        <h3 class="panel-title" style="margin:0"><?= count($notifs) ?> Notifikasi</h3>
        <?php if ($notifs): ?>
        <form method="post">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="hapus_semua">
            <button class="btn btn-danger btn-sm" data-confirm="Hapus semua notifikasi?">🗑️ Hapus Semua</button>
        </form>
        <?php endif; ?>
    </div>

    <?php if ($notifs): foreach ($notifs as $n): ?>
        <div class="notif-item <?= $n['is_read'] ? '' : 'notif-unread' ?>">
            <span class="notif-dot"><?= $n['is_read'] ? '✉️' : '🔔' ?></span>
            <div class="notif-body">
                <p><?= e($n['pesan']) ?></p>
                <small><?= formatTanggal($n['created_at']) ?> · <?= e(date('H:i', strtotime($n['created_at']))) ?></small>
            </div>
            <?php if ($n['link']): ?>
                <a href="<?= $base . e($n['link']) ?>" class="btn btn-info btn-sm">Lihat →</a>
            <?php endif; ?>
        </div>
    <?php endforeach; else: ?>
        <p class="empty-row" style="text-align:center;padding:30px;color:var(--text-secondary)">Belum ada notifikasi. Semua aman! 🌤️</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
