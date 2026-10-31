<?php
// Run daily: php cron/auto_billing.php   (Windows Task Scheduler / cron). CLI only; admins can also use the button on Invoices.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../includes/bootstrap.php';
[$n, $errs] = run_auto_billing();
echo "Automated billing: $n draft invoice(s) generated.\n";
foreach ($errs as $m) echo "  ! $m\n";
