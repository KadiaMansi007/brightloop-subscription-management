<?php
require __DIR__.'/../includes/bootstrap.php'; require_role(['admin','staff']);
$q=db()->query("SELECT s.*,u.name,u.email,p.name AS plan_name FROM subscriptions s JOIN users u ON u.id=s.customer_id JOIN plans p ON p.id=s.plan_id ORDER BY s.id DESC");
$rows=$q->fetchAll(); $pageTitle='Subscriptions'; include __DIR__.'/../includes/header.php'; ?>
<h1>Subscriptions</h1><p><a class="btn" href="<?= e(url('admin/subscription-form.php')) ?>">Create subscription</a></p>
<div class="table-wrap"><table><tr><th>Number</th><th>Customer</th><th>Plan</th><th>Status</th><th>Start</th><th>Expiry</th></tr>
<?php foreach($rows as $r): ?><tr><td><?=e($r['subscription_no'])?></td><td><?=e($r['name'])?> (<?=e($r['email'])?>)</td><td><?=e($r['plan_name'])?></td><td><?=e($r['status'])?></td><td><?=e($r['start_date'])?></td><td><?=e($r['expiry_date']??'—')?></td></tr><?php endforeach; ?></table></div>
<?php include __DIR__.'/../includes/footer.php'; ?>