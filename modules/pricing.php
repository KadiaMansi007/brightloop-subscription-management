<?php
/* Shared calculation interface (Participant 1). Participants 2 and 3 MUST reuse these functions. */

/** Add n billing intervals to a date. Month/year math clamps to month end (Jan 31 + 1 month = Feb 28/29). */
function add_billing_interval(string $date, string $freq, int $n = 1): string {
    $d = new DateTimeImmutable($date);
    switch ($freq) {
        case 'daily':   return $d->modify("+$n day")->format('Y-m-d');
        case 'weekly':  return $d->modify("+$n week")->format('Y-m-d');
        case 'monthly': return add_months_clamped($d, $n)->format('Y-m-d');
        case 'yearly':  return add_months_clamped($d, 12 * $n)->format('Y-m-d');
    }
    throw new InvalidArgumentException("Unknown billing frequency: $freq");
}
function add_months_clamped(DateTimeImmutable $d, int $months): DateTimeImmutable {
    $day = (int)$d->format('j');
    $first = $d->modify('first day of this month')->modify("+$months month");
    return $first->setDate((int)$first->format('Y'), (int)$first->format('n'), min($day, (int)$first->format('t')));
}
/** Start of billing period number $n (0-based) counted from the subscription start date. Avoids month-end drift. */
function billing_period_start(string $startDate, string $freq, int $n): string {
    return $n === 0 ? $startDate : add_billing_interval($startDate, $freq, $n);
}
/** First period start strictly after $current, computed from the anchor start date. */
function next_billing_date(string $startDate, string $freq, string $current): string {
    $n = 0;
    while (billing_period_start($startDate, $freq, $n) <= $current) { $n++; }
    return billing_period_start($startDate, $freq, $n);
}

function plan_is_available(array $plan, ?string $on = null): bool {
    $on = $on ?: date('Y-m-d');
    if (($plan['status'] ?? 'active') !== 'active') return false;
    if (!empty($plan['available_from']) && $on < $plan['available_from']) return false;
    if (!empty($plan['available_until']) && $on > $plan['available_until']) return false;
    return true;
}

/** Final unit price = product sales price + variant extra price. */
function variant_final_price(float $salesPrice, float $extra = 0.0): float { return round($salesPrice + $extra, 2); }

/** Active taxes that apply to a product (global ones + ones linked to the product). */
function taxes_for_product(PDO $pdo, int $productId): array {
    $s = $pdo->prepare('SELECT t.* FROM taxes t WHERE t.is_active=1 AND (t.applies_to_all=1 OR EXISTS
        (SELECT 1 FROM tax_products tp WHERE tp.tax_id=t.id AND tp.product_id=?)) ORDER BY t.id');
    $s->execute([$productId]);
    return $s->fetchAll();
}
/** Combined % of all applicable taxes (usually just one). */
function tax_percent_for_product(PDO $pdo, int $productId): float {
    return (float)array_sum(array_column(taxes_for_product($pdo, $productId), 'percentage'));
}

/** One line: amount = qty*unit, tax = amount*pct/100, total = amount+tax. e.g. 1000 @18% => 180 / 1180. */
function calc_line(float $qty, float $unit, float $taxPct = 0.0): array {
    $amt = round($qty * $unit, 2);
    $tax = round($amt * $taxPct / 100, 2);
    return ['line_amount' => $amt, 'tax_amount' => $tax, 'line_total' => round($amt + $tax, 2)];
}
/** Sum an array of calc_line() results. */
function calc_document(array $lines): array {
    $sub = $tax = 0.0;
    foreach ($lines as $l) { $sub += $l['line_amount']; $tax += $l['tax_amount']; }
    return ['subtotal' => round($sub, 2), 'tax_total' => round($tax, 2), 'total' => round($sub + $tax, 2)];
}
