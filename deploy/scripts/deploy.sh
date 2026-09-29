#!/usr/bin/env bash
# Build frontend + (re)start MADD website stack on 127.0.0.1:8080
# Run from repo root on the production server as a deploy user.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

echo "==> Building React (Vite) for https://madd.ps"
if [[ ! -f .env ]]; then
  cp .env.production.example .env
  echo "Created .env from .env.production.example — review VITE_* values."
fi
npm ci
npm run build

if [[ ! -f portal/.env ]]; then
  echo "ERROR: portal/.env missing. Copy portal/.env.production.example → portal/.env and set DB + APP_KEY."
  exit 1
fi

echo "==> Laravel optimize"
cd portal
composer install --no-dev --optimize-autoloader --no-interaction
php artisan storage:link 2>/dev/null || true
php artisan config:cache
php artisan route:cache
php artisan view:cache
cd "$ROOT"

echo "==> Docker compose up (127.0.0.1:8080 only)"
cd deploy/docker
docker compose up -d --build

echo "==> Health check"
sleep 2
curl -fsS -o /dev/null -w "%{http_code}\n" -H "Host: madd.ps" http://127.0.0.1:8080/ || true
curl -fsS -o /dev/null -w "%{http_code}\n" -H "Host: madd.ps" http://127.0.0.1:8080/api/site/config || true

echo "Done. Host Nginx should proxy https://madd.ps → http://127.0.0.1:8080"
echo "Do not touch UISP / GenieACS / MediaMTX containers."
