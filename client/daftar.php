<?php
/* ============================================================
   client/daftar.php — Form daftar MULTIPLE entitas (dinamis)
   Update data client + insert banyak entitas sekaligus.
   ============================================================ */
require_once __DIR__ . '/../includes/functions.php';
cekRole(['client']);

$uid  = (int)$_SESSION['user_id'];
$base = baseUrl();

// Pastikan client row ada
$stmt = $pdo->prepare('SELECT * FROM clients WHERE user_id = ? LIMIT 1');
$stmt->execute([$uid]);
$client = $stmt->fetch();
if (!$client) {
    // Buat jika belum ada (mis. akun lama)
    $u = currentUser();
    $pdo->prepare('INSERT INTO clients (user_id, nama_client, no_hp, alamat, jumlah_entitas) VALUES (?,?,?,?,0)')
        ->execute([$uid, $u['nama_lengkap'], $u['no_hp'], $u['alamat']]);
    $stmt->execute([$uid]);
    $client = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $namaClient = trim($_POST['nama_client'] ?? '');
    $noHp       = trim($_POST['no_hp'] ?? '');
    $alamat     = trim($_POST['alamat'] ?? '');
    $names      = $_POST['nama_entitas'] ?? [];
    $usias      = $_POST['usia'] ?? [];
    $jks        = $_POST['jenis_kelamin'] ?? [];
    $haris      = $_POST['jadwal_hari'] ?? [];
    $jams       = $_POST['jadwal_jam'] ?? [];

    $validRows = [];
    foreach ($names as $i => $nm) {
        $nm = trim($nm);
        if ($nm !== '') {
            $validRows[] = [
                'nama' => $nm,
                'usia' => (int)($usias[$i] ?? 0) ?: null,
                'jk'   => in_array(($jks[$i] ?? ''), ['L','P'], true) ? $jks[$i] : null,
                'hari' => trim($haris[$i] ?? ''),
                'jam'  => !empty($jams[$i]) ? $jams[$i] : null,
            ];
        }
    }

    if ($namaClient === '') {
        flashMessage('error', 'Nama pendaftar wajib diisi.');
    } elseif (empty($validRows)) {
        flashMessage('error', 'Tambahkan minimal satu data santri.');
    } else {
        try {
            $pdo->beginTransaction();
            // Update info client + set pending lagi (pendaftaran baru perlu approval)
            $pdo->prepare('UPDATE clients SET nama_client=?, no_hp=?, alamat=?, status_pendaftaran="pending" WHERE id=?')
                ->execute([$namaClient, $noHp, $alamat, $client['id']]);

            $insE = $pdo->prepare('INSERT INTO entitas (client_id, nama_entitas, usia, jenis_kelamin, jadwal_hari, jadwal_jam, status_belajar)
                                   VALUES (?,?,?,?,?,?,"baru")');
            foreach ($validRows as $r) {
                $insE->execute([$client['id'], $r['nama'], $r['usia'], $r['jk'], $r['hari'], $r['jam']]);
            }
            // Update jumlah_entitas (total)
            $cnt = $pdo->prepare('SELECT COUNT(*) FROM entitas WHERE client_id=?');
            $cnt->execute([$client['id']]);
            $pdo->prepare('UPDATE clients SET jumlah_entitas=? WHERE id=?')
                ->execute([(int)$cnt->fetchColumn(), $client['id']]);
            $pdo->commit();

            logAktivitas($uid, 'client', 'daftar_entitas', count($validRows) . ' santri didaftarkan');

            // 🔔 Notifikasi ke SEMUA manager: ada pendaftaran baru yang perlu di-approve
            foreach ($validRows as $r) {
                notifikasiUntukRole('manager',
                    "$namaClient meminta anda untuk melakukan penerimaan pelajar atas nama {$r['nama']} 📝",
                    'manager/clients.php');
            }

            flashMessage('success', count($validRows) . ' santri berhasil didaftarkan! Menunggu persetujuan manager. 🎉');
            redirect($base . 'client/status.php');
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flashMessage('error', 'Gagal menyimpan pendaftaran. Coba lagi.');
        }
    }
}

$panelTitle = 'Daftarkan Santri';
$activeMenu = 'daftar';
require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="page-head">
    <h2>Form Pendaftaran Santri 📝</h2>
    <p>Isi data pendaftar lalu tambahkan satu atau beberapa santri sekaligus.</p>
</div>

<form method="post" class="panel">
    <?= csrfField() ?>
    <h3 class="panel-title">Data Pendaftar 🧑</h3>
    <div class="form-row">
        <div class="form-col">
            <label class="form-label">Nama Pendaftar / Orang Tua</label>
            <input type="text" name="nama_client" class="form-input" required value="<?= e($client['nama_client']) ?>">
        </div>
        <div class="form-col">
            <label class="form-label">No. WhatsApp</label>
            <input type="text" name="no_hp" class="form-input" value="<?= e($client['no_hp']) ?>">
        </div>
    </div>
    <label class="form-label">Alamat (untuk guru datang ke rumah)</label>
    <textarea name="alamat" class="form-input" rows="2"><?= e($client['alamat']) ?></textarea>

    <h3 class="panel-title" style="margin-top:24px">Data Santri 🧒</h3>
    <p class="form-hint" style="margin-bottom:12px">Klik "Tambah Santri" untuk mendaftarkan lebih dari satu anak.</p>

    <div id="entitas-wrap">
        <div class="entitas-row">
            <span class="remove-row" title="Hapus baris">✕</span>
            <div class="form-col"><label class="form-label">Nama Santri</label>
                <input type="text" name="nama_entitas[]" class="form-input" required></div>
            <div class="form-col"><label class="form-label">Usia</label>
                <input type="number" name="usia[]" class="form-input" min="4" max="25"></div>
            <div class="form-col"><label class="form-label">Jenis Kelamin</label>
                <select name="jenis_kelamin[]" class="form-input"><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></div>
            <div class="form-col"><label class="form-label">Hari</label>
                <input type="text" name="jadwal_hari[]" class="form-input" placeholder="Senin, Rabu"></div>
            <div class="form-col"><label class="form-label">Jam</label>
                <input type="time" name="jadwal_jam[]" class="form-input"></div>
        </div>
    </div>

    <div class="page-actions">
        <button type="button" id="add-entitas" class="btn btn-info btn-sm">➕ Tambah Santri</button>
    </div>

    <button type="submit" class="btn btn-primary btn-lg" style="margin-top:10px">Kirim Pendaftaran 🚀</button>
</form>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
