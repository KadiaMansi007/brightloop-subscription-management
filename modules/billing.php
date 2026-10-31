<?php
/* Merged Participant 2 + 3 business logic, built on Participant 1's schema and pricing.php helpers. */
function q1(string $sql, array $a = []) { $s = db()->prepare($sql); $s->execute($a); return $s->fetchColumn(); }
function qrow(string $sql, array $a = []) { $s = db()->prepare($sql); $s->execute($a); return $s->fetch() ?: null; }
function qall(string $sql, array $a = []): array { $s = db()->prepare($sql); $s->execute($a); return $s->fetchAll(); }
function status_badge(string $s): string { return '<span class="badge b-' . e($s) . '">' . e(ucwords(str_replace('_', ' ', $s))) . '</span>'; }
function doc_number(string $prefix, string $table): string {
    $n = (int)q1("SELECT COALESCE(MAX(id),0)+1 FROM $table");
    return $prefix . date('y') . str_pad((string)$n, 5, '0', STR_PAD_LEFT);
}
/** Small POST button used in tables. */
function act_form(int $id, string $action, string $label, string $cls = '', string $extra = ''): string {
    return '<form method="post" class="inline">' . csrf_field() . '<input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="action" value="' . e($action) . '">' . $extra
        . '<button class="btn small ' . e($cls) . '">' . e($label) . '</button></form> ';
}
function notify(int $uid, string $title, string $msg = ''): void {
    db()->prepare('INSERT INTO notifications (user_id,title,message) VALUES (?,?,?)')->execute([$uid, $title, $msg ?: null]);
}

