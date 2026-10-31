<?php
require __DIR__.'/../includes/bootstrap.php'; $u=require_role(['admin','staff']); $id=(int)($_GET['id']??0);
$q=db()->prepare("SELECT i.*,u.name,u.email FROM invoices i JOIN users u ON u.id=i.customer_id WHERE i.id=?");$q->execute([$id]);$r=$q->fetch();
if(!$r){http_response_code(404);exit('Invoice not found.');}
$pageTitle='Invoice '.$r['invoice_no'];include __DIR__.'/../includes/header.php';?>
<div class="panel invoice-box"><h1>Invoice <?=e($r['invoice_no'])?></h1><p>Customer: <?=e($r['name'])?> (<?=e($r['email'])?>)</p><p>Invoice date: <?=e($r['invoice_date'])?></p><p>Due date: <?=e($r['due_date'])?></p><hr><p>Subtotal: <?=money($r['subtotal'])?></p><p>Tax: <?=money($r['tax_total'])?></p><h2>Total: <?=money($r['total'])?></h2><p>Paid: <?=money($r['amount_paid'])?></p><p>Status: <?=e($r['status'])?></p><button class="btn" onclick="window.print()">Print</button></div>
<?php include __DIR__.'/../includes/footer.php';?>