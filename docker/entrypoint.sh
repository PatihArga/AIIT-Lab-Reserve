#!/usr/bin/env bash
#
# Container bootstrap for the `app` service.
# Order: ensure .env → composer install → app key → npm build → wait for DB
#        → migrate → seed (fresh DB only) → clear caches → serve.
set -euo pipefail

cd /var/www/html

echo "==> UKRIDA LabReserve — container bootstrap"

# 1) Ensure an .env exists. A host .env (bind-mounted) is reused as-is; the
#    DB_* values are overridden by docker-compose env vars regardless.
if [ ! -f .env ]; then
  echo "==> .env not found — creating from .env.docker"
  cp .env.docker .env
fi

# 2) PHP dependencies (vendor is a named volume — installed once, then cached).
if [ ! -f vendor/autoload.php ]; then
  echo "==> Installing PHP dependencies (composer install)"
  composer install --no-interaction --prefer-dist --no-progress
fi

# 3) Application encryption key.
if ! grep -q '^APP_KEY=base64:' .env; then
  echo "==> Generating application key"
  php artisan key:generate --force
fi

# 4) Frontend dependencies + one-time asset build.
if [ ! -d node_modules ] || [ -z "$(ls -A node_modules 2>/dev/null)" ]; then
  echo "==> Installing Node dependencies (npm install)"
  npm install --no-audit --no-fund
fi
if [ ! -f public/build/manifest.json ]; then
  echo "==> Building frontend assets (npm run build)"
  npm run build
fi

# 5) Wait until the database accepts connections (compose healthcheck already
#    gates this, but a PDO probe guards against the brief startup race).
echo "==> Waiting for database at ${DB_HOST:-db}:${DB_PORT:-3306} ..."
until php -r '
  try {
    new PDO(
      "mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: 3306).";dbname=".getenv("DB_DATABASE"),
      getenv("DB_USERNAME"),
      getenv("DB_PASSWORD")
    );
    exit(0);
  } catch (Throwable $e) { exit(1); }
'; do
  sleep 2
done
echo "==> Database is ready"

# 6) Migrations run every boot (idempotent). Seed only when the DB is empty so
#    restarts never reset the admin password or wipe user-created bookings.
echo "==> Running migrations"
php artisan migrate --force

USER_COUNT=$(php -r '
  try {
    $pdo = new PDO(
      "mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: 3306).";dbname=".getenv("DB_DATABASE"),
      getenv("DB_USERNAME"),
      getenv("DB_PASSWORD")
    );
    echo (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
  } catch (Throwable $e) { echo "0"; }
')
if [ "$USER_COUNT" = "0" ]; then
  echo "==> Fresh database — seeding default data"
  php artisan db:seed --force
else
  echo "==> Existing data detected ($USER_COUNT users) — skipping seed"
fi

# 7) Clear stale config/view caches for the current container.
php artisan config:clear
php artisan view:clear

# 8) Serve.
echo "==> Starting Laravel on http://0.0.0.0:8000"
exec php artisan serve --host=0.0.0.0 --port=8000
