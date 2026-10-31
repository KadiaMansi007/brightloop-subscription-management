<?php
require_once '../includes/bootstrap.php';
require_once '../includes/auth.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT i.*, u.email FROM invoices i JOIN users u ON i.customer_id = u.id WHERE i.id = ?");
$stmt->execute([$id]);
$invoice = $stmt->fetch();
?>
<div class="invoice-box">
    <h2>INVOICE: <?= htmlspecialchars($invoice['invoice_number']) ?></h2>
    <p>Customer: <?= htmlspecialchars($invoice['email']) ?></p>
    <p>Total Due: $<?= number_format($invoice['total_amount'], 2) ?></p>
    <button onclick="window.print()">Print Invoice</button>
</div>
