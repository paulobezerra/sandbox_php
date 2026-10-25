
# Keep one explicit, verifiable PHP 8.4 patch across both Docker paths.
ARG PHP_VERSION=8.4.27

# Development PHP-FPM runtime, pinned to a patch containing the required fixes.
FROM php:${PHP_VERSION}-fpm AS development
ARG PHP_VERSION

RUN php -r 'if (PHP_VERSION_ID < 80427 || PHP_VERSION_ID >= 80500) { fwrite(STDERR, "PHP 8.4.27 or newer PHP 8.4 patch required; found " . PHP_VERSION . PHP_EOL); exit(1); }'

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip libzip-dev \
    && docker-php-ext-install \
        pdo_mysql mysqli zip opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/99-xdebug.ini
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

EXPOSE 9000

# Build frontend assets before assembling the production PHP image.
FROM node:22-alpine AS frontend-build

WORKDIR /var/www/html
COPY frontend/package.json frontend/package-lock.json ./frontend/
RUN npm --prefix frontend ci
COPY frontend/ ./frontend/
RUN npm --prefix frontend run build

# Production PHP-FPM runtime: no Xdebug, optimized OPcache, and dependencies installed at build time.
FROM php:${PHP_VERSION}-fpm AS production
ARG PHP_VERSION

RUN php -r 'if (PHP_VERSION_ID < 80427 || PHP_VERSION_ID >= 80500) { fwrite(STDERR, "PHP 8.4.27 or newer PHP 8.4 patch required; found " . PHP_VERSION . PHP_EOL); exit(1); }' \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip libzip-dev \
    && docker-php-ext-install \
        pdo_mysql mysqli zip opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Install production dependencies
COPY composer.json composer.lock* ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-progress \
    --no-scripts \
    --optimize-autoloader

# Application code
COPY . .
COPY --from=frontend-build /var/www/html/public/assets/build/ ./public/assets/build/

RUN composer assets:publish

# PHP-FPM permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000

# Nginx serves the exact public tree and compiled assets from the PHP image.
FROM nginx:1.28-alpine AS production-nginx
COPY docker/nginx/production.conf /etc/nginx/conf.d/default.conf
COPY --from=production /var/www/html/public /var/www/html/public
