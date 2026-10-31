<?php
require_once __DIR__.'/../includes/permissions.php';$cid=customer_id_for_session();
$q=$pdo->prepare("SELECT id,product_name,status,start_date,next_billing_date,end_date FROM subscriptions WHERE customer_id=? ORDER BY id DESC");$q->execute([$cid]);$rows=$q->fetchAll();
$pageTitle='My Subscriptions';require_once __DIR__.'/../includes/header.php';
?><h1>My subscriptions</h1><table><tr><th>Product</th><th>Status</th><th>Start</th><th>Next billing</th><th>End</th></tr><?php foreach($rows as $r):?><tr><td><?=h($r['product_name'])?></td><td><?=h($r['status'])?></td><td><?=h($r['start_date'])?></td><td><?=h($r['next_billing_date'])?></td><td><?=h($r['end_date'])?></td></tr><?php endforeach?></table>
<?php require_once __DIR__.'/../includes/footer.php';?>
