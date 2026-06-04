<?php
// public/dashboard.php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/stock.php';
require_login();

// all drugs with current balance
$drugs = $pdo->query('SELECT * FROM drugs ORDER BY name')->fetchAll();
$low = [];
foreach ($drugs as $d) {
    $bal = current_balance($pdo, (int)$d['id']);
    if ($bal <= (int)$d['reorder_level']) {
        $low[] = ['label' => drug_label($d), 'balance' => $bal, 'reorder' => (int)$d['reorder_level']];
    }
}

// near-expiry: received_in batches expiring within 30 days
$expStmt = $pdo->query(
    "SELECT d.generic_name, d.brand_name, d.dosage_form, d.dosage, t.expiry_date, t.quantity
     FROM transactions t JOIN drugs d ON d.id = t.drug_id
     WHERE t.type='received_in' AND t.expiry_date IS NOT NULL
       AND t.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
     ORDER BY t.expiry_date ASC"
);
$expiring = $expStmt->fetchAll();

$totalDrugs = count($drugs);
$pageTitle = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<h1>Dashboard</h1>
<p>Total drugs tracked: <strong><?= $totalDrugs ?></strong></p>

<h2>Low-Stock Alerts</h2>
<?php if ($low): ?>
  <?php foreach ($low as $l): ?>
    <div class="alert danger"><?= htmlspecialchars($l['label']) ?>: balance <?= $l['balance'] ?> (reorder at <?= $l['reorder'] ?>)</div>
  <?php endforeach; ?>
<?php else: ?><p>No low-stock items. &#9989;</p><?php endif; ?>

<h2>Near-Expiry (within 30 days)</h2>
<?php if ($expiring): ?>
  <?php foreach ($expiring as $e): ?>
    <div class="alert"><?= htmlspecialchars(drug_label($e)) ?> &mdash; expires <?= htmlspecialchars($e['expiry_date']) ?> (qty <?= (int)$e['quantity'] ?>)</div>
  <?php endforeach; ?>
<?php else: ?><p>No items expiring soon. &#9989;</p><?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
