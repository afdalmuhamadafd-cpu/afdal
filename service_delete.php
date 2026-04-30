<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

verify_csrf();

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    flash('error', 'ID service tidak valid.');
    redirect('services.php');
}

if (!delete_service($id)) {
    flash('error', 'Data service tidak ditemukan.');
    redirect('services.php');
}

flash('success', 'Data service berhasil dihapus.');
redirect('services.php');
