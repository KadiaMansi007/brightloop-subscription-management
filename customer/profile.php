<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['customer']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $name = trim($_POST['name'] ?? ''); $phone = trim($_POST['phone'] ?? ''); $addr = trim($_POST['address'] ?? '');
    if ($name === '' || mb_strlen($name) > 120 || mb_strlen($phone) > 30 || mb_strlen($addr) > 255) flash('error', 'Please check your details (name is required).');
    else { db()->prepare('UPDATE users SET name=?, phone=?, address=? WHERE id=?')->execute([$name, $phone ?: null, $addr ?: null, (int)$u['id']]); audit('update_profile', 'user', (int)$u['id']); flash('success', 'Profile updated.'); }
    redirect('customer/profile.php');
}
$me = qrow('SELECT name,email,phone,address FROM users WHERE id=?', [(int)$u['id']]);
$pageTitle = 'My profile'; include __DIR__ . '/../includes/header.php'; ?>
<h1>My profile</h1>
<div class="card narrow"><form method="post"><?= csrf_field() ?>
<label>Email <input value="<?= e($me['email']) ?>" disabled></label>
<label>Name <input name="name" value="<?= e($me['name']) ?>" maxlength="120" required></label>
<label>Phone <input name="phone" value="<?= e($me['phone']) ?>" maxlength="30"></label>
<label>Address <input name="address" value="<?= e($me['address']) ?>" maxlength="255"></label>
<button class="btn">Save</button></form></div>
<?php include __DIR__ . '/../includes/footer.php';
