<?php
function current_user(): ?array {
    static $u = false;
    if ($u === false) {
        $u = null;
        if (!empty($_SESSION['uid'])) {
            $s = db()->prepare('SELECT id,name,email,role,status FROM users WHERE id=?');
            $s->execute([$_SESSION['uid']]);
            $r = $s->fetch();
            if ($r && $r['status'] === 'active') $u = $r; else unset($_SESSION['uid']);
        }
    }
    return $u;
}
function home_for(string $role): string {
    return ['admin' => 'admin/dashboard.php', 'staff' => 'staff/dashboard.php', 'customer' => 'customer/dashboard.php'][$role] ?? 'login.php';
}
function nav_for(string $role): array {
    if ($role === 'admin') return [
        'Dashboard' => 'admin/dashboard.php', 'Users' => 'admin/users.php',
        'Products' => 'admin/products.php', 'Plans' => 'admin/plans.php',
        'Taxes' => 'admin/taxes.php', 'Templates' => 'admin/templates.php',
        'Subscriptions' => 'admin/subscriptions.php', 'Invoices' => 'admin/invoices.php',
        'Discounts' => 'admin/discounts.php', 'Reports' => 'admin/reports.php'
    ];
    if ($role === 'staff') return ['Dashboard' => 'staff/dashboard.php', 'Subscriptions' => 'admin/subscriptions.php',
        'Invoices' => 'admin/invoices.php', 'Record payment' => 'staff/payments.php', 'Reports' => 'admin/reports.php'];
    return ['Dashboard' => 'customer/dashboard.php', 'Subscriptions' => 'customer/subscriptions.php',
        'Invoices' => 'customer/invoices.php', 'Payments' => 'customer/payments.php',
        'Profile' => 'customer/profile.php', 'Requests' => 'customer/requests.php'];
}
function login_user(array $u): void { session_regenerate_id(true); $_SESSION['uid'] = (int)$u['id']; }
function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) { $p = session_get_cookie_params(); setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']); }
    session_destroy();
}
function require_login(): array {
    $u = current_user();
    if (!$u) { flash('error', 'Please log in to continue.'); redirect('login.php'); }
    return $u;
}
/** Server-side authorisation. Call at the top of EVERY protected page. */
function require_role(array $roles): array {
    $u = require_login();
    if (!in_array($u['role'], $roles, true)) {
        http_response_code(403);
        exit('<h1>403 Forbidden</h1><p>You do not have permission to view this page. <a href="' . e(url(home_for($u['role']))) . '">Go to your dashboard</a></p>');
    }
    return $u;
}
