<?php
// public/drugs.php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/stock.php';
require_login();

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare('SELECT * FROM drugs WHERE generic_name LIKE ? OR brand_name LIKE ? OR description LIKE ? ORDER BY generic_name');
    $like = "%$search%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query('SELECT * FROM drugs ORDER BY generic_name');
}
$drugs = $stmt->fetchAll();
$dosageForms = [
    'Tablet','Capsule','Syrup','Suspension','Ampule','Vial','Injection','Solution (IV)',
    'Ointment','Cream','Gel','Lotion','Drops (eye/ear)','Inhaler','Spray','Nebule',
    'Suppository','Powder','Sachet','Lozenge','Patch','Granules',
];
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
  <label>Generic Name <input name="generic_name" required></label>
  <label>Brand Name <input name="brand_name" required></label>
  <label>Dosage Form
    <select name="dosage_form" id="form-select" required onchange="toggleOther()">
      <option value="">-- select --</option>
      <?php foreach ($dosageForms as $f): ?>
        <option value="<?= htmlspecialchars($f) ?>"><?= htmlspecialchars($f) ?></option>
      <?php endforeach; ?>
      <option value="__other__">Others...</option>
    </select>
  </label>
  <label id="form-other-wrap" style="display:none;">Specify form
    <input name="dosage_form_other" id="form-other-input" autocomplete="off">
  </label>
  <label>Dosage <input name="dosage" placeholder="e.g. 500mg, 15mg" required></label>
  <label>Description <input name="description"></label>
  <label>Reorder level <input type="number" name="reorder_level" value="0" min="0"></label>
  <label><button type="submit">Add Drug</button></label>
</form>
<script>
function toggleOther(){
  var other = document.getElementById('form-select').value === '__other__';
  document.getElementById('form-other-wrap').style.display = other ? 'flex' : 'none';
  document.getElementById('form-other-input').required = other;
}
toggleOther();
</script>

<table>
  <tr><th>Generic</th><th>Brand</th><th>Dosage Form</th><th>Dosage</th><th>Description</th><th>Balance</th><th>Reorder</th><th>Status</th><th></th></tr>
  <?php foreach ($drugs as $d): $bal = current_balance($pdo, (int)$d['id']); ?>
  <tr>
    <td><?= htmlspecialchars($d['generic_name']) ?></td>
    <td><?= htmlspecialchars($d['brand_name'] ?? '') ?></td>
    <td><?= htmlspecialchars($d['dosage_form'] ?? '') ?></td>
    <td><?= htmlspecialchars($d['dosage'] ?? '') ?></td>
    <td><?= htmlspecialchars($d['description'] ?? '') ?></td>
    <td><?= $bal ?></td>
    <td><?= (int)$d['reorder_level'] ?></td>
    <td><?= $bal <= (int)$d['reorder_level'] ? '<span class="minus">LOW</span>' : 'OK' ?></td>
    <td><a class="btn" href="stock_card.php?drug_id=<?= (int)$d['id'] ?>">Stock Card</a></td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$drugs): ?><tr><td colspan="9">No drugs found.</td></tr><?php endif; ?>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
