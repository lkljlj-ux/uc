# HostingRaja VPS Deployment

## Required server details

- VPS IP or hostname
- SSH user and port
- Domain name
- Deployment root, recommended: `/var/www/myjoin`
- Ubuntu/Debian version and PHP version

Never put passwords, private keys, database credentials, or API keys in the repository.

## 1. Prepare the VPS

Copy `deployment/bootstrap-ubuntu-apache.sh` to the VPS, then run:

```bash
sudo APP_DOMAIN=example.com APP_ROOT=/var/www/myjoin \
  DEPLOY_USER=deploy PHP_VERSION=8.2 \
  bash bootstrap-ubuntu-apache.sh
```

Add the deployment user's SSH public key to:

```text
/home/deploy/.ssh/authorized_keys
```

## 2. Configure production environment

Set these variables in the Apache/PHP-FPM service environment:

```text
DB_HOST
DB_PORT
DB_NAME
DB_USER
DB_PASSWORD
SESSION_SECRET
UC_API_KEY
```

Restart Apache after setting environment variables. Do not upload `.env` into the public web root.

## 3. Create the database

Create a dedicated database and least-privilege database user. Back up any existing live database before importing `aadhaar_test.sql`. Never automate a destructive import on every deployment.

## 4. GitHub production secrets

Create these GitHub Actions secrets:

```text
VPS_HOST
VPS_PORT
VPS_USER
VPS_SSH_KEY
VPS_APP_ROOT
APP_URL
```

`APP_URL` must be the HTTPS production origin, without a trailing slash.

## 5. First deployment

Push to the `main` branch or run the “Deploy to HostingRaja VPS” workflow manually. The workflow:

1. Validates every PHP file.
2. Uploads a new release.
3. Preserves `collections/` and `downloads/`.
4. Atomically switches the `current` symlink.
5. Calls `/health.php`.
6. Restores the previous release if the health check fails.

## 6. HTTPS and verification

After DNS points to the VPS:

```bash
sudo certbot --apache -d example.com -d www.example.com
```

Verify login, database writes, UC Operator, UC Map Machine, and all `/api/v1` endpoints.