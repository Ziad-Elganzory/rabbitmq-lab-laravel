FROM php:8.5-cli-bookworm

ARG WWWUSER=1000
ARG WWWGROUP=1000

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends $PHPIZE_DEPS \
        git unzip libzip-dev libicu-dev libonig-dev \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath exif gd intl mbstring pcntl pdo_mysql sockets zip \
    && pecl install redis xdebug \
    && docker-php-ext-enable redis xdebug \
    && (getent group "${WWWGROUP}" || groupadd --gid "${WWWGROUP}" laravel) \
    && (getent passwd "${WWWUSER}" || useradd --uid "${WWWUSER}" --gid "${WWWGROUP}" --create-home --shell /bin/bash laravel) \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

USER laravel
