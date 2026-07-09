<?php
/* ============================================================
   manager/entitas.php — Assign pengajar & atur jadwal santri
   Hanya entitas dari client yang sudah approved.
   ============================================================ */
require_once __DIR__ . '/../includes/functions.php';
cekRole(['manager']);
$base = baseUrl();
$mid  = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $eid    = (int)($_POST['entitas_id'] ?? 0);
    $pid    = $_POST['pengajar_id'] !== '' ? (int)$_POST['pengajar_id'] : null;
    $hari   = trim($_POST['jadwal_hari'] ?? '');
    $jam    = !empty($_POST['jadwal_jam']) ? $_POST['jadwal_jam'] : null;
    $status = in_array($_POST['status_belajar'] ?? '', ['baru','aktif','lulus','nonaktif'], true) ? $_POST['status_belajar'] : 'baru';

    // Ambil data lengkap sebelum update (untuk deteksi pengajar baru & notifikasi)
    $chk = $pdo->prepare('SELECT e.nama_entitas, e.pengajar_id AS pengajar_lama, c.nama_client, c.user_id AS client_user_id
                          FROM entitas e JOIN clients c ON e.client_id=c.id WHERE e.id=?');
    $chk->execute([$eid]);
    $ent = $chk->fetch();
    if ($ent) {
        $pdo->prepare('UPDATE entitas SET pengajar_id=?, jadwal_hari=?, jadwal_jam=?, status_belajar=? WHERE id=?')
            ->execute([$pid, $hari, $jam, $status, $eid]);
        logAktivitas($mid, 'manager', 'assign_pengajar', "Entitas #$eid -> pengajar " . ($pid ?? 'none'));

        // 🔔 Notifikasi saat pengajar BARU di-assign (bukan saat sekadar edit jadwal)
        if ($pid && (int)$ent['pengajar_lama'] !== $pid) {
            $pg = $pdo->prepare('SELECT p.nama_pengajar, p.user_id FROM pengajar p WHERE p.id=?');
            $pg->execute([$pid]);
            if ($pgData = $pg->fetch()) {
                // Ke pengajar: tugas mengajar baru
                buatNotifikasi((int)$pgData['user_id'],
                    "{$pgData['nama_pengajar']} anda diminta untuk mengajar dari {$ent['nama_client']} dengan nama {$ent['nama_entitas']} 📖📚",
                    'pengajar/santri.php');
                // Ke client: anaknya sudah mendapat pengajar
                buatNotifikasi((int)$ent['client_user_id'],
                    "{$ent['nama_entitas']} telah mendapatkan pengajar: {$pgData['nama_pengajar']} 🌟",
                    'client/status.php');
            }
        }

        flashMessage('success', 'Data santri diperbarui. ✅');
    }
    redirect($base . 'manager/entitas.php' . (isset($_GET['fp']) ? '?fp='.$_GET['fp'] : ''));
}

// Daftar pengajar aktif untuk dropdown
$pengajarOpts = $pdo->query("SELECT id, nama_pengajar FROM pengajar WHERE status_aktif=1 ORDER BY nama_pengajar")->fetchAll();

// Filter
$fp = $_GET['fp'] ?? '';   // filter pengajar
$fs = $_GET['fs'] ?? '';   // filter status
$page = max(1, (int)($_GET['page'] ?? 1)); $perPage = 10; $offset = ($page-1)*$perPage;

$where = "WHERE c.status_pendaftaran='approved'"; $params = [];
if ($fp === 'none') { $where .= " AND e.pengajar_id IS NULL"; }
elseif ($fp !== '') { $where .= " AND e.pengajar_id=?"; $params[] = (int)$fp; }
if (in_array($fs, ['baru','aktif','lulus','nonaktif'], true)) { $where .= " AND e.status_belajar=?"; $params[] = $fs; }

$cnt = $pdo->prepare("SELECT COUNT(*) FROM entitas e JOIN clients c ON e.client_id=c.id $where");
$cnt->execute($params); $total = (int)$cnt->fetchColumn();

$sql = "SELECT e.*, c.nama_client FROM entitas e JOIN clients c ON e.client_id=c.id
        $where ORDER BY e.created_at DESC LIMIT $perPage OFFSET $offset";
$st = $pdo->prepare($sql); $st->execute($params); $rows = $st->fetchAll();