/* ---------- Quotations ---------- */
function plan_lines(int $planId, ?int $variantId, int $qty): array {
    $plan = qrow('SELECT pl.*, pr.name AS pname FROM plans pl JOIN products pr ON pr.id=pl.product_id WHERE pl.id=?', [$planId]);
    if (!$plan || !plan_is_available($plan)) throw new RuntimeException('That plan is not available.');
    if ($qty < (int)$plan['min_quantity']) throw new RuntimeException('Minimum quantity for this plan is ' . (int)$plan['min_quantity'] . '.');
    $extra = 0.0; $label = $plan['pname'];
    if ($variantId) {
        $v = qrow('SELECT * FROM product_variants WHERE id=? AND product_id=?', [$variantId, $plan['product_id']]);
        if (!$v) throw new RuntimeException('That variant does not belong to the plan\'s product.');
        $extra = (float)$v['extra_price']; $label .= ' - ' . $v['name'];
    }
    $taxes = taxes_for_product(db(), (int)$plan['product_id']);
    $pct = (float)array_sum(array_column($taxes, 'percentage'));
    $unit = variant_final_price((float)$plan['price'], $extra);
    $line = ['product_id' => (int)$plan['product_id'], 'variant_id' => $variantId, 'description' => $label . ' (' . $plan['name'] . ')',
             'quantity' => $qty, 'unit_price' => $unit, 'tax_id' => $taxes ? (int)$taxes[0]['id'] : null, 'tax_percent' => $pct] + calc_line($qty, $unit, $pct);
    return [$plan, [$line]];
}
function create_quotation(int $customerId, int $planId, ?int $variantId, int $qty, int $validity, ?int $templateId, int $by): int {
    if (!q1("SELECT 1 FROM users WHERE id=? AND role='customer' AND status='active'", [$customerId])) throw new RuntimeException('Choose a valid active customer.');
    [$plan, $lines] = plan_lines($planId, $variantId, $qty);
    $doc = calc_document($lines);
    $pdo = db(); $pdo->beginTransaction();
    try {
        $pdo->prepare("INSERT INTO quotations (quotation_no,customer_id,template_id,plan_id,validity_days,expires_on,status,subtotal,tax_total,total,created_by) VALUES (?,?,?,?,?,?,'draft',?,?,?,?)")
            ->execute([doc_number('QT', 'quotations'), $customerId, $templateId, $planId, $validity, date('Y-m-d', strtotime("+$validity days")), $doc['subtotal'], $doc['tax_total'], $doc['total'], $by]);
        $qid = (int)$pdo->lastInsertId();
        $ins = $pdo->prepare('INSERT INTO quotation_items (quotation_id,product_id,variant_id,description,quantity,unit_price,tax_id,tax_percent,tax_amount,line_amount) VALUES (?,?,?,?,?,?,?,?,?,?)');
        foreach ($lines as $l) $ins->execute([$qid, $l['product_id'], $l['variant_id'], $l['description'], $l['quantity'], $l['unit_price'], $l['tax_id'], $l['tax_percent'], $l['tax_amount'], $l['line_amount']]);
        $pdo->commit(); return $qid;
    } catch (Throwable $t) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $t; }
}
function convert_quotation(int $qid, string $start, ?string $expiry = null): int {
    $pdo = db(); $pdo->beginTransaction();
    try {
        $s = $pdo->prepare('SELECT * FROM quotations WHERE id=? FOR UPDATE'); $s->execute([$qid]); $q = $s->fetch();
        if (!$q) throw new RuntimeException('Quotation not found.');
        if (!in_array($q['status'], ['draft', 'sent', 'accepted'], true)) throw new RuntimeException('This quotation can no longer be converted (' . $q['status'] . ').');
        if ($q['expires_on'] && $q['expires_on'] < date('Y-m-d')) throw new RuntimeException('This quotation has expired.');
        if (!$q['plan_id']) throw new RuntimeException('Quotation has no plan.');
        if (!DateTime::createFromFormat('Y-m-d', $start)) throw new RuntimeException('Enter a valid start date.');
        if ($expiry && $expiry < $start) throw new RuntimeException('Expiry date cannot be before the start date.');
        $pdo->prepare("INSERT INTO subscriptions (subscription_no,customer_id,plan_id,quotation_id,status,start_date,expiry_date,next_billing_date,payment_terms,subtotal,tax_total,total,created_by) VALUES (?,?,?,?,'confirmed',?,?,?,'Net 15',?,?,?,?)")
            ->execute([doc_number('SUB', 'subscriptions'), $q['customer_id'], $q['plan_id'], $qid, $start, $expiry ?: null, $start, $q['subtotal'], $q['tax_total'], $q['total'], $_SESSION['uid'] ?? null]);
        $sid = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO subscription_items (subscription_id,product_id,variant_id,quantity,unit_price,tax_id,tax_percent,tax_amount,line_amount) SELECT ?,product_id,variant_id,quantity,unit_price,tax_id,tax_percent,tax_amount,line_amount FROM quotation_items WHERE quotation_id=?')->execute([$sid, $qid]);
        $pdo->prepare("UPDATE quotations SET status='converted' WHERE id=?")->execute([$qid]);
        $pdo->commit(); return $sid;
    } catch (Throwable $t) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $t; }
}

/* ---------- Subscriptions ---------- */
/** $customerId set => acting as that customer: ownership + plan permissions are enforced. */
function subscription_action(int $sid, string $action, ?int $customerId = null): void {
    $sub = qrow('SELECT s.*, p.allow_pause, p.allow_early_close FROM subscriptions s JOIN plans p ON p.id=s.plan_id WHERE s.id=?', [$sid]);
    if (!$sub || ($customerId !== null && (int)$sub['customer_id'] !== $customerId)) throw new RuntimeException('Subscription not found.');
    $pdo = db();
    switch ($action) {
        case 'activate':
            if ($customerId !== null) throw new RuntimeException('Not allowed.');
            if ($sub['status'] !== 'confirmed') throw new RuntimeException('Only confirmed subscriptions can be activated.');
            $pdo->prepare("UPDATE subscriptions SET status='active' WHERE id=?")->execute([$sid]); break;
        case 'pause':
            if ($sub['status'] !== 'active' || $sub['is_paused']) throw new RuntimeException('Only a running subscription can be paused.');
            if ($customerId !== null && !$sub['allow_pause']) throw new RuntimeException('This plan does not allow pausing.');
            $pdo->prepare('UPDATE subscriptions SET is_paused=1 WHERE id=?')->execute([$sid]); break;
        case 'resume':
            if (!$sub['is_paused']) throw new RuntimeException('Subscription is not paused.');
            $pdo->prepare('UPDATE subscriptions SET is_paused=0 WHERE id=?')->execute([$sid]); break;
        case 'close':
            if (!in_array($sub['status'], ['confirmed', 'active'], true)) throw new RuntimeException('Subscription is already closed.');
            if ($customerId !== null && !$sub['allow_early_close']) throw new RuntimeException('This plan does not allow early closing.');
            $pdo->prepare("UPDATE subscriptions SET status='closed', is_paused=0, next_billing_date=NULL WHERE id=?")->execute([$sid]); break;
        default: throw new RuntimeException('Unknown action.');
    }
}

