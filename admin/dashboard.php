<?php
require __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);
$c = [
 'Customers' => (int)q1("SELECT COUNT(*) FROM users WHERE role='customer'"),
 'Active subscriptions' => (int)q1("SELECT COUNT(*) FROM subscriptions WHERE status='active'"),
 'Open quotations' => (int)q1("SELECT COUNT(*) FROM quotations WHERE status IN ('draft','sent','accepted')"),
 'Draft invoices' => (int)q1("SELECT COUNT(*) FROM invoices WHERE status='draft'"),
 'Outstanding' => money(q1("SELECT COALESCE(SUM(total-amount_paid),0) FROM invoices WHERE status='confirmed'")),
 'Revenue received' => money(q1('SELECT COALESCE(SUM(amount),0) FROM payments')),
];
$recent = qall('SELECT p.payment_date, p.amount, p.method, i.invoice_no, u.name FROM payments p JOIN invoices i ON i.id=p.invoice_id JOIN users u ON u.id=i.customer_id ORDER BY p.id DESC LIMIT 5');
$pageTitle = 'Admin dashboard'; include __DIR__ . '/../includes/header.php'; ?>
<h1>Admin dashboard</h1>
<div class="cards"><?php foreach ($c as $l => $v): ?><div class="stat"><span><?= e($v) ?></span><?= e($l) ?></div><?php endforeach; ?></div>
<h2>Recent payments</h2>
<table><tr><th>Date</th><th>Invoice</th><th>Customer</th><th>Method</th><th>Amount</th></tr>
<?php foreach ($recent as $r): ?><tr><td><?= e($r['payment_date']) ?></td><td><?= e($r['invoice_no']) ?></td><td><?= e($r['name']) ?></td><td><?= e($r['method']) ?></td><td><?= money($r['amount']) ?></td></tr><?php endforeach; ?>
<?php if (!$recent): ?><tr><td colspan="5" class="muted">No payments yet.</td></tr><?php endif; ?></table>
<?php include __DIR__ . '/../includes/footer.php';
