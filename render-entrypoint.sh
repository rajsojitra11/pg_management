#!/bin/bash
set -e

# Render auto-injects PGHOST, PGPORT, PGDATABASE, PGUSER, PGPASSWORD
# when a PostgreSQL database is linked to this service.
#
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

# If FRESH_MIGRATIONS=true, drop all tables and re-run everything.
# Otherwise, run only pending migrations and seed as normal.
if [ "${FRESH_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate:fresh --force --seed --no-interaction 2>&1 || true
else
    php artisan migrate --force --no-interaction 2>&1 | grep -v "Nothing to migrate" || true
    php artisan db:seed --force --no-interaction 2>&1 || true
fi

# Clear and cache config for performance
php artisan config:cache --no-interaction 2>/dev/null || true
php artisan route:cache --no-interaction 2>/dev/null || true
php artisan view:cache --no-interaction 2>/dev/null || true

exec "$@"
