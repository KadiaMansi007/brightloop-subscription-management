<?php
require __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $a = $_POST['action'] ?? '';
    try {
        if ($a === 'create') {
            $name = trim($_POST['name'] ?? ''); $plan = (int)($_POST['plan_id'] ?? 0); $var = (int)($_POST['variant_id'] ?? 0) ?: null;
            $qty = max(1, (int)($_POST['quantity'] ?? 1)); $val = max(1, min(365, (int)($_POST['validity_days'] ?? 30)));
            if ($name === '' || mb_strlen($name) > 150) throw new RuntimeException('Template name is required.');
            $planRow = plan_lines($plan, $var, $qty)[0];
            $pdo = db(); $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO quotation_templates (name,plan_id,validity_days,created_by) VALUES (?,?,?,?)')->execute([$name, $plan, $val, $_SESSION['uid']]);
            $tid = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO quotation_template_items (template_id,product_id,variant_id,quantity) VALUES (?,?,?,?)')->execute([$tid, $planRow['product_id'], $var, $qty]);
            $pdo->commit(); audit('create_template', 'template', $tid); flash('success', 'Template saved.');
        } elseif ($a === 'delete') {
            db()->prepare('DELETE FROM quotation_templates WHERE id=?')->execute([(int)$_POST['id']]); flash('success', 'Template deleted.');
        }
    } catch (Throwable $x) { if (db()->inTransaction()) db()->rollBack(); flash('error', $x->getMessage()); }
    redirect('admin/templates.php');
}
$plans = qall("SELECT id,name,price,billing_frequency FROM plans WHERE status='active' ORDER BY name");
$variants = qall('SELECT v.id, v.name, p.name pname FROM product_variants v JOIN products p ON p.id=v.product_id ORDER BY p.name, v.id');
$rows = qall('SELECT t.*, pl.name plan, i.quantity, v.name vname FROM quotation_templates t LEFT JOIN plans pl ON pl.id=t.plan_id LEFT JOIN quotation_template_items i ON i.template_id=t.id LEFT JOIN product_variants v ON v.id=i.variant_id ORDER BY t.id DESC');
$pageTitle = 'Quotation templates'; include __DIR__ . '/../includes/header.php'; ?>
<h1>Quotation templates</h1>
<div class="card"><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="create">
<label>Name <input name="name" maxlength="150" required></label>
<label>Plan <select name="plan_id"><?php foreach ($plans as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?> – <?= money($p['price']) ?>/<?= e($p['billing_frequency']) ?></option><?php endforeach; ?></select></label>
<label>Variant <select name="variant_id"><option value="">-- none --</option><?php foreach ($variants as $v): ?><option value="<?= (int)$v['id'] ?>"><?= e($v['pname']) ?> – <?= e($v['name']) ?></option><?php endforeach; ?></select></label>
<label>Quantity <input type="number" name="quantity" value="1" min="1"></label>
<label>Validity (days) <input type="number" name="validity_days" value="30" min="1" max="365"></label>
<button class="btn">Save template</button></form></div>
<table><tr><th>Name</th><th>Plan</th><th>Variant</th><th>Qty</th><th>Validity</th><th></th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= e($r['plan']) ?></td><td><?= e($r['vname'] ?: '—') ?></td><td><?= (int)$r['quantity'] ?></td><td><?= (int)$r['validity_days'] ?> days</td><td><?= act_form((int)$r['id'], 'delete', 'Delete') ?></td></tr><?php endforeach; ?></table>
<?php include __DIR__ . '/../includes/footer.php';
