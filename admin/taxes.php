<?php
require __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);
$errors = []; $editId = (int)($_GET['edit'] ?? $_POST['id'] ?? 0);
$tax = ['name' => '', 'percentage' => '', 'tax_type' => 'GST', 'applies_to_all' => 1, 'is_active' => 1]; $linked = [];
if ($editId) {
    $s = db()->prepare('SELECT * FROM taxes WHERE id=?'); $s->execute([$editId]);
    if ($r = $s->fetch()) { $tax = $r; $l = db()->prepare('SELECT product_id FROM tax_products WHERE tax_id=?'); $l->execute([$editId]); $linked = array_map('intval', $l->fetchAll(PDO::FETCH_COLUMN)); }
    else $editId = 0;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $a = $_POST['action'] ?? 'save';
    if ($a === 'toggle') { db()->prepare('UPDATE taxes SET is_active=1-is_active WHERE id=?')->execute([(int)$_POST['id']]); audit('toggle_tax', 'tax', (int)$_POST['id']); redirect('admin/taxes.php'); }
    $tax = ['name' => trim($_POST['name'] ?? ''), 'percentage' => $_POST['percentage'] ?? '', 'tax_type' => $_POST['tax_type'] ?? '',
            'applies_to_all' => isset($_POST['applies_to_all']) ? 1 : 0, 'is_active' => isset($_POST['is_active']) ? 1 : 0];
    $linked = array_map('intval', $_POST['products'] ?? []);
    if ($tax['name'] === '' || mb_strlen($tax['name']) > 100) $errors[] = 'Tax name is required.';
    if (!is_numeric($tax['percentage']) || $tax['percentage'] < 0 || $tax['percentage'] > 100) $errors[] = 'Percentage must be between 0 and 100.';
    if (!in_array($tax['tax_type'], TAX_TYPES, true)) $errors[] = 'Choose a tax type.';
    if (!$tax['applies_to_all'] && !$linked) $errors[] = 'Select at least one product, or tick "applies to all products".';
    if (!$errors) {
        $pdo = db(); $pdo->beginTransaction();
        if ($editId) $pdo->prepare('UPDATE taxes SET name=?,percentage=?,tax_type=?,applies_to_all=?,is_active=? WHERE id=?')->execute([$tax['name'], $tax['percentage'], $tax['tax_type'], $tax['applies_to_all'], $tax['is_active'], $editId]);
        else { $pdo->prepare('INSERT INTO taxes (name,percentage,tax_type,applies_to_all,is_active) VALUES (?,?,?,?,?)')->execute([$tax['name'], $tax['percentage'], $tax['tax_type'], $tax['applies_to_all'], $tax['is_active']]); $editId = (int)$pdo->lastInsertId(); }
        $pdo->prepare('DELETE FROM tax_products WHERE tax_id=?')->execute([$editId]);
        if (!$tax['applies_to_all']) foreach (array_unique($linked) as $pid) $pdo->prepare('INSERT IGNORE INTO tax_products (tax_id,product_id) VALUES (?,?)')->execute([$editId, $pid]);
        $pdo->commit(); audit('save_tax', 'tax', $editId, $tax['name']); flash('success', 'Tax saved.'); redirect('admin/taxes.php');
    }
}
$products = db()->query("SELECT id,name FROM products WHERE status='active' ORDER BY name")->fetchAll();
$rows = db()->query('SELECT t.*, (SELECT COUNT(*) FROM tax_products tp WHERE tp.tax_id=t.id) n FROM taxes t ORDER BY t.id DESC')->fetchAll();
$pageTitle = 'Taxes'; include __DIR__ . '/../includes/header.php'; ?>
<h1>Tax rules</h1>
<?php foreach ($errors as $m): ?><div class="alert error"><?= e($m) ?></div><?php endforeach; ?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$editId ?>">
<h2><?= $editId ? 'Edit tax' : 'New tax' ?></h2>
<div class="row"><label>Tax name<input name="name" value="<?= e($tax['name']) ?>" required></label>
<label>Percentage (%)<input type="number" step="0.01" min="0" max="100" name="percentage" value="<?= e($tax['percentage']) ?>" required></label>
<label>Type<select name="tax_type"><?php foreach (TAX_TYPES as $t): ?><option <?= $tax['tax_type'] === $t ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?></select></label></div>
<div class="checks"><label><input type="checkbox" name="is_active" <?= $tax['is_active'] ? 'checked' : '' ?>> Active</label>
<label><input type="checkbox" name="applies_to_all" <?= $tax['applies_to_all'] ? 'checked' : '' ?>> Applies to all products</label></div>
<p class="muted">Or choose specific products (ignored when "all products" is ticked):</p>
<div class="checks"><?php foreach ($products as $p): ?><label><input type="checkbox" name="products[]" value="<?= (int)$p['id'] ?>" <?= in_array((int)$p['id'], $linked, true) ? 'checked' : '' ?>> <?= e($p['name']) ?></label><?php endforeach; ?></div>
<button class="btn">Save tax</button> <?php if ($editId): ?><a href="<?= e(url('admin/taxes.php')) ?>">Cancel edit</a><?php endif; ?></form>
<p class="muted">Example: ₹1,000 product + 18% tax = ₹180 tax → ₹1,180 total (calculated by <code>calc_line()</code>).</p>
<table><tr><th>Name</th><th>%</th><th>Type</th><th>Scope</th><th>Status</th><th></th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= e($r['percentage']) ?>%</td><td><?= e($r['tax_type']) ?></td>
<td><?= $r['applies_to_all'] ? 'All products' : (int)$r['n'] . ' product(s)' ?></td><td><span class="badge <?= $r['is_active'] ? 'active' : 'inactive' ?>"><?= $r['is_active'] ? 'active' : 'inactive' ?></span></td>
<td class="actions"><a class="btn small" href="?edit=<?= (int)$r['id'] ?>">Edit</a><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn small" name="action" value="toggle"><?= $r['is_active'] ? 'Deactivate' : 'Activate' ?></button></form></td></tr>
<?php endforeach; ?></table>
<?php include __DIR__ . '/../includes/footer.php';
