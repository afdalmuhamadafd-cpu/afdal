<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $foundUser = find_user_by_email($email);

    if (!$foundUser || !password_verify($password, $foundUser['password_hash'])) {
        $error = 'Email atau password salah.';
    } else {
        $_SESSION['user'] = [
            'id' => (int) $foundUser['id'],
            'name' => $foundUser['name'],
            'email' => $foundUser['email'],
            'role' => $foundUser['role'],
        ];

        flash('success', 'Login berhasil.');

        if ($foundUser['role'] === 'customer') {
            redirect('customer_dashboard.php');
        } else {
            redirect('dashboard.php');
        }
    }
}

$successMessage = flash('success');
$errorMessage = flash('error');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Bengkel</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="auth-page">
    <div class="auth-card">
        <div>
            <p class="eyebrow">Sistem Bengkel</p>
            <h1>Login Pengguna</h1>
            <p class="muted">Hak akses akan ditentukan otomatis berdasarkan role akun.</p>
        </div>

        <?php if ($successMessage): ?>
            <div class="alert success"><p><?= e($successMessage) ?></p></div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert error"><p><?= e($errorMessage) ?></p></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert error"><p><?= e($error) ?></p></div>
        <?php endif; ?>

        <form method="post" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <label>
                Email
                <input type="email" name="email" value="<?= e($email) ?>" required>
            </label>

            <label>
                Password
                <input type="password" name="password" required>
            </label>

            <button type="submit" class="button primary">Login</button>
        </form>

        <p class="auth-link">Belum punya akun? <a href="register.php">Register di sini</a></p>
    </div>
</body>
</html>
