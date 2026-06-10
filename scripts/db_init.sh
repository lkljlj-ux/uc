#!/usr/bin/env bash
set -e
DATADIR="$HOME/workspace/.mysql/data"
if [ -f "$DATADIR/mysql/user.frm" ] || [ -f "$DATADIR/mysql/user.MAD" ] || [ -d "$DATADIR/mysql" ] && [ -f "$DATADIR/mysql/plugin.frm" ]; then
  echo "DB already initialized"
  exit 0
fi
rm -rf "$HOME/workspace/.mysql"
mkdir -p "$DATADIR"
mariadb-install-db \
  --datadir="$DATADIR" \
  --auth-root-authentication-method=normal \
  --skip-test-db
echo "INIT_DONE"
