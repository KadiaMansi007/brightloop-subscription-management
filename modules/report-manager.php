<?php
declare(strict_types=1);
function dashboard_metrics(PDO $pdo): array {
    $sql = [
      'customers'=>"SELECT COUNT(*) FROM users WHERE role='customer'",
      'active_subscriptions'=>"SELECT COUNT(*) FROM subscriptions WHERE status='active'",
      'invoice_total'=>"SELECT COALESCE(SUM(total_amount),0) FROM invoices",
      'payments_total'=>"SELECT COALESCE(SUM(amount),0) FROM payments",
      'outstanding'=>"SELECT COALESCE(SUM(GREATEST(total_amount-paid_amount,0)),0) FROM invoices WHERE status<>'paid'",
      'pending_requests'=>"SELECT COUNT(*) FROM subscription_requests WHERE status='pending'"
    ];
    $out=[]; foreach($sql as $k=>$q){ try{$out[$k]=$pdo->query($q)->fetchColumn();}catch(Throwable $e){$out[$k]=null;} }
    return $out;
}
function recent_payments(PDO $pdo, int $limit=10): array {
    $limit=max(1,min(100,$limit));
    return $pdo->query("SELECT p.*,u.name AS customer_name FROM payments p LEFT JOIN users u ON u.id=p.customer_id ORDER BY p.created_at DESC LIMIT {$limit}")->fetchAll();
}
