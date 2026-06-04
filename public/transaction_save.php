<?php
// public/transaction_save.php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/stock.php';
require_login();
check_csrf();

$drugId = (int)($_POST['drug_id'] ?? 0);
$type = $_POST['type'] ?? '';
$date = $_POST['date'] ?? '';
$quantity = (int)($_POST['quantity'] ?? 0);
$supplier = trim($_POST['supplier'] ?? '') ?: null;
$cost = ($_POST['cost'] ?? '') !== '' ? (float)$_POST['cost'] : null;
$particulars = trim($_POST['particulars'] ?? '') ?: null;
$remarks = trim($_POST['remarks'] ?? '') ?: null;
$expiry = ($_POST['expiry_date'] ?? '') !== '' ? $_POST['expiry_date'] : null;
$userId = $_SESSION['user_id'];

$back = "stock_card.php?drug_id=$drugId";

// validate drug exists
$chk = $pdo->prepare('SELECT id FROM drugs WHERE id = ?');
$chk->execute([$drugId]);
if (!$chk->fetch()) { http_response_code(404); die('Drug not found.'); }

try {
    $prev = current_balance($pdo, $drugId);
    $newBalance = compute_new_balance($prev, $type, $quantity); // throws on bad input/overdraw
} catch (Exception $e) {
    header('Location: ' . $back . '&err=' . urlencode($e->getMessage()));
    exit;
}

$stmt = $pdo->prepare(
    'INSERT INTO transactions
     (drug_id, type, date, supplier, cost, quantity, particulars, balance, remarks, expiry_date, user_id)
     VALUES (?,?,?,?,?,?,?,?,?,?,?)'
);
$stmt->execute([$drugId, $type, $date, $supplier, $cost, $quantity, $particulars, $newBalance, $remarks, $expiry, $userId]);
header('Location: ' . $back);
exit;
