<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../config/database.php';

function require_login(): void {
    if (empty($_SESSION['user_id'])) { http_response_code(401); exit('Please log in first.'); }
}
function require_roles(array $roles): void {
    require_login();
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) { http_response_code(403); exit('Access denied.'); }
}
function require_admin(): void { require_roles(['admin','administrator']); }
function require_staff(): void { require_roles(['admin','administrator','staff']); }
function current_user_id(): int { require_login(); return (int)$_SESSION['user_id']; }
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
