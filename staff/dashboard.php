<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['staff', 'admin']);
$c = [
 'Open quotations' => (int)q1("SELECT COUNT(*) FROM quotations WHERE status IN ('draft','sent','accepted')"),
 'Awaiting activation' => (int)q1("SELECT COUNT(*) FROM subscriptions WHERE status='confirmed'"),
 'Draft invoices' => (int)q1("SELECT COUNT(*) FROM invoices WHERE status='draft'"),
 'Unpaid invoices' => (int)q1("SELECT COUNT(*) FROM invoices WHERE status='confirmed'"),
];
$pageTitle = 'Staff dashboard'; include __DIR__ . '/../includes/header.php'; ?>
<h1>Staff dashboard</h1><p>Welcome, <?= e($u['name']) ?>.</p>
<div class="cards"><?php foreach ($c as $l => $v): ?><div class="stat"><span><?= e($v) ?></span><?= e($l) ?></div><?php endforeach; ?></div>
<?php include __DIR__ . '/../includes/footer.php';
