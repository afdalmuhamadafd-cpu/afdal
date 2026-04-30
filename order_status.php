<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

verify_csrf();

$id     = (int) ($_POST['id'] ?? 0);
$status = (string) ($_POST['status'] ?? '');

if ($id <= 0) {
    flash('error', 'ID pesanan tidak valid.');
    redirect('dashboard.php');
}

if (!in_array($status, ['pending', 'proses', 'selesai'], true)) {
    flash('error', 'Status tidak valid.');
    redirect('dashboard.php');
}

if (!update_order_status($id, $status)) {
    flash('error', 'Pesanan tidak ditemukan.');
    redirect('dashboard.php');
}

$labels = ['pending' => 'Pending', 'proses' => 'Diproses', 'selesai' => 'Selesai'];
flash('success', 'Status pesanan diubah menjadi ' . $labels[$status] . '.');
redirect('dashboard.php');
