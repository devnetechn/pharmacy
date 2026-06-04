<?php
// config/db.php
$DB_HOST = '127.0.0.1';
$DB_NAME = 'pharmacy';
$DB_USER = 'root';
$DB_PASS = ''; // default XAMPP has empty root password

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}
