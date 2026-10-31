<?php
require_once '../includes/bootstrap.php';
require_once '../includes/auth.php';
$stmt = $pdo->query("SELECT s.*, u.email FROM subscriptions s JOIN users u ON s.customer_id = u.id ORDER BY s.id DESC");
$subscriptions = $stmt->fetchAll();
?>
<h2>All Subscriptions</h2>
<table>
    <tr><th>Sub #</th><th>Customer</th><th>Status</th><th>Actions</th></tr>
    <?php foreach ($subscriptions as $sub): ?>
    <tr>
        <td><?= htmlspecialchars($sub['subscription_number']) ?></td>
        <td><?= htmlspecialchars($sub['email']) ?></td>
        <td><?= htmlspecialchars($sub['status']) ?></td>
        <td><a href="subscription-view.php?id=<?= $sub['id'] ?>">View</a></td>
    </tr>
    <?php endforeach; ?>
</table>
