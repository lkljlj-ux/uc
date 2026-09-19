#!/usr/bin/env bash
set -euo pipefail

: "${APP_ROOT:?APP_ROOT is required}"
: "${RELEASE_SOURCE:?RELEASE_SOURCE is required}"
: "${APP_URL:?APP_URL is required}"

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

if ! curl --fail --silent --show-error --max-time 20 "$APP_URL/health.php" >/dev/null; then
  if [[ -n "$previous_release" && -d "$previous_release" ]]; then
    ln -sfn "$previous_release" "$APP_ROOT/current"
  fi
  echo "Health check failed; previous release restored." >&2
  exit 1
fi

find "$APP_ROOT/releases" -mindepth 1 -maxdepth 1 -type d -printf '%T@ %p\n' \
  | sort -rn | tail -n +6 | cut -d' ' -f2- | xargs -r rm -rf

echo "Release $release_id deployed successfully."