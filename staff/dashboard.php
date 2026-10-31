<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['staff', 'admin']);
$pageTitle = 'Staff dashboard'; include __DIR__ . '/../includes/header.php'; ?>
<h1>Staff dashboard</h1><p>Welcome, <?= e($u['name']) ?>. Quotations, subscriptions and invoices (Participant 2) plug in here.</p>
<?php include __DIR__ . '/../includes/footer.php';
