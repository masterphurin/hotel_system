#!/bin/sh
set -e

# Wait for the database to become reachable before starting Apache.
# db.php auto-creates the schema on first request, so we only need TCP readiness.
DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"

echo "Waiting for database at ${DB_HOST}:${DB_PORT}..."

for i in $(seq 1 60); do
    if php -r '$c=@fsockopen(getenv("DB_HOST")?:"db", (int)(getenv("DB_PORT")?:3306), $e, $s, 2); exit($c?0:1);'; then
        echo "Database is reachable."
        break
    fi
    echo "  ...still waiting ($i/60)"
    sleep 2
done

exec "$@"
