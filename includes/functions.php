<?php
/* ============================================================
   includes/functions.php — Kumpulan helper inti webapp
   Berisi: session bootstrap, auth (cekLogin/cekRole),
   CSRF, flash message, redirect, upload file, log aktivitas,
   format tanggal, badge status, pagination, dan setting helper.
   ============================================================ */

require_once __DIR__ . '/db.php';

/* ---- Bootstrap session dengan timeout 2 jam (7200 detik) ---- */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$__timeout = 7200;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $__timeout)) {
    // Sesi kedaluwarsa -> bersihkan & paksa login ulang
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['flash'] = ['type' => 'info', 'message' => 'Sesi Anda telah berakhir. Silakan login kembali. ⏳'];
}
$_SESSION['last_activity'] = time();

/* ---- Base URL helper (agar link benar di subfolder) ---- */
function baseUrl(): string {
    // Hitung folder root project relatif dari DOCUMENT_ROOT
    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // Jika berada di subfolder admin/manager/dll, naik satu level
    foreach (['/admin', '/manager', '/pengajar', '/client', '/includes'] as $sub) {
        if (str_ends_with($script, $sub)) {
            $script = substr($script, 0, -strlen($sub));
            break;
        }
    }
    return rtrim($script, '/') . '/';
}

/* ============================================================
   1. cekLogin() — wajib login, kalau belum -> ke login.php
   ============================================================ */
function cekLogin(): void {
    if (empty($_SESSION['user_id'])) {
        flashMessage('error', 'Silakan login terlebih dahulu untuk mengakses halaman tersebut. 🔒');
        redirect(baseUrl() . 'login.php');
    }
}

/* ============================================================
   2. cekRole($allowedRoles) — RBAC. Jika role tak diizinkan,
      arahkan ke dashboard sesuai role miliknya.
   ============================================================ */
function cekRole(array $allowedRoles): void {
    cekLogin();
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, $allowedRoles, true)) {
        flashMessage('error', 'Anda tidak memiliki akses ke halaman tersebut. 🚫');
        redirect(dashboardUrl($role));
    }
}

/* ---- URL dashboard sesuai role ---- */
function dashboardUrl(string $role): string {
    $map = [
        'super_admin' => 'admin/dashboard.php',
        'manager'     => 'manager/dashboard.php',
        'pengajar'    => 'pengajar/dashboard.php',
        'client'      => 'client/dashboard.php',
    ];
    return baseUrl() . ($map[$role] ?? 'index.php');
}

/* ============================================================
   3. generateCSRF() — buat & simpan token di session
   ============================================================ */
function generateCSRF(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/* ---- Helper: hidden input CSRF siap pakai di form ---- */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCSRF()) . '">';
}

/* ============================================================
   4. validateCSRF($token) — bandingkan dengan session
   ============================================================ */
function validateCSRF(?string $token): bool {
    return !empty($token) && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/* ---- Guard: hentikan eksekusi jika CSRF tidak valid pada POST ---- */
function requireCSRF(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCSRF($_POST['csrf_token'] ?? null)) {
            flashMessage('error', 'Token keamanan tidak valid. Coba lagi. 🛡️');
            redirect($_SERVER['HTTP_REFERER'] ?? baseUrl());
        }
    }
}

/* ============================================================
   5. flashMessage($type, $message) — simpan pesan sekali tampil
   ============================================================ */
function flashMessage(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/* ============================================================
   6. showFlash() — tampilkan & hapus dari session (HTML toast)
   ============================================================ */
function showFlash(): string {
    if (empty($_SESSION['flash'])) return '';
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $type = htmlspecialchars($f['type']);
    $msg  = htmlspecialchars($f['message']);
    // Data attribute dipakai js/admin.js untuk memunculkan toast
    return '<div class="flash-toast" data-type="' . $type . '">' . $msg . '</div>';
}

/* ============================================================
   7. redirect($url) — header Location + stop
   ============================================================ */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/* ============================================================
   8. uploadFile($file, $folder, ...) — validasi & simpan file
      Return path relatif (string) bila sukses, false bila gagal.
   ============================================================ */
function uploadFile(array $file, string $folder, array $allowedTypes = ['jpg','jpeg','png'], int $maxSize = 2097152) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    if ($file['size'] > $maxSize) {
        return false; // melebihi 2MB
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedTypes, true)) {
        return false;
    }
    // Validasi MIME sebenarnya (anti-spoofing ekstensi)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowedMimes = ['image/jpeg', 'image/png'];
    if (!in_array($mime, $allowedMimes, true)) {
        return false;
    }
    // Pastikan folder ada
    $absFolder = __DIR__ . '/../' . trim($folder, '/') . '/';
    if (!is_dir($absFolder)) {
        @mkdir($absFolder, 0775, true);
    }
    // Nama unik: timestamp + 6 char random
    $unique = time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    $target = $absFolder . $unique;
    if (move_uploaded_file($file['tmp_name'], $target)) {
        return trim($folder, '/') . '/' . $unique; // path relatif untuk DB
    }
    return false;
}

/* ============================================================
   9. logAktivitas($userId, $role, $jenis, $detail)
   ============================================================ */
