-- Subscriptions Table Schema
CREATE TABLE IF NOT EXISTS subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subscription_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    plan_id INT NOT NULL,
    start_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    payment_terms VARCHAR(100),
    status ENUM('Draft', 'Quotation', 'Confirmed', 'Active', 'Closed') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
