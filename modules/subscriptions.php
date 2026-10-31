<?php
// Subscription Lifecycle & State Machine Handler
function create_subscription($pdo, $data) {
    $stmt = $pdo->prepare("INSERT INTO subscriptions (subscription_number, customer_id, plan_id, start_date, expiry_date, payment_terms, status) VALUES (?, ?, ?, ?, ?, ?, 'Draft')");
    return $stmt->execute([
        'SUB-' . strtoupper(uniqid()),
        $data['customer_id'],
        $data['plan_id'],
        $data['start_date'],
        $data['expiry_date'],
        $data['payment_terms']
    ]);
}

function update_subscription_status($pdo, $id, $status) {
    $allowed_statuses = ['Draft', 'Quotation', 'Confirmed', 'Active', 'Closed'];
    if (!in_array($status, $allowed_statuses)) return false;
    $stmt = $pdo->prepare("UPDATE subscriptions SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $id]);
}
