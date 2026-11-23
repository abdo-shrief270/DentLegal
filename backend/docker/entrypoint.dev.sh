#!/bin/sh
# Dev stack: install dependencies and a .env on first start, then run the command (serve, horizon, schedule:work).
set -e
if [ ! -f vendor/autoload.php ]; then composer install --no-interaction --prefer-dist; fi
if [ ! -f .env ]; then cp .env.example .env && php artisan key:generate --force; fi
exec "$@"
