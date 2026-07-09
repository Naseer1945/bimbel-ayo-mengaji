<?php
/* ============================================================
   slide-keunggulan.php — Journey slider (statis, 5 tahap)
   Memakai header/footer global agar navbar konsisten & dinamis.
   ============================================================ */
require_once __DIR__ . '/includes/functions.php';
$base       = baseUrl();
$pageTitle  = "Journey Belajar ⭐ | Bimbel Ayo Mengaji";
$activePage = 'keunggulan';
require_once __DIR__ . '/includes/header.php';
?>

    <!-- PAGE HEADER -->
    <section class="page-header">
        <div class="container">
            <p class="breadcrumb"><a href="<?= $base ?>index.php">🏠 Beranda</a> &nbsp;›&nbsp; Keunggulan & Journey</p>
            <h1>Journey <span class="text-gradient">Belajar</span> Mengaji ⭐</h1>
            <p>Ikuti tahapan belajar dari nol hingga lancar membaca Al-Qur'an. Geser slider untuk menjelajah! 🚀</p>
        </div>
    </section>

    <!-- SLIDER JOURNEY -->
    <section class="journey">
        <div class="container">
            <div class="journey-progress"><div class="journey-progress-bar" id="journey-progress-bar"></div></div>
            <div class="slider" id="journey-slider">
                <div class="slider-track">
                    <div class="slide">
                        <span class="slide-step">Tahap 1 dari 5</span><span class="slide-emoji">📚</span>
                        <h3>Mengenal Huruf Hijaiyah</h3>
                        <p>Langkah pertama: mengenal 29 huruf hijaiyah beserta bentuk awal, tengah, dan akhirnya. Fondasi agar anak siap membaca.</p>
                        <div class="slide-tags"><span class="slide-tag">🔤 29 Huruf</span><span class="slide-tag">👶 Untuk Pemula</span><span class="slide-tag">🥰 Ramah Anak</span></div>
                    </div>
                    <div class="slide">
                        <span class="slide-step">Tahap 2 dari 5</span><span class="slide-emoji">🗣️</span>
                        <h3>Belajar Makharijul Huruf</h3>
                        <p>Belajar tempat keluarnya huruf agar pelafalan benar dan tidak tertukar. Bacaan jadi jelas dan fasih.</p>
                        <div class="slide-tags"><span class="slide-tag">🎯 Pelafalan Tepat</span><span class="slide-tag">👂 Latihan Dengar</span><span class="slide-tag">✅ Koreksi Langsung</span></div>
                    </div>
                    <div class="slide">
                        <span class="slide-step">Tahap 3 dari 5</span><span class="slide-emoji">📖</span>
                        <h3>Iqra &amp; Tahsin</h3>
                        <p>Mulai merangkai huruf menjadi bacaan dan memperindah bacaan Al-Qur'an secara bertahap dan menyenangkan.</p>
                        <div class="slide-tags"><span class="slide-tag">📕 Metode Iqra</span><span class="slide-tag">✨ Tahsin</span><span class="slide-tag">📈 Bertahap</span></div>
                    </div>
                    <div class="slide">
                        <span class="slide-step">Tahap 4 dari 5</span><span class="slide-emoji">📜</span>
                        <h3>Ilmu Tajwid</h3>
                        <p>Memahami hukum bacaan: Nun Mati &amp; Tanwin, Mim Mati, hingga Mad, langsung dipraktikkan pada ayat.</p>
                        <div class="slide-tags"><span class="slide-tag">📏 Hukum Bacaan</span><span class="slide-tag">🧠 Praktik Ayat</span><span class="slide-tag">🎓 Sistematis</span></div>
                    </div>
                    <div class="slide">
                        <span class="slide-step">Tahap 5 dari 5</span><span class="slide-emoji">🤲</span>
                        <h3>Hafalan &amp; Generasi Qurani</h3>
                        <p>Menghafal surat pendek &amp; doa harian, membentuk akhlak Qurani. Selamat, santri siap melangkah lebih jauh! 🌟</p>
                        <div class="slide-tags"><span class="slide-tag">🧠 Hafalan Juz 30</span><span class="slide-tag">🤲 Doa Harian</span><span class="slide-tag">🌟 Generasi Qurani</span></div>
                    </div>
                </div>
                <button class="slider-btn prev" aria-label="Sebelumnya">‹</button>
                <button class="slider-btn next" aria-label="Berikutnya">›</button>
            </div>
            <div class="slider-dots" id="slider-dots"></div>
            <div class="slider-footer">
                <span class="slider-counter">Langkah <span id="slide-current">1</span> dari <span id="slide-total">5</span></span>
                <button class="autoplay-toggle" id="autoplay-toggle">⏸️ Jeda Otomatis</button>
            </div>
        </div>
    </section>

    <!-- KEUNGGULAN PENDUKUNG -->
    <section class="section keunggulan">
        <div class="container">
            <div class="section-header reveal">
                <span class="section-badge">Kenapa Kami ⭐</span>
                <h2 class="section-title">Keunggulan di Setiap <span>Tahapan</span> 🥰</h2>
            </div>
            <div class="grid-keunggulan">
                <div class="card keunggulan-card jello-hover reveal"><div class="keunggulan-icon emoji-pop">📚</div><h3>Kurikulum Bertahap</h3><p>Setiap tahap tersusun rapi & saling menyambung.</p></div>
                <div class="card keunggulan-card jello-hover reveal"><div class="keunggulan-icon emoji-pop">🎯</div><h3>Target Jelas</h3><p>Anak tahu sedang di tahap mana & tujuannya.</p></div>
                <div class="card keunggulan-card jello-hover reveal"><div class="keunggulan-icon emoji-pop">📊</div><h3>Evaluasi Tiap Tahap</h3><p>Kemajuan dipantau di setiap level belajar.</p></div>
                <div class="card keunggulan-card jello-hover reveal"><div class="keunggulan-icon emoji-pop">🚀</div><h3>Naik Level Termotivasi</h3><p>Suasana belajar yang membuat anak semangat.</p></div>
                <div class="card keunggulan-card jello-hover reveal"><div class="keunggulan-icon emoji-pop">🏠</div><h3>Belajar di Rumah</h3><p>Guru privat datang, anak nyaman & fokus.</p></div>
            </div>
        </div>
    </section>

    <script src="<?= $base ?>js/slider.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
