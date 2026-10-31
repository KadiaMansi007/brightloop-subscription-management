# Subscription Management System — Participant 3

This is one integrated project folder (not separate Hour 1/2/3/4 directories), matching the requested structure.

## Included features
- Admin: discounts/coupons, dashboard KPIs, recent payments, CSV reports
- Customer: dashboard, profile, own subscriptions, invoices, payments, subscription requests
- Staff: manual/demo payment recording
- Modules: coupon validation, transactional payment recording, reporting queries
- Shared: PDO database connection, session/role guards, common header/footer, CSS and JS

## Requirements
- PHP 8.0+
- MySQL 5.7+ / MariaDB equivalent
- Apache (XAMPP/WAMP/LAMP) with PDO MySQL enabled

## Setup
1. Put this folder in your web root, e.g. `htdocs/Subscription_Management_System`.
2. Create a MySQL database named `subscription_management`.
3. Update `config/database.php` or set DB_HOST, DB_NAME, DB_USER, DB_PASS.
4. Import `database/participant3.sql`.
5. Integrate your team's existing login so it sets `$_SESSION['user_id']`, `$_SESSION['role']` (`admin`, `staff`, or `customer`), and optionally `$_SESSION['name']`.
6. Ensure your team's existing shared tables match the schema contract below, or edit SQL queries to match actual names.
7. Sign in with an account of the correct role and open the routes below.

## Routes
- `/Subscription_Management_System/admin/dashboard.php`
- `/Subscription_Management_System/admin/discounts.php`
- `/Subscription_Management_System/admin/reports.php`
- `/Subscription_Management_System/staff/payments.php`
- `/Subscription_Management_System/customer/dashboard.php`

## Existing shared database schema expected
- `users(id, name, email, role)`
- `subscriptions(id, customer_id, product_name, status, start_date, next_billing_date, end_date)`
- `invoices(id, customer_id, total_amount, paid_amount, status, due_date)`

Participant 3's SQL creates only `discounts`, `payments`, and `subscription_requests`. It intentionally does not replace your team's users/subscriptions/invoices tables.

## Important integration notes
- Customer account `users.id` is treated as `customer_id`. If your project uses a separate customer table or mapping, update `includes/permissions.php` and customer queries.
- One shared database connection is exposed as `$pdo`.
- This is a module integration package, not a full authentication/login system. It relies on your team's login/session.
- The payment page records manual/demo/offline entries only. It does not process real cards, UPI, or banking transactions.
- Subscription requests are submitted as pending for staff/admin review; they do not automatically change subscription state.
- `discount-engine.php` validates a coupon but your checkout/invoice creation flow must call it and apply the returned discount. Increment `used_count` only after a successful order, within a transaction.
- Add CSRF tokens to forms before production deployment. This starter is for hackathon integration/testing and should be hardened with the team's shared CSRF helper.

## Quick checks
- Admin/staff pages reject customer sessions.
- Customer pages scope records by the logged-in user ID.
- Payment recording locks the invoice row and rejects overpayment.
- Run through the team's test accounts and sample data before demo.
