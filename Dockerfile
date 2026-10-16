
# Base stage: shared configuration
FROM php:8.4-apache AS base

# System dependencies and PHP extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip libzip-dev \
    && docker-php-ext-install \
        pdo_mysql mysqli zip opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Configure Apache to point to public/
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Composer executable
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

# Development stage: Xdebug enabled and development php.ini
FROM base AS development

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/99-xdebug.ini
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

EXPOSE 80

# Production stage: no Xdebug, optimized OPcache, and dependencies installed at build time
FROM base AS production

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

RUN composer assets:publish

# Apache permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
