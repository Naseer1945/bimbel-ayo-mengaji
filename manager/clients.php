<?php
/* ============================================================
   manager/clients.php — Approve/Reject client + detail entitas
   ============================================================ */
require_once __DIR__ . '/../includes/functions.php';
cekRole(['manager']);
$base = baseUrl();
$mid  = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $cid    = (int)($_POST['client_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $catatan= trim($_POST['catatan_manager'] ?? '');

    $cstmt = $pdo->prepare('SELECT * FROM clients WHERE id=?');
    $cstmt->execute([$cid]);
    $client = $cstmt->fetch();

    if ($client && in_array($action, ['approve','reject'], true)) {
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';
        $pdo->prepare('UPDATE clients SET status_pendaftaran=?, catatan_manager=? WHERE id=?')
            ->execute([$newStatus, $catatan, $cid]);
        logAktivitas($mid, 'manager', 'client_' . $action, "Client #$cid -> $newStatus");

        // 🔔 Notifikasi ke client per anak yang didaftarkan
        $ents = $pdo->prepare('SELECT nama_entitas FROM entitas WHERE client_id=?');
        $ents->execute([$cid]);
        $namaAnak = $ents->fetchAll(PDO::FETCH_COLUMN);
        if ($action === 'approve') {
            foreach ($namaAnak as $na) {
                buatNotifikasi((int)$client['user_id'],
                    "$na anda sudah diterima oleh manager, bersiap untuk belajar 🥰❤️",
                    'client/status.php');
            }
            if (!$namaAnak) {
                buatNotifikasi((int)$client['user_id'], 'Pendaftaran Anda sudah diterima oleh manager! 🥰❤️', 'client/status.php');
            }
        } else {
            buatNotifikasi((int)$client['user_id'],
                'Mohon maaf, pendaftaran Anda belum dapat diterima.' . ($catatan ? " Catatan: $catatan" : '') . ' 🙏',
                'client/status.php');
        }

        flashMessage('success', 'Client berhasil ' . ($action==='approve'?'disetujui ✅':'ditolak ❌') . '.');
    } else {
        flashMessage('error', 'Aksi tidak valid.');
    }
    redirect($base . 'manager/clients.php');
}

// Filter & pagination
$filter = $_GET['status'] ?? 'all';
$valid  = ['pending','approved','rejected'];
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage= 10;
$offset = ($page-1)*$perPage;

$where = ''; $params = [];
if (in_array($filter, $valid, true)) { $where = 'WHERE c.status_pendaftaran=?'; $params[] = $filter; }

$cntStmt = $pdo->prepare("SELECT COUNT(*) FROM clients c $where");
$cntStmt->execute($params);
$total = (int)$cntStmt->fetchColumn();

$sql = "SELECT c.*, u.email,
        (SELECT COUNT(*) FROM entitas e WHERE e.client_id=c.id) AS jml_santri
        FROM clients c JOIN users u ON c.user_id=u.id
        $where ORDER BY c.created_at DESC LIMIT $perPage OFFSET $offset";
$st = $pdo->prepare($sql);
$st->execute($params);
$clients = $st->fetchAll();

// Ambil entitas untuk detail modal
function entitasOfClient(PDO $pdo, int $cid): array {
    $s = $pdo->prepare('SELECT * FROM entitas WHERE client_id=? ORDER BY id ASC');
    $s->execute([$cid]);
    return $s->fetchAll();
}

$panelTitle = 'Data Client';
$activeMenu = 'clients';
require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="page-head">
    <h2>Data Client 🧑‍🤝‍🧑</h2>
    <p>Kelola pendaftaran client: setujui atau tolak permohonan.</p>
</div>

<form method="get" class="filter-bar">
    <label class="form-label" style="margin:0">Filter:</label>
    <select name="status" class="form-input" onchange="this.form.submit()">
        <option value="all" <?= $filter==='all'?'selected':'' ?>>Semua</option>
        <option value="pending"  <?= $filter==='pending'?'selected':'' ?>>Pending</option>
        <option value="approved" <?= $filter==='approved'?'selected':'' ?>>Disetujui</option>
        <option value="rejected" <?= $filter==='rejected'?'selected':'' ?>>Ditolak</option>
    </select>
</form>

<div class="panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Nama Client</th><th>Email</th><th>No. HP</th><th>Jml Santri</th><th>Status</th><th>Tgl Daftar</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if ($clients): foreach ($clients as $c): ?>
                <tr>
                    <td><?= e($c['nama_client']) ?></td>
                    <td><?= e($c['email']) ?></td>
                    <td><?= e($c['no_hp']) ?></td>
                    <td><?= (int)$c['jml_santri'] ?></td>
                    <td><?= getStatusBadge($c['status_pendaftaran']) ?></td>
                    <td><?= formatTanggal($c['created_at']) ?></td>
                    <td>
                        <div class="action-group">
                            <button class="btn btn-info btn-sm" data-modal-target="m-det-<?= $c['id'] ?>">👁️ Detail</button>
                            <?php if ($c['status_pendaftaran'] !== 'approved'): ?>
                                <button class="btn btn-success btn-sm" data-modal-target="m-app-<?= $c['id'] ?>">✅</button>
                            <?php endif; ?>
                            <?php if ($c['status_pendaftaran'] !== 'rejected'): ?>
                                <button class="btn btn-danger btn-sm" data-modal-target="m-rej-<?= $c['id'] ?>">❌</button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="7" class="empty-row">Tidak ada client untuk filter ini. 🧑</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?= pagination($total, $perPage, $page, $base.'manager/clients.php?status='.$filter) ?>
</div>

<!-- Modals -->
<?php foreach ($clients as $c): $ents = entitasOfClient($pdo, (int)$c['id']); ?>
    <!-- Detail -->
    <div class="modal-overlay" id="m-det-<?= $c['id'] ?>">
        <div class="modal modal-wide">
            <div class="modal-head"><h3>Detail Client: <?= e($c['nama_client']) ?></h3><button class="modal-close" data-modal-close>&times;</button></div>
            <p><strong>Email:</strong> <?= e($c['email']) ?> | <strong>HP:</strong> <?= e($c['no_hp']) ?></p>
            <p><strong>Alamat:</strong> <?= e($c['alamat']) ?: '-' ?></p>
            <h4 style="margin:14px 0 8px">Santri Didaftarkan (<?= count($ents) ?>)</h4>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Nama</th><th>Usia</th><th>JK</th><th>Hari</th><th>Jam</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if ($ents): foreach ($ents as $e): ?>
                        <tr><td><?= e($e['nama_entitas']) ?></td><td><?= e((string)$e['usia']) ?></td>
                            <td><?= $e['jenis_kelamin']==='P'?'P':'L' ?></td><td><?= e($e['jadwal_hari']) ?></td>
                            <td><?= $e['jadwal_jam']?e(substr($e['jadwal_jam'],0,5)):'-' ?></td><td><?= getStatusBadge($e['status_belajar']) ?></td></tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" class="empty-row">Belum ada santri.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Approve -->
    <div class="modal-overlay" id="m-app-<?= $c['id'] ?>">
        <div class="modal">
            <div class="modal-head"><h3>Setujui Client?</h3><button class="modal-close" data-modal-close>&times;</button></div>
            <p>Setujui pendaftaran <strong><?= e($c['nama_client']) ?></strong>?</p>
            <form method="post">
                <?= csrfField() ?>
                <input type="hidden" name="client_id" value="<?= $c['id'] ?>">
                <input type="hidden" name="action" value="approve">
                <label class="form-label">Catatan (opsional)</label>
                <textarea name="catatan_manager" class="form-input" rows="2"></textarea>
                <button type="submit" class="btn btn-success" style="margin-top:12px">✅ Setujui</button>
            </form>
        </div>
    </div>
    <!-- Reject -->
    <div class="modal-overlay" id="m-rej-<?= $c['id'] ?>">
        <div class="modal">
            <div class="modal-head"><h3>Tolak Client?</h3><button class="modal-close" data-modal-close>&times;</button></div>
            <p>Tolak pendaftaran <strong><?= e($c['nama_client']) ?></strong>? Beri alasan agar client tahu.</p>
            <form method="post">
                <?= csrfField() ?>
                <input type="hidden" name="client_id" value="<?= $c['id'] ?>">
                <input type="hidden" name="action" value="reject">
                <label class="form-label">Catatan / Alasan</label>
                <textarea name="catatan_manager" class="form-input" rows="2" required></textarea>
                <button type="submit" class="btn btn-danger" style="margin-top:12px">❌ Tolak</button>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
