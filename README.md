# Brightloop Subscription Management (merged: Participants 1 + 2 + 3)

PHP 8 + MySQL + HTML/CSS/JS on XAMPP. Database name: **ce1_mansi**.

## Install (XAMPP)
1. Copy this folder to `C:\xampp\htdocs\brightloop-subscription-management` (other folder name: set `BASE_URL` in `config/config.local.php`).
2. Start **Apache** and **MySQL**. In phpMyAdmin → Import `database/schema.sql` (creates/uses `ce1_mansi`), then `database/seed.sql`.
3. In a terminal inside the project folder (use `C:\xampp\php\php.exe` if `php` is not recognised):
   - `php database/create_admin.php "Admin" admin@brightloop.test "Admin@12345"`
   - `php database/seed_demo.php`  (staff + 2 customers)
4. Open http://localhost/brightloop-subscription-management/
5. Optional checks: `php tests/foundation_test.php` and `php tests/flow_test.php`.

DB password not empty? `config/config.local.php`: `<?php define('DB_PASS','secret');`

## Demo accounts
| Role | Email | Password |
|---|---|---|
| Admin | admin@brightloop.test | Admin@12345 |
| Staff | staff@brightloop.test | Staff@12345 |
| Customer | customer1@brightloop.test | Customer@123 |

## Flow
Quotation → (accept) → Convert to subscription → Activate → Generate invoice (optional discount) → Confirm invoice → Payment (staff records, or customer pays online demo).
Recurring: `php cron/auto_billing.php` daily, or the "Run automated billing now" button on Invoices.
Reset-password emails are written to `uploads/mail.log` (link also shown on screen in dev).
