# BudgetBasics — Setup & Documentation

Professional, dynamic personal budgeting app (PHP + MySQL + Bootstrap 5 + Chart.js, no frameworks).

## 1. Requirements
- PHP 8.0+ with PDO MySQL (`php -m` should list `pdo_mysql`)
- MySQL 5.7+ / MariaDB
- Apache (XAMPP/WAMP) or `php -S` for dev

## 2. Database setup
1. Create DB and import:
```sql
-- via mysql CLI:
CREATE DATABASE budgetbasics CHARACTER SET utf8mb4;
mysql -u root budgetbasics < database/budgetbasics.sql
-- or import database/budgetbasics.sql in phpMyAdmin
```
2. Edit credentials in `config/database.php` (or set env `DB_HOST/DB_NAME/DB_USER/DB_PASS`).

## 3. Run locally
Option A — XAMPP: copy `BudgetBasics/` to `htdocs/`, visit `http://localhost/BudgetBasics/`.
Option B — PHP built-in server:
```
cd BudgetBasics
php -S localhost:8000
```
Then open `http://localhost:8000/`.

## 4. Admin access
- Register a normal account, then promote:
```sql
UPDATE users SET role='admin' WHERE email='you@example.com';
```
- Log in again → sidebar shows **Admin**, or visit `/admin/`.
- The seed admin row in SQL uses a placeholder hash — reset it via registration + promote (recommended).

## 5. Features map
- `/` public site, `/register.php`, `/login.php`, `/logout.php`
- `/dashboard.php` dynamic stats + Chart.js (category doughnut, 6-mo trend)
- `/income.php`, `/expenses.php` full CRUD + search/filter/sort + confirm deletes
- `/budget.php` monthly budget + auto 50/30/20 + warnings
- `/savings.php` goals + progress bars
- `/reports.php` 4 charts from real data
- `/calculators.php` JS calculators
- `/learning.php`, `/tips.php` DB-driven + search/filter/sort
- `/profile.php` name/email/password update
- `/assistant.php` rule-based bot; swap `assistant_reply()` in `includes/functions.php` for an LLM API
- `/admin/` overview, users, tips, categories, articles (role-guarded)

## 6. Security
- `password_hash/verify`, PDO prepared statements everywhere, `htmlspecialchars` via `e()`,
  session guards (`require_login/require_admin`), per-user `user_id` scoping, server-side
  validation (`valid_amount/date/email`), confirm dialogs for deletes, safe logout.

## 7. Testing checklist
Register → login → add income → add expense → create budget (auto-split) → create goal →
check dashboard/charts → search/filter/sort → wrong password → duplicate email →
unauthorized `/dashboard.php` logged-out → admin role toggle → mobile resize.
