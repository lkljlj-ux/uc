#!/usr/bin/env bash
set -euo pipefail

: "${APP_ROOT:=/var/www/myjoin}"
: "${DEPLOY_USER:=deploy}"
: "${PHP_VERSION:=8.3}"

if [[ $EUID -ne 0 ]]; then
  echo "Run this script as root." >&2
  exit 1
fi

apt-get update
DEBIAN_FRONTEND=noninteractive apt-get install -y \
  nginx mariadb-server git rsync curl unzip certbot python3-certbot-nginx \
  "php${PHP_VERSION}-fpm" "php${PHP_VERSION}-cli" \
  "php${PHP_VERSION}-mysql" "php${PHP_VERSION}-curl" \
  "php${PHP_VERSION}-mbstring" "php${PHP_VERSION}-xml"

if ! id "$DEPLOY_USER" >/dev/null 2>&1; then
  adduser --disabled-password --gecos "" "$DEPLOY_USER"
fi

install -d -o "$DEPLOY_USER" -g www-data -m 2775 \
  "$APP_ROOT/releases" "$APP_ROOT/shared/collections" "$APP_ROOT/shared/downloads"

cat >/etc/nginx/sites-available/myjoin <<EOF
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name _;

    root ${APP_ROOT}/current;
    index index.php index.html;
    client_max_body_size 100M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ ^/api/v1/(get-auth-token|get-bio-token|get-pid|getStatusUc|verify-otp|verify-otp-uc)/?\$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root/api/v1/uc_api.php;
        fastcgi_param QUERY_STRING route=\$1&\$query_string;
        fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm.sock;
    }

    location ~ \.php\$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm.sock;
    }

    location ~ /\. {
        deny all;
    }

    location ~* \.(sql|zip|tar|gz|tgz|7z|log|ini|key|pem|env)\$ {
        deny all;
    }
}

server {
    listen 7070;
    listen [::]:7070;
    server_name _;

    root ${APP_ROOT}/current;

    location = /health.php {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root/health.php;
        fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm.sock;
    }

    location ~ ^/api/v1/(get-auth-token|get-bio-token|get-pid|getStatusUc|verify-otp|verify-otp-uc)/?\$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root/api/v1/uc_api.php;
        fastcgi_param QUERY_STRING route=\$1&\$query_string;
        fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm.sock;
    }

    location / {
        return 404;
    }
}
EOF

rm -f /etc/nginx/sites-enabled/default
ln -sfn /etc/nginx/sites-available/myjoin /etc/nginx/sites-enabled/myjoin
nginx -t
systemctl enable --now "php${PHP_VERSION}-fpm" nginx mariadb
systemctl reload nginx

echo "Nginx is serving the web application on port 80 and its API on port 7070."
echo "After adding a domain, replace server_name _ and run:"
echo "certbot --nginx -d app.example.com"