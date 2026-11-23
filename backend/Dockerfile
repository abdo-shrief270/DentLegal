# Laravel API image (PROJECT1_PLAN.md §8). `dev` is the local stack (compose.yaml at the repo root): the code is
# mounted, `php artisan serve` reloads per request. The production target (php-fpm + Caddy) comes with S0.4.
FROM php:8.4-cli-bookworm AS dev

RUN apt-get update && apt-get install -y --no-install-recommends git unzip libpq-dev libicu-dev libzip-dev \
 && docker-php-ext-install pdo_pgsql intl zip pcntl bcmath \
 && pecl install redis && docker-php-ext-enable redis \
 && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
COPY docker/entrypoint.dev.sh /usr/local/bin/entrypoint
ENTRYPOINT ["entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
