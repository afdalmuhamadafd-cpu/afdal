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

redirect('login.php');
