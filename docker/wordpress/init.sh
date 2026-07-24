#!/bin/sh

set -eu

cd /var/www/html

until [ -f /var/www/html/wp-config.php ]; do
    sleep 2
done

until wp db query 'SELECT 1' >/dev/null 2>&1; do
    sleep 2
done

fresh_install=false
if ! wp core is-installed >/dev/null 2>&1; then
    wp core install \
        --url="${WP_URL}" \
        --title="${WP_SITE_TITLE}" \
        --admin_user="${WP_ADMIN_USER}" \
        --admin_password="${WP_ADMIN_PASSWORD}" \
        --admin_email="${WP_ADMIN_EMAIL}" \
        --skip-email
    fresh_install=true
fi

if [ -n "${WP_OLD_URL:-}" ] && [ "${WP_OLD_URL}" != "${WP_URL}" ]; then
    wp search-replace "${WP_OLD_URL}" "${WP_URL}" --all-tables --precise --skip-columns=guid
fi

wp option update home "${WP_URL}"
wp option update siteurl "${WP_URL}"
wp option update timezone_string "${WP_TIMEZONE:-Asia/Ho_Chi_Minh}"
wp option update gmt_offset 7

if wp theme is-installed "${WP_THEME}" >/dev/null 2>&1; then
    wp theme activate "${WP_THEME}"
fi

for plugin in ${WP_PLUGINS:-}; do
    if wp plugin is-installed "${plugin}" >/dev/null 2>&1; then
        wp plugin activate "${plugin}" || true
    fi
done

if [ "${fresh_install}" = true ]; then
    wp rewrite structure '/%postname%/'
fi
wp rewrite flush --hard

echo "Beauty Core is ready at ${WP_URL}"
