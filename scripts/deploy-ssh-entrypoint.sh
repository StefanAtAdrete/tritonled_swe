#!/usr/bin/env bash
set -Eeuo pipefail

project_root="${TRITONLED_PRODUCTION_ROOT:-/home/tritonled/htdocs/tritonled.se}"
requested_command="${SSH_ORIGINAL_COMMAND:-}"

if [[ ! "$requested_command" =~ ^deploy\ ([0-9a-f]{40})$ ]]; then
  printf 'Only deploy followed by a full commit SHA is allowed.\n' >&2
  exit 64
fi

target_commit="${BASH_REMATCH[1]}"
cd "$project_root"
git fetch --prune origin main
git rev-parse --verify "${target_commit}^{commit}" >/dev/null

if ! git merge-base --is-ancestor "$target_commit" origin/main; then
  printf 'Requested commit is not part of origin/main.\n' >&2
  exit 65
fi

temporary_script="$(mktemp)"
cleanup() {
  rm -f "$temporary_script"
}
trap cleanup EXIT

git show "$target_commit:scripts/deploy-production.sh" > "$temporary_script"
bash "$temporary_script" "$target_commit"
