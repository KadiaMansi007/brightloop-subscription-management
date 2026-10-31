<?php
require __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$product = ['name' => '', 'type' => 'service', 'sales_price' => '0.00', 'cost_price' => '0.00', 'notes' => ''];
$variants = [];
if ($id) {
    $s = db()->prepare('SELECT * FROM products WHERE id=?'); $s->execute([$id]);
    $product = $s->fetch() ?: redirect('admin/products.php');
    $s = db()->prepare('SELECT id,name,extra_price FROM product_variants WHERE product_id=? ORDER BY id'); $s->execute([$id]);
    $variants = $s->fetchAll();
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $product = ['name' => trim($_POST['name'] ?? ''), 'type' => $_POST['type'] ?? '', 'sales_price' => $_POST['sales_price'] ?? '',
                'cost_price' => $_POST['cost_price'] ?? '', 'notes' => trim($_POST['notes'] ?? '')];
    if ($product['name'] === '' || mb_strlen($product['name']) > 150) $errors[] = 'Product name is required (max 150 characters).';
    if (!in_array($product['type'], PRODUCT_TYPES, true)) $errors[] = 'Choose a valid product type.';
    foreach (['sales_price' => 'Sales price', 'cost_price' => 'Cost price'] as $k => $l)
        if (!is_numeric($product[$k]) || $product[$k] < 0 || $product[$k] > 9999999999) $errors[] = "$l must be a number of 0 or more.";
    $variants = []; $seen = [];
    foreach (($_POST['v_name'] ?? []) as $i => $vn) {
        $vn = trim($vn); $ve = $_POST['v_extra'][$i] ?? '0'; $vid = (int)($_POST['v_id'][$i] ?? 0);
        if ($vn === '' && ($ve === '' || (float)$ve == 0)) continue;           // ignore blank rows
        if ($vn === '' || mb_strlen($vn) > 100) { $errors[] = 'Every variant needs a name.'; continue; }
        if (!is_numeric($ve)) { $errors[] = "Variant '$vn' needs a numeric additional price."; continue; }
        if (isset($seen[mb_strtolower($vn)])) { $errors[] = "Duplicate variant '$vn'."; continue; }
        $seen[mb_strtolower($vn)] = 1;
        $variants[] = ['id' => $vid, 'name' => $vn, 'extra_price' => $ve];
    }
    if (!$errors) {
        $pdo = db(); $pdo->beginTransaction();
        try {
            if ($id) {
                $pdo->prepare('UPDATE products SET name=?,type=?,sales_price=?,cost_price=?,notes=? WHERE id=?')
                    ->execute([$product['name'], $product['type'], $product['sales_price'], $product['cost_price'], $product['notes'] ?: null, $id]);
            } else {
                $pdo->prepare('INSERT INTO products (name,type,sales_price,cost_price,notes) VALUES (?,?,?,?,?)')
                    ->execute([$product['name'], $product['type'], $product['sales_price'], $product['cost_price'], $product['notes'] ?: null]);
                $id = (int)$pdo->lastInsertId();
            }
            $keep = [];
            foreach ($variants as $v) {   // update existing rows in place so quotations/subscriptions keep their variant_id
                if ($v['id']) {
                    $pdo->prepare('UPDATE product_variants SET name=?,extra_price=? WHERE id=? AND product_id=?')->execute([$v['name'], $v['extra_price'], $v['id'], $id]);
                    $keep[] = $v['id'];
                } else {
                    $pdo->prepare('INSERT INTO product_variants (product_id,name,extra_price) VALUES (?,?,?)')->execute([$id, $v['name'], $v['extra_price']]);
                    $keep[] = (int)$pdo->lastInsertId();
                }
            }
            $existing = $pdo->prepare('SELECT id FROM product_variants WHERE product_id=?'); $existing->execute([$id]);
            foreach ($existing->fetchAll(PDO::FETCH_COLUMN) as $vid) {
                if (!in_array((int)$vid, $keep, true)) {
                    try { $pdo->prepare('DELETE FROM product_variants WHERE id=?')->execute([$vid]); }
                    catch (PDOException $x) { flash('error', 'A removed variant is in use and was kept.'); }
                }
            }
            $pdo->commit();
            audit($_POST['id'] ? 'update_product' : 'create_product', 'product', $id, $product['name']);
            flash('success', 'Product saved.'); redirect('admin/products.php');
        } catch (PDOException $ex) {
            $pdo->rollBack();
            $errors[] = $ex->getCode() === '23000' ? 'Duplicate variant name for this product.' : 'Database error while saving.';
            if (APP_ENV === 'dev') $errors[] = $ex->getMessage();
        }
    }
}
if (!$variants) $variants = [['id' => 0, 'name' => '', 'extra_price' => '0']];
$pageTitle = $id ? 'Edit product' : 'New product'; include __DIR__ . '/../includes/header.php'; ?>
<h1><?= e($pageTitle) ?></h1>
<?php foreach ($errors as $m): ?><div class="alert error"><?= e($m) ?></div><?php endforeach; ?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$id ?>">
<div class="row">
  <label>Product name<input name="name" value="<?= e($product['name']) ?>" required></label>
  <label>Type<select name="type"><?php foreach (PRODUCT_TYPES as $t): ?><option <?= $product['type'] === $t ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?></select></label>
  <label>Sales price (₹)<input id="sales" type="number" step="0.01" min="0" name="sales_price" value="<?= e($product['sales_price']) ?>" required></label>
  <label>Cost price (₹)<input type="number" step="0.01" min="0" name="cost_price" value="<?= e($product['cost_price']) ?>" required></label>
