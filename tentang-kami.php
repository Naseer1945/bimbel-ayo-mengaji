<?php
/* ============================================================
   tentang-kami.php — Profil DINAMIS
   Menampilkan SEMUA pengajar aktif + galeri dokumentasi dari DB.
   Fallback aman bila DB kosong.
   ============================================================ */
require_once __DIR__ . '/includes/functions.php';

$pengajarList = [];
$dokumentasi  = [];
try {
    // Urutan pengajar bisa diganti-ganti manager (urutan_tampil)
    $pengajarList = $pdo->query("SELECT * FROM pengajar WHERE status_aktif=1 ORDER BY urutan_tampil ASC, created_at ASC")->fetchAll();
    $dokumentasi  = $pdo->query("SELECT * FROM assets_galeri WHERE status_aktif=1 AND kategori='dokumentasi' ORDER BY urutan_tampil ASC")->fetchAll();
} catch (PDOException $e) {}

// Link YouTube "Nilai Kami" (dikelola manager, toggle oleh super admin)
$ytOn = fiturAktif('youtube');
$ytN = [];
for ($i = 1; $i <= 4; $i++) $ytN[$i] = getSetting("yt_nilai_$i", '');

$base       = baseUrl();
$pageTitle  = "Tentang Kami ✨ | Bimbel Ayo Mengaji";
$activePage = 'tentang';
require_once __DIR__ . '/includes/header.php';

