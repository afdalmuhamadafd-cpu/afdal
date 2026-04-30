<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_login();

// Customer tidak boleh akses dashboard staff
if (is_customer()) {
    redirect('customer_dashboard.php');
}

$currentUser    = user();
$totalUsers     = count(all_users());
$totalServices  = count(all_services());
$allOrders      = all_orders();
$serviceList    = all_services();
$successMessage = flash('success');
$errorMessage   = flash('error');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Bengkel</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div>
                <p class="eyebrow">Sistem Bengkel</p>
                <h2>Panel <?= has_role('admin') ? 'Admin' : 'Mekanik' ?></h2>
            </div>
            <nav class="nav">
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="services.php">Jenis Service</a>
                <?php if (has_role('admin')): ?>
                    <a href="service_form.php">Tambah Service</a>
                <?php endif; ?>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>

        <main class="content">
            <header class="page-header">
                <div>
                    <p class="eyebrow">Selamat datang</p>
                    <h1><?= e($currentUser['name']) ?></h1>
                    <p class="muted">Role aktif: <strong><?= e(strtoupper($currentUser['role'])) ?></strong></p>
                </div>
            </header>

            <?php if ($successMessage): ?>
                <div class="alert success"><p><?= e($successMessage) ?></p></div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="alert error"><p><?= e($errorMessage) ?></p></div>
            <?php endif; ?>

            <section class="stats-grid">
                <article class="stat-card">
                    <p class="muted">Total Pengguna</p>
                    <h3><?= $totalUsers ?></h3>
                </article>
                <article class="stat-card">
                    <p class="muted">Jenis Service</p>
                    <h3><?= $totalServices ?></h3>
                </article>
                <article class="stat-card">
                    <p class="muted">Total Pesanan</p>
                    <h3><?= count($allOrders) ?></h3>
                </article>
            </section>

            <!-- Daftar Pesanan Service -->
            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2>Pesanan Service Masuk</h2>
                        <p class="muted">
                            <?= has_role('admin')
                                ? 'Semua pesanan dari customer.'
                                : 'Daftar pesanan yang perlu dikerjakan.' ?>
                        </p>
                    </div>
                </div>

                <?php if (!$allOrders): ?>
                    <p class="empty-state">Belum ada pesanan service masuk.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Jenis Service</th>
                                    <th>Kendaraan</th>
                                    <th>Plat</th>
                                    <th>Harga</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <?php if (has_role('admin')): ?>
                                        <th>Ubah Status</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allOrders as $order): ?>
                                    <tr>
                                        <td><?= e($order['customer_name']) ?></td>
                                        <td><?= e($order['service_name']) ?></td>
                                        <td><?= e($order['vehicle_name']) ?></td>
                                        <td><?= e($order['vehicle_plate']) ?></td>
                                        <td><?= e(format_rupiah((float) $order['price'])) ?></td>
                                        <td>
                                            <span class="badge badge-<?= e($order['status']) ?>">
                                                <?= e(ucfirst($order['status'])) ?>
                                            </span>
                                        </td>
                                        <td><?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></td>
                                        <?php if (has_role('admin')): ?>
                                            <td>
                                                <form method="post" action="order_status.php" class="status-form">
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                                                    <select name="status" class="status-select status-<?= e($order['status']) ?>"
                                                            onchange="this.form.submit()">
                                                        <option value="pending"  <?= $order['status'] === 'pending'  ? 'selected' : '' ?>>Pending</option>
                                                        <option value="proses"   <?= $order['status'] === 'proses'   ? 'selected' : '' ?>>Diproses</option>
                                                        <option value="selesai"  <?= $order['status'] === 'selesai'  ? 'selected' : '' ?>>Selesai</option>
                                                    </select>
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

            <!-- Ringkasan Jenis Service -->
            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2>Ringkasan Jenis Service</h2>
                        <p class="muted">
                            <?= has_role('admin')
                                ? 'Admin dapat mengelola data service.'
                                : 'Mekanik hanya dapat melihat daftar service.' ?>
                        </p>
                    </div>
                    <a href="services.php" class="button secondary">Lihat Semua</a>
                </div>

                <?php if (!$serviceList): ?>
                    <p class="empty-state">Belum ada data service.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nama Service</th>
                                    <th>Deskripsi</th>
                                    <th>Harga</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($serviceList, 0, 5) as $service): ?>
                                    <tr>
                                        <td><?= e($service['name']) ?></td>
                                        <td><?= e($service['description']) ?></td>
                                        <td><?= e(format_rupiah((float) $service['price'])) ?></td>
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
