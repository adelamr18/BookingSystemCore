#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
DB_PATH="$ROOT_DIR/database/playwright.sqlite"

mkdir -p "$ROOT_DIR/database"
touch "$DB_PATH"

export APP_ENV=playwright
export APP_URL="http://127.0.0.1:8002"
export DB_CONNECTION=sqlite
export DB_DATABASE="$DB_PATH"
export CACHE_STORE=file
export SESSION_DRIVER=file
export QUEUE_CONNECTION=sync
export MAIL_MAILER=log

cd "$ROOT_DIR"

php artisan migrate:fresh --seed --force
php artisan optimize:clear
php artisan serve --host=127.0.0.1 --port=8002
