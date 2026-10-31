<?php
require_once '../includes/bootstrap.php';
require_once '../includes/auth.php';

$user_id = $_SESSION['user_id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE customer_id = ?");
$stmt->execute([$user_id]);
$subscriptions = $stmt->fetchAll();
?>
<h2>My Subscriptions</h2>
<table>
    <tr><th>Sub #</th><th>Start Date</th><th>Expiry</th><th>Status</th></tr>
    <?php foreach ($subscriptions as $sub): ?>
    <tr>
        <td><?= htmlspecialchars($sub['subscription_number']) ?></td>
        <td><?= htmlspecialchars($sub['start_date']) ?></td>
        <td><?= htmlspecialchars($sub['expiry_date']) ?></td>
        <td><?= htmlspecialchars($sub['status']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
