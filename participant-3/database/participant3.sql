CREATE TABLE IF NOT EXISTS discounts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(40) NOT NULL UNIQUE,
 discount_type ENUM('percent','fixed') NOT NULL,
 discount_value DECIMAL(10,2) NOT NULL,
 min_purchase DECIMAL(10,2) NOT NULL DEFAULT 0,
 starts_at DATETIME NULL, ends_at DATETIME NULL,
 usage_limit INT UNSIGNED NULL, used_count INT UNSIGNED NOT NULL DEFAULT 0,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 invoice_id BIGINT UNSIGNED NOT NULL,
 customer_id BIGINT UNSIGNED NOT NULL,
 amount DECIMAL(10,2) NOT NULL,
 method VARCHAR(40) NOT NULL DEFAULT 'manual',
 status VARCHAR(30) NOT NULL DEFAULT 'completed',
 reference VARCHAR(100) NULL UNIQUE,
 recorded_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_payments_customer(customer_id),
 INDEX idx_payments_invoice(invoice_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS subscription_requests (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 customer_id BIGINT UNSIGNED NOT NULL,
 subscription_id BIGINT UNSIGNED NOT NULL,
 request_type ENUM('pause','renewal','cancellation') NOT NULL,
 note TEXT NULL,
 status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_requests_customer(customer_id),
 INDEX idx_requests_status(status)
) ENGINE=InnoDB;

-- This module expects your team's existing tables:
-- users(id, name, email, role)
-- subscriptions(id, customer_id, product_name, status, start_date, next_billing_date, end_date)
-- invoices(id, customer_id, total_amount, paid_amount, status, due_date)
-- Do not run a second CREATE TABLE for these shared tables; adapt code to your team's schema.
