<?php
require __DIR__ . '/includes/bootstrap.php';
$done = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $s = db()->prepare("SELECT id,name FROM users WHERE email=? AND status='active'"); $s->execute([$email]);
    if ($u = $s->fetch()) {
        $token = bin2hex(random_bytes(32));
        db()->prepare('DELETE FROM password_resets WHERE user_id=?')->execute([$u['id']]);
        db()->prepare('INSERT INTO password_resets (user_id,token_hash,expires_at) VALUES (?,?,DATE_ADD(NOW(), INTERVAL ? MINUTE))')
            ->execute([$u['id'], hash('sha256', $token), RESET_TOKEN_MINUTES]);
        $scheme = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
        $link = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url('reset-password.php?token=' . $token);
        send_mail($email, 'Reset your Brightloop password', "Hi {$u['name']},\n\nReset your password (valid " . RESET_TOKEN_MINUTES . " minutes):\n$link\n");
        audit('password_reset_requested', 'user', (int)$u['id']);
        if (APP_ENV === 'dev') flash('info', 'DEV MODE reset link: ' . $link);
    }
    $done = true;   // same answer whether or not the email exists (no account enumeration)
}
$pageTitle = 'Forgot password'; include __DIR__ . '/includes/header.php'; ?>
<div class="card narrow"><h1>Forgot password</h1>
<?php if ($done): ?><div class="alert success">If that email is registered, a reset link has been sent.</div><?php endif; ?>
<form method="post"><?= csrf_field() ?>
  <label>Registered email<input type="email" name="email" required></label>
  <button class="btn">Send reset link</button>
</form>
<p><a href="<?= e(url('login.php')) ?>">Back to login</a></p></div>
<?php include __DIR__ . '/includes/footer.php';
