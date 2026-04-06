FROM php:8.2-cli

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    git unzip curl libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite \
    && apt-get clean

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN touch database/database.sqlite

RUN php artisan key:generate --force \
    && php artisan migrate --force

EXPOSE 8002

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8002"]
