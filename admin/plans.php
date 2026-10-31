<?php
require __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);
$errors = []; $editId = (int)($_GET['edit'] ?? $_POST['id'] ?? 0);
$blank = ['name' => '', 'product_id' => '', 'price' => '', 'billing_frequency' => 'monthly', 'min_quantity' => 1, 'available_from' => '', 'available_until' => '',
          'auto_close' => 0, 'allow_early_close' => 0, 'allow_pause' => 0, 'allow_renewal' => 1, 'status' => 'active'];
$plan = $blank;
if ($editId) { $s = db()->prepare('SELECT * FROM plans WHERE id=?'); $s->execute([$editId]); $found = $s->fetch(); if ($found) $plan = $found; else $editId = 0; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $a = $_POST['action'] ?? 'save';
    if ($a === 'toggle' || $a === 'delete') {
        $pid = (int)$_POST['id'];
        if ($a === 'toggle') { db()->prepare("UPDATE plans SET status=IF(status='active','inactive','active') WHERE id=?")->execute([$pid]); flash('success', 'Plan status updated.'); }
        else { try { db()->prepare('DELETE FROM plans WHERE id=?')->execute([$pid]); flash('success', 'Plan deleted.'); } catch (PDOException $x) { flash('error', 'Plan is in use. Set it inactive instead.'); } }
        audit($a . '_plan', 'plan', $pid); redirect('admin/plans.php');
    }
    $plan = ['name' => trim($_POST['name'] ?? ''), 'product_id' => (int)($_POST['product_id'] ?? 0), 'price' => $_POST['price'] ?? '',
        'billing_frequency' => $_POST['billing_frequency'] ?? '', 'min_quantity' => $_POST['min_quantity'] ?? '',
        'available_from' => $_POST['available_from'] ?? '', 'available_until' => $_POST['available_until'] ?? '',
        'auto_close' => isset($_POST['auto_close']) ? 1 : 0, 'allow_early_close' => isset($_POST['allow_early_close']) ? 1 : 0,
        'allow_pause' => isset($_POST['allow_pause']) ? 1 : 0, 'allow_renewal' => isset($_POST['allow_renewal']) ? 1 : 0, 'status' => 'active'];
    if ($plan['name'] === '' || mb_strlen($plan['name']) > 150) $errors[] = 'Plan name is required.';
    $ps = db()->prepare('SELECT 1 FROM products WHERE id=?'); $ps->execute([$plan['product_id']]);
    if (!$ps->fetchColumn()) $errors[] = 'Select a valid product.';
    if (!is_numeric($plan['price']) || $plan['price'] < 0) $errors[] = 'Price must be 0 or more.';
    if (!in_array($plan['billing_frequency'], BILLING_FREQUENCIES, true)) $errors[] = 'Choose daily, weekly, monthly or yearly billing.';
    if (!ctype_digit((string)$plan['min_quantity']) || (int)$plan['min_quantity'] < 1) $errors[] = 'Minimum quantity must be at least 1.';
    foreach (['available_from', 'available_until'] as $k) {
        if ($plan[$k] !== '' && !DateTime::createFromFormat('Y-m-d', $plan[$k])) $errors[] = 'Dates must be valid.';
    }
    if ($plan['available_from'] !== '' && $plan['available_until'] !== '' && $plan['available_until'] < $plan['available_from']) $errors[] = '"Available until" cannot be before "available from".';
    if (!$errors) {
        $vals = [$plan['name'], $plan['product_id'], $plan['price'], $plan['billing_frequency'], (int)$plan['min_quantity'], $plan['available_from'] ?: null,
                 $plan['available_until'] ?: null, $plan['auto_close'], $plan['allow_early_close'], $plan['allow_pause'], $plan['allow_renewal']];
        if ($editId) { db()->prepare('UPDATE plans SET name=?,product_id=?,price=?,billing_frequency=?,min_quantity=?,available_from=?,available_until=?,auto_close=?,allow_early_close=?,allow_pause=?,allow_renewal=? WHERE id=?')->execute([...$vals, $editId]); }
        else { db()->prepare('INSERT INTO plans (name,product_id,price,billing_frequency,min_quantity,available_from,available_until,auto_close,allow_early_close,allow_pause,allow_renewal) VALUES (?,?,?,?,?,?,?,?,?,?,?)')->execute($vals); $editId = (int)db()->lastInsertId(); }
        audit('save_plan', 'plan', $editId, $plan['name']); flash('success', 'Plan saved.'); redirect('admin/plans.php');
    }
}
$products = db()->query("SELECT id,name FROM products WHERE status='active' ORDER BY name")->fetchAll();
$rows = db()->query('SELECT pl.*, p.name product_name FROM plans pl JOIN products p ON p.id=pl.product_id ORDER BY pl.id DESC LIMIT 200')->fetchAll();
$pageTitle = 'Plans'; include __DIR__ . '/../includes/header.php'; ?>
<h1>Subscription plans</h1>
<?php foreach ($errors as $m): ?><div class="alert error"><?= e($m) ?></div><?php endforeach; ?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$editId ?>">
<h2><?= $editId ? 'Edit plan #' . (int)$editId : 'New plan' ?></h2>
<div class="row">
<label>Plan name<input name="name" value="<?= e($plan['name']) ?>" required></label>
<label>Product<select name="product_id"><option value="">Select…</option><?php foreach ($products as $p): ?><option value="<?= (int)$p['id'] ?>" <?= (int)$plan['product_id'] === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?></select></label>
<label>Price (₹)<input type="number" step="0.01" min="0" name="price" value="<?= e($plan['price']) ?>" required></label>
<label>Billing frequency<select name="billing_frequency"><?php foreach (BILLING_FREQUENCIES as $f): ?><option <?= $plan['billing_frequency'] === $f ? 'selected' : '' ?>><?= e($f) ?></option><?php endforeach; ?></select></label>
<label>Minimum quantity<input type="number" min="1" name="min_quantity" value="<?= e($plan['min_quantity']) ?>" required></label>
<label>Available from<input type="date" name="available_from" value="<?= e($plan['available_from']) ?>"></label>
<label>Available until<input type="date" name="available_until" value="<?= e($plan['available_until']) ?>"></label></div>
<div class="checks">
<label><input type="checkbox" name="auto_close" <?= $plan['auto_close'] ? 'checked' : '' ?>> Close automatically at expiry</label>
<label><input type="checkbox" name="allow_early_close" <?= $plan['allow_early_close'] ? 'checked' : '' ?>> Allow early closing</label>
<label><input type="checkbox" name="allow_pause" <?= $plan['allow_pause'] ? 'checked' : '' ?>> Allow pause</label>
<label><input type="checkbox" name="allow_renewal" <?= $plan['allow_renewal'] ? 'checked' : '' ?>> Allow renewal</label></div>
<button class="btn">Save plan</button> <?php if ($editId): ?><a href="<?= e(url('admin/plans.php')) ?>">Cancel edit</a><?php endif; ?>
</form>
<table><tr><th>Plan</th><th>Product</th><th>Price</th><th>Billing</th><th>Min qty</th><th>Available</th><th>Flags</th><th>Status</th><th></th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= e($r['product_name']) ?></td><td><?= money($r['price']) ?></td><td><?= e($r['billing_frequency']) ?></td><td><?= (int)$r['min_quantity'] ?></td>
<td><?= e($r['available_from'] ?: '…') ?> → <?= e($r['available_until'] ?: '…') ?></td>
<td><?= $r['auto_close'] ? 'auto-close ' : '' ?><?= $r['allow_early_close'] ? 'early-close ' : '' ?><?= $r['allow_pause'] ? 'pause ' : '' ?><?= $r['allow_renewal'] ? 'renew' : '' ?></td>
<td><span class="badge <?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
<td class="actions"><a class="btn small" href="?edit=<?= (int)$r['id'] ?>">Edit</a>
<form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn small" name="action" value="toggle"><?= $r['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button>
<button class="btn small danger" name="action" value="delete" onclick="return confirm('Delete this plan?')">Delete</button></form></td></tr>
<?php endforeach; ?></table>
<?php include __DIR__ . '/../includes/footer.php';
