# syntax=docker/dockerfile:1

FROM composer:lts AS deps

WORKDIR /app

RUN --mount=type=bind,source=composer.json,target=composer.json \
    --mount=type=bind,source=composer.lock,target=composer.lock \
    --mount=type=cache,target=/tmp/cache \
    composer install --no-dev --no-interaction --ignore-platform-req=ext-igbinary --ignore-platform-req=ext-redis --no-scripts
RUN --mount=type=bind,source=composer.json,target=composer.json \
    --mount=type=bind,source=composer.lock,target=composer.lock \
    --mount=type=cache,target=/tmp/cache \
    composer dump-autoload


FROM php:8.3.6-apache AS final

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
COPY docker/init-script.sh /var/www/html/docker/init-script.sh
RUN chmod a+x /var/www/html/docker/init-script.sh \
    && sed -i -e 's/\r//g' /var/www/html/docker/init-script.sh \
    && mkdir -p /var/www/html/var/cache /var/www/html/var/log \
    && chown -R www-data:www-data /var/www/html/migrations /var/www/html/var/cache /var/www/html/var/log

COPY --from=composer:lts /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN /usr/bin/composer dump-autoload \
    && /usr/bin/composer install

USER www-data

