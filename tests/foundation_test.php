<?php
// Run: php tests/foundation_test.php   (needs schema imported; uses a rolled-back transaction, leaves no data)
if (PHP_SAPI !== 'cli') exit;
require __DIR__ . '/../includes/bootstrap.php';
$fail = 0;
function check(string $name, bool $ok): void { global $fail; echo ($ok ? 'PASS  ' : 'FAIL  ') . $name . "\n"; if (!$ok) $fail++; }

check('password: valid accepted', validate_password('Abcdef1!x') === []);
check('password: exactly 8 chars rejected', validate_password('Abcde1!x') !== []);
check('password: no uppercase rejected', validate_password('abcdef1!xy') !== []);
check('password: no lowercase rejected', validate_password('ABCDEF1!XY') !== []);
check('password: no special char rejected', validate_password('Abcdefg12') !== []);

check('billing: daily', add_billing_interval('2026-01-01', 'daily') === '2026-01-02');
check('billing: weekly', add_billing_interval('2026-01-01', 'weekly') === '2026-01-08');
check('billing: monthly', add_billing_interval('2026-01-15', 'monthly') === '2026-02-15');
check('billing: monthly clamps Jan 31 -> Feb 28', add_billing_interval('2026-01-31', 'monthly') === '2026-02-28');
check('billing: no drift (Jan 31 + 2 months = Mar 31)', billing_period_start('2026-01-31', 'monthly', 2) === '2026-03-31');
check('billing: yearly leap day', add_billing_interval('2024-02-29', 'yearly') === '2025-02-28');
check('billing: next_billing_date', next_billing_date('2026-01-31', 'monthly', '2026-02-28') === '2026-03-31');

$l = calc_line(1, 1000, 18);
check('tax: 1000 @18% = 180 / 1180', $l['tax_amount'] == 180.0 && $l['line_total'] == 1180.0);
check('variant: 1000 + Odoo 560 = 1560', variant_final_price(1000, 560) == 1560.0);
check('document totals', calc_document([calc_line(2, 500, 18), calc_line(1, 100, 0)])['total'] == 1280.0);
check('plan availability window', plan_is_available(['status' => 'active', 'available_from' => '2026-01-01', 'available_until' => '2026-12-31'], '2026-06-01')
    && !plan_is_available(['status' => 'active', 'available_until' => '2026-01-01'], '2026-06-01'));

$pdo = db();
$need = ['users','products','product_variants','plans','quotation_templates','quotations','quotation_items','subscriptions','subscription_items','taxes','discounts','discount_usage','invoices','invoice_items','payments','notifications','password_resets','audit_logs'];
$have = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
check('schema: all 18 shared tables exist', !array_diff($need, $have));
$fk = $pdo->prepare('SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=?'); $fk->execute([DB_NAME]);
check('schema: foreign keys present', (int)$fk->fetchColumn() >= 20);

$pdo->beginTransaction();
try {
    $ins = $pdo->prepare("INSERT INTO users (name,email,password_hash,role) VALUES ('T',?,?,'customer')");
    $ins->execute(['dup@test.local', 'x']);
    $dup = false; try { $ins->execute(['dup@test.local', 'x']); } catch (PDOException $e) { $dup = $e->getCode() === '23000'; }
    check('db: duplicate email rejected by UNIQUE constraint', $dup);
    $pdo->prepare("INSERT INTO products (name,sales_price) VALUES ('T',1000)")->execute(); $pid = (int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO taxes (name,percentage,applies_to_all) VALUES ('T18',18,1)")->execute();
    check('tax lookup: global tax applies to product', tax_percent_for_product($pdo, $pid) >= 18.0);
} finally { $pdo->rollBack(); }

echo $fail ? "\n$fail test(s) FAILED\n" : "\nAll tests passed\n"; exit($fail ? 1 : 0);
