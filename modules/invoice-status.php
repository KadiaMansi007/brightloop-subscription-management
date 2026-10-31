<?php
function update_invoice_status($pdo, $invoice_id, $status) {
    $valid_statuses = ['Draft', 'Confirmed', 'Paid', 'Cancelled'];
    if (!in_array($status, $valid_statuses)) return false;

    $stmt = $pdo->prepare("UPDATE invoices SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $invoice_id]);
}
