FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY public/images/logos /var/www/public/images/logos

WORKDIR /var/www

CMD ["php", "artisan", "serve", "--host", "0.0.0.0", "--port", "8000"]
