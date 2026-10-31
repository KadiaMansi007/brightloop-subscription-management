<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
function customer_id_for_session(): int {
    require_login();
    // This starter project uses users.id as customer_id for customer accounts.
    if (!in_array($_SESSION['role'] ?? '', ['customer'], true)) {
        http_response_code(403); exit('Customer account required.');
    }
    return current_user_id();
}
