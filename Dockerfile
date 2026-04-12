# ──────────────────────────────────────────────────────────────────────────────
# Stage 1 — Frontend assets (Node.js 20)
# ──────────────────────────────────────────────────────────────────────────────
FROM node:20-alpine AS node-builder

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --silent

COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY resources/ resources/
COPY public/ public/

RUN npm run build

# ──────────────────────────────────────────────────────────────────────────────
# Stage 2 — Composer dependencies
# ──────────────────────────────────────────────────────────────────────────────
FROM composer:2 AS composer-deps

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY . .

RUN composer dump-autoload --optimize --no-dev --no-scripts \
    && rm -f bootstrap/cache/packages.php bootstrap/cache/services.php

# ──────────────────────────────────────────────────────────────────────────────
# Stage 3 — Production image (PHP 8.3-FPM)
# ──────────────────────────────────────────────────────────────────────────────
FROM php:8.3-fpm

# ── System dependencies ───────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
        libicu-dev \
        unzip \
        git \
    && rm -rf /var/lib/apt/lists/*

# ── PHP extensions ────────────────────────────────────────────────────────────
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp

RUN docker-php-ext-install \
        pdo_mysql \
        gd \
        bcmath \
        zip \
        opcache \
        intl \
        pcntl

RUN pecl install redis \
    && docker-php-ext-enable redis

# ── PHP runtime configuration ─────────────────────────────────────────────────
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

# ── Application ───────────────────────────────────────────────────────────────
WORKDIR /var/www/html

COPY --from=composer-deps /app /var/www/html
COPY --from=node-builder /app/public/build /var/www/html/public/build

# ── Permissions ───────────────────────────────────────────────────────────────
RUN chown -R www-data:www-data \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache \
    && chmod -R 775 \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
