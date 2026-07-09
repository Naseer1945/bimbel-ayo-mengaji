<?php
/* ============================================================
   includes/db.php — Koneksi database PDO (MySQL)
   Konfigurasi XAMPP default: host=localhost, db=bimbel_ayo_mengaji,
   user=root, password kosong. Mengembalikan objek $pdo global.
   ============================================================ */

// ---- Konfigurasi koneksi ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'bimbel_ayo_mengaji');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ---- Buat koneksi PDO (prepared statement, exception mode) ----
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // lempar exception saat error
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // hasil sebagai array asosiatif
        PDO::ATTR_EMULATE_PREPARES   => false,                   // prepared statement asli (lebih aman)
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Pesan user-friendly, JANGAN tampilkan detail database ke publik
    http_response_code(500);
    die('<div style="font-family:sans-serif;max-width:560px;margin:80px auto;padding:30px;
         border-radius:16px;background:#FEF2F2;color:#991B1B;text-align:center">
         <h2>⚠️ Gagal Terhubung ke Database</h2>
         <p>Pastikan <b>MySQL di XAMPP sudah berjalan</b> dan database
         <code>bimbel_ayo_mengaji</code> sudah di-import dari <code>database.sql</code>.</p>
         </div>');
}
