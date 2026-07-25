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

# Wait for database to be ready
if [ "${DB_CONNECTION:-pgsql}" = "pgsql" ]; then
    echo "Waiting for PostgreSQL to be ready..."
    for i in $(seq 1 30); do
        if php -r "try { new PDO('pgsql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}'); echo 'ok'; } catch (Exception \$e) { exit(1); }" 2>/dev/null; then
            echo "PostgreSQL is ready."
            break
        fi
        echo "Attempt $i/30: PostgreSQL not ready yet..."
        sleep 2
    done
fi

# Ensure storage is writable
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# Storage link
php artisan storage:link --no-interaction 2>/dev/null || true

# If FRESH_MIGRATIONS=true, drop all tables and re-run everything.
# Otherwise, run only pending migrations and seed as normal.
if [ "${FRESH_MIGRATIONS:-false}" = "true" ]; then
    echo "FRESH_MIGRATIONS=true — dropping all tables and re-seeding..."
    php artisan migrate:fresh --force --seed --no-interaction 2>&1 || true
else
    echo "Running pending migrations..."
    php artisan migrate --force --no-interaction 2>&1 | grep -v "Nothing to migrate" || true
    echo "Running database seeders..."
    php artisan db:seed --force --no-interaction 2>&1 || true
fi

# Clear and cache config for performance
echo "Caching config, routes, and views..."
php artisan config:cache --no-interaction 2>/dev/null || true
php artisan route:cache --no-interaction 2>/dev/null || true
php artisan view:cache --no-interaction 2>/dev/null || true

echo "=== Entrypoint complete, starting Apache ==="
exec "$@"
