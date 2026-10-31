<?php
require_once '../includes/bootstrap.php';
require_once '../includes/auth.php';

$stmt = $pdo->query("SELECT i.*, u.email FROM invoices i JOIN users u ON i.customer_id = u.id ORDER BY i.id DESC");
$invoices = $stmt->fetchAll();
?>
<h2>Invoices Management</h2>
<table>
    <tr><th>Invoice #</th><th>Customer</th><th>Total</th><th>Status</th><th>Actions</th></tr>
    <?php foreach ($invoices as $inv): ?>
    <tr>
        <td><?= htmlspecialchars($inv['invoice_number']) ?></td>
        <td><?= htmlspecialchars($inv['email']) ?></td>
        <td>$<?= number_format($inv['total_amount'], 2) ?></td>
        <td><?= htmlspecialchars($inv['status']) ?></td>
        <td><a href="invoice-view.php?id=<?= $inv['id'] ?>">View / Print</a></td>
    </tr>
    <?php endforeach; ?>
</table>
