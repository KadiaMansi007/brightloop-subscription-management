<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['customer']); $id = (int)$u['id'];
$c = [
 'Active subscriptions' => (int)q1("SELECT COUNT(*) FROM subscriptions WHERE customer_id=? AND status='active'", [$id]),
 'Unpaid invoices' => (int)q1("SELECT COUNT(*) FROM invoices WHERE customer_id=? AND status='confirmed'", [$id]),
 'Balance due' => money(q1("SELECT COALESCE(SUM(total-amount_paid),0) FROM invoices WHERE customer_id=? AND status='confirmed'", [$id])),
 'Next billing' => (string)(q1("SELECT MIN(next_billing_date) FROM subscriptions WHERE customer_id=? AND status='active' AND is_paused=0", [$id]) ?: '—'),
];
$notes = qall('SELECT title,message,created_at FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 5', [$id]);
db()->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$id]);
$pageTitle = 'My account'; include __DIR__ . '/../includes/header.php'; ?>
<h1>My account</h1><p>Welcome, <?= e($u['name']) ?>.</p>
<div class="cards"><?php foreach ($c as $l => $v): ?><div class="stat"><span><?= e($v) ?></span><?= e($l) ?></div><?php endforeach; ?></div>
<h2>Notifications</h2>
<?php foreach ($notes as $n): ?><div class="card"><strong><?= e($n['title']) ?></strong> <span class="muted"><?= e($n['created_at']) ?></span><br><?= e($n['message']) ?></div><?php endforeach; ?>
<?php if (!$notes): ?><p class="muted">No notifications yet.</p><?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php';
