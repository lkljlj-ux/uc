#!/usr/bin/env bash
set -euo pipefail

echo "Validating PHP files..."
find . \
  -type f \
  -name '*.php' \
  -not -path './.git/*' \
  -not -path './.mysql/*' \
  -print0 \
  | xargs -0 -r -n1 php -l >/dev/null

echo "Validating deployment and project shell scripts..."
while IFS= read -r -d '' script; do
  bash -n "$script"
done < <(
  find scripts deployment \
    -type f \
    -name '*.sh' \
    -print0
)

echo "Post-merge validation completed."