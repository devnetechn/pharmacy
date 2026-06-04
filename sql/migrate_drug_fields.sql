-- sql/migrate_drug_fields.sql
-- One-time upgrade for an EXISTING `drugs` table created before the
-- generic_name / brand_name / dosage_form change.
--
-- Run ONCE per database that still has the old `name` / `unit` columns:
--   mysql -u root pharmacy < sql/migrate_drug_fields.sql
--
-- Skip this on fresh installs (schema.sql / setup.php already create the new
-- columns). Running it twice will error with "Unknown column 'name'" — that
-- error simply means the database is already migrated.

ALTER TABLE drugs
  CHANGE name generic_name VARCHAR(150) NOT NULL,
  ADD COLUMN brand_name VARCHAR(150) NOT NULL DEFAULT '' AFTER generic_name,
  CHANGE unit dosage_form VARCHAR(50) NOT NULL DEFAULT '';
