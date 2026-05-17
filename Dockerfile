FROM php:8.4-cli-alpine

RUN apk add --no-cache \
    bash \
    curl \
    curl-dev \
    icu-dev \
    libxml2-dev \
    libzip-dev \
    linux-headers \
    mariadb-dev \
    oniguruma-dev \
    sqlite-dev \
    $PHPIZE_DEPS

RUN docker-php-ext-install \
    intl \
    mbstring \
    pdo_mysql \
    zip

RUN pecl install xdebug && docker-php-ext-enable xdebug

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY .docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
