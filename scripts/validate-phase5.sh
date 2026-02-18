#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REPO="schpill/admin_koomky"

printf "\n[phase5] Validation run started at %s\n" "$(date -u '+%Y-%m-%dT%H:%M:%SZ')"

printf "\n[phase5] Backend coverage (>=80)\n"
(
  cd "$ROOT_DIR"
  docker compose run --rm api php -d memory_limit=512M ./vendor/bin/pest --coverage --min=80
)

printf "\n[phase5] Frontend coverage (>=80 on configured Phase 5 scope)\n"
(
  cd "$ROOT_DIR/frontend"
  pnpm vitest run --coverage
)

if command -v gh >/dev/null 2>&1; then
  printf "\n[phase5] Latest CI run on main (workflow: ci.yml)\n"
  gh run list \
    --repo "$REPO" \
    --workflow ci.yml \
    --branch main \
    --limit 1 \
    --json conclusion,status,url,headSha,updatedAt

  printf "\n[phase5] GitHub tag v1.1.0 status\n"
  if gh api "repos/$REPO/git/ref/tags/v1.1.0" >/dev/null 2>&1; then
    echo "v1.1.0 tag exists"
  else
    echo "v1.1.0 tag is missing"
  fi
else
  echo "\n[phase5] gh CLI not available: skipping remote CI/tag checks"
fi

printf "\n[phase5] Validation run completed.\n"
