#!/usr/bin/env bash
set -Eeuo pipefail

target_commit="${1:-}"
project_root="${TRITONLED_PRODUCTION_ROOT:-/home/tritonled/htdocs/tritonled.se}"
backup_root="${TRITONLED_BACKUP_ROOT:-/home/tritonled/backups}"
base_url="${TRITONLED_BASE_URL:-https://tritonled.se}"

if [[ ! "$target_commit" =~ ^[0-9a-f]{40}$ ]]; then
  printf 'A full 40-character Git commit SHA is required.\n' >&2
  exit 2
fi

cd "$project_root"

exec 9>"$project_root/.git/deploy.lock"
if ! flock -n 9; then
  printf 'Another deployment is already running.\n' >&2
  exit 3
fi

if [[ -n "$(git status --porcelain)" ]]; then
  printf 'Production worktree is not clean; deployment stopped.\n' >&2
  git status --short >&2
  exit 4
fi

git fetch --prune origin main
git rev-parse --verify "${target_commit}^{commit}" >/dev/null

if ! git merge-base --is-ancestor "$target_commit" origin/main; then
  printf 'Target commit is not part of origin/main.\n' >&2
  exit 5
fi

previous_commit="$(git rev-parse HEAD)"

if ! git merge-base --is-ancestor "$previous_commit" "$target_commit"; then
  printf 'Target commit is not a fast-forward from the deployed commit.\n' >&2
  exit 6
fi

if git diff --name-only "$previous_commit..$target_commit" -- config/sync | grep -q .; then
  printf 'Deployment contains config/sync changes. Reconcile Drupal config manually before deployment.\n' >&2
  exit 7
fi

timestamp="$(date -u +%Y%m%dT%H%M%SZ)"
backup_dir="$backup_root/deploy-$timestamp-${previous_commit:0:12}"
mkdir -p "$backup_dir"
printf '%s\n' "$previous_commit" > "$backup_dir/previous-commit.txt"
git bundle create "$backup_dir/repository.bundle" HEAD
vendor/bin/drush sql:dump --gzip --result-file="$backup_dir/database.sql"
git bundle verify "$backup_dir/repository.bundle" >/dev/null
database_dump="$(find "$backup_dir" -maxdepth 1 -type f -name 'database.sql*' -print -quit)"
if [[ -z "$database_dump" ]]; then
  printf 'Database backup was not created.\n' >&2
  exit 8
fi
if [[ "$database_dump" == *.gz ]]; then
  gzip -t "$database_dump"
fi

maintenance_enabled=0
rollback_required=1

finish() {
  exit_code=$?
  trap - EXIT
  set +e

  if [[ "$exit_code" -ne 0 && "$rollback_required" -eq 1 ]]; then
    printf 'Deployment failed; restoring code and database from %s.\n' "$backup_dir" >&2
    vendor/bin/drush state:set system.maintenance_mode 1 --input-format=integer
    maintenance_enabled=1
    git reset --hard "$previous_commit"
    composer install --no-dev --optimize-autoloader --no-interaction
    if [[ "$database_dump" == *.gz ]]; then
      gzip -dc "$database_dump" | vendor/bin/drush sql:cli
    else
      vendor/bin/drush sql:cli < "$database_dump"
    fi
    vendor/bin/drush cache:rebuild || true
  fi

  if [[ "$maintenance_enabled" -eq 1 ]]; then
    vendor/bin/drush state:set system.maintenance_mode 0 --input-format=integer || true
    vendor/bin/drush cache:rebuild || true
  fi

  exit "$exit_code"
}
trap finish EXIT

vendor/bin/drush state:set system.maintenance_mode 1 --input-format=integer
maintenance_enabled=1
vendor/bin/drush cache:rebuild

git merge --ff-only "$target_commit"
composer install --no-dev --optimize-autoloader --no-interaction
vendor/bin/drush updatedb -y
vendor/bin/drush cache:rebuild

vendor/bin/drush state:set system.maintenance_mode 0 --input-format=integer
maintenance_enabled=0
vendor/bin/drush cache:rebuild

urls=(
  "$base_url/"
  "$base_url/produkt/db218-linjar-led-armatur"
  "$base_url/en/product/db218-linear-led-luminaire"
  "$base_url/form/contact"
  "$base_url/user/login"
)

for url in "${urls[@]}"; do
  status="$(curl --silent --show-error --location --output /dev/null --write-out '%{http_code}' "$url")"
  if [[ "$status" != "200" ]]; then
    printf 'Smoke test failed: %s returned %s.\n' "$url" "$status" >&2
    exit 8
  fi
done

rollback_required=0
printf 'Deployment complete: %s\nBackup: %s\n' "$target_commit" "$backup_dir"
