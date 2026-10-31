<?php
// Creates demo staff + customers: php database/seed_demo.php   (run after schema.sql and seed.sql)
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../includes/bootstrap.php';
$users = [['Sam Staff', 'staff@brightloop.test', 'Staff@12345', 'staff'], ['Asha Patel', 'customer1@brightloop.test', 'Customer@123', 'customer'], ['Rohan Shah', 'customer2@brightloop.test', 'Customer@123', 'customer']];
foreach ($users as [$n, $em, $pw, $role]) {
    if (q1('SELECT 1 FROM users WHERE email=?', [$em])) { echo "exists: $em\n"; continue; }
    db()->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)')->execute([$n, $em, password_hash($pw, PASSWORD_DEFAULT), $role]);
    echo "created $role: $em / $pw\n";
}
