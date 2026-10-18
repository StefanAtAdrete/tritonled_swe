#!/usr/bin/env bash
set -Eeuo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$project_root"

required_files=(
  AGENTS.md
  docs/CURRENT-TASK.md
  docs/WORKLOG.md
  docs/DECISIONS.md
  composer.json
  composer.lock
)

for file in "${required_files[@]}"; do
  if [[ ! -s "$file" ]]; then
    printf 'Missing required project file: %s\n' "$file" >&2
    exit 1
  fi
done

composer validate --strict
composer audit --locked

while IFS= read -r -d '' file; do
  php -l "$file" >/dev/null
done < <(find web/modules/custom web/themes/custom scripts -type f -name '*.php' -print0 2>/dev/null)

while IFS= read -r -d '' file; do
  bash -n "$file"
done < <(find scripts -type f -name '*.sh' -print0)

printf 'Project validation passed.\n'
