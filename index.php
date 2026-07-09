<?php
/* ============================================================
   index.php — Landing page DINAMIS
   Mengambil: pengajar aktif, foto dokumentasi (assets_galeri),
   dan statistik (view_dashboard_stats). Fallback ke konten
   statis bila DB kosong/error. Desain existing dipertahankan.
   ============================================================ */
require_once __DIR__ . '/includes/functions.php';

// --- Ambil data dinamis (dengan fallback aman) ---
$pengajarList = [];
$dokumentasi  = [];
$stats        = ['total_client'=>0,'total_pengajar'=>1,'total_santri_aktif'=>0,'total_santri_baru'=>0];
$promoList = [];
try {
    $pengajarList = $pdo->query("SELECT * FROM pengajar WHERE status_aktif=1 ORDER BY urutan_tampil ASC, created_at ASC")->fetchAll();
    $dokumentasi  = $pdo->query("SELECT * FROM assets_galeri WHERE status_aktif=1 AND kategori='dokumentasi' ORDER BY urutan_tampil ASC")->fetchAll();
    $row = $pdo->query("SELECT * FROM view_dashboard_stats")->fetch();
    if ($row) $stats = $row;
    // Promo aktif & dalam rentang tanggal (jika fitur promosi menyala)
    if (fiturAktif('promosi')) {
        $promoList = $pdo->query("SELECT * FROM promosi WHERE status_aktif=1
            AND (tanggal_mulai IS NULL OR tanggal_mulai <= CURDATE())
            AND (tanggal_selesai IS NULL OR tanggal_selesai >= CURDATE())
            ORDER BY created_at DESC LIMIT 6")->fetchAll();
    }
} catch (PDOException $e) { /* fallback statis di bawah */ }

// Link YouTube dinamis (dikelola manager) + status fitur (dikelola super admin)
$ytOn = fiturAktif('youtube');
$ytChannel = getSetting('yt_channel', 'https://www.youtube.com/@BimbelAyoMengaji');
$ytK = [];
for ($i = 1; $i <= 5; $i++) $ytK[$i] = getSetting("yt_keunggulan_$i", '');

$base       = baseUrl();
$pageTitle  = "Bimbel Ayo Mengaji 📖✨ | Belajar Al-Qur'an Mudah & Menyenangkan";
$activePage = 'index';
require_once __DIR__ . '/includes/header.php';
?>

    <!-- HERO -->
    <section class="hero" id="hero">
        <div class="container hero-content">
            <span class="hero-tag">📖 Belajar Al-Qur'an Sejak Dini ✨</span>
            <h1 class="hero-title">
                Bimbel <span class="text-gradient">Ayo Mengaji</span>
                <span class="emoji-pop">📖</span>
            </h1>
            <p class="hero-subtitle">
                Belajar Mengaji <strong>Mudah</strong> 🎯, <strong>Menyenangkan</strong> 🥰, dan <strong>Fleksibel</strong> ⏳
            </p>
            <p class="hero-desc">
                Khusus untuk Anak-anak &amp; Remaja (Usia 6–17 Tahun). Guru privat datang langsung ke rumah,
                menghadirkan suasana belajar Al-Qur'an yang ramah, interaktif, dan penuh keberkahan! 🚀
            </p>
            <div class="hero-buttons">
                <a href="<?= $base ?>register.php" class="btn btn-primary btn-lg">Daftar Sekarang 🚀</a>
                <a href="<?= $base ?>slide-keunggulan.php" class="btn btn-outline btn-lg">Lihat Keunggulan ⭐</a>
            </div>

            <div class="hero-stats">
                <div class="stat-item"><span class="stat-num">6–17</span><span class="stat-label">Usia Santri 🧒</span></div>
                <div class="stat-item"><span class="stat-num"><?= (int)$stats['total_pengajar'] ?></span><span class="stat-label">Pengajar 👨‍🏫</span></div>
                <div class="stat-item"><span class="stat-num"><?= (int)$stats['total_santri_aktif'] ?></span><span class="stat-label">Santri Aktif 📈</span></div>
                <div class="stat-item"><span class="stat-num">100%</span><span class="stat-label">Datang ke Rumah 🏠</span></div>
            </div>
        </div>
        <div class="hero-wave" aria-hidden="true">
            <svg viewBox="0 0 1440 100" preserveAspectRatio="none"><path d="M0,40 C360,100 1080,0 1440,50 L1440,100 L0,100 Z"></path></svg>
        </div>
    </section>

    <?php if ($promoList): ?>
    <!-- PROMOSI & EVENT (dikelola super admin, toggle via Pengaturan & Fitur) -->
    <section class="promo-strip" id="promo">
        <div class="container">
            <div class="section-header reveal" style="margin-bottom:2rem">
                <span class="section-badge">Promo & Event 🎉</span>
                <h2 class="section-title">Jangan Sampai <span>Terlewat</span>! ✨</h2>
            </div>
            <div class="grid-promo">
                <?php foreach ($promoList as $pr): ?>
                <div class="promo-card reveal">
                    <span class="promo-label"><?= e($pr['label']) ?></span>
                    <h3><?= e($pr['judul']) ?></h3>
                    <?php if ($pr['deskripsi']): ?><p><?= e($pr['deskripsi']) ?></p><?php endif; ?>
                    <?php if ($pr['tanggal_selesai']): ?>
                        <span class="promo-tanggal">⏰ Berlaku sampai <?= formatTanggal($pr['tanggal_selesai']) ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- PROGRAM UTAMA -->
    <section class="section program-utama" id="program">
        <div class="container">
            <div class="section-header reveal">
                <span class="section-badge">Program Utama 📚</span>
                <h2 class="section-title">Materi <span>Inti</span> Pembelajaran 🎯</h2>
                <p class="section-desc">Dua fondasi penting agar santri dapat membaca Al-Qur'an dengan benar, jelas, dan fasih.</p>
            </div>
            <div class="grid-program-utama">
                <article class="card program-main-card jello-hover reveal">
                    <div class="program-main-icon emoji-pop">🗣️</div>
                    <h3>Belajar Makharijul Huruf</h3>
                    <p class="program-main-sub">Tempat Keluarnya Huruf</p>
                    <p class="program-main-desc">
                        Belajar mengucapkan setiap huruf hijaiyah dari tempat keluarnya yang benar agar
                        pelafalan tidak tertukar (misalnya <strong>ذ</strong> dengan <strong>ز</strong>).
                        Fondasi utama bacaan yang jelas dan fasih.
                    </p>
                    <ul class="program-main-list">
                        <li>✅ Pengenalan 29 huruf hijaiyah</li>
                        <li>✅ Latihan pelafalan dari makhraj</li>
                        <li>✅ Koreksi langsung oleh pengajar</li>
                    </ul>
                    <a href="<?= $base ?>register.php" class="btn btn-primary btn-block">Pilih Program Ini 🚀</a>
                </article>
                <article class="card program-main-card jello-hover reveal">
                    <div class="program-main-icon emoji-pop">📖</div>
                    <h3>Ilmu Tajwid</h3>
                    <p class="program-main-sub">Kaidah Membaca Al-Qur'an</p>
                    <p class="program-main-desc">
                        Mempelajari hukum-hukum bacaan agar Al-Qur'an dibaca sesuai kaidah yang benar,
                        mulai dari Nun Mati &amp; Tanwin, Mim Mati, hingga hukum Mad (bacaan panjang).
                    </p>
                    <ul class="program-main-list">
                        <li>✅ Ikhfa, Idgham, Izhar, Iqlab</li>
                        <li>✅ Hukum Mim Mati &amp; Mad</li>
                        <li>✅ Praktik langsung pada ayat</li>
                    </ul>
                    <a href="<?= $base ?>register.php" class="btn btn-primary btn-block">Pilih Program Ini 📖</a>
                </article>
            </div>
        </div>
    </section>

    <!-- PENGAJAR DINAMIS -->
    <section class="section" id="pengajar-home">
        <div class="container">
            <div class="section-header reveal">
                <span class="section-badge">Pengajar Kami 👨‍🏫</span>
                <h2 class="section-title">Dibimbing <span>Pengajar Berpengalaman</span> 🌟</h2>
                <p class="section-desc">Guru-guru kami siap mendampingi perjalanan mengaji putra-putri Anda.</p>
            </div>
            <div class="grid-pengajar-home">
                <?php if ($pengajarList): foreach ($pengajarList as $p): ?>
                    <article class="card pengajar-home-card jello-hover reveal">
                        <img src="<?= $base . rawurlencode_path($p['foto_pengajar']) ?>" alt="<?= e($p['nama_pengajar']) ?>" class="pengajar-home-img" loading="lazy"
                             onerror="this.src='<?= $base ?>assets/default-pengajar.png'">
                        <h3><?= e($p['nama_pengajar']) ?></h3>
                        <p class="pengajar-home-edu"><?= e(mb_strimwidth($p['pendidikan'] ?? '', 0, 90, '…')) ?></p>
                        <a href="<?= $base ?>tentang-kami.php#pengajar" class="btn btn-outline">Lihat Profil ✨</a>
                    </article>
                <?php endforeach; else: ?>
                    <p class="text-center section-desc">Profil pengajar segera hadir. 📖</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- KEUNGGULAN -->
    <section class="section keunggulan" id="keunggulan">
        <div class="container">
            <div class="section-header reveal">
                <span class="section-badge">Keunggulan Sistem ⭐</span>
                <h2 class="section-title">Kenapa Memilih <span>Ayo Mengaji</span>? 🥰</h2>
                <p class="section-desc">Lima keunggulan sistem belajar kami yang membuat anak cepat bisa &amp; betah mengaji.</p>
            </div>
            <div class="grid-keunggulan">
                <?php
                // Kartu keunggulan: link video dikelola manager, tampil jika fitur YouTube aktif
                $kartuKeunggulan = [
                    ['📚', 'Materi Bertahap',  'Disusun dari nol, mudah diikuti pemula sekalipun.'],
                    ['🎯', 'Fokus Praktik',    'Lebih banyak praktik membaca, langsung terasa hasilnya.'],
                    ['🥰', 'Ramah Anak',       'Pendekatan menyenangkan, belajar sambil tersenyum.'],
                    ['🏠', 'Datang ke Rumah',  'Guru privat yang datang, anak belajar dengan nyaman.'],
                    ['⏳', 'Jadwal Fleksibel', 'Waktu belajar menyesuaikan kesibukan anak & orang tua.'],
                ];
                foreach ($kartuKeunggulan as $idx => $kk):
                    $n = $idx + 1;
                    $pakaiYt = $ytOn && !empty($ytK[$n]);
                    $href    = $pakaiYt ? $ytK[$n] : $base . 'slide-keunggulan.php';
                    $target  = $pakaiYt ? ' target="_blank" rel="noopener noreferrer"' : '';
                ?>
                <a href="<?= e($href) ?>"<?= $target ?> class="card keunggulan-card jello-hover reveal">
                    <div class="keunggulan-icon emoji-pop"><?= $kk[0] ?></div><h3><?= e($kk[1]) ?></h3><p><?= e($kk[2]) ?></p>
                    <?php if ($pakaiYt): ?><span class="keunggulan-yt">▶️ Tonton Video</span><?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
            <div class="section-cta">
                <a href="<?= $base ?>slide-keunggulan.php" class="btn btn-secondary btn-lg">Jelajahi Journey Belajar Interaktif ✨🚀</a>
                <?php if ($ytOn && $ytChannel): ?>
                <a href="<?= e($ytChannel) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-lg" style="margin-left:10px">▶️ Kunjungi Channel YouTube</a>
                <p class="section-cta-hint">Klik tiap kartu di atas untuk menonton video penjelasannya di YouTube 🎬</p>
                <?php else: ?>
                <p class="section-cta-hint">Lihat tingkatan belajar dalam slider interaktif</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- DOKUMENTASI DINAMIS (rolling sesuai urutan upload) -->
    <section class="section dokumentasi" id="dokumentasi">
        <div class="container">
            <?php $docFirst = $dokumentasi[0]['path_file'] ?? 'assets/WhatsApp Image 2026-06-12 at 10.34.10.jpeg'; ?>
            <div class="doc-showcase">
                <div class="doc-img-wrap card reveal">
                    <img src="<?= $base . rawurlencode_path($docFirst) ?>" alt="Dokumentasi suasana belajar mengaji" class="doc-img" loading="lazy">
                </div>
                <div class="doc-text reveal">
                    <span class="section-badge">Dokumentasi 📸</span>
                    <h2 class="section-title">Suasana <span>Belajar Nyata</span> 🥰</h2>
                    <p class="section-desc" style="margin:0 0 1.4rem;">
                        Belajar Al-Qur'an dengan tenang, fokus, dan penuh kehangatan. Guru privat kami
                        mendampingi langsung setiap santri agar bacaannya semakin lancar dan benar. 📖✨
                    </p>
                    <ul class="doc-list">
                        <li>✅ Pendampingan satu per satu</li>
                        <li>✅ Suasana akrab &amp; menyenangkan</li>
                        <li>✅ Belajar langsung di rumah santri</li>
                    </ul>
                    <a href="<?= $base ?>register.php" class="btn btn-primary btn-lg">Gabung Sekarang 🚀</a>
                </div>
            </div>

            <?php if (count($dokumentasi) > 1): ?>
            <!-- Galeri rolling: semua foto dokumentasi aktif sesuai urutan -->
            <div class="galeri-grid" style="margin-top:2.5rem">
                <?php foreach ($dokumentasi as $d): ?>
                    <div class="galeri-item card reveal">
                        <img src="<?= $base . rawurlencode_path($d['path_file']) ?>" alt="<?= e($d['nama_file']) ?>" class="galeri-img" loading="lazy">
                        <div class="galeri-caption"><?= e($d['nama_file']) ?> 📖</div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
