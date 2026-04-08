FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    bash \
    git \
    curl \
    unzip \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    poppler-utils \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    nodejs \
    npm \
    chromium \
    nss \
    freetype \
    harfbuzz \
    ca-certificates \
    ttf-freefont \
    font-noto \
    $PHPIZE_DEPS \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install \
        intl \
        zip \
        opcache \
        pdo \
        pdo_pgsql \
        gd \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY package*.json ./
RUN npm install --omit=dev

CMD ["php-fpm"]
