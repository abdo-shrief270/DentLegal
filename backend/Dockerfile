# Laravel API image (PROJECT1_PLAN.md §8). `dev` is the local stack (compose.yaml at the repo root): the code is
# mounted, `php artisan serve` reloads per request. `prod` is the production image (S0.4): FrankenPHP serves
# public/ on :8080 behind the edge Caddy; the same image runs Horizon and the scheduler with another command.
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

FROM dunglas/frankenphp:1-php8.4-bookworm AS prod

RUN install-php-extensions pdo_pgsql intl zip pcntl bcmath redis opcache exif \
 && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.prod.ini "$PHP_INI_DIR/conf.d/zz-dentlegal.ini"
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader --no-progress
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
 && rm -f /usr/local/bin/composer \
 && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache /srv/documents \
 && chown -R www-data:www-data storage bootstrap/cache /srv/documents /data/caddy /config/caddy

ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr SERVER_NAME=:8080 DOCUMENTS_ROOT=/srv/documents
COPY docker/entrypoint.prod.sh /usr/local/bin/entrypoint
USER www-data
EXPOSE 8080
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s CMD php -r "exit(@file_get_contents('http://127.0.0.1:8080/up') === false ? 1 : 0);"
ENTRYPOINT ["entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
