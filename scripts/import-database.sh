#!/bin/sh

set -eu

file=${1:-seed/database/001-wordpress.sql}
test -f "$file"
docker compose exec -T db sh -c 'exec mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' < "$file"
echo "Imported $file"
