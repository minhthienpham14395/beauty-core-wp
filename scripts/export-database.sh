#!/bin/sh

set -eu

mkdir -p seed/database
docker compose exec -T db sh -c 'exec mysqldump --no-tablespaces -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' > seed/database/001-wordpress.sql
echo "Exported seed/database/001-wordpress.sql"
