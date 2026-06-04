<?php
// public/setup.php
// One-time setup: creates tables and the default admin account.
// Visit http://localhost/pharmacy/public/setup.php ONCE, then delete this file.

$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'pharmacy';

header('Content-Type: text/plain; charset=utf-8');

try {
    // connect without db, create it
    $pdo = new PDO("mysql:host=$DB_HOST;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$DB_NAME`");
    echo "OK: database '$DB_NAME' ready\n";

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS drugs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        generic_name VARCHAR(150) NOT NULL,
        brand_name VARCHAR(150) NOT NULL DEFAULT '',
        dosage VARCHAR(50) NULL,
        description VARCHAR(255) NULL,
        dosage_form VARCHAR(50) NOT NULL DEFAULT '',
        reorder_level INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        drug_id INT NOT NULL,
        type ENUM('received_in','given_out') NOT NULL,
        date DATE NOT NULL,
        supplier VARCHAR(150) NULL,
        cost DECIMAL(10,2) NULL,
        quantity INT NOT NULL,
        particulars VARCHAR(255) NULL,
        balance INT NOT NULL,
        remarks VARCHAR(255) NULL,
        expiry_date DATE NULL,
        user_id INT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (drug_id) REFERENCES drugs(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    echo "OK: tables created\n";

    // create/refresh admin
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute(['admin']);
    if ($stmt->fetch()) {
        $pdo->prepare('UPDATE users SET password=? WHERE username=?')->execute([$hash, 'admin']);
        echo "OK: admin password reset to 'admin123'\n";
    } else {
        $pdo->prepare('INSERT INTO users (username, password, full_name, role) VALUES (?,?,?,?)')
            ->execute(['admin', $hash, 'Administrator', 'admin']);
        echo "OK: admin user created (admin / admin123)\n";
    }

    echo "\nSETUP COMPLETE.\n";
    echo "Login at: login.php  (username: admin, password: admin123)\n";
    echo "IMPORTANT: delete setup.php after this, and change the admin password.\n";
} catch (PDOException $e) {
    http_response_code(500);
    echo "SETUP FAILED: " . $e->getMessage() . "\n";
    echo "Check that MySQL is running in XAMPP and the root password matches.\n";
}
