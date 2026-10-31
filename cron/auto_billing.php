<?php
require_once '../includes/bootstrap.php';
require_once '../modules/billing.php';

$count = generate_recurring_invoices($pdo);
echo "Automated Billing Executed: {$count} invoices generated successfully.";
