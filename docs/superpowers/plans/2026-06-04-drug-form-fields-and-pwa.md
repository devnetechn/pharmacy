# Drug Form Fields + PWA Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the single drug `name`/`unit` fields with Generic Name, Brand Name, and a Dosage Form dropdown (plus required Dosage), and make the web app installable as a desktop PWA.

**Architecture:** PHP + PDO + MySQL app served from XAMPP (`localhost/pharmacy`). Pages in `public/`, shared helpers in `includes/`. The `drugs` table is renamed/extended; every page that displayed a drug name now uses a shared `drug_label()` helper. A `manifest.json` + minimal service worker make the existing site installable. No backend architecture change.

**Tech Stack:** PHP 8 (XAMPP), MySQL/MariaDB, PDO, vanilla JS, PWA (web manifest + service worker).

**Testing note:** This codebase has no automated test framework, and adding one for this change would be out of scope (YAGNI). Verification is manual: SQL checks via the MySQL CLI and browser checks. Each task ends with explicit verification + a commit.

**MySQL CLI:** `C:/xampp/mysql/bin/mysql.exe -u root pharmacy`

---

## File Structure

- `sql/schema.sql` — fresh-install schema (modify `drugs` table).
- `public/setup.php` — fresh-install setup (modify `drugs` CREATE TABLE).
- `includes/stock.php` — add `drug_label()` helper.
- `public/drugs.php` — new form fields, dropdown, table columns, search.
- `public/drug_save.php` — validation + INSERT/UPDATE for new columns.
- `public/stock_card.php` — header/info-line display.
- `public/reports.php` — picker, headings, info line, CSV.
- `public/dashboard.php` — low-stock + expiring alert labels.
- `public/manifest.json` — NEW, PWA manifest.
- `public/sw.js` — NEW, minimal service worker.
- `public/icon-192.png`, `public/icon-512.png` — NEW, app icons.
- `includes/header.php` — manifest link + theme-color + SW registration.

---

## Task 1: Database migration

**Files:**
- Modify (live DB via CLI)
- Modify: `sql/schema.sql:5-12`
- Modify: `public/setup.php:22-29`

- [ ] **Step 1: Inspect current drugs table**

Run: `C:/xampp/mysql/bin/mysql.exe -u root pharmacy -e "DESCRIBE drugs;"`
Expected: columns `id, name, dosage, description, unit, reorder_level, created_at`.

- [ ] **Step 2: Run the migration**

Run:
```
C:/xampp/mysql/bin/mysql.exe -u root pharmacy -e "ALTER TABLE drugs CHANGE name generic_name VARCHAR(150) NOT NULL; ALTER TABLE drugs ADD COLUMN brand_name VARCHAR(150) NOT NULL DEFAULT '' AFTER generic_name; ALTER TABLE drugs CHANGE unit dosage_form VARCHAR(50) NOT NULL DEFAULT '';"
```
Expected: no error.

- [ ] **Step 3: Verify migration**

Run: `C:/xampp/mysql/bin/mysql.exe -u root pharmacy -e "DESCRIBE drugs; SELECT id, generic_name, brand_name, dosage_form, dosage FROM drugs;"`
Expected: columns now `id, generic_name, brand_name, dosage, description, dosage_form, reorder_level, created_at`; the 3 existing rows show old `name` in `generic_name`, old `unit` in `dosage_form`, empty `brand_name`.

- [ ] **Step 4: Update `sql/schema.sql` drugs table**

Replace the `drugs` CREATE TABLE block (lines 14-22) so it reads:
```sql
CREATE TABLE IF NOT EXISTS drugs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  generic_name VARCHAR(150) NOT NULL,
  brand_name VARCHAR(150) NOT NULL DEFAULT '',
  dosage VARCHAR(50) NULL,
  description VARCHAR(255) NULL,
  dosage_form VARCHAR(50) NOT NULL DEFAULT '',
  reorder_level INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

- [ ] **Step 5: Update `public/setup.php` drugs table**

Replace the `drugs` CREATE TABLE in `setup.php` (lines 30-38) with the same column definitions as Step 4 (matching the `$pdo->exec("CREATE TABLE IF NOT EXISTS drugs ( ... )")` style).

- [ ] **Step 6: Commit**

```bash
git add sql/schema.sql public/setup.php
git commit -m "feat(db): drugs table generic_name/brand_name/dosage_form"
```

---

## Task 2: drug_label() helper

**Files:**
- Modify: `includes/stock.php` (append function)

- [ ] **Step 1: Add the helper**

Append to `includes/stock.php`:
```php
/**
 * Human-readable drug label: "Generic (Brand) — Form Dosage".
 * Returns RAW text — callers must htmlspecialchars() it.
 * Brand / form / dosage are omitted when empty.
 */
