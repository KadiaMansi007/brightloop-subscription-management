<?php
// GET /api/product-variants.php?product_id=1  -> JSON with variant final prices and applicable tax %.
// For quotation/subscription forms (Participants 2 & 3). Admin and staff only.
require __DIR__ . '/../includes/bootstrap.php';
require_role(['admin', 'staff']);
header('Content-Type: application/json');
$pid = (int)($_GET['product_id'] ?? 0);
$s = db()->prepare("SELECT id,name,type,sales_price FROM products WHERE id=? AND status='active'"); $s->execute([$pid]);
$p = $s->fetch();
if (!$p) { http_response_code(404); echo json_encode(['error' => 'Product not found']); exit; }
$v = db()->prepare('SELECT id,name,extra_price FROM product_variants WHERE product_id=? ORDER BY id'); $v->execute([$pid]);
$variants = array_map(fn($r) => $r + ['final_price' => variant_final_price((float)$p['sales_price'], (float)$r['extra_price'])], $v->fetchAll());
echo json_encode(['product' => $p, 'tax_percent' => tax_percent_for_product(db(), $pid), 'variants' => $variants]);
