<?php
require_once __DIR__.'/../includes/bootstrap.php';
require_once __DIR__.'/../modules/billing.php';
$count=generate_recurring_invoices(db());
echo "Automated billing completed: {$count} invoice(s) generated.".PHP_EOL;
