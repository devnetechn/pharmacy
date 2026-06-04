<?php
// public/drug_save.php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/db.php';
require_login();
check_csrf();

$id = (int)($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$dosage = trim($_POST['dosage'] ?? '');
$description = trim($_POST['description'] ?? '');
$unit = trim($_POST['unit'] ?? '');
$reorder = max(0, (int)($_POST['reorder_level'] ?? 0));

if ($name === '') { http_response_code(400); die('Drug name is required.'); }

if ($id > 0) {
    $stmt = $pdo->prepare('UPDATE drugs SET name=?, dosage=?, description=?, unit=?, reorder_level=? WHERE id=?');
    $stmt->execute([$name, $dosage, $description, $unit, $reorder, $id]);
} else {
    $stmt = $pdo->prepare('INSERT INTO drugs (name, dosage, description, unit, reorder_level) VALUES (?,?,?,?,?)');
    $stmt->execute([$name, $dosage, $description, $unit, $reorder]);
}
header('Location: drugs.php');
exit;
