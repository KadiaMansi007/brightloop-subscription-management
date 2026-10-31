<?php
require __DIR__.'/../includes/bootstrap.php';$u=require_role(['customer']);
$q=db()->prepare("SELECT p.*,i.invoice_no FROM payments p JOIN invoices i ON i.id=p.invoice_id WHERE i.customer_id=? ORDER BY p.id DESC");$q->execute([$u['id']]);$rows=$q->fetchAll();
$pageTitle='My payments';include __DIR__.'/../includes/header.php';?>
<h1>My payments</h1><div class="table-wrap"><table><tr><th>Invoice</th><th>Amount</th><th>Method</th><th>Payment date</th><th>Note</th></tr>
<?php foreach($rows as $r):?><tr><td><?=e($r['invoice_no'])?></td><td><?=money($r['amount'])?></td><td><?=e($r['method'])?></td><td><?=e($r['payment_date'])?></td><td><?=e($r['note']??'—')?></td></tr><?php endforeach?></table></div>
<?php include __DIR__.'/../includes/footer.php';?>