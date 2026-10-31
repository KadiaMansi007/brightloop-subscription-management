<?php
require __DIR__ . '/../includes/bootstrap.php';
$me = require_role(['admin']);   // ONLY admins may create staff
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'create_staff') {
        $name = trim($_POST['name'] ?? ''); $email = strtolower(trim($_POST['email'] ?? '')); $pw = $_POST['password'] ?? '';
        if ($name === '' || mb_strlen($name) > 120) $errors[] = 'Name is required.';
        if (!valid_email($email)) $errors[] = 'Enter a valid email.';
        $errors = array_merge($errors, validate_password($pw));
        if (!$errors) {
            $s = db()->prepare('SELECT 1 FROM users WHERE email=?'); $s->execute([$email]);
            if ($s->fetchColumn()) $errors[] = 'This email is already registered.';
        }
        if (!$errors) {
            db()->prepare("INSERT INTO users (name,email,password_hash,role,created_by) VALUES (?,?,?,'staff',?)")
                ->execute([$name, $email, password_hash($pw, PASSWORD_DEFAULT), $me['id']]);
            audit('create_staff', 'user', (int)db()->lastInsertId(), $email);
            flash('success', 'Staff account created.'); redirect('admin/users.php');
        }
    } elseif ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$me['id']) { flash('error', 'You cannot deactivate your own account.'); }
        else {
            db()->prepare("UPDATE users SET status = IF(status='active','inactive','active') WHERE id=?")->execute([$id]);
            audit('toggle_user_status', 'user', $id); flash('success', 'User status updated.');
        }
        redirect('admin/users.php');
    }
}
$q = trim($_GET['q'] ?? ''); $role = $_GET['role'] ?? '';
$where = ['1=1']; $args = [];
if ($q !== '') { $where[] = '(name LIKE ? OR email LIKE ?)'; $args[] = "%$q%"; $args[] = "%$q%"; }
if (in_array($role, ['admin', 'staff', 'customer'], true)) { $where[] = 'role=?'; $args[] = $role; }
$w = implode(' AND ', $where);
$c = db()->prepare("SELECT COUNT(*) FROM users WHERE $w"); $c->execute($args);
$pg = paginate((int)$c->fetchColumn());
$s = db()->prepare("SELECT id,name,email,role,status,created_at FROM users WHERE $w ORDER BY id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}");
$s->execute($args); $users = $s->fetchAll();
$pageTitle = 'Users'; include __DIR__ . '/../includes/header.php'; ?>
<h1>Users</h1>
<div class="card"><h2>Add staff member</h2>
<?php foreach ($errors as $m): ?><div class="alert error"><?= e($m) ?></div><?php endforeach; ?>
<form method="post" class="row"><?= csrf_field() ?><input type="hidden" name="action" value="create_staff">
  <label>Name<input name="name" value="<?= e(old('name')) ?>" required></label>
  <label>Email<input type="email" name="email" value="<?= e(old('email')) ?>" required></label>
  <label>Password<input type="password" name="password" required></label>
  <button class="btn">Create staff</button>
</form></div>
<form method="get" class="row filters"><input name="q" placeholder="Search name/email" value="<?= e($q) ?>">
  <select name="role"><option value="">All roles</option><?php foreach (['admin', 'staff', 'customer'] as $r): ?><option <?= $role === $r ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?></select>
  <button class="btn small">Filter</button></form>
<table><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th></th></tr>
<?php foreach ($users as $r): ?><tr><td><?= (int)$r['id'] ?></td><td><?= e($r['name']) ?></td><td><?= e($r['email']) ?></td>
<td><?= e($r['role']) ?></td><td><span class="badge <?= e($r['status']) ?>"><?= e($r['status']) ?></span></td><td><?= e($r['created_at']) ?></td>
<td><?php if ((int)$r['id'] !== (int)$me['id']): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn small"><?= $r['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button></form><?php endif; ?></td></tr>
<?php endforeach; ?></table><?= pager($pg) ?>
<?php include __DIR__ . '/../includes/footer.php';
