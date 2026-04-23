FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
