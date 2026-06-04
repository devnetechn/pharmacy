<?php
// public/stock_card.php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/stock.php';
require_login();

$drugId = (int)($_GET['drug_id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM drugs WHERE id = ?');
$stmt->execute([$drugId]);
$drug = $stmt->fetch();
if (!$drug) { http_response_code(404); die('Drug not found.'); }

// optional date filter
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$sql = 'SELECT t.*, u.full_name FROM transactions t JOIN users u ON u.id=t.user_id WHERE t.drug_id = ?';
$params = [$drugId];
if ($from !== '') { $sql .= ' AND t.date >= ?'; $params[] = $from; }
if ($to !== '')   { $sql .= ' AND t.date <= ?'; $params[] = $to; }
$sql .= ' ORDER BY t.id ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
$balance = current_balance($pdo, $drugId);

$flash = $_GET['err'] ?? '';
$pageTitle = 'Stock Card — ' . $drug['generic_name'];
require __DIR__ . '/../includes/header.php';
?>
<h1>Stock Card: <?= htmlspecialchars(drug_label($drug)) ?></h1>
<p>Dosage Form: <?= htmlspecialchars($drug['dosage_form'] ?: '—') ?> |
   Dosage: <?= htmlspecialchars($drug['dosage'] ?: '—') ?> |
   Description: <?= htmlspecialchars($drug['description'] ?: '—') ?> |
   <strong>Current balance: <?= $balance ?></strong></p>
<?php if ($flash): ?><div class="alert danger"><?= htmlspecialchars($flash) ?></div><?php endif; ?>

<form class="inline-form no-print" method="post" action="transaction_save.php">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <input type="hidden" name="drug_id" value="<?= $drugId ?>">
  <label>Type
    <select name="type" id="type" onchange="toggleFields()">
      <option value="received_in">Received-In (+)</option>
      <option value="given_out">Given-Out (&minus;)</option>
    </select>
  </label>
  <label>Date <input type="date" name="date" required value="<?= date('Y-m-d') ?>"></label>
  <label>Quantity <input type="number" name="quantity" min="1" required></label>
  <label class="rcv">Supplier <input name="supplier"></label>
  <label class="rcv">Cost <input type="number" step="0.01" name="cost"></label>
  <label class="rcv">Expiry <input type="date" name="expiry_date"></label>
  <label>Particulars <input name="particulars"></label>
  <label>Remarks <input name="remarks"></label>
  <label><button type="submit">Add Entry</button></label>
</form>
<script>
function toggleFields(){
  var rcv = document.getElementById('type').value === 'received_in';
  document.querySelectorAll('.rcv').forEach(function(e){ e.style.display = rcv ? '' : 'none'; });
}
toggleFields();
</script>

<table>
  <thead>
  <tr>
    <th colspan="3">RECEIVED-IN</th><th rowspan="2">Date</th><th rowspan="2">Particulars</th>
    <th colspan="2">GIVEN-OUT</th><th rowspan="2">Remarks</th><th rowspan="2">By</th>
  </tr>
  <tr><th>Supplier</th><th>Cost</th><th>Qty</th><th>Qty</th><th>Balance</th></tr>
  </thead>
  <tbody>
  <?php foreach ($rows as $r): $rec = $r['type']==='received_in'; ?>
  <tr>
    <td><?= $rec ? htmlspecialchars($r['supplier'] ?? '') : '' ?></td>
    <td><?= $rec && $r['cost']!==null ? number_format((float)$r['cost'],2) : '' ?></td>
    <td class="plus"><?= $rec ? (int)$r['quantity'] : '' ?></td>
    <td><?= htmlspecialchars($r['date']) ?></td>
    <td><?= htmlspecialchars($r['particulars'] ?? '') ?></td>
    <td class="minus"><?= !$rec ? (int)$r['quantity'] : '' ?></td>
    <td><strong><?= (int)$r['balance'] ?></strong></td>
    <td><?= htmlspecialchars($r['remarks'] ?? '') ?></td>
    <td><?= htmlspecialchars($r['full_name']) ?></td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="9">No transactions yet.</td></tr><?php endif; ?>
  </tbody>
</table>
<a class="btn secondary no-print" href="drugs.php">&larr; Back to Drugs</a>
<?php require __DIR__ . '/../includes/footer.php'; ?>
