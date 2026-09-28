#!/usr/bin/env bash
set -euo pipefail

: "${APP_ROOT:?APP_ROOT is required}"
: "${RELEASE_SOURCE:?RELEASE_SOURCE is required}"
: "${APP_URL:?APP_URL is required}"

if [[ "$APP_URL" != https://* ]]; then
  echo "APP_URL must be an HTTPS origin for device API verification." >&2
  exit 1
fi
for required in api/v1/.uc_device_auth_ready api/v1/uc_api.php uc_device_access.php deployment/migrations/004_uc_device_access.sql; do
  if [[ ! -f "$RELEASE_SOURCE/$required" ]]; then
    echo "Incomplete secured UC release: missing $required." >&2
    exit 1
  fi
done

release_id="$(date -u +%Y%m%d%H%M%S)"
release_dir="$APP_ROOT/releases/$release_id"
previous_release="$(readlink -f "$APP_ROOT/current" 2>/dev/null || true)"

mkdir -p "$release_dir" "$APP_ROOT/shared/collections" "$APP_ROOT/shared/downloads"
rsync -a --delete \
  --exclude='.git/' \
  --exclude='.github/' \
  --exclude='.env*' \
  --exclude='collections/' \
  --exclude='downloads/' \
  "$RELEASE_SOURCE/" "$release_dir/"

ln -sfn "$APP_ROOT/shared/collections" "$release_dir/collections"
ln -sfn "$APP_ROOT/shared/downloads" "$release_dir/downloads"

find "$release_dir" -type d -exec chmod 0755 {} +
find "$release_dir" -type f -exec chmod 0644 {} +

ln -sfn "$release_dir" "$APP_ROOT/current"

# Releases are switched through a symlink. Reload PHP-FPM so OPcache does not
# continue serving scripts resolved from the previously active release.
# No device credentials are used by this probe: a secure endpoint must reject it.
device_status=""
if ! systemctl reload php8.3-fpm ||
   ! curl --fail --silent --show-error --max-time 20 "$APP_URL/health.php" >/dev/null ||
   ! device_status="$(curl --silent --show-error --max-time 20 -o /dev/null -w '%{http_code}' \
     "$APP_URL/api/v1/get-pid?macId=DEPLOY-UNAUTHENTICATED-PROBE")" ||
   [[ "$device_status" != 401 ]]; then
  if [[ -n "$previous_release" && -d "$previous_release" ]]; then
    ln -sfn "$previous_release" "$APP_ROOT/current"
    systemctl reload php8.3-fpm || echo "WARNING: PHP-FPM reload after rollback failed." >&2
  else
    rm -f "$APP_ROOT/current"
  fi
  echo "Release verification failed (health or unauthenticated UC API); previous release restored if available." >&2
  exit 1
fi

find "$APP_ROOT/releases" -mindepth 1 -maxdepth 1 -type d -printf '%T@ %p\n' \
  | sort -rn | tail -n +6 | cut -d' ' -f2- | xargs -r rm -rf

echo "Release $release_id deployed successfully."