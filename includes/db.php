<?php
/* ============================================================
   includes/db.php — Koneksi database PDO (MySQL)
   Mendukung 2 lingkungan sekaligus:
   1. RAILWAY (production) -> baca environment variable:
      - MYSQL_URL / DATABASE_URL (format mysql://user:pass@host:port/db)
      - atau MYSQLHOST, MYSQLPORT, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE
      - DB_NAME dapat meng-override nama database bila diperlukan
   2. XAMPP LOKAL (development) -> fallback localhost/root tanpa password
   ============================================================ */

/* ---- Helper baca environment variable dari semua sumber ---- */
function envval(string $key, ?string $default = null): ?string {
    $v = getenv($key);
    if ($v !== false && $v !== '') return $v;
    if (!empty($_ENV[$key]))    return $_ENV[$key];
    if (!empty($_SERVER[$key])) return $_SERVER[$key];
    return $default;
}

// ---- 1) Coba parse URL koneksi (Railway: MYSQL_URL / DATABASE_URL) ----
$dbHost = null; $dbPort = null; $dbUser = null; $dbPass = null; $dbNameFromUrl = null;
$mysqlUrl = envval('MYSQL_URL') ?? envval('DATABASE_URL');
if ($mysqlUrl) {
    $parts = parse_url($mysqlUrl);
    if ($parts !== false && !empty($parts['host'])) {
        $dbHost = $parts['host'];
        $dbPort = isset($parts['port']) ? (string)$parts['port'] : null;
        $dbUser = isset($parts['user']) ? rawurldecode($parts['user']) : null;
        $dbPass = isset($parts['pass']) ? rawurldecode($parts['pass']) : null;
        $dbNameFromUrl = isset($parts['path']) ? ltrim($parts['path'], '/') : null;
    }
}

// ---- 2) Variabel individual Railway (fallback bila URL tidak ada) ----
$dbHost = $dbHost ?? envval('MYSQLHOST') ?? envval('DB_HOST', 'localhost');
$dbPort = $dbPort ?? envval('MYSQLPORT') ?? envval('DB_PORT', '3306');
$dbUser = $dbUser ?? envval('MYSQLUSER') ?? envval('DB_USER', 'root');
$dbPass = $dbPass ?? envval('MYSQLPASSWORD') ?? envval('DB_PASS', '');

// ---- 3) Nama database ----
// Prioritas: DB_NAME (override manual) -> nama dari URL -> MYSQLDATABASE -> default lokal.
// Catatan: database.sql membuat DB "bimbel_ayo_mengaji"; di Railway DB default
// bernama "railway". Jika Anda import database.sql apa adanya ke Railway,
// set variabel DB_NAME=bimbel_ayo_mengaji pada service app.
$dbName = envval('DB_NAME')
       ?? ($dbNameFromUrl !== null && $dbNameFromUrl !== '' ? $dbNameFromUrl : null)
       ?? envval('MYSQLDATABASE')
       ?? 'bimbel_ayo_mengaji';

define('DB_HOST', $dbHost);
define('DB_PORT', $dbPort);
define('DB_NAME', $dbName);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_CHARSET', 'utf8mb4');

// ---- Buat koneksi PDO (prepared statement, exception mode) ----
try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // lempar exception saat error
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // hasil sebagai array asosiatif
        PDO::ATTR_EMULATE_PREPARES   => false,                   // prepared statement asli (lebih aman)
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Pesan user-friendly, JANGAN tampilkan detail database ke publik
    http_response_code(500);
    $isRailway = (bool)(envval('MYSQL_URL') ?? envval('MYSQLHOST') ?? envval('RAILWAY_ENVIRONMENT'));
    $hint = $isRailway
        ? 'Pastikan service <b>MySQL sudah ditambahkan di Railway</b>, variabel
           <code>MYSQL_URL</code> sudah di-reference ke service app, dan
           <code>database.sql</code> sudah di-import. Bila nama database berbeda,
           set variabel <code>DB_NAME=bimbel_ayo_mengaji</code>.'
        : 'Pastikan <b>MySQL di XAMPP sudah berjalan</b> dan database
           <code>bimbel_ayo_mengaji</code> sudah di-import dari <code>database.sql</code>.';
    die('<div style="font-family:sans-serif;max-width:560px;margin:80px auto;padding:30px;
         border-radius:16px;background:#FEF2F2;color:#991B1B;text-align:center">
         <h2>⚠️ Gagal Terhubung ke Database</h2>
         <p>' . $hint . '</p>
         </div>');
}
