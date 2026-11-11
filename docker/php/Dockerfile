FROM php:8.4-fpm-alpine AS base
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql bcmath zip opcache
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

# Desenvolvimento: o código entra por volume
FROM base AS dev
RUN apk add --no-cache git unzip
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

# Produção: o código é copiado para dentro da imagem
FROM base AS prod
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY backend/ .
RUN composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data storage bootstrap/cache
CMD ["sh", "-c", "php artisan migrate --force && php artisan optimize && exec php-fpm"]