<?php
declare(strict_types=1);
function validate_coupon(PDO $pdo, string $code, float $subtotal, int $customerId): array {
    $stmt = $pdo->prepare("SELECT * FROM discounts WHERE code=? AND is_active=1 AND (starts_at IS NULL OR starts_at<=NOW()) AND (ends_at IS NULL OR ends_at>=NOW()) AND (usage_limit IS NULL OR used_count<usage_limit) LIMIT 1");
    $stmt->execute([strtoupper(trim($code))]); $d=$stmt->fetch();
    if (!$d) return ['valid'=>false,'message'=>'Coupon is invalid or expired.','discount'=>0.0];
    if ($subtotal < (float)$d['min_purchase']) return ['valid'=>false,'message'=>'Minimum purchase condition not met.','discount'=>0.0];
    $amount = $d['discount_type']==='percent' ? $subtotal*((float)$d['discount_value']/100) : (float)$d['discount_value'];
    $amount = min($subtotal, max(0,$amount));
    return ['valid'=>true,'message'=>'Coupon applied.','discount'=>round($amount,2),'discount_id'=>(int)$d['id']];
}
