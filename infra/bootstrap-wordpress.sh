#!/bin/sh
set -eu

cd /var/www/html

until [ -f wp-includes/version.php ]; do
  sleep 2
done

until wp core is-installed --allow-root >/dev/null 2>&1; do
  if wp core install \
    --url="http://localhost:8080" \
    --title="eno 的小黑屋（本地开发）" \
    --admin_user="$WORDPRESS_ADMIN_USER" \
    --admin_password="$WORDPRESS_ADMIN_PASSWORD" \
    --admin_email="$WORDPRESS_ADMIN_EMAIL" \
    --skip-email \
    --allow-root; then
    break
  fi
  sleep 3
done

wp theme activate eno-workbench --allow-root
wp plugin activate eno-workbench-content-importer --allow-root
wp eval 'eno_workbench_import_articles();' --allow-root
wp rewrite flush --hard --allow-root
printf '%s\n' 'Local WordPress is ready: http://localhost:8080/'
