FROM node:22-bookworm AS frontend

WORKDIR /app

COPY package.json package-lock.json vite.config.js postcss.config.js tailwind.config.js ./
COPY resources ./resources

RUN npm ci && npm run build

FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY routes ./routes
COPY artisan ./

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

FROM php:8.4-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        default-mysql-client \
        libicu-dev \
        libzip-dev \
        unzip \
        zlib1g-dev \
    && docker-php-ext-install intl opcache pcntl pdo_mysql zip \
    && a2enmod headers rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build
COPY deploy/nas/apache.conf /etc/apache2/sites-available/000-default.conf
COPY deploy/nas/entrypoint.sh /usr/local/bin/hireme-entrypoint

RUN chmod +x /usr/local/bin/hireme-entrypoint \
    && mkdir -p storage/app storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

ENTRYPOINT ["hireme-entrypoint"]
CMD ["apache2-foreground"]
