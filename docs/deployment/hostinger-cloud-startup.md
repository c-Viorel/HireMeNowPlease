# Hostinger Cloud Startup Deployment

This guide documents the initial V1-ready deployment path for HireMe on Hostinger Cloud Startup. It may be revisited after the notifications and final UI work lands, especially for any mail-driver, queue-worker, or smoke-check refinements introduced by those tasks.

## Required Services

- PHP version supported by the Laravel app
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

Keep `.env` out of version control and create the production `APP_KEY` with `php artisan key:generate` before caching configuration.

## Release Commands

Run these commands from the deployed application directory after code and environment variables are in place:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If queues are enabled on the server, start or restart the configured queue worker after the release so database-backed jobs are processed from `queue_jobs`.

## Smoke Checks

- Homepage loads.
- `/jobs` loads.
- Registration works.
- Email verification sends.
- CV upload works.
- Employer can publish a job.
- Candidate can apply.
- Message notification sends.
- `storage` assets resolve through the public storage link.
