<?php
require __DIR__ . '/../includes/bootstrap.php';
require_role(['admin']);
$c = db()->query("SELECT
  (SELECT COUNT(*) FROM users WHERE role='customer') customers,
  (SELECT COUNT(*) FROM users WHERE role='staff') staff,
  (SELECT COUNT(*) FROM products WHERE status='active') products,
  (SELECT COUNT(*) FROM plans WHERE status='active') plans,
  (SELECT COUNT(*) FROM taxes WHERE is_active=1) taxes")->fetch();
$pageTitle = 'Admin dashboard'; include __DIR__ . '/../includes/header.php'; ?>
<h1>Admin dashboard</h1>
<div class="cards">
<?php foreach (['customers' => 'Customers', 'staff' => 'Staff', 'products' => 'Active products', 'plans' => 'Active plans', 'taxes' => 'Active taxes'] as $k => $l): ?>
  <div class="stat"><span><?= (int)$c[$k] ?></span><?= e($l) ?></div>
<?php endforeach; ?></div>
<p class="muted">Revenue, invoices and subscription analytics (Participant 3) replace this page later.</p>
<?php include __DIR__ . '/../includes/footer.php';
