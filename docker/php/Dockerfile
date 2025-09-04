FROM php:8.3-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libicu-dev libzip-dev zlib1g-dev libonig-dev libxml2-dev \
 && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql intl bcmath opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . /var/www/html

RUN mkdir -p var/cache var/log && chown -R www-data:www-data var

EXPOSE 9000
CMD ["php-fpm"]
