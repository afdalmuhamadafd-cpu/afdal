<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_login();

// Hanya customer yang boleh akses halaman ini
if (!is_customer()) {
    redirect('dashboard.php');
}

$currentUser  = user();
$serviceTypes = all_services();
$myOrders     = orders_by_user($currentUser['id']);
$errors       = [];
$successMessage = flash('success');
$errorMessage   = flash('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $serviceTypeId = (int) ($_POST['service_type_id'] ?? 0);
    $vehicleName   = trim((string) ($_POST['vehicle_name'] ?? ''));
    $vehiclePlate  = trim((string) ($_POST['vehicle_plate'] ?? ''));
    $notes         = trim((string) ($_POST['notes'] ?? ''));

    if ($serviceTypeId <= 0) {
        $errors[] = 'Pilih jenis service terlebih dahulu.';
    }

    if ($vehicleName === '') {
        $errors[] = 'Nama kendaraan wajib diisi.';
    }

    if ($vehiclePlate === '') {
        $errors[] = 'Nomor plat kendaraan wajib diisi.';
    }

    if (!$errors) {
        create_order($currentUser['id'], $serviceTypeId, $vehicleName, $vehiclePlate, $notes);
        flash('success', 'Pesanan service berhasil dikirim. Kami akan segera memproses kendaraan Anda.');
        redirect('customer_dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Customer - Bengkel</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div>
                <p class="eyebrow">Sistem Bengkel</p>
                <h2>Customer Panel</h2>
            </div>
            <nav class="nav">
                <a href="customer_dashboard.php" class="active">Pesan Service</a>
                <a href="logout.php">Logout</a>
            </nav>
            <div class="sidebar-footer">
                <p class="muted-sm">Login sebagai</p>
                <p class="user-name"><?= e($currentUser['name']) ?></p>
                <p class="muted-sm"><?= e($currentUser['email']) ?></p>
            </div>
        </aside>

        <main class="content">
            <header class="page-header">
                <div>
                    <p class="eyebrow">Selamat datang</p>
                    <h1>Pesan Layanan Service</h1>
                    <p class="muted">Isi form di bawah untuk memesan service kendaraan Anda.</p>
                </div>
            </header>

            <?php if ($successMessage): ?>
                <div class="alert success"><p><?= e($successMessage) ?></p></div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="alert error"><p><?= e($errorMessage) ?></p></div>
            <?php endif; ?>

            <!-- Form Order Service -->
            <section class="panel">
                <h2 class="panel-title">Form Pemesanan Service</h2>
                <p class="muted panel-subtitle">Pilih jenis service dan isi data kendaraan Anda.</p>

                <?php if ($errors): ?>
                    <div class="alert error">
                        <?php foreach ($errors as $error): ?>
                            <p><?= e($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!$serviceTypes): ?>
                    <p class="empty-state">Belum ada jenis service tersedia. Silakan hubungi bengkel.</p>
                <?php else: ?>
                    <form method="post" class="form-grid">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                        <label>
                            Jenis Service
                            <select name="service_type_id" required>
                                <option value="">-- Pilih Jenis Service --</option>
                                <?php foreach ($serviceTypes as $st): ?>
                                    <option value="<?= (int) $st['id'] ?>"
                                        <?= (isset($_POST['service_type_id']) && (int) $_POST['service_type_id'] === (int) $st['id']) ? 'selected' : '' ?>>
                                        <?= e($st['name']) ?> — <?= e(format_rupiah((float) $st['price'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label>
                            Nama Kendaraan
                            <input type="text" name="vehicle_name"
                                   value="<?= e((string) ($_POST['vehicle_name'] ?? '')) ?>"
                                   placeholder="Contoh: Honda Beat 2020" required>
                        </label>

                        <label>
                            Nomor Plat Kendaraan
                            <input type="text" name="vehicle_plate"
                                   value="<?= e((string) ($_POST['vehicle_plate'] ?? '')) ?>"
                                   placeholder="Contoh: B 1234 XYZ" required>
                        </label>

                        <label>
                            Catatan Tambahan (opsional)
                            <textarea name="notes" rows="3"
                                      placeholder="Keluhan atau catatan khusus untuk mekanik..."><?= e((string) ($_POST['notes'] ?? '')) ?></textarea>
                        </label>

                        <div class="form-actions">
                            <button type="submit" class="button primary">Kirim Pesanan</button>
                        </div>
                    </form>
                <?php endif; ?>
            </section>

            <!-- Riwayat Order -->
            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2>Riwayat Pesanan Saya</h2>
                        <p class="muted">Daftar semua pesanan service yang pernah Anda buat.</p>
                    </div>
                </div>

                <?php if (!$myOrders): ?>
                    <p class="empty-state">Belum ada riwayat pesanan.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Jenis Service</th>
                                    <th>Kendaraan</th>
                                    <th>Plat</th>
                                    <th>Harga</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($myOrders as $order): ?>
                                    <tr>
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
