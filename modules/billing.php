<?php
require_once __DIR__ . '/../includes/bootstrap.php';
function generate_recurring_invoices(PDO $pdo): int {
    $q=$pdo->query("SELECT s.*, p.price, p.billing_frequency FROM subscriptions s JOIN plans p ON p.id=s.plan_id WHERE s.status='active' AND (s.next_billing_date IS NULL OR s.next_billing_date<=CURDATE())");
    $count=0;
    foreach($q->fetchAll() as $s) {
        $periodStart=$s['next_billing_date'] ?: date('Y-m-d');
        $periodEnd=$periodStart;
        $exists=$pdo->prepare("SELECT id FROM invoices WHERE subscription_id=? AND period_start=?");
        $exists->execute([$s['id'],$periodStart]); if($exists->fetch()) continue;
        $subtotal=(float)($s['total'] ?: $s['price']);
        $tax=(float)($s['tax_total'] ?? 0);
        $invoiceNo='INV-'.strtoupper(bin2hex(random_bytes(4)));
        $ins=$pdo->prepare("INSERT INTO invoices(invoice_no,subscription_id,customer_id,period_start,period_end,status,invoice_date,due_date,subtotal,tax_total,total,amount_paid) VALUES(?,?,?,?,?,'draft',CURDATE(),DATE_ADD(CURDATE(), INTERVAL 14 DAY),?,?,?,0)");
        $ins->execute([$invoiceNo,$s['id'],$s['customer_id'],$periodStart,$periodEnd,$subtotal,$tax,$subtotal+$tax]);
        $count++;
    }
    return $count;
}
