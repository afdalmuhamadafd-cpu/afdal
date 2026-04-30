<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (is_logged_in()) {
    if (is_customer()) {
        redirect('customer_dashboard.php');
    } else {
        redirect('dashboard.php');
    }
}

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name            = trim((string) ($_POST['name'] ?? ''));
    $email           = trim((string) ($_POST['email'] ?? ''));
    $password        = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($name === '') {
        $errors[] = 'Nama wajib diisi.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email tidak valid.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Konfirmasi password tidak cocok.';
    }

    if (!$errors) {
        if (find_user_by_email($email)) {
            $errors[] = 'Email sudah terdaftar.';
        } else {
            create_user($name, $email, password_hash($password, PASSWORD_DEFAULT), 'customer');

            flash('success', 'Registrasi berhasil. Silakan login.');
            redirect('login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Customer - Bengkel</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="auth-page">
    <div class="auth-card">
        <div>
            <p class="eyebrow">Sistem Bengkel</p>
            <h1>Daftar Akun Customer</h1>
            <p class="muted">Buat akun untuk memesan layanan service kendaraan Anda.</p>
        </div>

        <?php if ($errors): ?>
            <div class="alert error">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <label>
                Nama Lengkap
                <input type="text" name="name" value="<?= e($name) ?>" required>
            </label>

            <label>
                Email
                <input type="email" name="email" value="<?= e($email) ?>" required>
            </label>

            <label>
                Password
                <input type="password" name="password" required>
            </label>

            <label>
                Konfirmasi Password
                <input type="password" name="confirm_password" required>
            </label>

            <button type="submit" class="button primary">Daftar Sekarang</button>
        </form>

        <p class="auth-link">Sudah punya akun? <a href="login.php">Login di sini</a></p>
    </div>
</body>
</html>
