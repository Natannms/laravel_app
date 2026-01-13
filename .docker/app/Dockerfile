FROM php:8.5-fpm-alpine

RUN apk add --no-cache \
    git \
    curl \
    zip \
    unzip \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    bash \
    nodejs \
    npm

RUN docker-php-ext-configure gd \
    --with-jpeg=/usr/include/ \
    --with-webp=/usr/include/ \
    --with-freetype=/usr/include/

RUN docker-php-ext-install \
    pdo_mysql \
    bcmath \
    mbstring \
    zip \
    gd \
    intl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY .docker/app/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
