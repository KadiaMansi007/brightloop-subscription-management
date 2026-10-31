<?php
require_once __DIR__.'/../includes/permissions.php';$cid=customer_id_for_session();
$q=$pdo->prepare("SELECT id,invoice_id,amount,method,status,reference,created_at FROM payments WHERE customer_id=? ORDER BY id DESC");$q->execute([$cid]);$rows=$q->fetchAll();
$pageTitle='My Payments';require_once __DIR__.'/../includes/header.php';
?><h1>My payments</h1><table><tr><th>ID</th><th>Invoice</th><th>Amount</th><th>Method</th><th>Status</th><th>Reference</th><th>Date</th></tr><?php foreach($rows as $r):?><tr><td><?=h($r['id'])?></td><td><?=h($r['invoice_id'])?></td><td><?=h($r['amount'])?></td><td><?=h($r['method'])?></td><td><?=h($r['status'])?></td><td><?=h($r['reference'])?></td><td><?=h($r['created_at'])?></td></tr><?php endforeach?></table>
<?php require_once __DIR__.'/../includes/footer.php';?>