/* ---- helper kecil: ubah teks ";"/baris jadi <li> ---- */
function toList(?string $text): string {
    if (empty($text)) return '';
    $items = preg_split('/[;\n]+/', $text);
    $out = '';
    foreach ($items as $it) {
        $it = trim($it);
        if ($it !== '') $out .= '<li>' . e($it) . '</li>';
    }
    return $out;
}
?>

    <!-- PAGE HEADER -->
    <section class="page-header">
        <div class="container">
            <p class="breadcrumb"><a href="<?= $base ?>index.php">🏠 Beranda</a> &nbsp;›&nbsp; Tentang Kami</p>
            <h1>Tentang <span class="text-gradient">Ayo Mengaji</span> ✨</h1>
            <p>Mengenal lebih dekat visi, misi, dan sosok di balik Bimbel Ayo Mengaji. 📖</p>
        </div>
    </section>

    <!-- VISI MISI -->
    <section class="section">
        <div class="container">
            <div class="section-header reveal">
                <span class="section-badge">Siapa Kami 🏡</span>
                <h2 class="section-title">Sarana Belajar <span>Al-Qur'an</span> yang Ramah 🥰</h2>
                <p class="section-desc">
                    Berdiri sejak tahun 2025, Bimbel Ayo Mengaji hadir sebagai sarana pembelajaran
                    Al-Qur'an yang mudah dipahami, menyenangkan, dan terjangkau bagi anak-anak,
                    remaja, maupun masyarakat umum di wilayah Cianjur. Guru privat datang langsung
                    ke rumah santri. 🚀
                </p>
            </div>
            <div class="visi-misi-grid">
                <div class="card vm-card jello-hover reveal">
                    <span class="vm-icon emoji-pop">🎯</span>
                    <h3>Visi Kami</h3>
                    <p>Menjadi bimbel pembelajaran Al-Qur'an yang terpercaya dan mampu melahirkan
                        generasi Qurani yang cinta Al-Qur'an, berakhlak mulia, serta fasih dalam
                        membaca kitab suci-Nya. ✨</p>
                </div>
                <div class="card vm-card jello-hover reveal">
                    <span class="vm-icon emoji-pop">🚀</span>
                    <h3>Misi Kami</h3>
                    <ul>
                        <li>Membantu peserta didik membaca Al-Qur'an dengan baik dan benar.</li>
                        <li>Mengajarkan tajwid dan makharijul huruf secara sistematis.</li>
                        <li>Menumbuhkan kecintaan terhadap Al-Qur'an sejak usia dini.</li>
                        <li>Menyediakan pembelajaran yang mudah, menyenangkan, dan berkualitas.</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- PROFIL PENGAJAR DINAMIS -->
    <section class="section pengajar" id="pengajar">
        <div class="container">
            <div class="section-header reveal">
                <span class="section-badge">Pengajar Kami 👨‍🏫</span>
                <h2 class="section-title">Profil <span>Pembimbing</span> 🌟</h2>
            </div>

            <?php if ($pengajarList): foreach ($pengajarList as $p): ?>
            <div class="pengajar-grid">
                <div class="pengajar-img-wrapper card reveal">
                    <img src="<?= $base . rawurlencode_path($p['foto_pengajar']) ?>" alt="Foto <?= e($p['nama_pengajar']) ?>" class="pengajar-img" loading="lazy"
                         onerror="this.src='<?= $base ?>assets/default-pengajar.png'">
                </div>
                <div class="pengajar-info reveal">
                    <h3><?= e($p['nama_pengajar']) ?></h3>
                    <p class="pengajar-role">Pengajar Al-Qur'an 🌟</p>
                    <?php if (!empty($p['pendidikan'])): ?>
                    <div class="detail-item">
                        <strong>🎓 Pendidikan</strong>
                        <ul><?= toList($p['pendidikan']) ?></ul>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($p['pengalaman'])): ?>
                    <div class="detail-item">
                        <strong>📖 Pengalaman Mengajar</strong>
                        <ul><?= toList($p['pengalaman']) ?></ul>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($p['bio'])): ?>
                    <p class="pengajar-quote">"<?= e($p['bio']) ?>" 💬</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; else: ?>
                <p class="text-center section-desc">Data pengajar belum tersedia. 📖</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- NILAI-NILAI -->
    <section class="section">
        <div class="container">
            <div class="section-header reveal">
                <span class="section-badge">Nilai Kami 💎</span>
                <h2 class="section-title">Yang Kami <span>Pegang Teguh</span> 🤝</h2>
            </div>
            <div class="grid-nilai">
                <?php
                // Kartu nilai: jika manager mengisi link YouTube (dan fitur aktif), kartu bisa diklik
                $kartuNilai = [
                    ['🥰', 'Ramah Anak',      'Suasana belajar yang hangat, sabar, dan menyenangkan.'],
                    ['📚', 'Bertahap',        'Materi disusun rapi dari nol agar mudah diikuti.'],
                    ['⏳', 'Fleksibel',       'Jadwal menyesuaikan kesibukan anak & orang tua.'],
                    ['🏠', 'Datang ke Rumah', 'Guru privat yang mendatangi lokasi santri.'],
                ];
                foreach ($kartuNilai as $idx => $kn):
                    $n = $idx + 1;
                    $pakaiYt = $ytOn && !empty($ytN[$n]);
                    if ($pakaiYt): ?>
                <a href="<?= e($ytN[$n]) ?>" target="_blank" rel="noopener noreferrer" class="card nilai-card jello-hover reveal">
                    <div class="nilai-icon emoji-pop"><?= $kn[0] ?></div><h3><?= e($kn[1]) ?></h3><p><?= e($kn[2]) ?></p>
                    <span class="keunggulan-yt">▶️ Tonton Video</span>
                </a>
                <?php else: ?>
                <div class="card nilai-card jello-hover reveal">
                    <div class="nilai-icon emoji-pop"><?= $kn[0] ?></div><h3><?= e($kn[1]) ?></h3><p><?= e($kn[2]) ?></p>
                </div>
                <?php endif; endforeach; ?>
            </div>
        </div>
    </section>

    <!-- GALERI DINAMIS -->
    <section class="section galeri">
        <div class="container">
            <div class="section-header reveal">
                <span class="section-badge">Dokumentasi 📸</span>
                <h2 class="section-title">Suasana <span>Belajar Kami</span> ✨</h2>
                <p class="section-desc">Intip ketenangan dan keseruan suasana mengaji bersama Bimbel Ayo Mengaji.</p>
            </div>
            <div class="galeri-grid">
                <?php
                $galeriTampil = $dokumentasi ?: [
                    ['path_file'=>'assets/WhatsApp Image 2026-06-12 at 10.34.10.jpeg','nama_file'=>'Belajar mengaji yang khusyuk'],
                    ['path_file'=>'assets/WhatsApp Image 2026-06-12 at 10.34.48.jpeg','nama_file'=>'Pembelajaran tatap muka, ramah anak'],
                ];
                foreach ($galeriTampil as $g): ?>
                    <div class="galeri-item card reveal">
                        <img src="<?= $base . rawurlencode_path($g['path_file']) ?>" alt="<?= e($g['nama_file']) ?>" class="galeri-img" loading="lazy">
                        <div class="galeri-caption"><?= e($g['nama_file']) ?> 📖</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
