#!/usr/bin/env bash
# Offline release/rollback test; never connects to the production server.
set -euo pipefail
cd "$(dirname "$0")/../.."

tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT
source_dir="$tmp/source"
app_root="$tmp/app"
mkdir -p "$source_dir/api/v1" "$source_dir/deployment/migrations" "$app_root" "$tmp/bin"
touch "$source_dir/api/v1/uc_api.php" "$source_dir/uc_device_access.php" \
  "$source_dir/deployment/migrations/004_uc_device_access.sql"

cat >"$tmp/bin/rsync" <<'SH'
#!/usr/bin/env bash
cp -a "${@: -2:1}/." "${@: -1}"
SH
cat >"$tmp/bin/systemctl" <<'SH'
#!/usr/bin/env bash
printf '%s\n' "$*" >> "$RELOAD_LOG"
SH
cat >"$tmp/bin/curl" <<'SH'
#!/usr/bin/env bash
[[ "$*" != *http://* ]] || exit 1
if [[ "$*" == *health.php* ]]; then
  [[ "${HEALTH_OK:-1}" == 1 ]]
else
  printf '%s' "${DEVICE_STATUS:-401}"
fi
SH
chmod +x "$tmp/bin/"*
export PATH="$tmp/bin:$PATH" RELOAD_LOG="$tmp/reloads"
export APP_ROOT="$app_root" RELEASE_SOURCE="$source_dir" APP_URL="http://example.invalid"

if bash deployment/deploy.sh >"$tmp/output" 2>&1; then
  echo "Missing marker was accepted" >&2
  exit 1
fi
[[ ! -e "$app_root/current" ]]

touch "$source_dir/api/v1/.uc_device_auth_ready"
bash deployment/deploy.sh >"$tmp/output"
first_release="$(readlink -f "$app_root/current")"
[[ -f "$first_release/api/v1/.uc_device_auth_ready" ]]
sleep 1

if DEVICE_STATUS=503 bash deployment/deploy.sh >"$tmp/output" 2>&1; then
  echo "A rejected UC endpoint was accepted" >&2
  exit 1
fi
[[ "$(readlink -f "$app_root/current")" == "$first_release" ]]
[[ "$(wc -l < "$tmp/reloads")" -eq 3 ]] # activation, failed activation, rollback
sleep 1

if DEVICE_STATUS=200 bash deployment/deploy.sh >"$tmp/output" 2>&1; then
  echo "An unauthenticated UC endpoint was accepted" >&2
  exit 1
fi
[[ "$(readlink -f "$app_root/current")" == "$first_release" ]]
[[ "$(wc -l < "$tmp/reloads")" -eq 5 ]]
sleep 1

if HEALTH_OK=0 bash deployment/deploy.sh >"$tmp/output" 2>&1; then
  echo "A failing health check was accepted" >&2
  exit 1
fi
[[ "$(readlink -f "$app_root/current")" == "$first_release" ]]
[[ "$(wc -l < "$tmp/reloads")" -eq 7 ]]
echo "Secured release preflight, HTTPS probe and rollback checks passed."