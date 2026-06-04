<?php
// public/drug_save.php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/db.php';
require_login();
check_csrf();

$id          = (int)($_POST['id'] ?? 0);
$generic     = trim($_POST['generic_name'] ?? '');
$brand       = trim($_POST['brand_name'] ?? '');
$dosageForm  = trim($_POST['dosage_form'] ?? '');
// "Others" reveals a free-text field
if ($dosageForm === '__other__') { $dosageForm = trim($_POST['dosage_form_other'] ?? ''); }
$dosage      = trim($_POST['dosage'] ?? '');
$description = trim($_POST['description'] ?? '');
$reorder     = max(0, (int)($_POST['reorder_level'] ?? 0));

$missing = [];
if ($generic === '')    { $missing[] = 'Generic Name'; }
if ($brand === '')      { $missing[] = 'Brand Name'; }
if ($dosageForm === '') { $missing[] = 'Dosage Form'; }
if ($dosage === '')     { $missing[] = 'Dosage'; }
if ($missing) { http_response_code(400); die('Required: ' . implode(', ', $missing)); }

if ($id > 0) {
    $stmt = $pdo->prepare('UPDATE drugs SET generic_name=?, brand_name=?, dosage=?, description=?, dosage_form=?, reorder_level=? WHERE id=?');
    $stmt->execute([$generic, $brand, $dosage, $description, $dosageForm, $reorder, $id]);
} else {
    $stmt = $pdo->prepare('INSERT INTO drugs (generic_name, brand_name, dosage, description, dosage_form, reorder_level) VALUES (?,?,?,?,?,?)');
    $stmt->execute([$generic, $brand, $dosage, $description, $dosageForm, $reorder]);
}
header('Location: drugs.php');
exit;
