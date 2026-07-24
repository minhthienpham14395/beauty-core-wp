#!/bin/sh

set -eu

database_file=${1:?Usage: scripts/restore.sh path/to/database.sql path/to/wp-content.tar.gz}
content_file=${2:?Usage: scripts/restore.sh path/to/database.sql path/to/wp-content.tar.gz}
test -f "$database_file"
test -f "$content_file"
docker compose exec -T db sh -c 'exec mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' < "$database_file"
tar -xzf "$content_file"
echo "Restored database and wp-content. Run docker compose restart wordpress wordpress-init."