function logAktivitas(?int $userId, ?string $role, string $jenis, string $detail = ''): void {
    global $pdo;
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO log_aktivitas (user_id, role, jenis_aktivitas, detail, ip_address)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $role, $jenis, $detail, $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (PDOException $e) {
        // diam-diam abaikan agar logging tidak memutus alur utama
    }
}

/* ============================================================
   10. formatTanggal($date) — "12 Juni 2026"
   ============================================================ */
function formatTanggal(?string $date): string {
    if (empty($date)) return '-';
    $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];
    $ts = strtotime($date);
    if (!$ts) return htmlspecialchars($date);
    return date('j', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

/* ============================================================
   11. getStatusBadge($status) — HTML badge berwarna
   ============================================================ */
function getStatusBadge(string $status): string {
    $map = [
        'pending'     => ['Pending',     'warning'],
        'approved'    => ['Disetujui',   'success'],
        'rejected'    => ['Ditolak',     'danger'],
        'baru'        => ['Baru',        'info'],
        'aktif'       => ['Aktif',       'success'],
        'lulus'       => ['Lulus',       'primary'],
        'nonaktif'    => ['Nonaktif',    'muted'],
        'assigned'    => ['Ditugaskan',  'info'],
        'in_progress' => ['Dikerjakan',  'warning'],
        'completed'   => ['Selesai',     'success'],
    ];
    [$label, $cls] = $map[$status] ?? [ucfirst($status), 'muted'];
    return '<span class="badge badge-' . $cls . '">' . htmlspecialchars($label) . '</span>';
}

/* ============================================================
   12. pagination($total, $perPage, $currentPage, $url) — HTML nav
   ============================================================ */
function pagination(int $total, int $perPage, int $currentPage, string $url): string {
    $totalPages = (int)ceil($total / max(1, $perPage));
    if ($totalPages <= 1) return '';
    $sep = (str_contains($url, '?')) ? '&' : '?';
    $html = '<nav class="pagination">';
    // Prev
    if ($currentPage > 1) {
        $html .= '<a href="' . $url . $sep . 'page=' . ($currentPage - 1) . '" class="page-link">‹</a>';
    }
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == $currentPage) {
            $html .= '<span class="page-link active">' . $i . '</span>';
        } elseif (abs($i - $currentPage) <= 2 || $i == 1 || $i == $totalPages) {
            $html .= '<a href="' . $url . $sep . 'page=' . $i . '" class="page-link">' . $i . '</a>';
        } elseif (abs($i - $currentPage) == 3) {
            $html .= '<span class="page-ellipsis">…</span>';
        }
    }
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . $url . $sep . 'page=' . ($currentPage + 1) . '" class="page-link">›</a>';
    }
    $html .= '</nav>';
    return $html;
}

/* ============================================================
   Helper tambahan: setting sistem (key-value)
   ============================================================ */
function getSetting(string $key, string $default = ''): string {
    global $pdo;
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach ($pdo->query('SELECT nama_key, nilai FROM pengaturan') as $row) {
                $cache[$row['nama_key']] = $row['nilai'];
            }
        } catch (PDOException $e) { $cache = []; }
    }
    return $cache[$key] ?? $default;
}

/* ---- Shortcut escaping output ---- */
function e(?string $v): string {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

/* ---- Encode path untuk <img src> (spasi -> %20) tapi jaga "/" ----
   Juga aman dipakai pada output HTML attribute. ---- */
function rawurlencode_path(?string $path): string {
    if (empty($path)) return '';
    $parts = array_map('rawurlencode', explode('/', $path));
    return implode('/', $parts);
}

/* ---- Ambil data user yang sedang login (cached) ---- */
function currentUser(): ?array {
    global $pdo;
    static $user = null;
    if ($user === null && !empty($_SESSION['user_id'])) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

/* ============================================================
   NOTIFIKASI — helper sistem notifikasi per akun
   ============================================================ */

/* Kirim notifikasi ke satu user */
function buatNotifikasi(int $userId, string $pesan, ?string $link = null): void {
    global $pdo;
    try {
        $pdo->prepare('INSERT INTO notifikasi (user_id, pesan, link) VALUES (?,?,?)')
            ->execute([$userId, $pesan, $link]);
    } catch (PDOException $e) { /* jangan putus alur utama */ }
}

/* Kirim notifikasi ke SEMUA user dengan role tertentu (mis. semua manager) */
function notifikasiUntukRole(string $role, string $pesan, ?string $link = null): void {
    global $pdo;
    try {
        $ids = $pdo->prepare('SELECT id FROM users WHERE role=? AND status_aktif=1');
        $ids->execute([$role]);
        foreach ($ids->fetchAll(PDO::FETCH_COLUMN) as $uid) {
            buatNotifikasi((int)$uid, $pesan, $link);
        }
    } catch (PDOException $e) {}
}

/* Jumlah notifikasi belum dibaca milik user */
function hitungNotifBelumDibaca(int $userId): int {
    global $pdo;
    try {
        $s = $pdo->prepare('SELECT COUNT(*) FROM notifikasi WHERE user_id=? AND is_read=0');
        $s->execute([$userId]);
        return (int)$s->fetchColumn();
    } catch (PDOException $e) { return 0; }
}

/* Cek apakah sebuah fitur aktif (toggle super admin di pengaturan) */
function fiturAktif(string $key): bool {
    return getSetting('fitur_' . $key, '1') === '1';
}
