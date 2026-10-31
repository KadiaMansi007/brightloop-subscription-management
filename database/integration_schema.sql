-- BrightLoop integration additions for Participants 2 and 3.
-- Import this AFTER database/schema.sql. Safe to re-run on MySQL 8+.
USE brightloop_subscription_db;

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

CREATE TABLE IF NOT EXISTS subscription_requests (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 customer_id INT UNSIGNED NOT NULL,
 subscription_id INT UNSIGNED NOT NULL,
 request_type ENUM('pause','renewal','cancellation') NOT NULL,
 note TEXT NULL,
 status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_requests_customer(customer_id),
 INDEX idx_requests_status(status),
 CONSTRAINT fk_request_customer FOREIGN KEY(customer_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_request_subscription FOREIGN KEY(subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Participant 3 payment views require customer ownership and a payment status/reference.
ALTER TABLE payments
 ADD COLUMN customer_id INT UNSIGNED NULL AFTER invoice_id,
 ADD COLUMN status VARCHAR(30) NOT NULL DEFAULT 'completed' AFTER amount,
 ADD COLUMN reference VARCHAR(100) NULL,
 ADD UNIQUE KEY uq_payment_reference(reference);

UPDATE payments p JOIN invoices i ON i.id=p.invoice_id
SET p.customer_id=i.customer_id WHERE p.customer_id IS NULL;
