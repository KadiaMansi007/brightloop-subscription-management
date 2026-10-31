<?php
require __DIR__.'/../includes/bootstrap.php';require_role(['admin','staff']);
if(($_GET['export']??'')==='payments'){header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="payments-report.csv"');$out=fopen('php://output','w');fputcsv($out,['Payment ID','Customer ID','Invoice ID','Amount','Method','Status','Reference','Payment Date']);$q=db()->query("SELECT id,customer_id,invoice_id,amount,method,status,reference,payment_date FROM payments ORDER BY id DESC");while($r=$q->fetch(PDO::FETCH_NUM))fputcsv($out,$r);fclose($out);exit;}
$count=(int)db()->query("SELECT COUNT(*) FROM payments")->fetchColumn();$pageTitle='Reports';include __DIR__.'/../includes/header.php';?>
<h1>Reports</h1><p>Recorded payments: <?=e($count)?></p><p><a class="btn" href="?export=payments">Download payments CSV</a></p>
<?php include __DIR__.'/../includes/footer.php';?>