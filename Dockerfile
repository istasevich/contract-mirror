FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    bash \
    git \
    curl \
    unzip \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    poppler-utils \
    $PHPIZE_DEPS \
    && docker-php-ext-install intl zip opcache \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

CMD ["php-fpm"]
