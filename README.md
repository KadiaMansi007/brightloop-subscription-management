# BrightLoop Subscription Management — Complete Integrated Project

This folder combines the Participant 1 foundation, Participant 2 subscription/billing files, and Participant 3 customer/payment/reporting files into one PHP + MySQL project.

## Requirements
- XAMPP (Apache + MySQL) or PHP 8+ with MySQL
- PHP PDO MySQL extension enabled
- MySQL / MariaDB

## Run directly from Desktop with Git Bash
1. Extract this ZIP.
2. Open Git Bash in the `brightloop-subscription-management` folder (the folder containing `index.php`).
3. Start the PHP server:
   ```bash
   /c/xampp/php/php.exe -S localhost:8000
   ```
4. Start MySQL from XAMPP Control Panel.
5. Open `http://localhost:8000/`.

Keep the Git Bash terminal open while using the website. This PHP development server serves the PHP frontend and backend together. MySQL must run separately.

## Database setup — important
Import these files in phpMyAdmin, in this order:
1. `database/schema.sql` — creates `brightloop_subscription_db` and the Participant 1 foundation tables.
2. `database/seed.sql` — optional demo data.
3. `database/integration_schema.sql` — adds Participant 2/3 integration tables and payment fields.

The integration migration is intended to be run once after `schema.sql`. Do not import it repeatedly after it succeeds because it adds columns and an index.

Default database settings are in `config/config.php`:
- Host: `127.0.0.1`
- Database: `brightloop_subscription_db`
- User: `root`
- Password: empty

If using XAMPP's Apache with this project placed in `htdocs/brightloop-subscription-management`, set `BASE_URL` in `config/config.local.php` to `/brightloop-subscription-management`. For the direct Git Bash server from this project folder, leave it empty (default).

## Create the first administrator
From Git Bash in this folder:
```bash
/c/xampp/php/php.exe database/create_admin.php "Admin" admin@example.com "Admin@12345"
```
Use a unique email and change the sample password for real use.

## Main routes
- Customer: `customer/dashboard.php`, `customer/subscriptions.php`, `customer/invoices.php`, `customer/payments.php`, `customer/requests.php`, `customer/profile.php`
- Admin: `admin/dashboard.php`, `admin/products.php`, `admin/plans.php`, `admin/taxes.php`, `admin/subscriptions.php`, `admin/invoices.php`, `admin/templates.php`, `admin/discounts.php`, `admin/reports.php`
- Staff: `staff/dashboard.php`, `staff/payments.php`

## Important limitations / honest integration notes
- Payments are manual/demo records only. This project does not charge real cards, UPI or bank accounts.
- Participant 3's coupon validation helper is included; it is not wired into a complete checkout/discount application workflow yet.
- Automatic billing is a starter routine. Run it manually from the project folder with:
  ```bash
  /c/xampp/php/php.exe cron/auto_billing.php
  ```
  Test it on demo data before relying on it.
- Existing Participant 1 authentication and session (`uid`) are retained. Customer data pages always use the logged-in account ID rather than a URL-supplied customer ID.
- Test all roles and workflows with demo data before hackathon submission. This merged package has been assembled from the three supplied ZIPs, but it has not been run against your local MySQL instance.

## Security
This is a hackathon/demo starter. Review all workflows, add CSRF protection to any future forms, configure production credentials, use HTTPS, and do not use demo payment recording as real payment processing.