/* ---------- Invoices ---------- */
function invoice_due_days(?string $terms): int { return preg_match('/(\d+)/', (string)$terms, $m) ? max(0, (int)$m[1]) : 15; }

/** Validates a discount against invoice items; returns ['d'=>row,'per_line'=>[idx=>amount],'amount'=>float] or null. */
function load_discount(?int $id, array $items): ?array {
    if (!$id) return null;
    $d = qrow('SELECT * FROM discounts WHERE id=?', [$id]); $today = date('Y-m-d');
    if (!$d || !$d['is_active']) throw new RuntimeException('That discount is not active.');
    if ($d['start_date'] && $today < $d['start_date']) throw new RuntimeException('That discount has not started yet.');
    if ($d['end_date'] && $today > $d['end_date']) throw new RuntimeException('That discount has expired.');
    if ($d['usage_limit'] !== null && (int)$d['times_used'] >= (int)$d['usage_limit']) throw new RuntimeException('That discount has reached its usage limit.');
    $elig = []; $base = 0.0; $qty = 0;
    foreach ($items as $i => $it) {
        if ($d['product_id'] && (int)$d['product_id'] !== (int)$it['product_id']) continue;
        $elig[] = $i; $base += (float)$it['line_amount']; $qty += (int)$it['quantity'];
    }
    if (!$elig) throw new RuntimeException('That discount does not apply to this subscription\'s product.');
    if ($base < (float)$d['min_purchase']) throw new RuntimeException('Minimum purchase of ' . money($d['min_purchase']) . ' not met.');
    if ($qty < (int)$d['min_quantity']) throw new RuntimeException('Minimum quantity of ' . (int)$d['min_quantity'] . ' not met.');
    $amt = $d['type'] === 'percentage' ? round($base * (float)$d['value'] / 100, 2) : (float)$d['value'];
    $amt = min($base, max(0.0, $amt)); $per = []; $given = 0.0;
    foreach ($elig as $k => $i) {
        $share = ($k === count($elig) - 1) ? round($amt - $given, 2) : round($amt * (float)$items[$i]['line_amount'] / $base, 2);
        $per[$i] = $share; $given += $share;
    }
    return ['d' => $d, 'per_line' => $per, 'amount' => $amt];
}

