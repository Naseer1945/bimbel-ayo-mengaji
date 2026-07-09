<?php
/* ============================================================
   admin/log.php — Tabel log aktivitas + filter & pagination
   ============================================================ */
require_once __DIR__ . '/../includes/functions.php';
cekRole(['super_admin']);
$base = baseUrl();

$fr=$_GET['role']??''; $fj=$_GET['jenis']??''; $fd1=$_GET['dari']??''; $fd2=$_GET['sampai']??'';
$page=max(1,(int)($_GET['page']??1)); $perPage=20; $offset=($page-1)*$perPage;

$cond=[]; $params=[];
if ($fr!==''){ $cond[]='l.role=?'; $params[]=$fr; }
if ($fj!==''){ $cond[]='l.jenis_aktivitas LIKE ?'; $params[]='%'.$fj.'%'; }
if ($fd1!==''){ $cond[]='DATE(l.created_at)>=?'; $params[]=$fd1; }
if ($fd2!==''){ $cond[]='DATE(l.created_at)<=?'; $params[]=$fd2; }
$where=$cond?('WHERE '.implode(' AND ',$cond)):'';

$cnt=$pdo->prepare("SELECT COUNT(*) FROM log_aktivitas l $where"); $cnt->execute($params); $total=(int)$cnt->fetchColumn();
$st=$pdo->prepare("SELECT l.*, u.username FROM log_aktivitas l LEFT JOIN users u ON l.user_id=u.id
                   $where ORDER BY l.created_at DESC LIMIT $perPage OFFSET $offset");
$st->execute($params); $logs=$st->fetchAll();

// daftar jenis untuk filter
$jenisOpts = $pdo->query("SELECT DISTINCT jenis_aktivitas FROM log_aktivitas ORDER BY jenis_aktivitas")->fetchAll(PDO::FETCH_COLUMN);

$panelTitle='Log Aktivitas'; $activeMenu='log';
require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="page-head">
    <h2>Log Aktivitas Sistem 📜</h2>
    <p>Audit trail seluruh aktivitas pengguna pada webapp.</p>
</div>

<form method="get" class="filter-bar">
    <select name="role" class="form-input">
        <option value="">Semua Role</option>
        <?php foreach (['client','manager','pengajar','super_admin'] as $r): ?><option value="<?= $r ?>" <?= $fr===$r?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$r)) ?></option><?php endforeach; ?>
    </select>
    <select name="jenis" class="form-input">
        <option value="">Semua Jenis</option>
        <?php foreach ($jenisOpts as $j): ?><option value="<?= e($j) ?>" <?= $fj===$j?'selected':'' ?>><?= e($j) ?></option><?php endforeach; ?>
    </select>
    <input type="date" name="dari" class="form-input" value="<?= e($fd1) ?>" title="Dari tanggal">
    <input type="date" name="sampai" class="form-input" value="<?= e($fd2) ?>" title="Sampai tanggal">
    <button class="btn btn-primary btn-sm">🔍 Filter</button>
    <a href="<?= $base ?>admin/log.php" class="btn btn-muted btn-sm">Reset</a>
</form>

<div class="panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Waktu</th><th>User</th><th>Role</th><th>Jenis</th><th>Detail</th><th>IP</th></tr></thead>
            <tbody>
            <?php if ($logs): foreach ($logs as $l): ?>
                <tr>
                    <td><?= e(date('d/m/Y H:i', strtotime($l['created_at']))) ?></td>
                    <td><?= $l['username']?e($l['username']):'<em>—</em>' ?></td>
                    <td><span class="badge badge-info"><?= e($l['role']??'-') ?></span></td>
                    <td><?= e($l['jenis_aktivitas']) ?></td>
                    <td><?= e($l['detail']) ?></td>
                    <td><small><?= e($l['ip_address']) ?></small></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="6" class="empty-row">Tidak ada log untuk filter ini. 📜</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?= pagination($total,$perPage,$page,$base.'admin/log.php?role='.urlencode($fr).'&jenis='.urlencode($fj).'&dari='.urlencode($fd1).'&sampai='.urlencode($fd2)) ?>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
