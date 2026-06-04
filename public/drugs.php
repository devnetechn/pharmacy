<?php
// public/drugs.php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/stock.php';
require_login();

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare('SELECT * FROM drugs WHERE name LIKE ? OR description LIKE ? ORDER BY name');
    $like = "%$search%";
    $stmt->execute([$like, $like]);
} else {
    $stmt = $pdo->query('SELECT * FROM drugs ORDER BY name');
}
$drugs = $stmt->fetchAll();
$u = current_user();
$pageTitle = 'Drugs';
require __DIR__ . '/../includes/header.php';
?>
<h1>Drugs</h1>
<form method="get" class="no-print" style="margin-bottom:12px;">
  <input name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search drug name...">
  <button type="submit">Search</button>
  <?php if ($search): ?><a class="btn secondary" href="drugs.php">Clear</a><?php endif; ?>
</form>

<form class="inline-form" method="post" action="drug_save.php">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <label>Name <input name="name" required></label>
  <label>Unit
    <input name="unit" id="unit-input" placeholder="ampule, tablet, vial..." autocomplete="off" oninput="toggleDosage()">
  </label>
  <label id="dosage-wrap" style="display:none;">Dosage / Strength
    <input name="dosage" id="dosage-input" placeholder="e.g. 15mg, 500mg" autocomplete="off">
  </label>
  <label>Description <input name="description"></label>
  <label>Reorder level <input type="number" name="reorder_level" value="0" min="0"></label>
  <label><button type="submit">Add Drug</button></label>
</form>
<script>
function toggleDosage(){
  var unit = document.getElementById('unit-input').value.trim();
  document.getElementById('dosage-wrap').style.display = unit === '' ? 'none' : 'flex';
}
toggleDosage();
</script>

<table>
  <tr><th>Name</th><th>Dosage</th><th>Unit</th><th>Description</th><th>Balance</th><th>Reorder</th><th>Status</th><th></th></tr>
  <?php foreach ($drugs as $d): $bal = current_balance($pdo, (int)$d['id']); ?>
  <tr>
    <td><?= htmlspecialchars($d['name']) ?></td>
    <td><?= htmlspecialchars($d['dosage'] ?? '') ?></td>
    <td><?= htmlspecialchars($d['unit'] ?? '') ?></td>
    <td><?= htmlspecialchars($d['description'] ?? '') ?></td>
    <td><?= $bal ?></td>
    <td><?= (int)$d['reorder_level'] ?></td>
    <td><?= $bal <= (int)$d['reorder_level'] ? '<span class="minus">LOW</span>' : 'OK' ?></td>
    <td><a class="btn" href="stock_card.php?drug_id=<?= (int)$d['id'] ?>">Stock Card</a></td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$drugs): ?><tr><td colspan="8">No drugs found.</td></tr><?php endif; ?>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
