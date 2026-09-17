FROM php:8.5-cli-bookworm

ARG WWWUSER=1000
ARG WWWGROUP=1000

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        $PHPIZE_DEPS \
        git \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" \
        pcntl \
        pdo_mysql \
        sockets \
        zip \
    && groupadd --gid "${WWWGROUP}" laravel \
    && useradd --uid "${WWWUSER}" --gid "${WWWGROUP}" --create-home --shell /bin/bash laravel \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

USER laravel
