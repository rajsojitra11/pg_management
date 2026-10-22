#!/bin/bash
set -e

echo "=== PG Management - Render Entrypoint ==="

# Render auto-injects PGHOST, PGPORT, PGDATABASE, PGUSER, PGPASSWORD
# when a PostgreSQL database is linked to this service.
# Map these to the env vars Laravel expects when DB_CONNECTION=pgsql.
if [ -n "$PGHOST" ]; then
    export DB_HOST="${DB_HOST:-$PGHOST}"
fi

if [ -n "$PGPORT" ]; then
    export DB_PORT="${DB_PORT:-$PGPORT}"
fi

if [ -n "$PGDATABASE" ]; then
    export DB_DATABASE="${DB_DATABASE:-$PGDATABASE}"
fi

if [ -n "$PGUSER" ]; then
    export DB_USERNAME="${DB_USERNAME:-$PGUSER}"
fi

if [ -n "$PGPASSWORD" ]; then
    export DB_PASSWORD="${DB_PASSWORD:-$PGPASSWORD}"
fi

# Default to require SSL for Render Postgres
if [ -n "$PGHOST" ] && [ -z "$DB_SSLMODE" ]; then
    export DB_SSLMODE="require"
fi

# Ensure storage is writable
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# Storage link
php artisan storage:link --no-interaction 2>/dev/null || true

# Clear stale caches before rebuilding them. This does not touch the database.
php artisan optimize:clear --no-interaction 2>/dev/null || true

# Cache config/views before serving. Route caching is intentionally skipped
# because the app defines closure routes (route:cache would fail).
echo "Caching config and views..."
php artisan config:cache --no-interaction
php artisan view:cache --no-interaction 2>/dev/null || true

# Start Apache in the background so Render detects the port immediately.
echo "Starting Apache..."
apache2-foreground &
APACHE_PID=$!

# Give Apache a moment to bind
sleep 2

# Wait for database to be ready
if [ "${DB_CONNECTION:-pgsql}" = "pgsql" ]; then
    echo "Waiting for PostgreSQL to be ready..."
    DB_READY=0
    for i in $(seq 1 30); do
        if php -r "try { new PDO('pgsql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}'); echo 'ok'; } catch (Exception \$e) { exit(1); }" 2>/dev/null; then
            echo "PostgreSQL is ready."
            DB_READY=1
            break
        fi
        echo "Attempt $i/30: PostgreSQL not ready yet..."
        sleep 2
    done

    if [ "$DB_READY" -ne 1 ]; then
        echo "WARNING: PostgreSQL did not become ready in time; continuing startup." >&2
    fi
fi

# ---------------------------------------------------------------------------
# Schema migrations
# ---------------------------------------------------------------------------
# NEVER run `migrate:fresh`, `migrate:refresh`, `db:wipe`, or `db:seed` here.
# Those commands destroy the production database on every deploy.
#
# `migrate --force` is additive: it only applies migrations that have not
# already been recorded in the `migrations` table and never drops data.
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "Running pending migrations (non-destructive)..."
    # php artisan migrate --force --no-interaction
    php artisan migrate:fresh --force
else
    echo "Skipping migrations because RUN_MIGRATIONS=${RUN_MIGRATIONS:-false}."
fi

# Seeders are opt-in because they can duplicate or overwrite production data.
if [ "${RUN_SEEDERS:-false}" = "true" ]; then
    echo "Running seeders because RUN_SEEDERS=true..."
    php artisan db:seed --force --no-interaction
else
    echo "Skipping seeders (RUN_SEEDERS is not true)."
fi

echo "=== Entrypoint complete ==="

# Keep the container alive with Apache in the foreground.
wait "$APACHE_PID"
