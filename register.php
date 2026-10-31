<?php
require __DIR__ . '/includes/bootstrap.php';
if (current_user()) redirect('index.php');
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $pw = $_POST['password'] ?? '';
    if ($name === '' || mb_strlen($name) > 120) $errors[] = 'Name is required (max 120 characters).';
    if (!valid_email($email)) $errors[] = 'Enter a valid email address.';
    if ($phone !== '' && !preg_match('/^[0-9+\-\s]{6,30}$/', $phone)) $errors[] = 'Phone number format is invalid.';
    $errors = array_merge($errors, validate_password($pw));
    if ($pw !== ($_POST['confirm'] ?? '')) $errors[] = 'Passwords do not match.';
    if (!$errors) {
        $s = db()->prepare('SELECT 1 FROM users WHERE email=?'); $s->execute([$email]);
        if ($s->fetchColumn()) $errors[] = 'This email is already registered.';
    }
    if (!$errors) {
        try {   // role is hard-coded: public registration can only ever create customers
            db()->prepare("INSERT INTO users (name,email,password_hash,role,phone) VALUES (?,?,?, 'customer', ?)")
                ->execute([$name, $email, password_hash($pw, PASSWORD_DEFAULT), $phone ?: null]);
            audit('register', 'user', (int)db()->lastInsertId());
            flash('success', 'Account created. Please log in.');
            redirect('login.php');
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') $errors[] = 'This email is already registered.'; else throw $ex;
        }
    }
}
$pageTitle = 'Register'; include __DIR__ . '/includes/header.php'; ?>
<div class="card narrow"><h1>Create customer account</h1>
<?php foreach ($errors as $m): ?><div class="alert error"><?= e($m) ?></div><?php endforeach; ?>
<form method="post" novalidate><?= csrf_field() ?>
  <label>Full name<input name="name" value="<?= e(old('name')) ?>" required></label>
  <label>Email<input type="email" name="email" value="<?= e(old('email')) ?>" required></label>
  <label>Phone (optional)<input name="phone" value="<?= e(old('phone')) ?>"></label>
  <label>Password<input type="password" name="password" required></label>
  <small>More than 8 characters, with uppercase, lowercase and a special character.</small>
  <label>Confirm password<input type="password" name="confirm" required></label>
  <button class="btn">Register</button>
</form>
<p>Already registered? <a href="<?= e(url('login.php')) ?>">Log in</a></p></div>
<?php include __DIR__ . '/includes/footer.php';
