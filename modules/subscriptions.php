<?php
require_once __DIR__ . '/../includes/bootstrap.php';

function create_subscription(PDO $pdo, array $data): int {
    $planId=(int)$data['plan_id']; $customerId=(int)$data['customer_id'];
    $q=$pdo->prepare("SELECT id, price FROM plans WHERE id=? AND status='active'");
    $q->execute([$planId]); $plan=$q->fetch();
    if (!$plan) throw new RuntimeException('Active plan not found.');
    $start=$data['start_date'] ?? date('Y-m-d');
    $expiry=$data['expiry_date'] ?? null;
    $number='SUB-'.strtoupper(bin2hex(random_bytes(4)));
    $ins=$pdo->prepare("INSERT INTO subscriptions (subscription_no,customer_id,plan_id,status,start_date,expiry_date,next_billing_date,subtotal,total,created_by) VALUES (?,?,?,'draft',?,?,?,?,?,?)");
    $ins->execute([$number,$customerId,$planId,$start,$expiry,$start,(float)$plan['price'],(float)$plan['price'],$_SESSION['uid']??null]);
    return (int)$pdo->lastInsertId();
}
function update_subscription_status(PDO $pdo, int $id, string $status): bool {
    $allowed=['draft','quotation','confirmed','active','closed'];
    if (!in_array(strtolower($status),$allowed,true)) return false;
    $q=$pdo->prepare("UPDATE subscriptions SET status=? WHERE id=?");
    return $q->execute([strtolower($status),$id]);
}
