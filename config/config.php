<?php
// Put overrides in config/config.local.php (git-ignored) if your setup differs.
if (file_exists(__DIR__ . '/config.local.php')) require __DIR__ . '/config.local.php';
defined('APP_NAME')  || define('APP_NAME', 'Brightloop Subscriptions');
defined('APP_ENV')   || define('APP_ENV', 'dev');            // 'prod' hides reset links and error details
defined('BASE_URL')  || define('BASE_URL', '/brightloop-subscription-management'); // folder inside htdocs
defined('DB_HOST')   || define('DB_HOST', '127.0.0.1');
defined('DB_NAME')   || define('DB_NAME', 'ce1_mansi');
defined('DB_USER')   || define('DB_USER', 'root');
defined('DB_PASS')   || define('DB_PASS', '');
defined('RESET_TOKEN_MINUTES') || define('RESET_TOKEN_MINUTES', 30);
defined('PAGE_SIZE') || define('PAGE_SIZE', 20);
defined('PRODUCT_TYPES') || define('PRODUCT_TYPES', ['service', 'goods', 'software', 'other']);
defined('BILLING_FREQUENCIES') || define('BILLING_FREQUENCIES', ['daily', 'weekly', 'monthly', 'yearly']);
defined('TAX_TYPES') || define('TAX_TYPES', ['GST', 'VAT', 'Sales Tax', 'Other']);
