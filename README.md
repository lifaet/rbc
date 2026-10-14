# ESG Data Platform

A simple, standard web app for collecting ESG (Environmental, Social and Governance) survey data from factories and reporting on it.

## Roles
| Role | What they can do |
|---|---|
| Admin (`admin`, `super_admin`) | Dashboard & reports, survey cycles, questions, factories, users, Excel/PDF exports |
| Factory user (`factory_user`) | Fill in and submit their own factory's survey, update their profile |

## How it works
1. Admin creates a **survey cycle** and adds **categories & questions** (text, number, date, single-choice, multiple-choice).
2. Factory users log in, answer the survey section by section, **Save Draft** anytime, then **Submit**.
3. Admin views results on the **dashboard** (by factory, category or question, with charts) and can **export to Excel or PDF**.

Submission status is simply: `draft → submitted`. Factories can edit and resubmit while a cycle is open.

## Quick start (demo)
```bash
cp .env.example .env       # for a local demo set DB_CONNECTION=sqlite
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```
Demo logins (password `password` for all — change in production):
`admin@example.com` · `factory1@example.com` … `factory5@example.com`

Optional scale test (200 demo factories, test database only):
`php artisan db:seed --class=LargeScaleDemoSeeder`

## Production
Use MySQL/MariaDB, set `APP_ENV=production`, `APP_DEBUG=false` and your real `APP_URL`/SMTP in `.env`, serve `public/` via Nginx/Apache with HTTPS, and add the scheduler cron so reminder emails run:
```
* * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1
```
Take a daily database backup (`mysqldump`) and keep it off-server. See `docs/USER_GUIDE.md` for end-user help.

## Tech
Laravel 12 · Livewire · MySQL/SQLite · Excel & PDF export · Tailwind/Chart.js

## Tests
```bash
php artisan test
```
