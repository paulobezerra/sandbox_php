
# Estágio base: configurações comuns
FROM php:8.4-apache AS base

# Dependências do sistema e extensões PHP
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip libzip-dev \
    && docker-php-ext-install \
        pdo_mysql mysqli zip opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Configuração do Apache apontando para public/
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Composer binário
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

# Estágio de desenvolvimento: Xdebug habilitado e php.ini de desenvolvimento
FROM base AS development

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/99-xdebug.ini
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

EXPOSE 80

# Estágio de produção: sem Xdebug, com OPcache otimizado e dependências instaladas no build
FROM base AS production

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Instala dependências de produção
COPY composer.json composer.lock* ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-progress \
    --no-scripts \
    --optimize-autoloader

# Código da aplicação
COPY . .

RUN composer assets:publish

# Permissões do Apache
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
