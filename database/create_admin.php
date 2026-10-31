<?php
// Secure initial admin: php database/create_admin.php "Admin Name" admin@example.com "Str0ng!Passw0rd"
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../includes/bootstrap.php';
[$self, $name, $email, $pw] = array_pad($argv, 4, '');
$email = strtolower(trim($email));
if ($name === '' || !valid_email($email) || ($errs = validate_password($pw))) {
    fwrite(STDERR, "Usage: php database/create_admin.php \"Name\" email \"Password\"\n" . implode("\n", $errs ?? []) . "\n"); exit(1);
}
$s = db()->prepare('SELECT id FROM users WHERE email=?'); $s->execute([$email]);
if ($s->fetch()) { fwrite(STDERR, "Email already exists.\n"); exit(1); }
db()->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,'admin')")->execute([$name, $email, password_hash($pw, PASSWORD_DEFAULT)]);
echo "Admin created: $email\n";