$panelTitle = 'Santri & Assign Pengajar';
$activeMenu = 'entitas';
require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="page-head">
    <h2>Santri & Penugasan 🧒</h2>
    <p>Tetapkan pengajar dan jadwal untuk santri dari client yang sudah disetujui.</p>
</div>

<form method="get" class="filter-bar">
    <label class="form-label" style="margin:0">Pengajar:</label>
    <select name="fp" class="form-input" onchange="this.form.submit()">
        <option value="">Semua</option>
        <option value="none" <?= $fp==='none'?'selected':'' ?>>Belum di-assign</option>
        <?php foreach ($pengajarOpts as $p): ?>
            <option value="<?= $p['id'] ?>" <?= (string)$fp===(string)$p['id']?'selected':'' ?>><?= e($p['nama_pengajar']) ?></option>
        <?php endforeach; ?>
    </select>
    <label class="form-label" style="margin:0">Status:</label>
    <select name="fs" class="form-input" onchange="this.form.submit()">
        <option value="">Semua</option>
        <?php foreach (['baru','aktif','lulus','nonaktif'] as $s): ?>
            <option value="<?= $s ?>" <?= $fs===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<div class="panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Santri</th><th>Usia</th><th>Client</th><th>Status</th><th>Pengajar / Jadwal</th><th></th></tr></thead>
            <tbody>
            <?php if ($rows): foreach ($rows as $e): ?>
                <tr>
                    <td><?= e($e['nama_entitas']) ?></td>
                    <td><?= e((string)$e['usia']) ?></td>
                    <td><?= e($e['nama_client']) ?></td>
                    <td><?= getStatusBadge($e['status_belajar']) ?></td>
                    <td>
                        <?php
                        $nmP = '-';
                        foreach ($pengajarOpts as $p) if ((int)$p['id']===(int)$e['pengajar_id']) $nmP = $p['nama_pengajar'];
                        echo $e['pengajar_id'] ? e($nmP) : '<span class="badge badge-muted">Belum</span>';
                        echo ' · ' . e($e['jadwal_hari']) . ' ' . ($e['jadwal_jam']?e(substr($e['jadwal_jam'],0,5)):'');
                        ?>
                    </td>
                    <td><button class="btn btn-info btn-sm" data-modal-target="m-as-<?= $e['id'] ?>">🔗 Atur</button></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="6" class="empty-row">Tidak ada santri (pastikan client sudah di-approve). 🧒</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?= pagination($total, $perPage, $page, $base.'manager/entitas.php?fp='.urlencode($fp).'&fs='.urlencode($fs)) ?>
</div>

<?php foreach ($rows as $e): ?>
<div class="modal-overlay" id="m-as-<?= $e['id'] ?>">
    <div class="modal">
        <div class="modal-head"><h3>Atur: <?= e($e['nama_entitas']) ?></h3><button class="modal-close" data-modal-close>&times;</button></div>
        <form method="post" action="<?= $base ?>manager/entitas.php?fp=<?= urlencode($fp) ?>&fs=<?= urlencode($fs) ?>">
            <?= csrfField() ?>
            <input type="hidden" name="entitas_id" value="<?= $e['id'] ?>">
            <label class="form-label">Pengajar</label>
            <select name="pengajar_id" class="form-input">
                <option value="">— Belum di-assign —</option>
                <?php foreach ($pengajarOpts as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= (int)$e['pengajar_id']===(int)$p['id']?'selected':'' ?>><?= e($p['nama_pengajar']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-row">
                <div class="form-col"><label class="form-label">Hari</label>
                    <input type="text" name="jadwal_hari" class="form-input" value="<?= e($e['jadwal_hari']) ?>" placeholder="Senin, Rabu"></div>
                <div class="form-col"><label class="form-label">Jam</label>
                    <input type="time" name="jadwal_jam" class="form-input" value="<?= $e['jadwal_jam']?e(substr($e['jadwal_jam'],0,5)):'' ?>"></div>
            </div>
            <label class="form-label">Status Belajar</label>
            <select name="status_belajar" class="form-input">
                <?php foreach (['baru','aktif','lulus','nonaktif'] as $s): ?>
                    <option value="<?= $s ?>" <?= $e['status_belajar']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary" style="margin-top:14px">Simpan 💾</button>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
