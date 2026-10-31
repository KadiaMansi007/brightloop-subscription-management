<?php
require_once '../includes/bootstrap.php';
require_once '../includes/auth.php';
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE id = ?");
$stmt->execute([$id]);
$sub = $stmt->fetch();
?>
<h2>Subscription Details: <?= htmlspecialchars($sub['subscription_number'] ?? '') ?></h2>
<p>Status: <?= htmlspecialchars($sub['status'] ?? '') ?></p>
