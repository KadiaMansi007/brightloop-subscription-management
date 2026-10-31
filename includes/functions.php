<?php
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
    session_start();
}
if (PHP_SAPI !== 'cli') {
    set_exception_handler(function (Throwable $t) {
        http_response_code(500);
        error_log($t);
        echo '<h1>Something went wrong</h1>';
        if (APP_ENV === 'dev') echo '<pre>' . htmlspecialchars($t->getMessage()) . '</pre>';
    });
}

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function url(string $p = ''): string { return BASE_URL . '/' . ltrim($p, '/'); }
function redirect(string $p) { header('Location: ' . url($p)); exit; }
function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }
function money($v): string { return '₹' . number_format((float)$v, 2); }
function old(string $k, $d = '') { return $_POST[$k] ?? $d; }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419); exit('Invalid or expired form token. Go back and reload the page.');
    }
}

function valid_email(string $e): bool { return strlen($e) <= 190 && filter_var($e, FILTER_VALIDATE_EMAIL) !== false; }

/** Returns list of error strings; empty array = password OK. "More than 8 characters" => at least 9. */
function validate_password(string $p): array {
    $err = [];
    if (strlen($p) <= 8) $err[] = 'Password must be more than 8 characters.';
    if (!preg_match('/[A-Z]/', $p)) $err[] = 'Password must contain an uppercase letter.';
    if (!preg_match('/[a-z]/', $p)) $err[] = 'Password must contain a lowercase letter.';
    if (!preg_match('/[^A-Za-z0-9]/', $p)) $err[] = 'Password must contain a special character.';
    return $err;
}

function audit(string $action, string $entity = '', ?int $id = null, string $details = ''): void {
    try {
        $uid = $_SESSION['uid'] ?? null;
        db()->prepare('INSERT INTO audit_logs (user_id,action,entity,entity_id,details,ip) VALUES (?,?,?,?,?,?)')
            ->execute([$uid, $action, $entity ?: null, $id, mb_substr($details, 0, 500), $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Throwable $t) { error_log('audit failed: ' . $t->getMessage()); }
}

function paginate(int $total, int $per = PAGE_SIZE): array {
    $pages = max(1, (int)ceil($total / $per));
    $page = min(max(1, (int)($_GET['page'] ?? 1)), $pages);
    return ['page' => $page, 'pages' => $pages, 'per' => $per, 'offset' => ($page - 1) * $per, 'total' => $total];
}
function pager(array $p): string {
    if ($p['pages'] < 2) return '';
    $q = $_GET; $o = '<div class="pager">';
    for ($i = 1; $i <= $p['pages']; $i++) {
        $q['page'] = $i;
        $o .= '<a class="' . ($i == $p['page'] ? 'on' : '') . '" href="?' . e(http_build_query($q)) . '">' . $i . '</a>';
    }
    return $o . '</div>';
}

/** Dev mail helper: tries mail(), always appends to uploads/mail.log (XAMPP usually has no SMTP). */
function send_mail(string $to, string $subject, string $body): void {
    @file_put_contents(__DIR__ . '/../uploads/mail.log', "[" . date('c') . "] To: $to\nSubject: $subject\n$body\n\n", FILE_APPEND);
    @mail($to, $subject, $body, 'From: no-reply@brightloop.local');
}
