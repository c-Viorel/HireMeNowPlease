# HireMe Recruitment Marketplace

HireMe is a Laravel recruitment marketplace for candidates, employers, and admins. The first deployable version is planned around candidate and employer accounts, job publishing, applications, shortlisting, messaging, moderation, notifications, and Hostinger Cloud Startup deployment readiness.

## Local Setup

Install the PHP and Node dependencies, create your local environment file, generate an application key, migrate and seed the database, then run Vite and Laravel:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan serve
```

By default `.env.example` uses SQLite-friendly Laravel defaults for local development. For MySQL, set `DB_CONNECTION=mysql` and fill in `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in your local `.env`.

## Deployment

Hostinger Cloud Startup deployment notes are maintained in [docs/deployment/hostinger-cloud-startup.md](docs/deployment/hostinger-cloud-startup.md).
