FROM php:8.2-cli

WORKDIR /var/www/html

# Dependências do sistema
RUN apt-get update && apt-get install -y \
    git unzip curl libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite \
    && apt-get clean

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copia arquivos do projeto
COPY . .

# Instala dependências PHP
RUN composer install --no-dev --optimize-autoloader

# Cria banco SQLite
RUN touch database/database.sqlite

# Gera chave e executa migrations
RUN php artisan key:generate --force \
    && php artisan migrate --force \
    && php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --force \
    && php artisan migrate --force

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
