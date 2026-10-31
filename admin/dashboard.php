<?php
require __DIR__.'/../includes/bootstrap.php';$u=require_role(['admin']);
$c=db()->query("SELECT (SELECT COUNT(*) FROM users WHERE role='customer') customers,(SELECT COUNT(*) FROM users WHERE role='staff') staff,(SELECT COUNT(*) FROM products WHERE status='active') products,(SELECT COUNT(*) FROM plans WHERE status='active') plans,(SELECT COUNT(*) FROM subscriptions WHERE status='active') subscriptions,(SELECT COUNT(*) FROM invoices) invoices,(SELECT COALESCE(SUM(amount),0) FROM payments) payments")->fetch();
$pageTitle='Admin dashboard';include __DIR__.'/../includes/header.php';?>
<h1>Admin dashboard</h1><div class="cards"><?php foreach(['customers'=>'Customers','staff'=>'Staff','products'=>'Active products','plans'=>'Active plans','subscriptions'=>'Active subscriptions','invoices'=>'Invoices','payments'=>'Payments received'] as $k=>$label):?><div class="stat"><span><?=e($c[$k])?></span><?=e($label)?></div><?php endforeach?></div>
<p class="muted">Use the navigation above to manage products, plans, subscriptions, invoices, discounts and reports.</p>
<?php include __DIR__.'/../includes/footer.php';?>