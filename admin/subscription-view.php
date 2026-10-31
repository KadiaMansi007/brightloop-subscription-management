<?php
require __DIR__.'/../includes/bootstrap.php';require_role(['admin','staff']);$id=(int)($_GET['id']??0);
$q=db()->prepare("SELECT s.*,u.name,u.email,p.name AS plan_name FROM subscriptions s JOIN users u ON u.id=s.customer_id JOIN plans p ON p.id=s.plan_id WHERE s.id=?");$q->execute([$id]);$s=$q->fetch();
if(!$s){http_response_code(404);exit('Subscription not found.');}
$pageTitle='Subscription details';include __DIR__.'/../includes/header.php';?>
<h1>Subscription <?=e($s['subscription_no'])?></h1><section class="panel"><p>Customer: <?=e($s['name'])?> (<?=e($s['email'])?>)</p><p>Plan: <?=e($s['plan_name'])?></p><p>Status: <?=e($s['status'])?></p><p>Start: <?=e($s['start_date'])?></p><p>Expiry: <?=e($s['expiry_date']??'—')?></p><p>Next billing: <?=e($s['next_billing_date']??'—')?></p><p>Total: <?=money($s['total'])?></p></section>
<?php include __DIR__.'/../includes/footer.php';?>