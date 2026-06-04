<?php
// public/user_save.php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/db.php';
require_admin();
check_csrf();

$username = trim($_POST['username'] ?? '');
$fullName = trim($_POST['full_name'] ?? '');
$password = $_POST['password'] ?? '';
$role = ($_POST['role'] ?? 'staff') === 'admin' ? 'admin' : 'staff';

if ($username === '' || $fullName === '' || $password === '') {
    http_response_code(400); die('All fields are required.');
}
$hash = password_hash($password, PASSWORD_DEFAULT);
try {
    $stmt = $pdo->prepare('INSERT INTO users (username, password, full_name, role) VALUES (?,?,?,?)');
    $stmt->execute([$username, $hash, $fullName, $role]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') { http_response_code(400); die('Username already exists.'); }
    throw $e;
}
header('Location: users.php');
exit;
