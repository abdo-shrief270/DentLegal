#!/usr/bin/env bash
# Runs on the VPS as `deploy`, from the deploy workflow: pull the release images, migrate, start, check health.
#   deploy.sh <environment: staging|production> <image-tag> [web-tag]
set -euo pipefail
cd /srv/dentlegal

ENV_NAME="${1:?environment}"
TAG="${2:?image tag}"
WEB="${3:-$TAG}"
set_env() { if grep -q "^$1=" .env; then sed -i "s|^$1=.*|$1=$2|" .env; else echo "$1=$2" >> .env; fi; }
set_env IMAGE_TAG "$TAG"
set_env WEB_TAG "$WEB"
dc() { docker compose -f compose.yaml "$@"; }

dc pull --quiet
dc up -d --wait postgres redis
dc run --rm --no-deps app php artisan migrate --force
# Recreates every container whose image changed and waits for the health checks (the API, ai-svc with its models).
dc up -d --remove-orphans --wait --wait-timeout 420

api_host="$(grep '^API_HOST=' .env | cut -d= -f2)"
curl -fsS --retry 10 --retry-delay 3 --retry-all-errors "https://$api_host/up" > /dev/null
# Production must pass every check; staging (APP_ENV=staging, test SMS) only reports them.
if [[ "$ENV_NAME" == production ]]; then dc exec -T app php artisan security:check; else dc exec -T app php artisan security:check || true; fi
docker image prune -f > /dev/null
echo "deployed $TAG (web $WEB) to $ENV_NAME"
