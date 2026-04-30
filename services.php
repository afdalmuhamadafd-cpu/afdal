<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_login();

$services = all_services();
$successMessage = flash('success');
$errorMessage = flash('error');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jenis Service</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div>
                <p class="eyebrow">Sistem Bengkel</p>
                <h2>Menu Utama</h2>
            </div>
            <nav class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="services.php" class="active">Jenis Service</a>
                <?php if (has_role('admin')): ?>
                    <a href="service_form.php">Tambah Service</a>
                <?php endif; ?>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>

        <main class="content">
            <header class="page-header">
                <div>
                    <p class="eyebrow">Modul Data</p>
                    <h1>Jenis Service</h1>
                    <p class="muted">
                        <?= has_role('admin')
                            ? 'Anda dapat menambah, mengedit, menghapus, dan melihat data service.'
                            : 'Anda hanya dapat melihat daftar service tanpa mengubah data.' ?>
                    </p>
                </div>
                <?php if (has_role('admin')): ?>
                    <a href="service_form.php" class="button primary">Tambah Service</a>
                <?php endif; ?>
            </header>

            <?php if ($successMessage): ?>
                <div class="alert success"><p><?= e($successMessage) ?></p></div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="alert error"><p><?= e($errorMessage) ?></p></div>
            <?php endif; ?>

            <section class="panel">
                <?php if (!$services): ?>
                    <p class="empty-state">Belum ada jenis service yang tersimpan.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nama Service</th>
                                    <th>Deskripsi</th>
                                    <th>Harga</th>
                                    <?php if (has_role('admin')): ?>
                                        <th>Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $service): ?>
                                    <tr>
                                        <td><?= e($service['name']) ?></td>
                                        <td><?= e($service['description']) ?></td>
                                        <td><?= e(format_rupiah((float) $service['price'])) ?></td>
                                        <?php if (has_role('admin')): ?>
                                            <td class="actions">
                                                <a href="service_form.php?id=<?= (int) $service['id'] ?>" class="button small secondary">Edit</a>
                                                <form method="post" action="service_delete.php" onsubmit="return confirm('Hapus data service ini?');">
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
                                                    <button type="submit" class="button small danger">Hapus</button>
                                                </form>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
