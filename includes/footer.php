<?php
/* ============================================================
   includes/footer.php — Footer global + Floating WhatsApp
   Membaca nomor WA & nama web dari tabel pengaturan (dinamis).
   ============================================================ */
$base    = baseUrl();
$waRaw   = preg_replace('/[^0-9]/', '', getSetting('kontak_wa', '087750275958'));
$waIntl  = preg_replace('/^0/', '62', $waRaw);
$namaWeb = getSetting('nama_web', 'Bimbel Ayo Mengaji');
$waText  = 'Assalamualaikum%20Kak%2C%20saya%20tertarik%20dengan%20Bimbel%20Ayo%20Mengaji.%20Boleh%20minta%20info%20lengkapnya%3F';
?>
    <!-- CTA BANNER -->
    <section class="cta-banner">
        <div class="container cta-banner-inner">
            <h2>Siap Memulai Perjalanan Mengaji? 🚀✨</h2>
            <p>Daftarkan putra-putri Anda sekarang. Jadwal fleksibel, guru privat datang ke rumah! 🏠</p>
            <div class="cta-banner-buttons">
                <a href="<?= $base ?>register.php" class="btn btn-light btn-lg">Daftar Akun 📝</a>
                <a href="https://wa.me/<?= e($waIntl) ?>?text=<?= $waText ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light btn-lg">Tanya via WhatsApp 💬</a>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3>📖 <?= e($namaWeb) ?></h3>
                    <p>Belajar Mengaji Mudah, Menyenangkan, dan Fleksibel. Mencetak generasi Qurani yang cinta Al-Qur'an sejak dini. ✨</p>
                </div>
                <div class="footer-col">
                    <h3>Navigasi 🧭</h3>
                    <ul class="footer-links">
                        <li><a href="<?= $base ?>index.php">🏠 Beranda</a></li>
                        <li><a href="<?= $base ?>tentang-kami.php">✨ Tentang Kami</a></li>
                        <li><a href="<?= $base ?>slide-keunggulan.php">⭐ Keunggulan</a></li>
                        <li><a href="<?= $base ?>register.php">📝 Daftar Sekarang</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Kontak Admin 📱</h3>
                    <p><strong>WhatsApp:</strong> <?= e($waRaw) ?></p>
                    <p><strong>Admin:</strong> Al Dira Achmad Arrazib</p>
                    <a href="https://wa.me/<?= e($waIntl) ?>?text=<?= $waText ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline footer-wa">Chat WhatsApp 💬</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 <?= e($namaWeb) ?>. Dibuat dengan ❤️ untuk Generasi Qurani.</p>
            </div>
        </div>
    </footer>

    <!-- FLOATING WHATSAPP -->
    <a href="https://wa.me/<?= e($waIntl) ?>?text=<?= $waText ?>"
       class="floating-wa" target="_blank" rel="noopener noreferrer" aria-label="Chat WhatsApp">
        <span class="floating-wa-icon">💬</span>
        <span class="floating-wa-text">Daftar Yuk!</span>
    </a>

    <!-- Flash toast (jika ada) -->
    <?= showFlash() ?>

    <script src="<?= $base ?>js/core.js"></script>
    <script src="<?= $base ?>js/admin.js"></script>
</body>
</html>
