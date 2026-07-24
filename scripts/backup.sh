#!/bin/sh

set -eu

timestamp=$(date +%Y%m%d-%H%M%S)
mkdir -p backups
docker compose exec -T db sh -c 'exec mysqldump --no-tablespaces -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' > "backups/database-${timestamp}.sql"
tar -czf "backups/wp-content-${timestamp}.tar.gz" src/wp-content
echo "Created backups/database-${timestamp}.sql and backups/wp-content-${timestamp}.tar.gz"
