<?php
require __DIR__.'/../includes/bootstrap.php';$u=require_role(['customer']);
$q=db()->prepare("SELECT COUNT(*) FROM subscriptions WHERE customer_id=? AND status='active'");$q->execute([$u['id']]);$active=(int)$q->fetchColumn();
$q=db()->prepare("SELECT COUNT(*) FROM invoices WHERE customer_id=?");$q->execute([$u['id']]);$invoiceCount=(int)$q->fetchColumn();
$q=db()->prepare("SELECT COALESCE(SUM(GREATEST(total-amount_paid,0)),0) FROM invoices WHERE customer_id=? AND status<>'paid'");$q->execute([$u['id']]);$due=(float)$q->fetchColumn();
$pageTitle='My account';include __DIR__.'/../includes/header.php';?>
<h1>My account</h1><p>Welcome, <?=e($u['name'])?>. Here is your subscription overview.</p>
<div class="cards"><div class="stat"><span><?=e($active)?></span>Active subscriptions</div><div class="stat"><span><?=e($invoiceCount)?></span>Invoices</div><div class="stat"><span><?=money($due)?></span>Outstanding balance</div></div>
<h2>Manage your account</h2><p><a class="btn" href="<?=e(url('customer/subscriptions.php'))?>">My subscriptions</a> <a class="btn" href="<?=e(url('customer/invoices.php'))?>">My invoices</a> <a class="btn" href="<?=e(url('customer/payments.php'))?>">My payments</a> <a class="btn" href="<?=e(url('customer/requests.php'))?>">Requests</a> <a class="btn" href="<?=e(url('customer/profile.php'))?>">Profile</a></p>
<?php include __DIR__.'/../includes/footer.php';?>