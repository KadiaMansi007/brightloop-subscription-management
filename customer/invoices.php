<?php
require_once __DIR__.'/../includes/permissions.php';$cid=customer_id_for_session();
$q=$pdo->prepare("SELECT id,total_amount,paid_amount,status,due_date FROM invoices WHERE customer_id=? ORDER BY id DESC");$q->execute([$cid]);$rows=$q->fetchAll();
$pageTitle='My Invoices';require_once __DIR__.'/../includes/header.php';
?><h1>My invoices</h1><table><tr><th>ID</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th>Due date</th></tr><?php foreach($rows as $r):?><tr><td><?=h($r['id'])?></td><td><?=h($r['total_amount'])?></td><td><?=h($r['paid_amount'])?></td><td><?=h(max(0,(float)$r['total_amount']-(float)$r['paid_amount']))?></td><td><?=h($r['status'])?></td><td><?=h($r['due_date'])?></td></tr><?php endforeach?></table>
<?php require_once __DIR__.'/../includes/footer.php';?>