</div>
<label>Notes<textarea name="notes" rows="3"><?= e($product['notes']) ?></textarea></label>
<h2>Variants</h2><p class="muted">Additional price is added to the sales price when the variant is selected (e.g. Basic ₹0, Odoo ₹560, Premium ₹1,000).</p>
<table id="variants"><tr><th>Variant name</th><th>Additional price (₹)</th><th></th></tr>
<?php foreach ($variants as $v): ?><tr><td><input type="hidden" name="v_id[]" value="<?= (int)$v['id'] ?>"><input name="v_name[]" value="<?= e($v['name']) ?>"></td>
<td><input type="number" step="0.01" name="v_extra[]" value="<?= e($v['extra_price']) ?>"></td><td><button type="button" class="btn small danger rm">Remove</button></td></tr><?php endforeach; ?></table>
<button type="button" class="btn small" id="addVariant">+ Add variant</button>
<div class="preview"><strong>Price preview:</strong> variant <select id="pv"></select> → <strong id="pvPrice"></strong></div>
<p><button class="btn">Save product</button> <a href="<?= e(url('admin/products.php')) ?>">Cancel</a></p>
</form>
<script>
(function () {
  var t = document.getElementById('variants'), sales = document.getElementById('sales'), pv = document.getElementById('pv'), out = document.getElementById('pvPrice');
  function refresh() {
    var cur = pv.value; pv.innerHTML = '<option value="0">(no variant)</option>';
    t.querySelectorAll('tr').forEach(function (r, i) {
      var n = r.querySelector('[name="v_name[]"]'); if (!n || !n.value.trim()) return;
      var o = document.createElement('option'); o.value = r.querySelector('[name="v_extra[]"]').value || 0; o.textContent = n.value; pv.appendChild(o);
    });
    for (var i = 0; i < pv.options.length; i++) if (pv.options[i].textContent === cur) pv.selectedIndex = i;
    calc();
  }
  function calc() { out.textContent = '₹' + ((parseFloat(sales.value) || 0) + (parseFloat(pv.value) || 0)).toFixed(2); }
  document.getElementById('addVariant').onclick = function () {
    var r = t.insertRow(-1);
    r.innerHTML = '<td><input type="hidden" name="v_id[]" value="0"><input name="v_name[]"></td><td><input type="number" step="0.01" name="v_extra[]" value="0"></td><td><button type="button" class="btn small danger rm">Remove</button></td>';
  };
  t.addEventListener('click', function (e) { if (e.target.classList.contains('rm')) { e.target.closest('tr').remove(); refresh(); } });
  document.addEventListener('input', refresh); pv.addEventListener('change', calc); refresh();
})();
</script>
<?php include __DIR__ . '/../includes/footer.php';
