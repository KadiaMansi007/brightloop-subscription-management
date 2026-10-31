<?php
require __DIR__.'/../includes/bootstrap.php'; require_role(['admin','staff']);
$q=db()->query("SELECT i.*,u.name,u.email FROM invoices i JOIN users u ON u.id=i.customer_id ORDER BY i.id DESC"); $rows=$q->fetchAll();
$pageTitle='Invoices'; include __DIR__.'/../includes/header.php'; ?>
<h1>Invoices</h1><div class="table-wrap"><table><tr><th>Invoice</th><th>Customer</th><th>Total</th><th>Paid</th><th>Status</th><th>Due</th><th>Action</th></tr>
<?php foreach($rows as $r):?><tr><td><?=e($r['invoice_no'])?></td><td><?=e($r['name'])?> (<?=e($r['email'])?>)</td><td><?=money($r['total'])?></td><td><?=money($r['amount_paid'])?></td><td><?=e($r['status'])?></td><td><?=e($r['due_date'])?></td><td><a href="<?=e(url('admin/invoice-view.php?id='.(int)$r['id']))?>">View / Print</a></td></tr><?php endforeach?></table></div>
<?php include __DIR__.'/../includes/footer.php'; ?>