function drug_label(array $d): string {
    $label = (string)($d['generic_name'] ?? '');
    if (!empty($d['brand_name'])) {
        $label .= ' (' . $d['brand_name'] . ')';
    }
    $tail = trim(((string)($d['dosage_form'] ?? '')) . ' ' . ((string)($d['dosage'] ?? '')));
    if ($tail !== '') {
        $label .= ' — ' . $tail;
    }
    return $label;
}
```

- [ ] **Step 2: Verify it parses**

Run: `php -l includes/stock.php`
Expected: `No syntax errors detected in includes/stock.php`.

- [ ] **Step 3: Commit**

```bash
git add includes/stock.php
git commit -m "feat: add drug_label() display helper"
```

---

## Task 3: drug_save.php validation + persistence

**Files:**
- Modify: `public/drug_save.php`

- [ ] **Step 1: Rewrite the body**

Replace `public/drug_save.php` (after the requires/`check_csrf()`) with:
```php
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
```

- [ ] **Step 2: Verify it parses**

Run: `php -l public/drug_save.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add public/drug_save.php
git commit -m "feat: validate + save new drug fields"
```

(Browser verification happens at the end of Task 4 once the form exists.)

---

## Task 4: drugs.php — form, dropdown, table, search

**Files:**
- Modify: `public/drugs.php`

- [ ] **Step 1: Update the search query (lines 9-12)**

Replace the search branch so it matches the new columns:
```php
if ($search !== '') {
    $stmt = $pdo->prepare('SELECT * FROM drugs WHERE generic_name LIKE ? OR brand_name LIKE ? OR description LIKE ? ORDER BY generic_name');
    $like = "%$search%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query('SELECT * FROM drugs ORDER BY generic_name');
}
```

- [ ] **Step 2: Define the dosage-form list near the top (after `$drugs = $stmt->fetchAll();`)**

```php
$dosageForms = [
    'Tablet','Capsule','Syrup','Suspension','Ampule','Vial','Injection','Solution (IV)',
    'Ointment','Cream','Gel','Lotion','Drops (eye/ear)','Inhaler','Spray','Nebule',
    'Suppository','Powder','Sachet','Lozenge','Patch','Granules',
];
```

- [ ] **Step 3: Replace the Add-Drug form (lines 28-47)**

```php
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
```

- [ ] **Step 4: Replace the table (lines 49-64)**

```php
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
```

- [ ] **Step 5: Verify it parses**

Run: `php -l public/drugs.php`
Expected: `No syntax errors detected`.

- [ ] **Step 6: Browser verification**

Open `http://localhost/pharmacy/public/drugs.php` (login admin/admin123 if needed).
- Confirm the table shows the 3 existing drugs with Generic/Brand/Dosage Form columns.
- Add a drug: Generic `Paracetamol`, Brand `Biogesic`, Dosage Form `Tablet`, Dosage `500mg`. Confirm it appears.
- Add a drug with Dosage Form = `Others...`, type `Implant`. Confirm the free-text shows and saves (check the table row shows `Implant`).
- Submit with a blank Generic Name → expect the page to reject with `Required: Generic Name`.
- Search `Bio` → confirm Biogesic row returns.

- [ ] **Step 7: Commit**

```bash
git add public/drugs.php
git commit -m "feat: drug form generic/brand/dosage-form dropdown + table"
```

---

## Task 5: stock_card.php display

**Files:**
- Modify: `public/stock_card.php:28,31-34`

- [ ] **Step 1: Update page title (line 28)**

```php
$pageTitle = 'Stock Card — ' . $drug['generic_name'];
```

- [ ] **Step 2: Update heading + info line (lines 31-35)**

