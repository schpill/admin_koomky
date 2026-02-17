#!/usr/bin/env bash
set -euo pipefail

if [[ $# -lt 1 ]]; then
  echo "Usage: $0 <backup-file.sql.gz>"
  exit 1
fi

BACKUP_FILE="$1"
if [[ ! -f "${BACKUP_FILE}" ]]; then
  echo "Backup file not found: ${BACKUP_FILE}"
  exit 1
fi

DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-5432}"
DB_NAME="${DB_DATABASE:-koomky}"
DB_USER="${DB_USERNAME:-koomky}"
DB_PASSWORD="${DB_PASSWORD:-}"

echo "Restoring database ${DB_NAME} from ${BACKUP_FILE}"

run_psql_restore() {
  if command -v psql >/dev/null 2>&1; then
    gunzip -c "${BACKUP_FILE}" | PGPASSWORD="${DB_PASSWORD}" psql \
      --host="${DB_HOST}" \
      --port="${DB_PORT}" \
      --username="${DB_USER}" \
      --dbname="${DB_NAME}"

    return
  fi

  if command -v docker >/dev/null 2>&1 && docker compose ps postgres >/dev/null 2>&1; then
    gunzip -c "${BACKUP_FILE}" | docker compose exec -T postgres env PGPASSWORD="${DB_PASSWORD}" psql \
      --host=127.0.0.1 \
      --port=5432 \
      --username="${DB_USER}" \
      --dbname="${DB_NAME}"

    return
  fi

  echo "Unable to run psql. Install postgres client tools or start docker compose postgres service." >&2
  exit 1
}

run_psql_restore

echo "Restore completed"
