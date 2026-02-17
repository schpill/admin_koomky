#!/usr/bin/env bash
set -euo pipefail

TARGET_URL="${UPTIME_TARGET_URL:-https://localhost/api/v1/health}"
PING_URL="${HEALTHCHECKS_PING_URL:-}"
TIMEOUT_SECONDS="${UPTIME_TIMEOUT_SECONDS:-10}"

if curl -fsS --max-time "${TIMEOUT_SECONDS}" "${TARGET_URL}" >/dev/null; then
  if [[ -n "${PING_URL}" ]]; then
    curl -fsS --max-time 5 "${PING_URL}" >/dev/null
  fi

  echo "Uptime check passed for ${TARGET_URL}"
  exit 0
fi

if [[ -n "${PING_URL}" ]]; then
  curl -fsS --max-time 5 "${PING_URL}/fail" >/dev/null || true
fi

echo "Uptime check failed for ${TARGET_URL}" >&2
exit 1
