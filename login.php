<?php
/* ============================================================
   login.php — Login universal (redirect sesuai role)
   ============================================================ */
require_once __DIR__ . '/includes/functions.php';

// Jika sudah login, langsung ke dashboard sesuai role
if (!empty($_SESSION['user_id'])) {
    redirect(dashboardUrl($_SESSION['role'] ?? ''));
}

$base  = baseUrl();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? null)) {
        $error = 'Token keamanan tidak valid. Muat ulang halaman. 🛡️';
    } else {
        $login = trim($_POST['login'] ?? '');     // username ATAU email
        $pass  = $_POST['password'] ?? '';
        if ($login === '' || $pass === '') {
            $error = 'Username/email dan password wajib diisi.';
        } else {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE (username = ? OR email = ?) LIMIT 1');
            $stmt->execute([$login, $login]);
            $user = $stmt->fetch();
            if ($user && password_verify($pass, $user['password_hash'])) {
                if (!$user['status_aktif']) {
                    $error = 'Akun Anda dinonaktifkan. Hubungi admin. 🚫';
                } else {
                    // Sukses: regenerate session ID (anti session fixation)
                    session_regenerate_id(true);
                    $_SESSION['user_id']      = (int)$user['id'];
                    $_SESSION['username']     = $user['username'];
                    $_SESSION['role']         = $user['role'];
                    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                    $_SESSION['last_activity']= time();
                    logAktivitas((int)$user['id'], $user['role'], 'login', 'Login berhasil');
                    flashMessage('success', 'Selamat datang kembali, ' . $user['nama_lengkap'] . '! 👋');
                    redirect(dashboardUrl($user['role']));
                }
            } else {
                $error = 'Username/email atau password salah. ❌';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk | Bimbel Ayo Mengaji 📖</title>
    <link rel="stylesheet" href="<?= $base ?>css/global.css">
    <link rel="stylesheet" href="<?= $base ?>css/admin-dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
</head>
<body class="page-fade auth-body">
    <div class="bg-emojis" aria-hidden="true">
        <span style="--x:10%; --y:18%; --d:0s;  --s:1.4rem;">📖</span>
        <span style="--x:80%; --y:24%; --d:1s;  --s:1.5rem;">✨</span>
        <span style="--x:24%; --y:78%; --d:2s;  --s:1.3rem;">🚀</span>
        <span style="--x:72%; --y:70%; --d:1.5s;--s:1.4rem;">⭐</span>
    </div>

    <div class="auth-wrap">
        <div class="auth-card card">
            <div class="auth-head">
                <a href="<?= $base ?>index.php" class="auth-logo">📖 Ayo <span>Mengaji</span></a>
                <h1>Selamat Datang 👋</h1>
                <p>Masuk untuk mengakses dashboard Anda.</p>
            </div>

            <?php if ($error): ?>
                <div class="auth-alert auth-alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" class="auth-form" autocomplete="off">
                <?= csrfField() ?>
                <label class="form-label">Email / Username</label>
                <input type="text" name="login" class="form-input" placeholder="masukan email/username" required value="<?= e($_POST['login'] ?? '') ?>">

                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" placeholder="masukan password email/username" required>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:1rem">Masuk 🚀</button>
            </form>

            <p class="auth-foot">Belum punya akun? <a href="<?= $base ?>register.php">Daftar di sini</a></p>
            <p class="auth-foot"><a href="<?= $base ?>index.php">← Kembali ke Beranda</a></p>
        </div>
    </div>

    <script src="<?= $base ?>js/core.js"></script>
</body>
</html>
