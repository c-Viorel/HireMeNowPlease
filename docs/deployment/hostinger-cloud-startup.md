# Hostinger Cloud Startup Deployment

This guide documents the V1-ready deployment path for HireMe on Hostinger Cloud Startup.

## Required Services

- PHP 8.3+ with the Laravel-required extensions enabled
- MySQL database
- Composer
- Node.js for Vite build
- SMTP mailbox or transactional SMTP credentials
- SSH or Git deployment access
- Writable Laravel storage directory for uploaded CVs, logos, cache, sessions, and logs

## Production Environment

Set these variables in `.env`:

- `APP_NAME=HireMe`
- `APP_ENV=production`
- `APP_KEY=base64:generate-this-on-the-server`
- `APP_DEBUG=false`
- `APP_URL=https://your-domain.example`
- `LOG_CHANNEL=stack`
- `LOG_LEVEL=error`
- `DB_CONNECTION=mysql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_DATABASE=hireme`
- `DB_USERNAME=hireme_user`
- `DB_PASSWORD=change-me`
- `SESSION_DRIVER=database`
- `CACHE_STORE=database`
- `QUEUE_CONNECTION=database`
- `DB_QUEUE_TABLE=queue_jobs`
- `MAIL_MAILER=smtp`
- `MAIL_HOST=smtp.example.com`
- `MAIL_PORT=587`
- `MAIL_USERNAME=mailbox@example.com`
- `MAIL_PASSWORD=change-me`
- `MAIL_ENCRYPTION=tls`
- `MAIL_FROM_ADDRESS=no-reply@your-domain.example`
- `MAIL_FROM_NAME="${APP_NAME}"`
- `FILESYSTEM_DISK=local`
- `HIREME_ADMIN_PASSWORD=generate-a-strong-admin-password`

Keep `.env` out of version control. Generate `APP_KEY` once during first production setup with `php artisan key:generate --force`; do not regenerate `APP_KEY` on an existing production install unless intentionally rotating keys with a rollback plan.

## Web Root / File Layout

- Configure the domain document root to Laravel's `public/` directory when Hostinger allows it.
- If Hostinger requires `public_html`, keep Laravel application files outside the public web root and make `public_html` serve the contents of Laravel `public/` using Hostinger-supported layout/rewrite.
- Before smoke testing, verify `/.env`, `/composer.json`, and `/storage/logs/laravel.log` are not publicly accessible.

## Release Commands

Use composer2 instead of composer on Hostinger accounts where Composer 1 is the default alias.

Run these commands from the deployed application directory after code and environment variables are in place:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Notifications are delivered through Laravel's mail and database notification channels. The mail channel uses the SMTP settings above, while in-app notification records are stored in the `notifications` table.

Set `HIREME_ADMIN_PASSWORD` before running `php artisan db:seed --force`; the seeder creates or updates the initial admin account at `admin@hireme.local`. Change the admin email after first login if the production team wants a domain-specific address.

V1 notifications run synchronously because the notification classes do not implement queued delivery yet. `QUEUE_CONNECTION=database` and `DB_QUEUE_TABLE=queue_jobs` keep the deployment ready for future database-backed queued work. If queued jobs are enabled later, start or restart the configured queue worker after each release so jobs are processed from `queue_jobs`:

```bash
php artisan queue:work database --queue=default --tries=3
```

Use Hostinger's process manager or a supervised shell process if available. If a persistent worker is not available on the plan and queued notifications are introduced later, set `QUEUE_CONNECTION=sync` until a supervised worker is configured.

## Smoke Checks

- Homepage loads and the public navigation fits on mobile and desktop widths.
- `/jobs` loads and job detail pages open from company-scoped URLs.
- Registration works for candidate and employer roles.
- Email verification sends through SMTP.
- Candidate dashboard loads, CV upload works, and the uploaded CV is not publicly browseable.
- Employer dashboard loads, company logo upload works, and an approved employer can publish a job.
- Candidate can apply and the employer receives both email and in-app notification records.
- Employer can update application status and the candidate receives both email and in-app notification records.
- Candidate and employer can exchange messages without email notifications exposing the message body.
- Admin dashboard loads and can moderate companies, jobs, and users.
- `storage` assets resolve through the public storage link.
