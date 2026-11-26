#!/bin/sh
# Production: cache configuration, routes and events from the environment, then run the command (the web server,
# `php artisan horizon` or `php artisan schedule:work`). Migrations run from the deploy script, once per release.
set -e
php artisan config:cache
php artisan route:cache
php artisan event:cache
exec "$@"
