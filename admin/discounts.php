<?php
require __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $a = $_POST['action'] ?? '';
    try {
        if ($a === 'create') {
            $name = trim($_POST['name'] ?? ''); $type = $_POST['type'] ?? ''; $val = $_POST['value'] ?? '';
            if ($name === '' || mb_strlen($name) > 120) throw new RuntimeException('Discount name is required.');
            if (!in_array($type, ['fixed', 'percentage'], true)) throw new RuntimeException('Choose a discount type.');
            if (!is_numeric($val) || $val <= 0 || ($type === 'percentage' && $val > 100)) throw new RuntimeException('Enter a valid value (percentage must be 1–100).');
            $d = fn($k) => ($_POST[$k] ?? '') !== '' ? $_POST[$k] : null;
            if ($d('start_date') && $d('end_date') && $d('end_date') < $d('start_date')) throw new RuntimeException('End date must be after the start date.');
            $prod = (int)($_POST['product_id'] ?? 0) ?: null;
            db()->prepare('INSERT INTO discounts (name,type,value,min_purchase,min_quantity,start_date,end_date,usage_limit,product_id) VALUES (?,?,?,?,?,?,?,?,?)')
                ->execute([$name, $type, $val, max(0, (float)($_POST['min_purchase'] ?? 0)), max(0, (int)($_POST['min_quantity'] ?? 0)), $d('start_date'), $d('end_date'), $d('usage_limit') !== null ? (int)$d('usage_limit') : null, $prod]);
            flash('success', 'Discount created.');
        } elseif ($a === 'toggle') {
            db()->prepare('UPDATE discounts SET is_active=1-is_active WHERE id=?')->execute([(int)$_POST['id']]); flash('success', 'Discount updated.');
        }
    } catch (Throwable $x) { flash('error', $x->getMessage()); }
    redirect('admin/discounts.php');
}
$products = qall("SELECT id,name FROM products WHERE status='active' ORDER BY name");
$rows = qall('SELECT d.*, p.name pname FROM discounts d LEFT JOIN products p ON p.id=d.product_id ORDER BY d.id DESC');
$pageTitle = 'Discounts'; include __DIR__ . '/../includes/header.php'; ?>
<h1>Discounts</h1>
<div class="card"><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="create">
<label>Name <input name="name" maxlength="120" required></label>
<label>Type <select name="type"><option value="percentage">Percentage</option><option value="fixed">Fixed amount</option></select></label>
<label>Value <input type="number" step="0.01" min="0.01" name="value" required></label>
<label>Minimum purchase <input type="number" step="0.01" min="0" name="min_purchase" value="0"></label>
<label>Minimum quantity <input type="number" min="0" name="min_quantity" value="0"></label>
<label>Start date <input type="date" name="start_date"></label><label>End date <input type="date" name="end_date"></label>
<label>Usage limit (blank = unlimited) <input type="number" min="1" name="usage_limit"></label>
<label>Only for product <select name="product_id"><option value="">All products</option><?php foreach ($products as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?></select></label>
<button class="btn">Create discount</button></form></div>
<table><tr><th>Name</th><th>Value</th><th>Rules</th><th>Used</th><th>Status</th><th></th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= $r['type'] === 'percentage' ? e($r['value']) . '%' : money($r['value']) ?></td>
<td>Min <?= money($r['min_purchase']) ?>, qty <?= (int)$r['min_quantity'] ?>, <?= e($r['start_date'] ?: 'any') ?> → <?= e($r['end_date'] ?: 'any') ?>, <?= e($r['pname'] ?: 'all products') ?></td>
<td><?= (int)$r['times_used'] ?><?= $r['usage_limit'] !== null ? ' / ' . (int)$r['usage_limit'] : '' ?></td><td><?= status_badge($r['is_active'] ? 'active' : 'inactive') ?></td><td><?= act_form((int)$r['id'], 'toggle', $r['is_active'] ? 'Disable' : 'Enable') ?></td></tr><?php endforeach; ?></table>
<?php include __DIR__ . '/../includes/footer.php';
