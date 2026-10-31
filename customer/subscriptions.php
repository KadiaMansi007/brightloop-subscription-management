<?php
require __DIR__.'/../includes/bootstrap.php'; $u=require_role(['customer']);
$q=db()->prepare("SELECT s.*,p.name AS plan_name FROM subscriptions s JOIN plans p ON p.id=s.plan_id WHERE s.customer_id=? ORDER BY s.id DESC");$q->execute([$u['id']]);$rows=$q->fetchAll();
$pageTitle='My subscriptions';include __DIR__.'/../includes/header.php';?>
<h1>My subscriptions</h1><div class="table-wrap"><table><tr><th>Number</th><th>Plan</th><th>Status</th><th>Start</th><th>Next billing</th><th>Expiry</th></tr>
<?php foreach($rows as $r):?><tr><td><?=e($r['subscription_no'])?></td><td><?=e($r['plan_name'])?></td><td><?=e($r['status'])?></td><td><?=e($r['start_date'])?></td><td><?=e($r['next_billing_date']??'—')?></td><td><?=e($r['expiry_date']??'—')?></td></tr><?php endforeach?></table></div>
<?php include __DIR__.'/../includes/footer.php';?>