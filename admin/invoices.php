<?php
require __DIR__ . '/../includes/bootstrap.php';
require_role(['admin', 'staff']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'autobill') {
        [$n, $errs] = run_auto_billing(); audit('auto_billing', 'invoice', null, "$n generated");
        flash('success', "Automated billing finished: $n draft invoice(s) generated.");
        foreach ($errs as $m) flash('error', $m);
    }
    redirect('admin/invoices.php');
}
$status = $_GET['status'] ?? ''; $valid = ['draft', 'confirmed', 'paid', 'cancelled'];
$where = in_array($status, $valid, true) ? 'WHERE i.status=?' : ''; $args = $where ? [$status] : [];
$pg = paginate((int)q1("SELECT COUNT(*) FROM invoices i $where", $args));
$rows = qall("SELECT i.*, u.name customer FROM invoices i JOIN users u ON u.id=i.customer_id $where ORDER BY i.id DESC LIMIT " . $pg['per'] . ' OFFSET ' . $pg['offset'], $args);
$pageTitle = 'Invoices'; include __DIR__ . '/../includes/header.php'; ?>
<h1>Invoices</h1>
<form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="autobill"><button class="btn">Run automated billing now</button></form>
<form method="get" class="inline"><select name="status" onchange="this.form.submit()"><option value="">All statuses</option><?php foreach ($valid as $s): ?><option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option><?php endforeach; ?></select></form>
<table><tr><th>No.</th><th>Customer</th><th>Period</th><th>Due</th><th>Total</th><th>Paid</th><th>Status</th><th></th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['invoice_no']) ?></td><td><?= e($r['customer']) ?></td><td><?= e($r['period_start']) ?> → <?= e($r['period_end']) ?></td><td><?= e($r['due_date']) ?></td><td><?= money($r['total']) ?></td><td><?= money($r['amount_paid']) ?></td><td><?= status_badge($r['status']) ?></td><td><a class="btn small" href="invoice-view.php?id=<?= (int)$r['id'] ?>">Open</a></td></tr><?php endforeach; ?></table><?= pager($pg) ?>
<?php include __DIR__ . '/../includes/footer.php';
