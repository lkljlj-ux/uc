#!/usr/bin/env bash
set -euo pipefail

: "${APP_DOMAIN:?Set APP_DOMAIN, for example app.example.com}"
: "${APP_ROOT:=/var/www/myjoin}"
: "${DEPLOY_USER:=deploy}"
: "${PHP_VERSION:=8.2}"

if [[ $EUID -ne 0 ]]; then
  echo "Run this script as root or with sudo." >&2
  exit 1
fi

apt-get update
DEBIAN_FRONTEND=noninteractive apt-get install -y \
  apache2 mariadb-server git rsync curl unzip certbot python3-certbot-apache \
  "php${PHP_VERSION}" "php${PHP_VERSION}-cli" "php${PHP_VERSION}-mysql" \
  "php${PHP_VERSION}-mbstring" "php${PHP_VERSION}-xml" "php${PHP_VERSION}-curl"

a2enmod rewrite headers ssl

if ! id "$DEPLOY_USER" >/dev/null 2>&1; then
  adduser --disabled-password --gecos "" "$DEPLOY_USER"
fi

install -d -o "$DEPLOY_USER" -g www-data -m 2775 \
  "$APP_ROOT/releases" "$APP_ROOT/shared/collections" "$APP_ROOT/shared/downloads"

cat >"/etc/apache2/sites-available/${APP_DOMAIN}.conf" <<EOF
<VirtualHost *:80>
    ServerName ${APP_DOMAIN}
    ServerAlias www.${APP_DOMAIN}
    DocumentRoot ${APP_ROOT}/current

    <Directory ${APP_ROOT}/current>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/${APP_DOMAIN}-error.log
    CustomLog \${APACHE_LOG_DIR}/${APP_DOMAIN}-access.log combined
</VirtualHost>
EOF

a2ensite "${APP_DOMAIN}.conf"
a2dissite 000-default.conf || true
apache2ctl configtest
systemctl reload apache2

echo "Base server setup complete."
echo "Next: configure Apache environment secrets, deploy the first release, then run:"
echo "certbot --apache -d ${APP_DOMAIN} -d www.${APP_DOMAIN}"