```php
<h1>Stock Card: <?= htmlspecialchars(drug_label($drug)) ?></h1>
<p>Dosage Form: <?= htmlspecialchars($drug['dosage_form'] ?: '—') ?> |
   Dosage: <?= htmlspecialchars($drug['dosage'] ?: '—') ?> |
   Description: <?= htmlspecialchars($drug['description'] ?: '—') ?> |
   <strong>Current balance: <?= $balance ?></strong></p>
```

- [ ] **Step 3: Verify + browser check**

Run: `php -l public/stock_card.php` → `No syntax errors detected`.
Open a stock card from drugs.php → confirm heading shows `Generic (Brand) — Form Dosage` and the info line shows Dosage Form correctly.

- [ ] **Step 4: Commit**

```bash
git add public/stock_card.php
git commit -m "feat: stock card uses new drug fields"
```

---

## Task 6: reports.php display + CSV

**Files:**
- Modify: `public/reports.php:19,50,54,79,123-126`

- [ ] **Step 1: Picker query (line 19)**

```php
$allDrugs = $pdo->query('SELECT id, generic_name, brand_name, dosage_form, dosage FROM drugs ORDER BY generic_name')->fetchAll();
```

- [ ] **Step 2: Report-drug queries ordering (lines 23, 26)**

Change both `ORDER BY name` to `ORDER BY generic_name` (in the `$all` query and the `IN (...)` prepared query).

- [ ] **Step 3: CSV header (line 50)**

```php
fputcsv($out, ['Generic','Brand','Dosage Form','Dosage','Date','Type','Supplier','Cost','Quantity','Particulars','Balance','Remarks','By']);
```

- [ ] **Step 4: CSV row (lines 53-57)**

```php
fputcsv($out, [
    $drug['generic_name'], $drug['brand_name'], $drug['dosage_form'], $drug['dosage'],
    $r['date'], $r['type'], $r['supplier'], $r['cost'], $r['quantity'],
    $r['particulars'], $r['balance'], $r['remarks'], $r['full_name'],
]);
```

- [ ] **Step 5: Picker label (line 79)**

```php
<?= htmlspecialchars(drug_label($d)) ?>
```

- [ ] **Step 6: Report section heading + info line (lines 123-126)**

```php
<h2>Drug: <?= htmlspecialchars(drug_label($drug)) ?></h2>
<p>Dosage Form: <?= htmlspecialchars($drug['dosage_form'] ?: '—') ?> |
   Dosage: <?= htmlspecialchars($drug['dosage'] ?: '—') ?> |
   Description: <?= htmlspecialchars($drug['description'] ?: '—') ?></p>
```

- [ ] **Step 7: Verify + browser check**

Run: `php -l public/reports.php` → `No syntax errors detected`.
Open `reports.php`: picker labels show `Generic (Brand) — Form Dosage`; pick a drug, View → heading + info line correct. Click Export CSV → open file, confirm header row is `Generic,Brand,Dosage Form,Dosage,...` and data rows populate.

- [ ] **Step 8: Commit**

```bash
git add public/reports.php
git commit -m "feat: reports + CSV use new drug fields"
```

---

## Task 7: dashboard.php display

**Files:**
- Modify: `public/dashboard.php:14,19-25,38,44-45`

- [ ] **Step 1: Low-stock label (line 14)**

`$drugs` already selects `*`, so build the label from the row:
```php
$low[] = ['label' => drug_label($d), 'balance' => $bal, 'reorder' => (int)$d['reorder_level']];
```

- [ ] **Step 2: Low-stock render (line 38)**

```php
<div class="alert danger"><?= htmlspecialchars($l['label']) ?>: balance <?= $l['balance'] ?> (reorder at <?= $l['reorder'] ?>)</div>
```

- [ ] **Step 3: Expiring query (lines 19-25)**

```php
$expStmt = $pdo->query(
    "SELECT d.generic_name, d.brand_name, d.dosage_form, d.dosage, t.expiry_date, t.quantity
     FROM transactions t JOIN drugs d ON d.id = t.drug_id
     WHERE t.type='received_in' AND t.expiry_date IS NOT NULL
       AND t.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
     ORDER BY t.expiry_date ASC"
);
```

- [ ] **Step 4: Expiring render (lines 44-45)**

```php
<div class="alert"><?= htmlspecialchars(drug_label($e)) ?> &mdash; expires <?= htmlspecialchars($e['expiry_date']) ?> (qty <?= (int)$e['quantity'] ?>)</div>
```

