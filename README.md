# Sustainability Data Collection Platform — Setup Guide

This package contains the **custom application code** (migrations, models, Livewire components, views, routes) for the survey platform we scoped. Since a fresh Laravel project needs `composer install` (internet access to Packagist), you'll run these steps **on your own machine or server** where Composer/internet access is available.

---

## Step 1 — Create a fresh Laravel project

```bash
composer create-project laravel/laravel sustainability-platform
cd sustainability-platform
```

## Step 2 — Install required packages

```bash
composer require livewire/livewire
composer require spatie/laravel-permission
composer require maatwebsite/laravel-excel
composer require barryvdh/laravel-dompdf
```

(We're not using Spatie's permission tables directly in this code — roles are handled with a simple `role` column for simplicity — but the package is listed here in case you want to extend to more granular permissions later. Excel/PDF packages are used for report exports if/when you add export buttons.)

## Step 3 — Copy the custom files from this package into your project

Copy these folders/files from this package **into the matching location** in your fresh `sustainability-platform` project, overwriting where prompted:

```
app/Models/*                       →  app/Models/
app/Http/Middleware/*              →  app/Http/Middleware/
app/Http/Controllers/Auth/*        →  app/Http/Controllers/Auth/
app/Livewire/*                     →  app/Livewire/
app/Console/Commands/*             →  app/Console/Commands/
app/Console/Kernel.php             →  app/Console/Kernel.php  (overwrite)
bootstrap/app.php                  →  bootstrap/app.php       (overwrite)
database/migrations/*              →  database/migrations/    (delete Laravel's default users/migrations first — see note below)
database/seeders/DatabaseSeeder.php → database/seeders/DatabaseSeeder.php (overwrite)
resources/views/*                  →  resources/views/
routes/web.php                     →  routes/web.php          (overwrite)
```

**Important:** Laravel's fresh install already includes default migrations for `users`, `password_reset_tokens`, `sessions`, and `cache`. Delete the default `0001_01_01_000000_create_users_table.php`, `..._create_cache_table.php`, and any other starter migrations from `database/migrations/`, and use **only** the ones provided in this package (they define the custom `users` table with `role` and `factory_id`, plus all the other tables).

## Step 4 — Configure your `.env`

Copy values from `.env.example.custom` (included in this package) into your project's `.env` file — update `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` to match your MariaDB setup, and your real SMTP credentials for reminder emails.

```bash
php artisan key:generate
```

## Step 5 — Create the MariaDB database

```sql
CREATE DATABASE sustainability_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Step 6 — Run migrations and seed demo data

```bash
php artisan migrate
php artisan db:seed
```

This creates:
- An admin login: **admin@example.com / password**
- 5 demo factories, each with a login: **factory1@example.com / password** (through factory5)
- One open cycle ("2026 H1") with 4 sample categories and 8 sample questions

### Optional: seed at real scale (for testing the dashboard properly)

The small seeder above is fine for a quick check, but with only 5 factories the dashboard's factory/category/question views won't really show you how things behave with real data volume. For that, run the second seeder included in this package:

```bash
php artisan db:seed --class=LargeScaleDemoSeeder
```

This generates:
- **200 factories**, each with its own login (`demofactory1@example.com` through `demofactory200@example.com`, password: `password`)
- **15 categories**, **~100–110 questions** spread across them, mixing all question types (text, number, date, single-choice, multi-choice)
- One cycle, with **~70% of factories having a full randomized submission** (the rest left as "not yet submitted," so you can also see how the reminder logic and completion-rate stats behave with a realistic incomplete-submission scenario)

Run this on a fresh/test database — it's meant for evaluating dashboard performance and behavior at scale, not as your production seed data.

## Step 7 — Run the app locally to test

```bash
php artisan serve
```

Visit `http://localhost:8000` — log in as admin to manage cycles/questions and view the dashboard, or as a factory user to fill in the survey.

## Step 8 — Set up the scheduler and queue worker (for production)

Add this cron entry on your server (runs Laravel's scheduler every minute, which in turn checks daily for reminder emails):

```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

If you set `QUEUE_CONNECTION=database` (as in the sample `.env`), no separate queue worker is strictly required since `Mail::raw()` in the reminder command sends synchronously. If you later switch heavier tasks (like large exports) to queued jobs, run a worker via Supervisor:

```bash
php artisan queue:work
```

## Step 9 — Deploy

- Point Nginx + PHP-FPM at `public/`
- Put Cloudflare in front for free SSL
- Set `APP_ENV=production`, `APP_DEBUG=false` in `.env` on the live server
- Set up a daily `mysqldump` cron job for backups

---

## Troubleshooting

**Error: "No hint path defined for [layouts]" on any admin/survey page**

This happens if a Livewire full-page component doesn't explicitly declare its layout — Livewire's default layout resolution can behave differently across framework/package versions. Every full-page component in this package (`SurveyForm`, `ReportDashboard`, `CycleManager`, `QuestionManager`) now explicitly declares:

```php
use Livewire\Attributes\Layout;

#[Layout('components.layout')]
class YourComponent extends Component
{
    ...
}
```

This points directly at `resources/views/components/layout.blade.php` and avoids relying on any package default. If you add new full-page Livewire components later, add this same attribute to each one.

**Error: "Table 'users' already exists" during migration, or "Unknown column 'role'" during seeding**

Laravel's fresh install ships a default migration that also creates the `users` table. Delete it before migrating — our custom migration (which includes `role` and `factory_id`) replaces it entirely:

```bash
rm database/migrations/0001_01_01_000000_create_users_table.php
php artisan migrate:fresh
php artisan db:seed
```



**Included (working core):**
- Full database schema (8 tables)
- Models with relationships
- Login/logout (no 2FA, as scoped)
- Role-based route protection (`admin`, `factory_user`)
- Admin: create/edit cycles, clone a cycle's questions, open/close cycles
- Admin: **add, edit, reorder, and rename** categories and questions per cycle (text/number/date/single-choice/multi-choice) — question management now supports full editing, not just add/delete
- Admin: **Factory management** — add/edit factories (name, region, contact email, status), create or reset a factory's login credentials, search/paginate the list
- **Profile page** for both admin and factory users — update name/email, change password; factory users can also update their region/contact email (used for reminder emails)
- Factory: category-tab survey wizard with auto-progress tracking, save draft, submit — multi-choice checkbox binding fixed (previously unanswered multi-choice questions could behave inconsistently; answer slots are now initialized properly on load), choice-pill selection now updates instantly (`wire:model.live`)
- Dashboard: Overview tab (category response-rate snapshot + pending-factories list, no filter-picking required), Factory-wise (searchable list with status badges), Category-wise and Question-wise (clickable browsing, not dropdowns) — all with real Chart.js charts
- Reminder command (`php artisan reminders:send`) that emails factories who haven't submitted, at configurable day-offsets before the deadline

**You'll likely want to add next:**
- Excel/PDF export buttons on the dashboard (packages are already installed — just needs export classes wired to buttons)
- Bengali translation strings (`resources/lang/bn/`)
- A "forgot password" flow (not included — login/logout only, per current scope)
- Bulk import of factories via CSV (currently one-at-a-time through the Factory Manager form — fine for initial setup of ~200, but a CSV importer would speed up onboarding)
- Deleting/deactivating a factory currently does not deactivate its login user account — worth linking those two states if you want a fully locked-out factory on deactivation
