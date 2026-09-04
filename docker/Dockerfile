FROM composer:2 AS composer

FROM php:8.4-cli-alpine

RUN apk add --no-cache \
        curl \
        freetype \
        libjpeg-turbo \
        libpng \
        libpq \
        libxml2 \
        libzip \
        oniguruma \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        curl-dev \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libxml2-dev \
        libzip-dev \
        oniguruma-dev \
        postgresql-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        curl \
        dom \
        gd \
        mbstring \
        pcntl \
        pdo_pgsql \
        simplexml \
        xml \
        zip \
    && apk del .build-deps

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY docker/ensure-dependencies.sh /usr/local/bin/ensure-dependencies
COPY docker/migrate.sh /usr/local/bin/migrate

RUN chmod +x /usr/local/bin/ensure-dependencies /usr/local/bin/migrate

WORKDIR /var/www/html
