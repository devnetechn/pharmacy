<?php
// public/reports.php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/stock.php';
require_login();

// --- selection ---
$all = ($_GET['all'] ?? '') === '1';
$selectedIds = array_values(array_filter(array_map('intval', (array)($_GET['drug_ids'] ?? []))));
// backward-compat: a single ?drug_id= still works
if (!$selectedIds && isset($_GET['drug_id']) && (int)$_GET['drug_id'] > 0) {
    $selectedIds = [(int)$_GET['drug_id']];
}
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$export = ($_GET['export'] ?? '') === 'csv';

// all drugs (for the picker)
$allDrugs = $pdo->query('SELECT id, generic_name, brand_name, dosage_form, dosage FROM drugs ORDER BY generic_name')->fetchAll();

// resolve which drugs to report on (full rows, incl. dosage_form/description)
if ($all) {
    $reportDrugs = $pdo->query('SELECT * FROM drugs ORDER BY generic_name')->fetchAll();
} elseif ($selectedIds) {
    $place = implode(',', array_fill(0, count($selectedIds), '?'));
    $stmt = $pdo->prepare("SELECT * FROM drugs WHERE id IN ($place) ORDER BY generic_name");
    $stmt->execute($selectedIds);
    $reportDrugs = $stmt->fetchAll();
} else {
    $reportDrugs = [];
}

// fetch transactions for one drug, honoring the date filter
function fetch_tx(PDO $pdo, int $drugId, string $from, string $to): array {
    $sql = 'SELECT t.*, u.full_name FROM transactions t JOIN users u ON u.id=t.user_id WHERE t.drug_id=?';
    $params = [$drugId];
    if ($from !== '') { $sql .= ' AND t.date >= ?'; $params[] = $from; }
    if ($to !== '')   { $sql .= ' AND t.date <= ?'; $params[] = $to; }
    $sql .= ' ORDER BY t.id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// --- CSV export (one file, Drug column prepended) ---
if ($export && $reportDrugs) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="stockcard_report.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Generic','Brand','Dosage Form','Dosage','Date','Type','Supplier','Cost','Quantity','Particulars','Balance','Remarks','By']);
    foreach ($reportDrugs as $drug) {
        foreach (fetch_tx($pdo, (int)$drug['id'], $from, $to) as $r) {
            fputcsv($out, [
                $drug['generic_name'], $drug['brand_name'], $drug['dosage_form'], $drug['dosage'],
                $r['date'], $r['type'], $r['supplier'], $r['cost'], $r['quantity'],
                $r['particulars'], $r['balance'], $r['remarks'], $r['full_name'],
            ]);
        }
    }
    fclose($out);
    exit;
}

$pageTitle = 'Reports';
require __DIR__ . '/../includes/header.php';
?>
<h1>Reports</h1>
<form method="get" class="inline-form no-print">
  <label style="grid-column:1/-1; flex-direction:row; align-items:center; gap:8px;">
    <input type="checkbox" name="all" value="1" id="all-chk" <?= $all ? 'checked' : '' ?> onchange="toggleAll()">
    <strong>Print ALL drugs</strong>
  </label>

  <fieldset class="drug-picker" id="drug-picker">
    <legend>Or select drug(s):</legend>
    <?php foreach ($allDrugs as $d): $checked = in_array((int)$d['id'], $selectedIds, true); ?>
      <label class="chk">
        <input type="checkbox" name="drug_ids[]" value="<?= (int)$d['id'] ?>" <?= $checked ? 'checked' : '' ?>>
        <?= htmlspecialchars(drug_label($d)) ?>
      </label>
    <?php endforeach; ?>
    <?php if (!$allDrugs): ?><span>No drugs yet.</span><?php endif; ?>
  </fieldset>

  <label>From <input type="date" name="from" value="<?= htmlspecialchars($from) ?>"></label>
  <label>To <input type="date" name="to" value="<?= htmlspecialchars($to) ?>"></label>
  <label><button type="submit">View</button></label>
</form>
<script>
function toggleAll(){
  var on = document.getElementById('all-chk').checked;
  document.querySelectorAll('#drug-picker input[type=checkbox]').forEach(function(c){
    c.disabled = on; if (on) c.checked = false;
  });
  document.getElementById('drug-picker').style.opacity = on ? '0.5' : '1';
}
toggleAll();
</script>

<?php if ($reportDrugs): ?>
  <div class="no-print" style="margin:10px 0;">
    <button onclick="window.print()">&#128424; Print</button>
    <?php
      // build a query string that preserves the current selection for the CSV link
      $q = ['export' => 'csv', 'from' => $from, 'to' => $to];
      if ($all) { $q['all'] = '1'; }
      $csvHref = 'reports.php?' . http_build_query($q);
      foreach ($selectedIds as $sid) { if (!$all) $csvHref .= '&drug_ids[]=' . $sid; }
    ?>
    <a class="btn secondary" href="<?= htmlspecialchars($csvHref) ?>">Export CSV</a>
  </div>

  <div class="print-header">
    <div class="hosp">SOCSARGEN COUNTY HOSPITAL</div>
    <div class="dept">PHARMACY</div>
    <div class="doc">STOCK CARD REPORT<?= $all ? ' — ALL DRUGS' : (count($reportDrugs) > 1 ? ' — ' . count($reportDrugs) . ' DRUGS' : '') ?></div>
    <div class="printed">Printed: <?= date('F j, Y g:i A') ?></div>
  </div>
  <p class="period-line">Period: <?= htmlspecialchars($from ?: 'start') ?> to <?= htmlspecialchars($to ?: 'now') ?></p>

  <?php foreach ($reportDrugs as $i => $drug): $rows = fetch_tx($pdo, (int)$drug['id'], $from, $to); ?>
    <section class="drug-section"<?= $i > 0 ? ' style="page-break-before:always;"' : '' ?>>
      <h2>Drug: <?= htmlspecialchars(drug_label($drug)) ?></h2>
      <p>Dosage Form: <?= htmlspecialchars($drug['dosage_form'] ?: '—') ?> |
         Dosage: <?= htmlspecialchars($drug['dosage'] ?: '—') ?> |
         Description: <?= htmlspecialchars($drug['description'] ?: '—') ?></p>
      <table>
        <tr><th>Date</th><th>Type</th><th>Supplier</th><th>Cost</th><th>Qty</th><th>Particulars</th><th>Balance</th><th>Remarks</th><th>By</th></tr>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['date']) ?></td>
          <td><?= $r['type']==='received_in' ? '<span class="plus">IN</span>' : '<span class="minus">OUT</span>' ?></td>
          <td><?= htmlspecialchars($r['supplier'] ?? '') ?></td>
          <td><?= $r['cost']!==null ? number_format((float)$r['cost'],2) : '' ?></td>
          <td><?= (int)$r['quantity'] ?></td>
          <td><?= htmlspecialchars($r['particulars'] ?? '') ?></td>
          <td><strong><?= (int)$r['balance'] ?></strong></td>
          <td><?= htmlspecialchars($r['remarks'] ?? '') ?></td>
          <td><?= htmlspecialchars($r['full_name']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="9">No transactions in range.</td></tr><?php endif; ?>
      </table>
    </section>
  <?php endforeach; ?>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
