<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['customer']);
$pageTitle = 'My account'; include __DIR__ . '/../includes/header.php'; ?>
<h1>My account</h1><p>Welcome, <?= e($u['name']) ?>. Your subscriptions, invoices and payments (Participant 3) plug in here.
Always filter by <code>customer_id = <?= (int)$u['id'] ?></code> taken from the session, never from the URL.</p>
<?php include __DIR__ . '/../includes/footer.php';
