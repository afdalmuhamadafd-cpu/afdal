<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_role('admin');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $id > 0;
$errors = [];

$service = [
    'name' => '',
    'description' => '',
    'price' => '',
];

if ($isEdit) {
    $found = find_service($id);

    if (!$found) {
        flash('error', 'Data service tidak ditemukan.');
        redirect('services.php');
    }

    $service = [
        'name' => $found['name'],
        'description' => $found['description'],
        'price' => (string) $found['price'],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $service['name'] = trim((string) ($_POST['name'] ?? ''));
    $service['description'] = trim((string) ($_POST['description'] ?? ''));
    $service['price'] = trim((string) ($_POST['price'] ?? ''));

    if ($service['name'] === '') {
        $errors[] = 'Nama service wajib diisi.';
    }

    if ($service['description'] === '') {
        $errors[] = 'Deskripsi wajib diisi.';
    }

    if ($service['price'] === '' || !is_numeric($service['price']) || (float) $service['price'] < 0) {
        $errors[] = 'Harga harus berupa angka dan tidak boleh negatif.';
    }

    if (!$errors) {
        if ($isEdit) {
            if (!update_service($id, $service['name'], $service['description'], (float) $service['price'])) {
                flash('error', 'Data service gagal diperbarui.');
                redirect('services.php');
            }

            flash('success', 'Data service berhasil diperbarui.');
        } else {
            create_service($service['name'], $service['description'], (float) $service['price']);

            flash('success', 'Data service berhasil ditambahkan.');
        }

        redirect('services.php');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Edit' : 'Tambah' ?> Jenis Service</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div>
                <p class="eyebrow">Sistem Bengkel</p>
                <h2>Kelola Service</h2>
            </div>
            <nav class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="services.php" class="active">Jenis Service</a>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>

        <main class="content">
            <header class="page-header">
                <div>
                    <p class="eyebrow">Hak Akses Admin</p>
                    <h1><?= $isEdit ? 'Edit' : 'Tambah' ?> Jenis Service</h1>
                    <p class="muted">Halaman ini dilindungi di backend dan hanya dapat diakses admin.</p>
                </div>
            </header>

            <?php if ($errors): ?>
                <div class="alert error">
                    <?php foreach ($errors as $error): ?>
                        <p><?= e($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <section class="panel">
                <form method="post" class="form-grid">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                    <label>
                        Nama Service
                        <input type="text" name="name" value="<?= e($service['name']) ?>" required>
                    </label>

                    <label>
                        Deskripsi
                        <textarea name="description" rows="5" required><?= e($service['description']) ?></textarea>
                    </label>

                    <label>
                        Harga
                        <input type="number" name="price" min="0" step="1000" value="<?= e($service['price']) ?>" required>
                    </label>

                    <div class="form-actions">
                        <button type="submit" class="button primary"><?= $isEdit ? 'Simpan Perubahan' : 'Tambah Service' ?></button>
                        <a href="services.php" class="button secondary">Kembali</a>
                    </div>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
