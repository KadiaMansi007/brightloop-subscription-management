<?php
require __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0); $a = $_POST['action'] ?? '';
    if ($a === 'archive' || $a === 'restore') {
        db()->prepare('UPDATE products SET status=? WHERE id=?')->execute([$a === 'archive' ? 'archived' : 'active', $id]);
        audit($a . '_product', 'product', $id); flash('success', 'Product ' . $a . 'd.');
    } elseif ($a === 'delete') {
        try { db()->prepare('DELETE FROM products WHERE id=?')->execute([$id]); audit('delete_product', 'product', $id); flash('success', 'Product deleted.'); }
        catch (PDOException $ex) { flash('error', 'This product is used by plans or subscriptions, so it cannot be deleted. Archive it instead.'); }
    }
    redirect('admin/products.php');
}
$q = trim($_GET['q'] ?? ''); $st = $_GET['status'] ?? '';
$where = ['1=1']; $args = [];
if ($q !== '') { $where[] = 'p.name LIKE ?'; $args[] = "%$q%"; }
if (in_array($st, ['active', 'archived'], true)) { $where[] = 'p.status=?'; $args[] = $st; }
$w = implode(' AND ', $where);
$c = db()->prepare("SELECT COUNT(*) FROM products p WHERE $w"); $c->execute($args);
$pg = paginate((int)$c->fetchColumn());
$s = db()->prepare("SELECT p.*, (SELECT COUNT(*) FROM product_variants v WHERE v.product_id=p.id) variants
  FROM products p WHERE $w ORDER BY p.id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}");
$s->execute($args); $rows = $s->fetchAll();
$pageTitle = 'Products'; include __DIR__ . '/../includes/header.php'; ?>
<div class="between"><h1>Products</h1><a class="btn" href="<?= e(url('admin/product-form.php')) ?>">+ New product</a></div>
<form method="get" class="row filters"><input name="q" placeholder="Search name" value="<?= e($q) ?>">
<select name="status"><option value="">All</option><option value="active" <?= $st === 'active' ? 'selected' : '' ?>>Active</option><option value="archived" <?= $st === 'archived' ? 'selected' : '' ?>>Archived</option></select>
<button class="btn small">Filter</button></form>
<table><tr><th>Name</th><th>Type</th><th>Sales price</th><th>Cost price</th><th>Variants</th><th>Status</th><th></th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= e($r['type']) ?></td><td><?= money($r['sales_price']) ?></td><td><?= money($r['cost_price']) ?></td>
<td><?= (int)$r['variants'] ?></td><td><span class="badge <?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
<td class="actions"><a class="btn small" href="<?= e(url('admin/product-form.php?id=' . $r['id'])) ?>">Edit</a>
<form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
<button class="btn small" name="action" value="<?= $r['status'] === 'active' ? 'archive' : 'restore' ?>"><?= $r['status'] === 'active' ? 'Archive' : 'Restore' ?></button>
<button class="btn small danger" name="action" value="delete" onclick="return confirm('Delete this product permanently?')">Delete</button></form></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="7">No products found.</td></tr><?php endif; ?></table><?= pager($pg) ?>
<?php include __DIR__ . '/../includes/footer.php';
