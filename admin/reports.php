<?php
require __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);
$export = $_GET['export'] ?? '';
if ($export === 'invoices' || $export === 'payments') {
    header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename="' . $export . '-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    $rows = $export === 'invoices'
        ? qall('SELECT i.invoice_no, u.name customer, i.period_start, i.period_end, i.due_date, i.status, i.subtotal, i.discount_total, i.tax_total, i.total, i.amount_paid FROM invoices i JOIN users u ON u.id=i.customer_id ORDER BY i.id')
        : qall('SELECT p.id, i.invoice_no, u.name customer, p.payment_date, p.method, p.amount, p.note FROM payments p JOIN invoices i ON i.id=p.invoice_id JOIN users u ON u.id=i.customer_id ORDER BY p.id');
    if ($rows) fputcsv($out, array_keys($rows[0]));
    foreach ($rows as $r) fputcsv($out, array_map(fn($v) => preg_match('/^[=+\-@]/', (string)$v) ? "'" . $v : $v, $r));
    exit;
}
$k = [
 'Revenue received' => money(q1('SELECT COALESCE(SUM(amount),0) FROM payments')),
 'Revenue this month' => money(q1("SELECT COALESCE(SUM(amount),0) FROM payments WHERE DATE_FORMAT(payment_date,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')")),
 'Outstanding (confirmed)' => money(q1("SELECT COALESCE(SUM(total-amount_paid),0) FROM invoices WHERE status='confirmed'")),
 'Overdue invoices' => (int)q1("SELECT COUNT(*) FROM invoices WHERE status='confirmed' AND due_date < CURDATE()"),
 'Active subscriptions' => (int)q1("SELECT COUNT(*) FROM subscriptions WHERE status='active'"),
 'Paused subscriptions' => (int)q1('SELECT COUNT(*) FROM subscriptions WHERE is_paused=1'),
];
$byStatus = qall('SELECT status, COUNT(*) n, COALESCE(SUM(total),0) amt FROM invoices GROUP BY status');
$byMonth = qall("SELECT DATE_FORMAT(payment_date,'%Y-%m') m, SUM(amount) amt, COUNT(*) n FROM payments GROUP BY m ORDER BY m DESC LIMIT 12");
$byPlan = qall("SELECT pl.name, COUNT(*) n FROM subscriptions s JOIN plans pl ON pl.id=s.plan_id WHERE s.status='active' GROUP BY pl.id ORDER BY n DESC");
$pageTitle = 'Reports'; include __DIR__ . '/../includes/header.php'; ?>
<h1>Reports</h1>
<div class="cards"><?php foreach ($k as $l => $v): ?><div class="stat"><span><?= e($v) ?></span><?= e($l) ?></div><?php endforeach; ?></div>
<p><a class="btn" href="?export=invoices">Export invoices (CSV)</a> <a class="btn" href="?export=payments">Export payments (CSV)</a></p>
<h2>Invoices by status</h2><table><tr><th>Status</th><th>Count</th><th>Value</th></tr><?php foreach ($byStatus as $r): ?><tr><td><?= status_badge($r['status']) ?></td><td><?= (int)$r['n'] ?></td><td><?= money($r['amt']) ?></td></tr><?php endforeach; ?></table>
<h2>Payments by month</h2><table><tr><th>Month</th><th>Payments</th><th>Amount</th></tr><?php foreach ($byMonth as $r): ?><tr><td><?= e($r['m']) ?></td><td><?= (int)$r['n'] ?></td><td><?= money($r['amt']) ?></td></tr><?php endforeach; ?></table>
<h2>Active subscriptions by plan</h2><table><tr><th>Plan</th><th>Active</th></tr><?php foreach ($byPlan as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= (int)$r['n'] ?></td></tr><?php endforeach; ?></table>
<?php include __DIR__ . '/../includes/footer.php';
