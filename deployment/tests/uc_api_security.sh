#!/usr/bin/env bash
# Runs locally against the development MySQL database. Does not test real TLS.
set -euo pipefail
cd "$(dirname "$0")/../.."

if [[ "${UC_API_TEST_ALLOW_DEV_DB:-}" != 1 ]]; then
  echo "Set UC_API_TEST_ALLOW_DEV_DB=1 to run the fixture test against your development database." >&2
  exit 2
fi

mac="TEST-UC-SECURITY"
token="$(printf 'a%.0s' {1..64})"
port="${UC_API_TEST_PORT:-18443}"
tmp="$(mktemp -d)"
cleanup() {
  if [[ -n "${server_pid:-}" ]]; then kill "$server_pid" 2>/dev/null || true; fi
  php -r 'require "database.php"; $m="TEST-UC-SECURITY"; foreach(["uc_upload_events","uc_upload_counts","uc_device_tokens"] as $table){$q=mysqli_prepare($link,"DELETE FROM ".$table." WHERE macid = ?");mysqli_stmt_bind_param($q,"s",$m);mysqli_stmt_execute($q);mysqli_stmt_close($q);}' >/dev/null 2>&1 || true
  rm -rf "$tmp"
}
trap cleanup EXIT

php -r 'require "database.php"; $m="TEST-UC-SECURITY"; foreach(["uc_upload_events","uc_upload_counts","uc_device_tokens"] as $table){$q=mysqli_prepare($link,"DELETE FROM ".$table." WHERE macid = ?");mysqli_stmt_bind_param($q,"s",$m);mysqli_stmt_execute($q);mysqli_stmt_close($q);} $hash=hash("sha256",str_repeat("a",64));$q=mysqli_prepare($link,"INSERT INTO uc_device_tokens (macid,token_hash) VALUES (?,?)");mysqli_stmt_bind_param($q,"ss",$m,$hash);mysqli_stmt_execute($q);mysqli_stmt_close($q);'

cat >"$tmp/router.php" <<'PHP'
<?php
// Simulates the HTTPS flag set by Nginx's TLS FastCGI listener.
$_SERVER['HTTPS'] = 'on';
if(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === '/api/v1/uc_api.php'){
    require getcwd() . '/api/v1/uc_api.php';
    return true;
}
return false;
PHP
UC_API_SECURITY_MODE=enforce php -S "127.0.0.1:$port" -t . "$tmp/router.php" >"$tmp/server.log" 2>&1 &
server_pid=$!
sleep 1
base="http://127.0.0.1:$port/api/v1/uc_api.php"
request() {
  local want="$1"
  shift
  local got
  got="$(curl -sS -o "$tmp/response.json" -w '%{http_code}' "$@")"
  if [[ "$got" != "$want" ]]; then
    echo "Expected HTTP $want but got $got: $(cat "$tmp/response.json")" >&2
    exit 1
  fi
}

request 401 "$base?route=get-pid&macId=$mac"
request 401 -H "X-Auth-Token: $token" "$base?route=get-pid&macId=OTHER-UC-DEVICE"
request 404 -H "X-Auth-Token: $token" "$base?route=get-pid&macId=$mac"
request 422 -X POST -H "X-Auth-Token: $token" -H 'Content-Type: application/json' \
  -d '{"sid":"TEST-SID-1"}' "$base?route=upload-uc-count&macId=$mac"
request 422 -X POST -H "X-Auth-Token: $token" -H 'Content-Type: application/json' \
  -d '{"sid":"TEST-SID-1","eventId":"TEST-EVENT-0001"}' "$base?route=upload-uc-count&macId=$mac&sid=TEST-SID-1"
request 200 -X POST -H "X-Auth-Token: $token" -H 'Content-Type: application/json' \
  -d '{"sid":"TEST-SID-1","eventId":"TEST-EVENT-0001"}' "$base?route=upload-uc-count&macId=$mac"
php -r '$j=json_decode(file_get_contents($argv[1]),true);if($j["count"]!==1 || $j["counted"]!==true)exit(1);' "$tmp/response.json"
request 200 -X POST -H "X-Auth-Token: $token" -H 'Content-Type: application/json' \
  -d '{"sid":"TEST-SID-1","eventId":"TEST-EVENT-0001"}' "$base?route=upload-uc-count&macId=$mac"
php -r '$j=json_decode(file_get_contents($argv[1]),true);if($j["count"]!==1 || $j["counted"]!==false)exit(1);' "$tmp/response.json"
request 409 -X POST -H "X-Auth-Token: $token" -H 'Content-Type: application/json' \
  -d '{"sid":"TEST-SID-2","eventId":"TEST-EVENT-0001"}' "$base?route=upload-uc-count&macId=$mac"
php -r 'require "database.php";$q=mysqli_prepare($link,"UPDATE uc_device_tokens SET revoked_at=NOW() WHERE macid=?");$m="TEST-UC-SECURITY";mysqli_stmt_bind_param($q,"s",$m);mysqli_stmt_execute($q);mysqli_stmt_close($q);'
request 401 -H "X-Auth-Token: $token" "$base?route=get-pid&macId=$mac"

kill "$server_pid" 2>/dev/null || true
wait "$server_pid" 2>/dev/null || true
UC_API_SECURITY_MODE=transition UC_API_REQUIRE_AUTH=0 UC_API_KEY='' SESSION_SECRET='fixture-legacy-key' \
  php -S "127.0.0.1:$port" -t . >"$tmp/legacy-server.log" 2>&1 &
server_pid=$!
sleep 1
# An old "0" setting must never restore unauthenticated HTTP access.
request 401 "$base?route=get-pid&macId=$mac"
request 404 -H 'X-Auth-Token: fixture-legacy-key' "$base?route=get-pid&macId=$mac"

echo 'UC API authentication, MAC binding, replay and revocation checks passed.'