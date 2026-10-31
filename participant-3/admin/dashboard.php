<?php
require_once __DIR__.'/../includes/auth.php'; require_staff();
require_once __DIR__.'/../modules/report-manager.php';
$m=dashboard_metrics($pdo); $recent=recent_payments($pdo,8);
$pageTitle='Admin Dashboard'; require_once __DIR__.'/../includes/header.php';
?>
<h1>Admin dashboard</h1><section class="cards">
<?php foreach(['customers'=>'Customers','active_subscriptions'=>'Active subscriptions','invoice_total'=>'Invoice value','payments_total'=>'Payments received','outstanding'=>'Outstanding balance','pending_requests'=>'Pending requests'] as $k=>$label):?>
<article class="card"><span><?=h($label)?></span><strong><?= $m[$k]===null?'N/A':h(number_format((float)$m[$k],2))?></strong></article><?php endforeach?>
</section><p><a href="discounts.php">Manage discounts</a> · <a href="reports.php">Reports</a></p>
<h2>Recent payments</h2><table><tr><th>ID</th><th>Customer</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr>
<?php foreach($recent as $p):?><tr><td><?=h($p['id'])?></td><td><?=h($p['customer_name']??'—')?></td><td><?=h($p['amount'])?></td><td><?=h($p['method'])?></td><td><?=h($p['status'])?></td><td><?=h($p['created_at'])?></td></tr><?php endforeach?></table>
<?php require_once __DIR__.'/../includes/footer.php';?>
