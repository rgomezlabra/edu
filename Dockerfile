# syntax=docker/dockerfile:1

# Comments are provided throughout this file to help you get started.
# If you need more help, visit the Dockerfile reference guide at
# https://docs.docker.com/go/dockerfile-reference/

FROM composer:lts as deps

WORKDIR /app

RUN --mount=type=bind,source=composer.json,target=composer.json \
    --mount=type=bind,source=composer.lock,target=composer.lock \
    --mount=type=cache,target=/tmp/cache \
    composer install --no-dev --no-interaction --ignore-platform-req=ext-igbinary --ignore-platform-req=ext-redis --no-scripts
RUN --mount=type=bind,source=composer.json,target=composer.json \
    --mount=type=bind,source=composer.lock,target=composer.lock \
    --mount=type=cache,target=/tmp/cache \
    composer dump-autoload


FROM php:8.3.6-apache as final

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       git libicu-dev libonig-dev libzip-dev unzip wget zip \
    && apt-get clean \
    && docker-php-ext-install intl mbstring pdo pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install igbinary \
    && pecl install redis \
    && docker-php-ext-enable igbinary redis

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --from=deps app/vendor/ /var/www/html/vendor
COPY . /var/www/html
COPY docker/.env.local /var/www/html/.env
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf
RUN chmod a+x /var/www/html/docker/init-script.sh \
    && chown -R www-data:www-data /var/www/html/migrations /var/www/html/var/cache /var/www/html/var/log

COPY --from=composer:lts /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER 1
RUN chmod +x /usr/bin/composer \
    && /usr/bin/composer dump-autoload \
    && /usr/bin/composer install

USER www-data

