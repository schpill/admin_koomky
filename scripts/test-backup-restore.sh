#!/usr/bin/env bash
set -euo pipefail

DB_USER="${DB_USERNAME:-koomky}"
DB_PASSWORD="${DB_PASSWORD:-secret}"
TEST_DB="koomky_backup_e2e_$(date +%s)"
BACKUP_DIR="$(mktemp -d)"

cleanup() {
  docker compose exec -T postgres dropdb -U "${DB_USER}" --if-exists "${TEST_DB}" >/dev/null 2>&1 || true
  rm -rf "${BACKUP_DIR}"
}
trap cleanup EXIT

docker compose up -d postgres >/dev/null

docker compose exec -T postgres createdb -U "${DB_USER}" "${TEST_DB}"
docker compose exec -T postgres psql -U "${DB_USER}" -d "${TEST_DB}" -c \
  "CREATE TABLE backup_restore_probe (id serial PRIMARY KEY, marker text NOT NULL); INSERT INTO backup_restore_probe(marker) VALUES ('phase4-e2e');" \
  >/dev/null

DB_DATABASE="${TEST_DB}" DB_USERNAME="${DB_USER}" DB_PASSWORD="${DB_PASSWORD}" BACKUP_DIR="${BACKUP_DIR}" ./scripts/backup.sh >/dev/null

BACKUP_FILE="$(ls -1 "${BACKUP_DIR}"/backup_*.sql.gz | tail -n1)"

docker compose exec -T postgres dropdb -U "${DB_USER}" "${TEST_DB}"
docker compose exec -T postgres createdb -U "${DB_USER}" "${TEST_DB}"

DB_DATABASE="${TEST_DB}" DB_USERNAME="${DB_USER}" DB_PASSWORD="${DB_PASSWORD}" ./scripts/restore.sh "${BACKUP_FILE}" >/dev/null

ROW_COUNT="$(
  docker compose exec -T postgres psql -U "${DB_USER}" -d "${TEST_DB}" -tAc \
    "SELECT COUNT(*) FROM backup_restore_probe WHERE marker = 'phase4-e2e';"
)"

if [[ "${ROW_COUNT}" != "1" ]]; then
  echo "Backup/restore E2E failed: expected 1 row, got ${ROW_COUNT}" >&2
  exit 1
fi

echo "Backup/restore E2E passed"
