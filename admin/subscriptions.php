<?php
require __DIR__ . '/../includes/bootstrap.php';
require_role(['admin', 'staff']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $id = (int)($_POST['id'] ?? 0); $a = $_POST['action'] ?? '';
    try {
        if ($a === 'invoice') {
            $iid = generate_invoice($id, (int)($_POST['discount_id'] ?? 0) ?: null); audit('generate_invoice', 'subscription', $id);
            flash('success', 'Draft invoice created. Confirm it to send it to the customer.'); redirect('admin/invoice-view.php?id=' . $iid);
        }
        subscription_action($id, $a); audit($a . '_subscription', 'subscription', $id); flash('success', 'Subscription updated.');
    } catch (Throwable $x) { flash('error', $x->getMessage()); }
    redirect('admin/subscriptions.php');
}
$discounts = qall("SELECT id,name,type,value FROM discounts WHERE is_active=1 ORDER BY name");
$pg = paginate((int)q1('SELECT COUNT(*) FROM subscriptions'));
$rows = qall('SELECT s.*, u.name customer, pl.name plan, pl.billing_frequency freq FROM subscriptions s JOIN users u ON u.id=s.customer_id JOIN plans pl ON pl.id=s.plan_id ORDER BY s.id DESC LIMIT ' . $pg['per'] . ' OFFSET ' . $pg['offset']);
$pageTitle = 'Subscriptions'; include __DIR__ . '/../includes/header.php'; ?>
<h1>Subscriptions</h1>
<p class="muted">Create subscriptions by converting a quotation. A new subscription is "Confirmed"; activate it to start billing.</p>
<table><tr><th>No.</th><th>Customer</th><th>Plan</th><th>Start</th><th>Next billing</th><th>Total / period</th><th>Status</th><th>Actions</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['subscription_no']) ?></td><td><?= e($r['customer']) ?></td><td><?= e($r['plan']) ?> (<?= e($r['freq']) ?>)</td><td><?= e($r['start_date']) ?></td><td><?= e($r['next_billing_date'] ?: '—') ?></td><td><?= money($r['total']) ?></td>
<td><?= status_badge($r['status']) ?><?= $r['is_paused'] ? ' ' . status_badge('paused') : '' ?></td><td>
<?php if ($r['status'] === 'confirmed') echo act_form((int)$r['id'], 'activate', 'Activate'); ?>
<?php if ($r['status'] === 'active'): ?>
  <?= $r['is_paused'] ? act_form((int)$r['id'], 'resume', 'Resume') : act_form((int)$r['id'], 'pause', 'Pause') ?>
  <?php if (!$r['is_paused'] && $r['next_billing_date']): ?>
  <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="invoice">
  <select name="discount_id"><option value="">No discount</option><?php foreach ($discounts as $d): ?><option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?></select>
  <button class="btn small">Generate invoice</button></form>
  <?php endif; ?>
<?php endif; ?>
<?php if (in_array($r['status'], ['confirmed', 'active'], true)) echo act_form((int)$r['id'], 'close', 'Close'); ?></td></tr><?php endforeach; ?></table><?= pager($pg) ?>
<?php include __DIR__ . '/../includes/footer.php';
