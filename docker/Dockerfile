FROM composer:2 AS dependencies

WORKDIR /build

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts

FROM php:7.4-cli

RUN docker-php-ext-install pdo_mysql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .
COPY --from=dependencies /build/vendor ./vendor

EXPOSE 8080

