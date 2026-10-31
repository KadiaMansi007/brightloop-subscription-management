<?php
require __DIR__ . '/includes/bootstrap.php';
if (current_user()) redirect('index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $locked = ($_SESSION['lock_until'] ?? 0) > time();
    $email = strtolower(trim($_POST['email'] ?? ''));
    if ($locked) {
        $error = 'Too many failed attempts. Try again in a few minutes.';
    } else {
        $s = db()->prepare('SELECT * FROM users WHERE email=?'); $s->execute([$email]);
        $u = $s->fetch();
        $ok = $u && password_verify($_POST['password'] ?? '', $u['password_hash']);
        if ($ok && $u['status'] !== 'active') { $error = 'This account is inactive. Contact an administrator.'; }
        elseif ($ok) {
            unset($_SESSION['fails'], $_SESSION['lock_until']);
            if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT))
                db()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($_POST['password'], PASSWORD_DEFAULT), $u['id']]);
            login_user($u); audit('login', 'user', (int)$u['id']);
            redirect(home_for($u['role']));
        } else {
            $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
            if ($_SESSION['fails'] >= 5) { $_SESSION['lock_until'] = time() + 300; $_SESSION['fails'] = 0; }
            $error = 'Invalid email or password.';
        }
    }
}
$pageTitle = 'Login'; include __DIR__ . '/includes/header.php'; ?>
<div class="card narrow"><h1>Log in</h1>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?>
  <label>Email<input type="email" name="email" value="<?= e(old('email')) ?>" required></label>
  <label>Password<input type="password" name="password" required></label>
  <button class="btn">Log in</button>
</form>
<p><a href="<?= e(url('forgot-password.php')) ?>">Forgot password?</a> · <a href="<?= e(url('register.php')) ?>">Create account</a></p></div>
<?php include __DIR__ . '/includes/footer.php';
