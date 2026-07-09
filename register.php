<?php
/* ============================================================
   register.php — Registrasi CLIENT baru
   Membuat akun (role=client) + baris di tabel clients.
   ============================================================ */
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['user_id'])) {
    redirect(dashboardUrl($_SESSION['role'] ?? ''));
}

$base   = baseUrl();
$error  = '';
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? null)) {
        $error = 'Token keamanan tidak valid. Muat ulang halaman. 🛡️';
    } else {
        $old = $_POST;
        $nama     = trim($_POST['nama_lengkap'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $noHp     = trim($_POST['no_hp'] ?? '');
        $alamat   = trim($_POST['alamat'] ?? '');
        $pass     = $_POST['password'] ?? '';
        $pass2    = $_POST['password2'] ?? '';

        if ($nama === '' || $username === '' || $email === '' || $pass === '') {
            $error = 'Semua field bertanda * wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } elseif (strlen($pass) < 6) {
            $error = 'Password minimal 6 karakter.';
        } elseif ($pass !== $pass2) {
            $error = 'Konfirmasi password tidak cocok.';
        } else {
            // Cek duplikat username/email
            $chk = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
            $chk->execute([$username, $email]);
            if ($chk->fetch()) {
                $error = 'Username atau email sudah terdaftar. Coba yang lain.';
            } else {
                try {
                    $pdo->beginTransaction();
                    $hash = password_hash($pass, PASSWORD_DEFAULT);
                    $ins = $pdo->prepare(
                        'INSERT INTO users (username, email, password_hash, role, nama_lengkap, no_hp, alamat)
                         VALUES (?, ?, ?, "client", ?, ?, ?)'
                    );
                    $ins->execute([$username, $email, $hash, $nama, $noHp, $alamat]);
                    $userId = (int)$pdo->lastInsertId();

                    $insC = $pdo->prepare(
                        'INSERT INTO clients (user_id, nama_client, no_hp, alamat, jumlah_entitas, status_pendaftaran)
                         VALUES (?, ?, ?, ?, 0, "pending")'
                    );
                    $insC->execute([$userId, $nama, $noHp, $alamat]);
                    $pdo->commit();

                    logAktivitas($userId, 'client', 'register', 'Registrasi client baru: ' . $username);

                    // Auto-login setelah daftar
                    session_regenerate_id(true);
                    $_SESSION['user_id']      = $userId;
                    $_SESSION['username']     = $username;
                    $_SESSION['role']         = 'client';
                    $_SESSION['nama_lengkap'] = $nama;
                    flashMessage('success', 'Pendaftaran berhasil! Silakan daftarkan santri Anda. 🎉');
                    redirect($base . 'client/daftar.php');
                } catch (PDOException $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $error = 'Terjadi kesalahan saat mendaftar. Coba lagi.';
                }
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
    <title>Daftar Akun | Bimbel Ayo Mengaji 📖</title>
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
        <div class="auth-card card auth-card-wide">
            <div class="auth-head">
                <a href="<?= $base ?>index.php" class="auth-logo">📖 Ayo <span>Mengaji</span></a>
                <h1>Buat Akun Client 📝</h1>
                <p>Daftar untuk mendaftarkan putra-putri Anda belajar mengaji.</p>
            </div>

            <?php if ($error): ?>
                <div class="auth-alert auth-alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" class="auth-form">
                <?= csrfField() ?>
                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label">Nama Lengkap *</label>
                        <input type="text" name="nama_lengkap" class="form-input" required value="<?= e($old['nama_lengkap'] ?? '') ?>">
                    </div>
                    <div class="form-col">
                        <label class="form-label">No. WhatsApp</label>
                        <input type="text" name="no_hp" class="form-input" placeholder="08xxxx" value="<?= e($old['no_hp'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-input" required value="<?= e($old['username'] ?? '') ?>">
                    </div>
                    <div class="form-col">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-input" required value="<?= e($old['email'] ?? '') ?>">
                    </div>
                </div>
                <label class="form-label">Alamat</label>
                <textarea name="alamat" class="form-input" rows="2" placeholder="Alamat lengkap (untuk guru datang ke rumah)"><?= e($old['alamat'] ?? '') ?></textarea>

                <div class="form-row">
                    <div class="form-col">
                        <label class="form-label">Password * (min. 6)</label>
                        <input type="password" name="password" class="form-input" required>
                    </div>
                    <div class="form-col">
                        <label class="form-label">Ulangi Password *</label>
                        <input type="password" name="password2" class="form-input" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:1rem">Daftar Sekarang 🚀</button>
            </form>

            <p class="auth-foot">Sudah punya akun? <a href="<?= $base ?>login.php">Masuk di sini</a></p>
            <p class="auth-foot"><a href="<?= $base ?>index.php">← Kembali ke Beranda</a></p>
        </div>
    </div>

    <script src="<?= $base ?>js/core.js"></script>
</body>
</html>