/** Creates a DRAFT invoice for the subscription's next billing period and advances next_billing_date. */
function generate_invoice(int $sid, ?int $discountId = null, bool $onlyIfDue = false): int {
    $pdo = db(); $pdo->beginTransaction();
    try {
        $s = $pdo->prepare('SELECT s.*, p.billing_frequency AS freq, p.auto_close FROM subscriptions s JOIN plans p ON p.id=s.plan_id WHERE s.id=? FOR UPDATE');
        $s->execute([$sid]); $sub = $s->fetch();
        if (!$sub) throw new RuntimeException('Subscription not found.');
        if ($sub['status'] !== 'active') throw new RuntimeException('Only active subscriptions can be invoiced. Activate it first.');
        if ($sub['is_paused']) throw new RuntimeException('Subscription is paused.');
        if (!$sub['next_billing_date']) throw new RuntimeException('There are no further billing periods.');
        $ps = $sub['next_billing_date'];
        if ($onlyIfDue && $ps > date('Y-m-d')) throw new RuntimeException('Not due yet.');
        $nextStart = next_billing_date($sub['start_date'], $sub['freq'], $ps);
        $periodEnd = date('Y-m-d', strtotime($nextStart . ' -1 day'));
        $items = qall('SELECT si.*, pr.name AS pname, v.name AS vname FROM subscription_items si JOIN products pr ON pr.id=si.product_id LEFT JOIN product_variants v ON v.id=si.variant_id WHERE si.subscription_id=?', [$sid]);
        if (!$items) throw new RuntimeException('Subscription has no items.');
        $disc = load_discount($discountId, $items);
        $rows = []; $sub_t = $disc_t = $tax_t = 0.0;
        foreach ($items as $i => $it) {
            $dis = $disc['per_line'][$i] ?? 0.0;
            $net = round((float)$it['line_amount'] - $dis, 2);
            $tax = round($net * (float)$it['tax_percent'] / 100, 2);
            $rows[] = [$it['product_id'], $it['pname'] . ($it['vname'] ? ' - ' . $it['vname'] : '') . " ($ps to $periodEnd)", $it['quantity'], $it['unit_price'], $it['tax_percent'], $tax, $dis, $net];
            $sub_t += (float)$it['line_amount']; $disc_t += $dis; $tax_t += $tax;
        }
        $total = round($sub_t - $disc_t + $tax_t, 2);
        $due = date('Y-m-d', strtotime('+' . invoice_due_days($sub['payment_terms']) . ' days'));
        try {
            $pdo->prepare("INSERT INTO invoices (invoice_no,subscription_id,customer_id,period_start,period_end,status,invoice_date,due_date,subtotal,discount_total,tax_total,total) VALUES (?,?,?,?,?,'draft',?,?,?,?,?,?)")
                ->execute([doc_number('INV', 'invoices'), $sid, $sub['customer_id'], $ps, $periodEnd, date('Y-m-d'), $due, round($sub_t, 2), round($disc_t, 2), round($tax_t, 2), $total]);
        } catch (PDOException $x) {
            if ($x->getCode() === '23000') throw new RuntimeException('An invoice for this billing period already exists.');
            throw $x;
        }
        $iid = (int)$pdo->lastInsertId();
        $ins = $pdo->prepare('INSERT INTO invoice_items (invoice_id,product_id,description,quantity,unit_price,tax_percent,tax_amount,discount_amount,line_amount) VALUES (?,?,?,?,?,?,?,?,?)');
        foreach ($rows as $r) $ins->execute(array_merge([$iid], $r));
        if ($disc && $disc_t > 0) {
            $pdo->prepare('INSERT INTO discount_usage (discount_id,invoice_id,subscription_id,customer_id,amount) VALUES (?,?,?,?,?)')->execute([$disc['d']['id'], $iid, $sid, $sub['customer_id'], round($disc_t, 2)]);
            $pdo->prepare('UPDATE discounts SET times_used=times_used+1 WHERE id=?')->execute([$disc['d']['id']]);
        }
        $next = ($sub['expiry_date'] && $nextStart > $sub['expiry_date']) ? null : $nextStart;
        $newStatus = ($next === null && $sub['auto_close']) ? 'closed' : $sub['status'];
        $pdo->prepare('UPDATE subscriptions SET next_billing_date=?, status=? WHERE id=?')->execute([$next, $newStatus, $sid]);
        $pdo->commit(); return $iid;
    } catch (Throwable $t) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $t; }
}

