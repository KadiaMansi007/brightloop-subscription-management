<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['customer']);
$rows = qall('SELECT p.*, i.invoice_no FROM payments p JOIN invoices i ON i.id=p.invoice_id WHERE i.customer_id=? ORDER BY p.id DESC', [(int)$u['id']]);
$pageTitle = 'My payments'; include __DIR__ . '/../includes/header.php'; ?>
<h1>My payments</h1>
<table><tr><th>Date</th><th>Invoice</th><th>Method</th><th>Amount</th><th>Note</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['payment_date']) ?></td><td><?= e($r['invoice_no']) ?></td><td><?= e($r['method']) ?></td><td><?= money($r['amount']) ?></td><td><?= e($r['note']) ?></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="5" class="muted">No payments yet.</td></tr><?php endif; ?></table>
<?php include __DIR__ . '/../includes/footer.php';
