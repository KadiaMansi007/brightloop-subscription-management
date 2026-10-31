<?php
// End-to-end check of the whole flow: php tests/flow_test.php  (needs schema + seed.sql + an admin; cleans up after itself)
if (PHP_SAPI !== 'cli') exit;
require __DIR__ . '/../includes/bootstrap.php';
$fail = 0;
function check(string $n, bool $ok): void { global $fail; echo ($ok ? 'PASS  ' : 'FAIL  ') . $n . "\n"; if (!$ok) $fail++; }
function throws(callable $f): bool { try { $f(); return false; } catch (Throwable $t) { return true; } }
function cleanup(): void {
    $uid = q1("SELECT id FROM users WHERE email='flowtest@brightloop.test'"); if (!$uid) return;
    db()->prepare('DELETE FROM payments WHERE invoice_id IN (SELECT id FROM invoices WHERE customer_id=?)')->execute([$uid]);
    foreach (['discount_usage', 'invoices', 'subscriptions', 'quotations'] as $t) db()->prepare("DELETE FROM $t WHERE customer_id=?")->execute([$uid]);
    db()->prepare('DELETE FROM users WHERE id=?')->execute([$uid]);
}
cleanup();
$admin = (int)q1("SELECT id FROM users WHERE role='admin' LIMIT 1"); check('an admin user exists (run create_admin.php)', $admin > 0);
$plan = qrow("SELECT * FROM plans WHERE status='active' ORDER BY id LIMIT 1"); check('seed.sql plans exist', (bool)$plan);
if (!$admin || !$plan) exit(1);
db()->prepare("INSERT INTO users (name,email,password_hash,role) VALUES ('Flow Test','flowtest@brightloop.test','x','customer')")->execute();
$cust = (int)db()->lastInsertId();
$qid = create_quotation($cust, (int)$plan['id'], null, (int)$plan['min_quantity'], 30, null, $admin);
$q = qrow('SELECT * FROM quotations WHERE id=?', [$qid]);
check('quotation total = price*qty + 18% GST', abs((float)$q['total'] - round($plan['price'] * $plan['min_quantity'] * 1.18, 2)) < 0.01);
$sid = convert_quotation($qid, date('Y-m-d'));
check('quotation converted', q1('SELECT status FROM quotations WHERE id=?', [$qid]) === 'converted');
check('invoice refused before activation', throws(fn() => generate_invoice($sid)));
subscription_action($sid, 'activate');
$iid = generate_invoice($sid);
$inv = qrow('SELECT * FROM invoices WHERE id=?', [$iid]);
check('invoice total matches subscription total', abs((float)$inv['total'] - (float)$q['total']) < 0.01);
check('next billing date advanced', q1('SELECT next_billing_date FROM subscriptions WHERE id=?', [$sid]) > date('Y-m-d'));
check('payment refused on draft invoice', throws(fn() => record_payment($iid, 1, 'cash', date('Y-m-d'), null, $admin)));
set_invoice_status($iid, 'confirmed');
check('overpayment refused', throws(fn() => record_payment($iid, $inv['total'] + 1, 'cash', date('Y-m-d'), null, $admin)));
check("other customer can't pay it", throws(fn() => record_payment($iid, 1, 'cash', date('Y-m-d'), null, $admin, $cust + 999)));
record_payment($iid, 1.00, 'cash', date('Y-m-d'), 'part', $admin);
check('partial payment keeps invoice confirmed', q1('SELECT status FROM invoices WHERE id=?', [$iid]) === 'confirmed');
record_payment($iid, (float)$inv['total'] - 1.00, 'upi', date('Y-m-d'), null, $admin, $cust);
check('full payment marks invoice paid', q1('SELECT status FROM invoices WHERE id=?', [$iid]) === 'paid');
check('customer cannot control another customer subscription', throws(fn() => subscription_action($sid, 'close', $cust + 999)));
cleanup();
echo $fail ? "\n$fail check(s) FAILED\n" : "\nAll checks passed\n"; exit($fail ? 1 : 0);
