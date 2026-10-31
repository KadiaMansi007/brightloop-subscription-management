<?php
// Automated Billing Engine
function generate_recurring_invoices($pdo) {
    $stmt = $pdo->prepare("SELECT s.*, c.email FROM subscriptions s JOIN users c ON s.customer_id = c.id WHERE s.status = 'Active'");
    $stmt->execute();
    $subscriptions = $stmt->fetchAll();

    $generated_count = 0;
    foreach ($subscriptions as $sub) {
        $check = $pdo->prepare("SELECT id FROM invoices WHERE subscription_id = ? AND invoice_date = CURDATE()");
        $check->execute([$sub['id']]);
        if ($check->fetch()) continue;

        $subtotal = 100.00;
        $tax_rate = 0.18;
        $tax_amount = $subtotal * $tax_rate;
        $total_amount = $subtotal + $tax_amount;

        $inv_stmt = $pdo->prepare("INSERT INTO invoices (invoice_number, subscription_id, customer_id, subtotal, tax_amount, total_amount, status, invoice_date) VALUES (?, ?, ?, ?, ?, ?, 'Draft', CURDATE())");
        $inv_stmt->execute([
            'INV-' . strtoupper(uniqid()),
            $sub['id'],
            $sub['customer_id'],
            $subtotal,
            $tax_amount,
            $total_amount
        ]);
        $generated_count++;
    }
    return $generated_count;
}
