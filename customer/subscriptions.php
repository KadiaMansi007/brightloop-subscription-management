<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['customer']); $id = (int)$u['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try { subscription_action((int)($_POST['id'] ?? 0), $_POST['action'] ?? '', $id); audit('customer_' . $_POST['action'], 'subscription', (int)$_POST['id']); flash('success', 'Subscription updated.'); }
    catch (Throwable $x) { flash('error', $x->getMessage()); }
    redirect('customer/subscriptions.php');
}
$rows = qall('SELECT s.*, pl.name plan, pl.billing_frequency freq, pl.allow_pause, pl.allow_early_close FROM subscriptions s JOIN plans pl ON pl.id=s.plan_id WHERE s.customer_id=? ORDER BY s.id DESC', [$id]);
$pageTitle = 'My subscriptions'; include __DIR__ . '/../includes/header.php'; ?>
<h1>My subscriptions</h1>
<table><tr><th>No.</th><th>Plan</th><th>Start</th><th>Expiry</th><th>Next billing</th><th>Amount</th><th>Status</th><th></th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['subscription_no']) ?></td><td><?= e($r['plan']) ?> (<?= e($r['freq']) ?>)</td><td><?= e($r['start_date']) ?></td><td><?= e($r['expiry_date'] ?: '—') ?></td><td><?= e($r['next_billing_date'] ?: '—') ?></td><td><?= money($r['total']) ?></td>
<td><?= status_badge($r['status']) ?><?= $r['is_paused'] ? ' ' . status_badge('paused') : '' ?></td><td>
<?php if ($r['status'] === 'active' && $r['allow_pause']) echo $r['is_paused'] ? act_form((int)$r['id'], 'resume', 'Resume') : act_form((int)$r['id'], 'pause', 'Pause'); ?>
<?php if (in_array($r['status'], ['confirmed', 'active'], true) && $r['allow_early_close']) echo act_form((int)$r['id'], 'close', 'Close'); ?></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="8" class="muted">You have no subscriptions yet.</td></tr><?php endif; ?></table>
<?php include __DIR__ . '/../includes/footer.php';
