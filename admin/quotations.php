<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['admin', 'staff']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $a = $_POST['action'] ?? ''; $id = (int)($_POST['id'] ?? 0);
    try {
        if ($a === 'create') {
            $plan = (int)($_POST['plan_id'] ?? 0); $var = (int)($_POST['variant_id'] ?? 0) ?: null;
            $qty = max(1, (int)($_POST['quantity'] ?? 1)); $val = max(1, min(365, (int)($_POST['validity_days'] ?? 30)));
            $tpl = (int)($_POST['template_id'] ?? 0) ?: null;
            if ($tpl) {
                $t = qrow('SELECT t.*, i.variant_id, i.quantity FROM quotation_templates t LEFT JOIN quotation_template_items i ON i.template_id=t.id WHERE t.id=? LIMIT 1', [$tpl]);
                if (!$t || !$t['plan_id']) throw new RuntimeException('Template is invalid.');
                $plan = (int)$t['plan_id']; $val = (int)$t['validity_days']; $var = $t['variant_id'] ? (int)$t['variant_id'] : null; $qty = max(1, (int)$t['quantity']);
            }
            $qid = create_quotation((int)($_POST['customer_id'] ?? 0), $plan, $var, $qty, $val, $tpl, (int)$u['id']);
            audit('create_quotation', 'quotation', $qid); flash('success', 'Quotation created.');
        } elseif ($a === 'send') {
            db()->prepare("UPDATE quotations SET status='sent' WHERE id=? AND status='draft'")->execute([$id]);
            $q = qrow('SELECT quotation_no, customer_id, total FROM quotations WHERE id=?', [$id]);
            if ($q) notify((int)$q['customer_id'], 'Quotation ' . $q['quotation_no'], 'Total ' . money($q['total']));
            flash('success', 'Quotation marked as sent.');
        } elseif ($a === 'accept') {
            db()->prepare("UPDATE quotations SET status='accepted' WHERE id=? AND status IN ('draft','sent')")->execute([$id]); flash('success', 'Quotation accepted.');
        } elseif ($a === 'convert') {
            $sid = convert_quotation($id, $_POST['start_date'] ?: date('Y-m-d'), $_POST['expiry_date'] ?: null);
            audit('convert_quotation', 'quotation', $id); flash('success', 'Subscription created. Activate it to start billing.'); redirect('admin/subscriptions.php');
        }
    } catch (Throwable $x) { flash('error', $x->getMessage()); }
    redirect('admin/quotations.php');
}
$customers = qall("SELECT id,name,email FROM users WHERE role='customer' AND status='active' ORDER BY name");
$plans = qall("SELECT pl.id, pl.name, pl.price, pl.billing_frequency, pl.min_quantity FROM plans pl WHERE pl.status='active' ORDER BY pl.name");
$variants = qall('SELECT v.id, v.name, v.extra_price, p.name pname FROM product_variants v JOIN products p ON p.id=v.product_id ORDER BY p.name, v.id');
$tpls = qall('SELECT id,name FROM quotation_templates ORDER BY name');
$pg = paginate((int)q1('SELECT COUNT(*) FROM quotations'));
$rows = qall('SELECT q.*, u.name customer, pl.name plan FROM quotations q JOIN users u ON u.id=q.customer_id LEFT JOIN plans pl ON pl.id=q.plan_id ORDER BY q.id DESC LIMIT ' . $pg['per'] . ' OFFSET ' . $pg['offset']);
$pageTitle = 'Quotations'; include __DIR__ . '/../includes/header.php'; ?>
<h1>Quotations</h1>
<div class="card"><h2>New quotation</h2>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="create">
<label>Customer <select name="customer_id" required><option value="">-- choose --</option><?php foreach ($customers as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?> (<?= e($c['email']) ?>)</option><?php endforeach; ?></select></label>
<label>Template (optional – overrides plan/variant/quantity/validity) <select name="template_id"><option value="">-- none --</option><?php foreach ($tpls as $t): ?><option value="<?= (int)$t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?></select></label>
<label>Plan <select name="plan_id"><?php foreach ($plans as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?> – <?= money($p['price']) ?>/<?= e($p['billing_frequency']) ?></option><?php endforeach; ?></select></label>
<label>Variant <select name="variant_id"><option value="">-- none --</option><?php foreach ($variants as $v): ?><option value="<?= (int)$v['id'] ?>"><?= e($v['pname']) ?> – <?= e($v['name']) ?> (+<?= money($v['extra_price']) ?>)</option><?php endforeach; ?></select></label>
<label>Quantity <input type="number" name="quantity" value="1" min="1"></label>
<label>Validity (days) <input type="number" name="validity_days" value="30" min="1" max="365"></label>
<button class="btn">Create quotation</button></form></div>
<table><tr><th>No.</th><th>Customer</th><th>Plan</th><th>Total</th><th>Expires</th><th>Status</th><th>Actions</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['quotation_no']) ?></td><td><?= e($r['customer']) ?></td><td><?= e($r['plan']) ?></td><td><?= money($r['total']) ?></td><td><?= e($r['expires_on']) ?></td><td><?= status_badge($r['status']) ?></td><td>
<?php if ($r['status'] === 'draft') echo act_form((int)$r['id'], 'send', 'Send'); ?>
<?php if (in_array($r['status'], ['draft', 'sent'], true)) echo act_form((int)$r['id'], 'accept', 'Accept'); ?>
<?php if (in_array($r['status'], ['draft', 'sent', 'accepted'], true)): ?>
<form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="convert">
<input type="date" name="start_date" value="<?= date('Y-m-d') ?>" title="Start date"><input type="date" name="expiry_date" title="Expiry date (optional)"><button class="btn small">Convert to subscription</button></form>
<?php endif; ?></td></tr><?php endforeach; ?></table><?= pager($pg) ?>
<?php include __DIR__ . '/../includes/footer.php';
