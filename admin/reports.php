<?php
require_once __DIR__.'/../includes/auth.php'; require_staff();
$pageTitle='Reports'; require_once __DIR__.'/../includes/header.php';
if(($_GET['export']??'')==='payments'){
 header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="payments-report.csv"');
 $out=fopen('php://output','w'); fputcsv($out,['Payment ID','Customer ID','Invoice ID','Amount','Method','Status','Reference','Created At']);
 $q=$pdo->query("SELECT id,customer_id,invoice_id,amount,method,status,reference,created_at FROM payments ORDER BY created_at DESC");
 while($r=$q->fetch(PDO::FETCH_NUM))fputcsv($out,$r); fclose($out); exit;
}
$count=(int)$pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn();
?><h1>Reports</h1><p>Recorded payments: <?=h($count)?></p><p><a href="reports.php?export=payments">Download payments CSV</a></p>
<?php require_once __DIR__.'/../includes/footer.php';?>
