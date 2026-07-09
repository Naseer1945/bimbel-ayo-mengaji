<?php
/* ============================================================
   manager/youtube.php — Kelola link video YouTube (manager)
   Link keunggulan (5), nilai kami (4), dan channel.
   Videonya dilihat semua pengunjung; hanya manager yang bisa
   mengubah link. Super admin bisa menonaktifkan fitur ini
   lewat Pengaturan & Fitur.
   ============================================================ */
require_once __DIR__ . '/../includes/functions.php';
cekRole(['manager']);
$base = baseUrl();
$mid  = (int)$_SESSION['user_id'];

// Definisi slot link yang bisa dikelola
$slots = [
    'yt_channel'      => ['Channel YouTube', '📺'],
    'yt_keunggulan_1' => ['Keunggulan 1 — Materi Bertahap', '📚'],
    'yt_keunggulan_2' => ['Keunggulan 2 — Fokus Praktik', '🎯'],
    'yt_keunggulan_3' => ['Keunggulan 3 — Ramah Anak', '🥰'],
    'yt_keunggulan_4' => ['Keunggulan 4 — Datang ke Rumah', '🏠'],
    'yt_keunggulan_5' => ['Keunggulan 5 — Jadwal Fleksibel', '⏳'],
    'yt_nilai_1'      => ['Nilai Kami 1 — Ramah Anak', '🥰'],
    'yt_nilai_2'      => ['Nilai Kami 2 — Bertahap', '📚'],
    'yt_nilai_3'      => ['Nilai Kami 3 — Fleksibel', '⏳'],
    'yt_nilai_4'      => ['Nilai Kami 4 — Datang ke Rumah', '🏠'],
];

/* Validasi sederhana: harus URL youtube (atau kosong untuk nilai kami) */
function isYoutubeUrl(string $url): bool {
    return (bool)preg_match('~^https?://(www\.)?(youtube\.com|youtu\.be)/~i', $url);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $errors = [];
    $up = $pdo->prepare('INSERT INTO pengaturan (nama_key,nilai) VALUES (?,?) ON DUPLICATE KEY UPDATE nilai=VALUES(nilai)');
    foreach (array_keys($slots) as $key) {
        $val = trim($_POST[$key] ?? '');
        if ($val !== '' && !isYoutubeUrl($val)) {
            $errors[] = $slots[$key][0] . ': bukan link YouTube yang valid.';
            continue;
        }
        // Channel & keunggulan tidak boleh dikosongkan total (fallback tetap dibutuhkan)
        $up->execute([$key, $val]);
    }
    if ($errors) {
        flashMessage('error', implode(' ', $errors));
    } else {
        logAktivitas($mid, 'manager', 'update_youtube', 'Memperbarui link YouTube web');
        flashMessage('success', 'Link YouTube berhasil diperbarui! ▶️');
    }
    redirect($base . 'manager/youtube.php');
}

// Baca nilai terkini langsung dari DB (hindari cache getSetting)
$cfg = [];
foreach ($pdo->query("SELECT nama_key,nilai FROM pengaturan WHERE nama_key LIKE 'yt_%'") as $r) $cfg[$r['nama_key']] = $r['nilai'];

$panelTitle = 'Link YouTube';
$activeMenu = 'youtube';
require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="page-head">
    <h2>Kelola Link YouTube ▶️</h2>
    <p>Perbarui link video kapan saja — kartu di beranda &amp; halaman Tentang Kami otomatis memakai link terbaru.</p>
    <?php if (!fiturAktif('youtube')): ?>
        <p><span class="badge badge-warning">⚠️ Fitur YouTube sedang DINONAKTIFKAN oleh Super Admin — link tidak tampil di web publik.</span></p>
    <?php endif; ?>
</div>

<form method="post" class="panel">
    <?= csrfField() ?>
    <h3 class="panel-title">Channel 📺</h3>
    <label class="form-label"><?= $slots['yt_channel'][1] ?> <?= e($slots['yt_channel'][0]) ?></label>
    <input type="url" name="yt_channel" class="form-input" value="<?= e($cfg['yt_channel'] ?? '') ?>" placeholder="https://www.youtube.com/@NamaChannel">

    <h3 class="panel-title" style="margin-top:24px">Video Kartu Keunggulan (Beranda) ⭐</h3>
    <p class="form-hint" style="margin-bottom:8px">Video pendek yang terbuka saat pengunjung mengeklik kartu keunggulan.</p>
    <?php for ($i=1; $i<=5; $i++): $k="yt_keunggulan_$i"; ?>
        <label class="form-label"><?= $slots[$k][1] ?> <?= e($slots[$k][0]) ?></label>
        <input type="url" name="<?= $k ?>" class="form-input" value="<?= e($cfg[$k] ?? '') ?>" placeholder="https://youtube.com/shorts/...">
    <?php endfor; ?>

    <h3 class="panel-title" style="margin-top:24px">Video Kartu Nilai Kami (Tentang Kami) 💎</h3>
    <p class="form-hint" style="margin-bottom:8px">Opsional — kosongkan jika kartu tidak perlu video. Jika diisi, kartu "Nilai Kami" jadi bisa diklik.</p>
    <?php for ($i=1; $i<=4; $i++): $k="yt_nilai_$i"; ?>
        <label class="form-label"><?= $slots[$k][1] ?> <?= e($slots[$k][0]) ?></label>
        <input type="url" name="<?= $k ?>" class="form-input" value="<?= e($cfg[$k] ?? '') ?>" placeholder="(kosongkan jika tidak ada video)">
    <?php endfor; ?>

    <button type="submit" class="btn btn-primary btn-lg" style="margin-top:18px">Simpan Semua Link 💾</button>
</form>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
