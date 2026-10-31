-- Brightloop shared schema (Participant 1 owns this file; changes go through P1)
CREATE DATABASE IF NOT EXISTS ce1_mansi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ce1_mansi;
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','staff','customer') NOT NULL DEFAULT 'customer',
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  phone VARCHAR(30) NULL,
  address VARCHAR(255) NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role_status (role,status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_resets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_reset_token (token_hash),
  CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  type ENUM('service','goods','software','other') NOT NULL DEFAULT 'service',
  sales_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  cost_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  notes TEXT NULL,
  status ENUM('active','archived') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_products_status (status),
  KEY idx_products_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS product_variants (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  extra_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_variant (product_id,name),
  CONSTRAINT fk_variant_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS taxes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  percentage DECIMAL(5,2) NOT NULL,
  tax_type VARCHAR(30) NOT NULL DEFAULT 'GST',
  applies_to_all TINYINT(1) NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_taxes_active (is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tax_products (
  tax_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (tax_id,product_id),
  CONSTRAINT fk_tp_tax FOREIGN KEY (tax_id) REFERENCES taxes(id) ON DELETE CASCADE,
  CONSTRAINT fk_tp_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS plans (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  price DECIMAL(12,2) NOT NULL,
  billing_frequency ENUM('daily','weekly','monthly','yearly') NOT NULL DEFAULT 'monthly',
  min_quantity INT UNSIGNED NOT NULL DEFAULT 1,
  available_from DATE NULL,
  available_until DATE NULL,
  auto_close TINYINT(1) NOT NULL DEFAULT 0,
  allow_early_close TINYINT(1) NOT NULL DEFAULT 0,
  allow_pause TINYINT(1) NOT NULL DEFAULT 0,
  allow_renewal TINYINT(1) NOT NULL DEFAULT 1,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_plans_status (status),
  CONSTRAINT fk_plan_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS quotation_templates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  plan_id INT UNSIGNED NULL,
  validity_days INT UNSIGNED NOT NULL DEFAULT 30,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_qt_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- extra table (not in the original list): predefined product lines of a template
CREATE TABLE IF NOT EXISTS quotation_template_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  template_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  variant_id INT UNSIGNED NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  CONSTRAINT fk_qti_tpl FOREIGN KEY (template_id) REFERENCES quotation_templates(id) ON DELETE CASCADE,
  CONSTRAINT fk_qti_prod FOREIGN KEY (product_id) REFERENCES products(id),
  CONSTRAINT fk_qti_var FOREIGN KEY (variant_id) REFERENCES product_variants(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS quotations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quotation_no VARCHAR(30) NOT NULL,
  customer_id INT UNSIGNED NOT NULL,
  template_id INT UNSIGNED NULL,
  plan_id INT UNSIGNED NULL,
  validity_days INT UNSIGNED NOT NULL DEFAULT 30,
  expires_on DATE NULL,
  status ENUM('draft','sent','accepted','expired','converted') NOT NULL DEFAULT 'draft',
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  tax_total DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_quotation_no (quotation_no),
  KEY idx_quotation_customer (customer_id,status),
  CONSTRAINT fk_q_customer FOREIGN KEY (customer_id) REFERENCES users(id),
  CONSTRAINT fk_q_template FOREIGN KEY (template_id) REFERENCES quotation_templates(id) ON DELETE SET NULL,
  CONSTRAINT fk_q_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS quotation_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quotation_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  variant_id INT UNSIGNED NULL,
  description VARCHAR(255) NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  unit_price DECIMAL(12,2) NOT NULL,
  tax_id INT UNSIGNED NULL,
  tax_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
  tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  line_amount DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_qi_q FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE,
  CONSTRAINT fk_qi_p FOREIGN KEY (product_id) REFERENCES products(id),
  CONSTRAINT fk_qi_v FOREIGN KEY (variant_id) REFERENCES product_variants(id),
  CONSTRAINT fk_qi_t FOREIGN KEY (tax_id) REFERENCES taxes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS subscriptions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subscription_no VARCHAR(30) NOT NULL,
  customer_id INT UNSIGNED NOT NULL,
  plan_id INT UNSIGNED NOT NULL,
  quotation_id INT UNSIGNED NULL,
  status ENUM('draft','quotation','confirmed','active','closed') NOT NULL DEFAULT 'draft',
  is_paused TINYINT(1) NOT NULL DEFAULT 0,
  start_date DATE NOT NULL,
  expiry_date DATE NULL,
  next_billing_date DATE NULL,
  payment_terms VARCHAR(100) NULL,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  tax_total DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_subscription_no (subscription_no),
  KEY idx_sub_due (status,is_paused,next_billing_date),
  KEY idx_sub_customer (customer_id),
  CONSTRAINT fk_s_customer FOREIGN KEY (customer_id) REFERENCES users(id),
  CONSTRAINT fk_s_plan FOREIGN KEY (plan_id) REFERENCES plans(id),
  CONSTRAINT fk_s_quote FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS subscription_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subscription_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  variant_id INT UNSIGNED NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  unit_price DECIMAL(12,2) NOT NULL,
  tax_id INT UNSIGNED NULL,
  tax_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
  tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  line_amount DECIMAL(12,2) NOT NULL,
  KEY idx_si_sub (subscription_id),
  CONSTRAINT fk_si_s FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE,
  CONSTRAINT fk_si_p FOREIGN KEY (product_id) REFERENCES products(id),
  CONSTRAINT fk_si_v FOREIGN KEY (variant_id) REFERENCES product_variants(id),
  CONSTRAINT fk_si_t FOREIGN KEY (tax_id) REFERENCES taxes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS discounts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  type ENUM('fixed','percentage') NOT NULL,
  value DECIMAL(12,2) NOT NULL,
  min_purchase DECIMAL(12,2) NOT NULL DEFAULT 0,
  min_quantity INT UNSIGNED NOT NULL DEFAULT 0,
  start_date DATE NULL,
  end_date DATE NULL,
  usage_limit INT UNSIGNED NULL,
  times_used INT UNSIGNED NOT NULL DEFAULT 0,
  product_id INT UNSIGNED NULL,
  applies_to ENUM('product','subscription') NOT NULL DEFAULT 'subscription',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_d_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS invoices (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_no VARCHAR(30) NOT NULL,
  subscription_id INT UNSIGNED NOT NULL,
  customer_id INT UNSIGNED NOT NULL,
  period_start DATE NOT NULL,
  period_end DATE NOT NULL,
  status ENUM('draft','confirmed','paid','cancelled') NOT NULL DEFAULT 'draft',
  invoice_date DATE NOT NULL,
  due_date DATE NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount_total DECIMAL(12,2) NOT NULL DEFAULT 0,
  tax_total DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
  sent_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_invoice_no (invoice_no),
  UNIQUE KEY uq_invoice_period (subscription_id,period_start),
  KEY idx_inv_customer (customer_id,status),
  KEY idx_inv_due (status,due_date),
  CONSTRAINT fk_i_sub FOREIGN KEY (subscription_id) REFERENCES subscriptions(id),
  CONSTRAINT fk_i_customer FOREIGN KEY (customer_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS invoice_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NULL,
  description VARCHAR(255) NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  unit_price DECIMAL(12,2) NOT NULL,
  tax_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
  tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  line_amount DECIMAL(12,2) NOT NULL,
  KEY idx_ii_inv (invoice_id),
  CONSTRAINT fk_ii_i FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
  CONSTRAINT fk_ii_p FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS discount_usage (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  discount_id INT UNSIGNED NOT NULL,
  invoice_id INT UNSIGNED NULL,
  subscription_id INT UNSIGNED NOT NULL,
  customer_id INT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  used_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_du_discount (discount_id),
  CONSTRAINT fk_du_d FOREIGN KEY (discount_id) REFERENCES discounts(id),
  CONSTRAINT fk_du_i FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
  CONSTRAINT fk_du_s FOREIGN KEY (subscription_id) REFERENCES subscriptions(id),
  CONSTRAINT fk_du_c FOREIGN KEY (customer_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT UNSIGNED NOT NULL,
  method ENUM('cash','bank_transfer','card','upi','other') NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  payment_date DATE NOT NULL,
  note VARCHAR(255) NULL,
  recorded_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pay_invoice (invoice_id),
  KEY idx_pay_date (payment_date),
  CONSTRAINT fk_pay_i FOREIGN KEY (invoice_id) REFERENCES invoices(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  message VARCHAR(500) NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_n_user (user_id,is_read),
  CONSTRAINT fk_n_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(60) NOT NULL,
  entity VARCHAR(40) NULL,
  entity_id INT UNSIGNED NULL,
  details VARCHAR(500) NULL,
  ip VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_audit_entity (entity,entity_id),
  KEY idx_audit_created (created_at)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS=1;
