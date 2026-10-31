<?php
require __DIR__ . '/includes/bootstrap.php';
$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$s = db()->prepare('SELECT * FROM password_resets WHERE token_hash=? AND used_at IS NULL AND expires_at > NOW()');
$s->execute([hash('sha256', $token)]);
$reset = $token !== '' ? $s->fetch() : false;
$errors = [];
if ($reset && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pw = $_POST['password'] ?? '';
    $errors = validate_password($pw);
    if ($pw !== ($_POST['confirm'] ?? '')) $errors[] = 'Passwords do not match.';
    if (!$errors) {
        $pdo = db(); $pdo->beginTransaction();
        $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($pw, PASSWORD_DEFAULT), $reset['user_id']]);
        $pdo->prepare('UPDATE password_resets SET used_at=NOW() WHERE id=?')->execute([$reset['id']]);
        $pdo->commit();
        audit('password_reset', 'user', (int)$reset['user_id']);
        flash('success', 'Password updated. Please log in.');
        redirect('login.php');
    }
}
$pageTitle = 'Reset password'; include __DIR__ . '/includes/header.php'; ?>
<div class="card narrow"><h1>Reset password</h1>
<?php if (!$reset): ?>
  <div class="alert error">This reset link is invalid or has expired.</div>
  <p><a href="<?= e(url('forgot-password.php')) ?>">Request a new link</a></p>
<?php else: foreach ($errors as $m): ?><div class="alert error"><?= e($m) ?></div><?php endforeach; ?>
<form method="post"><?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>">
  <label>New password<input type="password" name="password" required></label>
  <label>Confirm password<input type="password" name="confirm" required></label>
  <button class="btn">Update password</button>
</form><?php endif; ?></div>
<?php include __DIR__ . '/includes/footer.php';
