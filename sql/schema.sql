-- sql/schema.sql
CREATE DATABASE IF NOT EXISTS pharmacy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pharmacy;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  full_name VARCHAR(100) NOT NULL,
  role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

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

CREATE TABLE IF NOT EXISTS transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  drug_id INT NOT NULL,
  type ENUM('received_in','given_out') NOT NULL,
  date DATE NOT NULL,
  supplier VARCHAR(150) NULL,
  cost DECIMAL(10,2) NULL,
  quantity INT NOT NULL,
  particulars VARCHAR(255) NULL,
  balance INT NOT NULL,
  remarks VARCHAR(255) NULL,
  expiry_date DATE NULL,
  user_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (drug_id) REFERENCES drugs(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Seed admin user: username "admin", password "admin123"
-- NOTE: run setup.php once (or the UPDATE printed by it) to set a valid password hash.
INSERT INTO users (username, password, full_name, role)
SELECT 'admin', 'PLACEHOLDER_REPLACED_BY_SETUP', 'Administrator', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='admin');