function set_invoice_status(int $id, string $to): void {
    $inv = qrow('SELECT * FROM invoices WHERE id=?', [$id]);
    if (!$inv) throw new RuntimeException('Invoice not found.');
    $from = ['confirmed' => ['draft'], 'cancelled' => ['draft', 'confirmed']];
    if (!isset($from[$to]) || !in_array($inv['status'], $from[$to], true)) throw new RuntimeException("A {$inv['status']} invoice cannot become $to.");
    if ($to === 'cancelled' && (float)$inv['amount_paid'] > 0) throw new RuntimeException('Cannot cancel an invoice that has payments.');
    db()->prepare('UPDATE invoices SET status=? WHERE id=?')->execute([$to, $id]);
    if ($to === 'confirmed') notify((int)$inv['customer_id'], 'New invoice ' . $inv['invoice_no'], 'Amount ' . money($inv['total']) . ' due by ' . $inv['due_date']);
}

/** Records a payment against a CONFIRMED invoice (row-locked, no overpayment). $customerId restricts to the customer's own invoice. */
function record_payment(int $invoiceId, float $amount, string $method, string $date, ?string $note, ?int $by, ?int $customerId = null): void {
    if (!in_array($method, ['cash', 'bank_transfer', 'card', 'upi', 'other'], true)) throw new RuntimeException('Choose a payment method.');
    if (!DateTime::createFromFormat('Y-m-d', $date)) throw new RuntimeException('Enter a valid payment date.');
    $amount = round($amount, 2);
    if ($amount <= 0) throw new RuntimeException('Amount must be greater than zero.');
    $pdo = db(); $pdo->beginTransaction();
    try {
        $s = $pdo->prepare('SELECT * FROM invoices WHERE id=? FOR UPDATE'); $s->execute([$invoiceId]); $inv = $s->fetch();
        if (!$inv || ($customerId !== null && (int)$inv['customer_id'] !== $customerId)) throw new RuntimeException('Invoice not found.');
        if ($inv['status'] !== 'confirmed') throw new RuntimeException('Only confirmed invoices can be paid (this one is ' . $inv['status'] . ').');
        $balance = round((float)$inv['total'] - (float)$inv['amount_paid'], 2);
        if ($amount > $balance + 0.001) throw new RuntimeException('Payment exceeds the balance of ' . money($balance) . '.');
        $pdo->prepare('INSERT INTO payments (invoice_id,method,amount,payment_date,note,recorded_by) VALUES (?,?,?,?,?,?)')->execute([$invoiceId, $method, $amount, $date, $note ?: null, $by]);
        $paid = round((float)$inv['amount_paid'] + $amount, 2);
        $pdo->prepare('UPDATE invoices SET amount_paid=?, status=? WHERE id=?')->execute([$paid, $paid >= (float)$inv['total'] - 0.005 ? 'paid' : 'confirmed', $invoiceId]);
        notify((int)$inv['customer_id'], 'Payment received', money($amount) . ' received for ' . $inv['invoice_no']);
        $pdo->commit();
    } catch (Throwable $t) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $t; }
}

/** Recurring billing: one draft invoice per due active subscription, plus housekeeping. Safe to run repeatedly. */
function run_auto_billing(): array {
    db()->exec("UPDATE quotations SET status='expired' WHERE status IN ('draft','sent') AND expires_on < CURDATE()");
    db()->exec("UPDATE subscriptions s JOIN plans p ON p.id=s.plan_id SET s.status='closed', s.next_billing_date=NULL WHERE s.status='active' AND p.auto_close=1 AND s.expiry_date IS NOT NULL AND s.expiry_date < CURDATE()");
    $made = 0; $errors = [];
    foreach (qall("SELECT id, subscription_no FROM subscriptions WHERE status='active' AND is_paused=0 AND next_billing_date IS NOT NULL AND next_billing_date <= CURDATE()") as $r) {
        try { generate_invoice((int)$r['id'], null, true); $made++; }
        catch (Throwable $x) { $errors[] = $r['subscription_no'] . ': ' . $x->getMessage(); }
    }
    return [$made, $errors];
}
