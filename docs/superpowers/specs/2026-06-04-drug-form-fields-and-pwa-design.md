# Drug Form Enhancement + PWA — Design

**Date:** 2026-06-04
**Status:** Approved

## Goal

1. Restructure the drug record so each drug captures **Generic Name**, **Brand Name**,
   **Dosage Form** (a fixed dropdown), **Dosage**, and **Description**.
2. Make the existing web app installable as a desktop "app" on the PC via a PWA
   (Progressive Web App) — a desktop shortcut that opens standalone, no browser chrome.
   No backend/architecture change; still served from `localhost/pharmacy`.

## 1. Database changes — table `drugs`

| Current column | New column | Notes |
|---|---|---|
| `name` (VARCHAR 150) | `generic_name` (VARCHAR 150) | rename; **required** |
| *(none)* | `brand_name` (VARCHAR 150) | new; **required** |
| `unit` (VARCHAR 50) | `dosage_form` (VARCHAR 50) | rename; **required**; chosen from dropdown |
| `dosage` (VARCHAR 50) | `dosage` | unchanged; now **required** |
| `description` (VARCHAR 255) | `description` | unchanged; optional |
| `reorder_level` (INT) | `reorder_level` | unchanged |
| `id`, `created_at` | same | unchanged |

### Migration of existing data
3 existing rows are test data. Migration:
- `name` value moves to `generic_name`.
- `unit` value moves to `dosage_form` (values may not match the dropdown list — left as-is;
  user can re-edit via the form).
- `brand_name` set to empty string for existing rows (user edits later).

### Migration approach
- Run direct `ALTER TABLE` statements against the live `pharmacy` DB:
  - `ALTER TABLE drugs CHANGE name generic_name VARCHAR(150) NOT NULL;`
  - `ALTER TABLE drugs ADD COLUMN brand_name VARCHAR(150) NOT NULL DEFAULT '' AFTER generic_name;`
  - `ALTER TABLE drugs CHANGE unit dosage_form VARCHAR(50) NOT NULL DEFAULT '';`
- Update `sql/schema.sql` and `public/setup.php` so fresh installs create the new schema.

## 2. Dosage Form dropdown

Fixed options (a `<select>`), plus an **"Others"** choice that reveals a free-text input:

```
Tablet, Capsule, Syrup, Suspension, Ampule, Vial, Injection, Solution (IV),
Ointment, Cream, Gel, Lotion, Drops (eye/ear), Inhaler, Spray, Nebule,
Suppository, Powder, Sachet, Lozenge, Patch, Granules
```

The option list is defined once in `drugs.php` (a PHP array) so it is easy to extend later.

## 3. Drug form — `public/drugs.php`

New "Add Drug" form field order:
1. **Generic Name** — text, required
2. **Brand Name** — text, required
3. **Dosage Form** — `<select>` (list above + "Others"); selecting "Others" shows a text input
4. **Dosage** — text, required (placeholder e.g. `500mg`, `15mg`)
5. **Description** — text, optional
6. **Reorder level** — number, default 0

Remove the old "toggle dosage" JavaScript (dosage is always required now). Add small JS to
show/hide the "Others" free-text input based on the dropdown selection.

### Validation — `public/drug_save.php`
- Required (reject with HTTP 400 if blank): `generic_name`, `brand_name`, `dosage_form`, `dosage`.
- `description` optional; `reorder_level` clamped to `>= 0`.
- INSERT and UPDATE statements updated to the new columns.
- Search in `drugs.php` updated to match `generic_name`, `brand_name`, or `description`.

## 4. Display updates

The single `name` column is gone, so every place that printed a drug name now renders a
shared label format: **`Generic (Brand) — Dosage Form Dosage`** (Brand/Dosage omitted from the
label only if empty).

Affected files:
- `public/drugs.php` — table columns: Generic, Brand, Dosage Form, Dosage, Description, Balance, Reorder, Status.
- `public/stock_card.php` — page title + heading + the "Dosage / Unit" info line (relabel Unit → Dosage Form).
- `public/reports.php` — drug picker checkboxes, report heading, info line, and CSV export
  header/rows (replace `Drug`/`Unit` columns with Generic, Brand, Dosage Form).
- `public/dashboard.php` — low-stock and expiring-soon alert lines use the new label.

A small shared helper (e.g. `drug_label($drug)`) is added to `includes/stock.php` (already
required where drugs are shown) to avoid repeating the formatting logic.

## 5. PWA — desktop installable shortcut

- `public/manifest.json` — `name`, `short_name`, `start_url` (`dashboard.php`), `display: standalone`,
  `background_color`, `theme_color`, and at least one icon (192px + 512px). Reuse/derive a simple icon.
- `public/sw.js` — minimal service worker (an `install`/`fetch` listener) so the app meets the
  installability bar. No offline caching strategy required beyond the minimum; pass-through fetch is fine.
- `includes/header.php` — add `<link rel="manifest" href="manifest.json">`, a `theme-color` meta tag,
  and a small script that registers `sw.js`.

Result: Chrome/Edge shows an Install button → creates a desktop icon that opens the app
standalone (no address bar). Backend unchanged.

## Out of scope (YAGNI)
- Offline data sync / caching of dynamic pages.
- Real .exe packaging (Electron) or a browser extension.
- Reworking the transactions schema (transactions reference `drug_id`, unaffected).

## Testing
- Run the migration, confirm the 3 existing rows display correctly with the new labels.
- Add a new drug with each required field; confirm validation rejects blanks.
- Add a drug using "Others" dosage form; confirm the free-text value saves.
- Open stock card, reports (web + CSV), and dashboard; confirm labels render and CSV columns are correct.
- In Chrome/Edge, confirm the Install prompt appears and the installed app opens standalone.
