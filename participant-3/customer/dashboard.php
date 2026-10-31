<?php
require_once __DIR__.'/../includes/permissions.php'; $cid=customer_id_for_session();
$pageTitle='Customer Dashboard'; require_once __DIR__.'/../includes/header.php';
$q=$pdo->prepare("SELECT COUNT(*) FROM subscriptions WHERE customer_id=? AND status='active'");$q->execute([$cid]);$active=(int)$q->fetchColumn();
$q=$pdo->prepare("SELECT COALESCE(SUM(total_amount-paid_amount),0) FROM invoices WHERE customer_id=? AND status<>'paid'");$q->execute([$cid]);$due=(float)$q->fetchColumn();
?><h1>Welcome, <?=h($_SESSION['name']??'Customer')?></h1><section class="cards"><article class="card"><span>Active subscriptions</span><strong><?=h($active)?></strong></article><article class="card"><span>Outstanding balance</span><strong><?=h(number_format($due,2))?></strong></article></section>
<ul><li><a href="profile.php">Profile</a></li><li><a href="subscriptions.php">Subscriptions</a></li><li><a href="invoices.php">Invoices</a></li><li><a href="payments.php">Payments</a></li><li><a href="requests.php">Requests</a></li></ul>
<?php require_once __DIR__.'/../includes/footer.php';?>
