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

`APP_URL` must be the production origin without a trailing slash. An IP TLS
certificate is now available; use `https://103.118.17.117` for direct IP access.

## 5. First deployment

Push to the `main` branch or run the “Deploy to HostingRaja VPS” workflow manually. The workflow:

1. Validates every PHP file.
2. Uploads a new release.
3. Preserves `collections/` and `downloads/`.
4. Atomically switches the `current` symlink.
5. Calls `/health.php`.
6. Restores the previous release if the health check fails.

## 6. Direct IP access

When no domain is available, use the short-lived Let's Encrypt certificate
for the IP address and the Nginx configuration in
`deployment/nginx-https-ip.conf`. The secure origin is:

```text
https://103.118.17.117
```

The HTTP API aliases are retained temporarily for existing Java clients.
Do not treat them as secure. Port-80 login and admin pages redirect to HTTPS
while the old API aliases remain available during rollout. Existing admin
sessions must sign in again after enabling secure session cookies. After all
devices are migrated, switch `UC_API_SECURITY_MODE` to `enforce` and disable
public HTTP API access. Do not turn off legacy access before confirming the
Java client rollout.

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

`http://103.118.17.117:7070/health.php` is available for legacy health checks.
Port 7070 is not encrypted and must be closed after the device rollout.

On success, `get-pid` returns only the decrypted PID data as plain text (not a
JSON object). Authentication, missing mapping, and other errors still return
JSON error responses.
On success, `verify-otp` and `verify-otp-uc` likewise return only their
respective decrypted values as plain text; their error responses remain JSON.

Before deploying verify-otp support on an existing database, apply
`deployment/migrations/002_verify_otp.sql` to the application database and add
`verify-otp` to both port 80 and port 7070 Nginx API route allowlists. Existing
operators have no verify-otp token until an admin sets it from UC Operator Add;
the existing verify-otp-uc value remains unchanged until an admin updates it.
Both verify-otp and verify-otp-uc fields accept arbitrary tokens up to 10000
characters despite the API route names.

Before deploying upload-count support, apply `deployment/migrations/003_uc_upload_counts.sql`
to the application database and add `upload-uc-count` to the Nginx API route
allowlists. The old HTTP-only caller can still send
`POST /api/v1/upload-uc-count?macId=...&sid=...` in transition mode. This
legacy form is not retry-safe and must not be used by new clients.

The HTTPS endpoint requires a per-device token in `X-Auth-Token`, a matching
`macId` query parameter, and a JSON POST body with `sid` and a stable,
client-generated `eventId` (8–128 ASCII letters, numbers, `_` or `-`). A
repeated `(macId, eventId, sid)` returns the same count with `counted:false`.
Reusing that event ID with a different SID returns 409; a different event ID
increments the count. The SID must not appear in the URL.

The supplied UI JAR instead calls a local `127.0.0.1:7078` upload-count bridge.
That bridge's source was not supplied, so the JAR patcher deliberately leaves
it alone. Do not assume the remote count route is used by that JAR. Obtain
the bridge source/contract and a stable upload event ID before migrating it.

## 8. Device credentials and HTTPS rollout

Apply `deployment/migrations/004_uc_device_access.sql` before deploying the
new API. The admin page `uc_device_access.php` issues one-time, random device
tokens tied to a MAC ID. The database stores only SHA-256 token hashes. Record
the MAC ID exactly as `StationBiosIdentifier` produces it on the device.
The token is shown only when created; it is not retrievable afterward. Revoke
lost or retired tokens from that page. Never put a token in a URL, log, shared
config file, or repository.

Replace the two original unsigned JARs with the patched copies produced by
`tools/uc-jar-patch/patch.sh` (never load both copies alongside the originals).
Set the one-time token in the Java process's `UC_DEVICE_TOKEN` environment on
that specific device. Validate the replacement on a backed-up test installation
first: the original host application's loader and external dependencies were
not supplied, so the JAR smoke checks are not full runtime validation. The
patcher changes the identified GET APIs to `https://103.118.17.117:443` and
adds the token header. Do not disable TLS verification. The host JRE must
trust the current Let's Encrypt root and support an IP-address SAN.

The production IP certificate uses Certbot's `shortlived` profile and lasts
six days. Certbot's renewal timer must remain active and Nginx must reload
after renewal. The HTTP `/.well-known/acme-challenge/` path must remain
accessible on port 80. A certificate check that does not use `-k` is:

```bash
curl -I https://103.118.17.117/health.php
```

Keep `api/v1/.uc_device_auth_ready` in each release containing the secured API.
The HTTPS Nginx listener refuses UC API requests if the marker is missing,
so accidentally deploying an old release fails closed rather than reviving
the former authentication bypass. The direct PHP API file URL is not public;
use the documented `/api/v1/get-pid`-style aliases.

Before the next GitHub deployment, confirm migration 004 has been applied to
the VPS database and the HTTPS Nginx configuration above is active. The
deployment script requires the marker, secured API, admin page and migration
file in the release source. It upgrades a legacy HTTP `APP_URL` to HTTPS for
verification (the host must serve HTTPS on the standard port). After switching
releases it checks `/health.php` and expects HTTP 401 from a tokenless
`/api/v1/get-pid` request with a test MAC ID. This check sends no credential
and returns no PID. A failed check restores the previous release and reloads
PHP-FPM again. If the previous release lacks the readiness marker, the
Nginx HTTPS UC route still returns 503 after rollback; recover by deploying
a secured release, not by removing the Nginx guard.

For a local rehearsal of the release and rollback checks without VPS access,
run `bash deployment/tests/deploy_device_guard.sh`. After a real deployment,
confirm HTTPS health returns 200 and a tokenless UC request returns 401; do
not test with a live device token in command history or workflow logs.

On the VPS, `UC_API_SECURITY_MODE=transition` temporarily accepts old HTTP
requests only if they supply the existing shared `X-Auth-Token` header.
`UC_API_KEY` is preferred; the old `SESSION_SECRET` header fallback applies
only in this explicit transition mode. The old `UC_API_REQUIRE_AUTH=0` bypass
is no longer supported: it could expose OTP and PID values to anyone with
a MAC ID. HTTPS always requires a MAC-bound device token. The default
and final mode, `enforce`, rejects sensitive requests over HTTP (426).
A blank, unknown or missing mode is enforced. After confirming every device
has migrated, remove transition mode, block port 7070, and check that the
old routes no longer return credentials. The only intentionally public
UC route is `getStatusUc`, which reveals only ACTIVE/INACTIVE mapping status.