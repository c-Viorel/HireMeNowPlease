#!/usr/bin/env bash
set -euo pipefail

mkdir -p storage/app storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

php artisan storage:link >/dev/null 2>&1 || true
php artisan package:discover --ansi >/dev/null 2>&1 || true

exec "$@"
