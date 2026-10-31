# Brightloop Subscription Management – Participant 1 (Foundation)

Covers Tasks 1–4: database + authentication, products/variants, plans/taxes, foundation integration.

## Install (XAMPP)
1. Copy this folder to `htdocs/brightloop-subscription-management` (if you use another folder, set `BASE_URL` in `config/config.local.php`).
2. Start Apache + MySQL. In phpMyAdmin import `database/schema.sql`, then (optional demo data) `database/seed.sql`.
3. Create the first admin (never hard-coded): `php database/create_admin.php "Admin" admin@brightloop.test "Admin@12345"`
4. Open `http://localhost/brightloop-subscription-management/`.
5. Run checks: `php tests/foundation_test.php`

DB credentials default to root / empty password; override in `config/config.local.php`:
`<?php define('DB_PASS','secret');`

## Dev email
XAMPP has no mail server. Reset emails are appended to `uploads/mail.log`, and in `APP_ENV=dev` the link is also shown on screen. Set `APP_ENV` to `prod` to hide it.

## Interface contract for Participants 2 and 3
| Need | Use |
|---|---|
| DB connection | `db()` (PDO, exceptions on, prepared statements) |
| Page guard | `require_role(['admin','staff'])` at the top of every page; `current_user()` |
| CSRF | `csrf_field()` in forms, `csrf_check()` on POST |
| Line/tax math | `calc_line($qty,$unit,$taxPct)`, `calc_document($lines)` |
| Variant price | `variant_final_price($sales,$extra)` or `GET api/product-variants.php?product_id=` |
| Tax for product | `taxes_for_product()`, `tax_percent_for_product()` |
| Billing dates | `add_billing_interval()`, `billing_period_start($start,$freq,$n)`, `next_billing_date($start,$freq,$current)` |
| Plan window | `plan_is_available($plan)` |
| Audit | `audit($action,$entity,$id,$details)` |
| Pagination | `paginate($total)`, `pager($pg)` |

Rules: customers are always filtered by `customer_id = current_user()['id']` from the session, never by a URL id.
Billing: compute period starts from the subscription `start_date` with `billing_period_start` (no month-end drift); `invoices` has `UNIQUE(subscription_id, period_start)`.
Schema additions beyond the original list: `tax_products`, `quotation_template_items`.

## Manual permission test
- Customer opens `/admin/products.php` → 403. Staff opens `/admin/users.php` → 403. Logged out → redirected to login.
- Register twice with the same email → rejected. Passwords of 8 chars / no uppercase / no special char → rejected.
- Admin creates staff; staff can log in; customer self-registration can never produce staff/admin.
- Product with variants Basic 0 / Odoo 560 / Premium 1000: preview shows price + extra.
