#!/usr/bin/env sh
set -eu

docker run --rm \
  --name eno-certbot-renew \
  -v /etc/letsencrypt:/etc/letsencrypt \
  -v /var/lib/letsencrypt:/var/lib/letsencrypt \
  -v /var/www/certbot:/var/www/certbot \
  certbot/certbot:latest renew --webroot -w /var/www/certbot --quiet

docker exec nginx-test nginx -s reload
