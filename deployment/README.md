# HostingRaja VPS Deployment

## Required server details

- VPS IP or hostname
- SSH user and port
- Domain name (optional; the app can instead be served directly from the VPS IP over HTTP)
- Deployment root, recommended: `/var/www/myjoin`
- Ubuntu/Debian version and PHP version

Never put passwords, private keys, database credentials, or API keys in the repository.

## 1. Prepare the VPS

Copy `deployment/bootstrap-ubuntu-nginx.sh` to the VPS, then run:

```bash
sudo APP_ROOT=/var/www/myjoin DEPLOY_USER=deploy PHP_VERSION=8.3 \
  bash bootstrap-ubuntu-nginx.sh
```

This installs Nginx, PHP-FPM, MariaDB, and Certbot; creates the release and
shared-data directories; and configures direct IP access on port 80.

Add the deployment user's SSH public key to:

```text
/home/deploy/.ssh/authorized_keys
```

## 2. Configure production environment

Set these variables in the PHP-FPM service environment:

```text
DB_HOST
DB_PORT
DB_NAME
DB_USER
DB_PASSWORD
SESSION_SECRET
UC_API_KEY
```

Restart PHP-FPM after setting environment variables. Do not upload `.env` into the public web root.

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

`APP_URL` must be the production origin without a trailing slash. Use the
HTTPS origin when a domain is configured. For direct IP access, use
`http://103.118.17.117`.

## 5. First deployment

Push to the `main` branch or run the “Deploy to HostingRaja VPS” workflow manually. The workflow:

1. Validates every PHP file.
2. Uploads a new release.
3. Preserves `collections/` and `downloads/`.
4. Atomically switches the `current` symlink.
5. Calls `/health.php`.
6. Restores the previous release if the health check fails.

## 6. Direct IP access

When no domain is available, configure Nginx as the default server on port 80
with `server_name _;` and the application root set to
`/var/www/myjoin/current`. The live origin is:

```text
http://103.118.17.117
```

This setup does not encrypt login sessions or API traffic. Do not enable an
HTTPS redirect until a domain and valid TLS certificate are configured.

Verify `/login.php`, `/health.php`, and the required `/api/v1` routes after
each deployment.

## 7. Direct API access on port 7070

The Nginx bootstrap creates a separate API-only listener. Use endpoints in
this form:

```text
http://103.118.17.117:7070/api/v1/get-auth-token
http://103.118.17.117:7070/api/v1/get-bio-token
http://103.118.17.117:7070/api/v1/get-pid
http://103.118.17.117:7070/api/v1/getStatusUc
http://103.118.17.117:7070/api/v1/verify-otp
http://103.118.17.117:7070/api/v1/verify-otp-uc
http://103.118.17.117:7070/api/v1/upload-uc-count
```

`http://103.118.17.117:7070/health.php` is available for health checks. Every
other path on port 7070 returns `404`, so application pages and release files
are not exposed by the API listener.

On success, `get-pid` returns only the decrypted PID data as plain text (not a
JSON object). Authentication, missing mapping, and other errors still return
JSON error responses.
On success, `verify-otp-uc` likewise returns only its decrypted OTP value as
plain text; its error responses remain JSON.

Before deploying verify-otp support on an existing database, apply
`deployment/migrations/002_verify_otp.sql` to the application database and add
`verify-otp` to both port 80 and port 7070 Nginx API route allowlists. Existing
operators have no verify-otp token until an admin sets it from UC Operator Add;
the existing verify-otp-uc value remains unchanged until an admin updates it.
Both verify-otp and verify-otp-uc fields accept arbitrary tokens up to 10000
characters despite the API route names.

Before deploying upload-count support, apply `deployment/migrations/003_uc_upload_counts.sql`
to the application database and add `upload-uc-count` to both Nginx API route
allowlists. The client sends `POST /api/v1/upload-uc-count?macId=...&sid=...`
with no request body. Each POST increments the stored count for that MAC and
SID by one, even when that MAC has no UC operator mapping. Admins can view
the totals from UC Report in the sidebar.

## 8. Optional HTTPS upgrade

After DNS points to the VPS:

```bash
sudo sed -i 's/server_name _;/server_name app.example.com;/' \
  /etc/nginx/sites-available/myjoin
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d app.example.com
```

Verify login, database writes, UC Operator, UC Map Machine, and all `/api/v1` endpoints.