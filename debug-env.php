<?php
/* ============================================================
   debug-env.php — Diagnostik SEMENTARA untuk deployment Railway.
   Menampilkan variabel DB mana yang terbaca (password disamarkan)
   + hasil uji koneksi. HAPUS file ini setelah masalah selesai.
   Akses: /debug-env.php?key=cekbimbel2026
   ============================================================ */
if (($_GET['key'] ?? '') !== 'cekbimbel2026') {
    http_response_code(404);
    die('Not Found');
}

header('Content-Type: text/plain; charset=utf-8');

function baca(string $key): ?string {
    $v = getenv($key);
    if ($v !== false && $v !== '') return $v;
    if (!empty($_ENV[$key]))    return $_ENV[$key];
    if (!empty($_SERVER[$key])) return $_SERVER[$key];
    return null;
}
function samar(?string $v): string {
    if ($v === null) return '(TIDAK ADA)';
    if (strlen($v) <= 8) return '(ada, disamarkan)';
    // samarkan password di dalam URL maupun nilai mentah
    $v = preg_replace('~(mysql://[^:]+:)[^@]+(@)~', '$1*****$2', $v);
    if (!str_contains($v, '://') && strlen($v) > 12) $v = substr($v, 0, 4) . '*****';
    return $v;
}

echo "=== DIAGNOSTIK ENV RAILWAY ===\n";
foreach (['MYSQL_URL','DATABASE_URL','MYSQLHOST','MYSQLPORT','MYSQLUSER','MYSQLDATABASE','DB_NAME','DB_HOST','RAILWAY_ENVIRONMENT'] as $k) {
    echo str_pad($k, 20) . ': ' . samar(baca($k)) . "\n";
}
echo 'MYSQLPASSWORD       : ' . (baca('MYSQLPASSWORD') ? '(ada, disamarkan)' : '(TIDAK ADA)') . "\n";

echo "\n=== HASIL RESOLUSI db.php ===\n";
require_once __DIR__ . '/includes/db.php';   // jika gagal, die() dgn pesan error
echo "KONEKSI BERHASIL ✓\n";
echo 'Host   : ' . DB_HOST . "\n";
echo 'Port   : ' . DB_PORT . "\n";
echo 'DB     : ' . DB_NAME . "\n";
echo 'User   : ' . DB_USER . "\n";
$n = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
echo "users  : $n baris\n";
