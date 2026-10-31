<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['customer']);
$rows = qall("SELECT * FROM invoices WHERE customer_id=? AND status<>'draft' ORDER BY id DESC", [(int)$u['id']]);
$pageTitle = 'My invoices'; include __DIR__ . '/../includes/header.php'; ?>
<h1>My invoices</h1>
<table><tr><th>No.</th><th>Period</th><th>Due</th><th>Total</th><th>Paid</th><th>Status</th><th></th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['invoice_no']) ?></td><td><?= e($r['period_start']) ?> → <?= e($r['period_end']) ?></td><td><?= e($r['due_date']) ?></td><td><?= money($r['total']) ?></td><td><?= money($r['amount_paid']) ?></td><td><?= status_badge($r['status']) ?></td>
<td><a class="btn small" href="invoice-view.php?id=<?= (int)$r['id'] ?>"><?= $r['status'] === 'confirmed' ? 'View / Pay' : 'View' ?></a></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="7" class="muted">No invoices yet.</td></tr><?php endif; ?></table>
<?php include __DIR__ . '/../includes/footer.php';
