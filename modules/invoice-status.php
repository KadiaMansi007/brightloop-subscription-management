<?php
function update_invoice_status(PDO $pdo, int $invoice_id, string $status): bool {
    $allowed=['draft','confirmed','paid','cancelled'];
    if (!in_array(strtolower($status),$allowed,true)) return false;
    $stmt=$pdo->prepare("UPDATE invoices SET status=? WHERE id=?");
    return $stmt->execute([strtolower($status),$invoice_id]);
}