- [ ] **Step 5: Verify + browser check**

Run: `php -l public/dashboard.php` → `No syntax errors detected`.
Open `dashboard.php` → low-stock and (if any) expiring alerts show the new drug label without PHP warnings.

- [ ] **Step 6: Commit**

```bash
git add public/dashboard.php
git commit -m "feat: dashboard alerts use new drug fields"
```

---

## Task 8: PWA — installable desktop app

**Files:**
- Create: `public/manifest.json`
- Create: `public/sw.js`
- Create: `public/icon-192.png`, `public/icon-512.png`
- Modify: `includes/header.php`

- [ ] **Step 1: Create `public/manifest.json`**

```json
{
  "name": "SOCSARGEN County Hospital — Pharmacy",
  "short_name": "Pharmacy",
  "start_url": "dashboard.php",
  "scope": "./",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#0d6efd",
  "icons": [
    { "src": "icon-192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "icon-512.png", "sizes": "512x512", "type": "image/png" }
  ]
}
```

- [ ] **Step 2: Create `public/sw.js` (minimal pass-through)**

```js
// Minimal service worker — required for installability. No offline caching.
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (e) => e.waitUntil(self.clients.claim()));
self.addEventListener('fetch', () => {}); // pass-through (browser handles the request)
```

- [ ] **Step 3: Generate the two PNG icons**

Run (PowerShell, uses GD via PHP to draw a simple blue square with "Rx"):
```
php -r "foreach([192,512] as \$s){\$im=imagecreatetruecolor(\$s,\$s);\$bg=imagecolorallocate(\$im,13,110,253);\$fg=imagecolorallocate(\$im,255,255,255);imagefilledrectangle(\$im,0,0,\$s,\$s,\$bg);\$fs=(int)(\$s*0.45);\$tb=imagettfbbox(\$fs,0,'C:/Windows/Fonts/arialbd.ttf','Rx');\$tw=\$tb[2]-\$tb[0];\$th=\$tb[1]-\$tb[7];imagettftext(\$im,\$fs,0,(int)((\$s-\$tw)/2),(int)((\$s+\$th)/2),\$fg,'C:/Windows/Fonts/arialbd.ttf','Rx');imagepng(\$im,\"public/icon-\$s.png\");}"
```
Expected: creates `public/icon-192.png` and `public/icon-512.png`.

Run: `dir public\icon-*.png` (PowerShell) → both files exist and are non-zero.

If PHP GD/freetype is unavailable (error), fall back to drawing without text:
```
php -r "foreach([192,512] as \$s){\$im=imagecreatetruecolor(\$s,\$s);\$bg=imagecolorallocate(\$im,13,110,253);imagefilledrectangle(\$im,0,0,\$s,\$s,\$bg);imagepng(\$im,\"public/icon-\$s.png\");}"
```

- [ ] **Step 4: Wire up `includes/header.php`**

In the `<head>` (after the stylesheet link, line 12), add:
```html
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#0d6efd">
```
Just before `</body>`-bound content is not available here; instead add the SW registration right after the stylesheet/manifest block, still inside `<head>`:
```html
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', function () {
        navigator.serviceWorker.register('sw.js').catch(function(){});
      });
    }
  </script>
```

Note: header.php is included by pages in `public/`, so the relative paths `manifest.json` and `sw.js` resolve correctly against `/pharmacy/public/`.

- [ ] **Step 5: Verify + browser check**

Run: `php -l includes/header.php` → `No syntax errors detected`.
Open `http://localhost/pharmacy/public/dashboard.php` in Chrome/Edge:
- DevTools → Application → Manifest: confirm name/icons load with no errors.
- DevTools → Application → Service Workers: confirm `sw.js` is activated.
- Confirm an Install icon appears in the address bar; install it and confirm it opens standalone (no address bar) on `dashboard.php`.

- [ ] **Step 6: Commit**

```bash
git add public/manifest.json public/sw.js public/icon-192.png public/icon-512.png includes/header.php
git commit -m "feat: PWA manifest + service worker for desktop install"
```

---

## Final verification

- [ ] All 8 tasks committed.
- [ ] `php -l` clean on every modified PHP file.
- [ ] Full click-through: dashboard → drugs (add/search) → stock card → reports (view + CSV) all show the new fields.
- [ ] App installs and opens standalone in Chrome/Edge.
