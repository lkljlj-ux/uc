#!/usr/bin/env bash
set -e
DATADIR="$HOME/workspace/.mysql/data"
SOCK="$HOME/workspace/.mysql/mysql.sock"
exec mariadbd \
  --datadir="$DATADIR" \
  --socket="$SOCK" \
  --port=3306 \
  --bind-address=127.0.0.1 \
  --innodb-use-native-aio=0 \
  --skip-name-resolve
