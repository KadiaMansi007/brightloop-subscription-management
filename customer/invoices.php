<?php
require __DIR__.'/../includes/bootstrap.php';$u=require_role(['customer']);
$q=db()->prepare("SELECT id,invoice_no,total,amount_paid,status,due_date,invoice_date FROM invoices WHERE customer_id=? ORDER BY id DESC");$q->execute([$u['id']]);$rows=$q->fetchAll();
$pageTitle='My invoices';include __DIR__.'/../includes/header.php';?>
<h1>My invoices</h1><div class="table-wrap"><table><tr><th>Invoice</th><th>Date</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th>Due</th></tr>
<?php foreach($rows as $r):?><tr><td><?=e($r['invoice_no'])?></td><td><?=e($r['invoice_date'])?></td><td><?=money($r['total'])?></td><td><?=money($r['amount_paid'])?></td><td><?=money(max(0,(float)$r['total']-(float)$r['amount_paid']))?></td><td><?=e($r['status'])?></td><td><?=e($r['due_date'])?></td></tr><?php endforeach?></table></div>
<?php include __DIR__.'/../includes/footer.php';?>