<?php
declare(strict_types=1);

session_start();

const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'bengkel';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    try {
        $serverDsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', DB_HOST, DB_PORT);
        $serverPdo = new PDO(
            $serverDsn,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        $serverPdo->exec(
            sprintf(
                'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
                DB_NAME
            )
        );

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        $pdo = new PDO(
            $dsn,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        initialize_database($pdo);
    } catch (Throwable $exception) {
        http_response_code(500);
        exit(
            'Koneksi MySQL gagal. Periksa konfigurasi database di bootstrap.php ' .
            '(host, port, username, password) dan pastikan service MySQL Laragon sedang berjalan.'
        );
    }

    return $pdo;
}

function initialize_database(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM("admin", "mekanik", "customer") NOT NULL DEFAULT "customer",
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    // Migrate existing table: add customer to role enum if not present
    try {
        $pdo->exec(
            'ALTER TABLE users MODIFY COLUMN role ENUM("admin", "mekanik", "customer") NOT NULL DEFAULT "customer"'
        );
    } catch (Throwable) {
        // Column already up-to-date, ignore
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS service_types (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            description TEXT NOT NULL,
            price DECIMAL(12,2) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS service_orders (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            service_type_id INT UNSIGNED NOT NULL,
            vehicle_name VARCHAR(150) NOT NULL,
            vehicle_plate VARCHAR(20) NOT NULL,
            notes TEXT,
            status ENUM("pending", "proses", "selesai") NOT NULL DEFAULT "pending",
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (service_type_id) REFERENCES service_types(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function find_user_by_email(string $email): ?array
{
    $statement = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $statement->execute(['email' => $email]);
    $user = $statement->fetch();

    return $user ?: null;
}

function create_user(string $name, string $email, string $passwordHash, string $role): void
{
    $statement = db()->prepare(
        'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)'
    );

    $statement->execute([
        'name' => $name,
        'email' => $email,
        'password_hash' => $passwordHash,
        'role' => $role,
    ]);
}

function all_users(): array
{
    $statement = db()->query('SELECT * FROM users ORDER BY created_at DESC, id DESC');
    return $statement->fetchAll();
}

function all_services(): array
{
    $statement = db()->query('SELECT * FROM service_types ORDER BY created_at DESC, id DESC');
    return $statement->fetchAll();
}

function find_service(int $id): ?array
{
    $statement = db()->prepare('SELECT * FROM service_types WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $service = $statement->fetch();

    return $service ?: null;
}

function create_service(string $name, string $description, float $price): void
{
    $statement = db()->prepare(
        'INSERT INTO service_types (name, description, price) VALUES (:name, :description, :price)'
    );

    $statement->execute([
        'name' => $name,
        'description' => $description,
        'price' => $price,
    ]);
}

function update_service(int $id, string $name, string $description, float $price): bool
{
    $statement = db()->prepare(
        'UPDATE service_types
        SET name = :name, description = :description, price = :price
        WHERE id = :id'
    );

    $statement->execute([
        'id' => $id,
        'name' => $name,
        'description' => $description,
        'price' => $price,
    ]);

    return $statement->rowCount() > 0;
}

function delete_service(int $id): bool
{
    $statement = db()->prepare('DELETE FROM service_types WHERE id = :id');
    $statement->execute(['id' => $id]);

    return $statement->rowCount() > 0;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $value = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $value;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = (string) ($_POST['csrf_token'] ?? '');

    if ($token === '' || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
        http_response_code(419);
        exit('Permintaan tidak valid.');
    }
}

function user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return user() !== null;
}

function has_role(string $role): bool
{
    return is_logged_in() && user()['role'] === $role;
}

function is_customer(): bool
{
    return has_role('customer');
}

function is_staff(): bool
{
    return is_logged_in() && in_array(user()['role'], ['admin', 'mekanik'], true);
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'Silakan login terlebih dahulu.');
        redirect('login.php');
    }
}

function require_role(string $role): void
{
    require_login();

    if (!has_role($role)) {
        http_response_code(403);
        flash('error', 'Anda tidak memiliki hak akses untuk melakukan aksi tersebut.');
        redirect('dashboard.php');
    }
}

function require_staff(): void
{
    require_login();

    if (!is_staff()) {
        flash('error', 'Halaman ini hanya untuk admin dan mekanik.');
        redirect('customer_dashboard.php');
    }
}

// ── Service Orders ────────────────────────────────────────────────────────────

function all_orders(): array
{
    $statement = db()->query(
        'SELECT so.*, u.name AS customer_name, st.name AS service_name, st.price
         FROM service_orders so
         JOIN users u ON u.id = so.user_id
         JOIN service_types st ON st.id = so.service_type_id
         ORDER BY so.created_at DESC, so.id DESC'
    );
    return $statement->fetchAll();
}

function orders_by_user(int $userId): array
{
    $statement = db()->prepare(
        'SELECT so.*, st.name AS service_name, st.price
         FROM service_orders so
         JOIN service_types st ON st.id = so.service_type_id
         WHERE so.user_id = :user_id
         ORDER BY so.created_at DESC, so.id DESC'
    );
    $statement->execute(['user_id' => $userId]);
    return $statement->fetchAll();
}

function create_order(int $userId, int $serviceTypeId, string $vehicleName, string $vehiclePlate, string $notes): void
{
    $statement = db()->prepare(
        'INSERT INTO service_orders (user_id, service_type_id, vehicle_name, vehicle_plate, notes)
         VALUES (:user_id, :service_type_id, :vehicle_name, :vehicle_plate, :notes)'
    );
    $statement->execute([
        'user_id'         => $userId,
        'service_type_id' => $serviceTypeId,
        'vehicle_name'    => $vehicleName,
        'vehicle_plate'   => $vehiclePlate,
        'notes'           => $notes,
    ]);
}

function update_order_status(int $id, string $status): bool
{
    $allowed = ['pending', 'proses', 'selesai'];
    if (!in_array($status, $allowed, true)) {
        return false;
    }

    $statement = db()->prepare(
        'UPDATE service_orders SET status = :status WHERE id = :id'
    );
    $statement->execute(['status' => $status, 'id' => $id]);

    return $statement->rowCount() > 0;
}

function format_rupiah(float $value): string
{
    return 'Rp ' . number_format($value, 0, ',', '.');
}
