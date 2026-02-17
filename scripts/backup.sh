#!/usr/bin/env bash
set -euo pipefail

BACKUP_DIR="${BACKUP_DIR:-./backups}"
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-5432}"
DB_NAME="${DB_DATABASE:-koomky}"
DB_USER="${DB_USERNAME:-koomky}"
DB_PASSWORD="${DB_PASSWORD:-}"
S3_BUCKET="${S3_BUCKET:-${AWS_BUCKET:-}}"
S3_PREFIX="${S3_PREFIX:-database}"
KEEP_DAILY_DAYS="${KEEP_DAILY_DAYS:-30}"
KEEP_WEEKLY_WEEKS="${KEEP_WEEKLY_WEEKS:-12}"

mkdir -p "${BACKUP_DIR}"
BACKUP_FILE="${BACKUP_DIR}/backup_${TIMESTAMP}.sql.gz"

run_pg_dump() {
  if command -v pg_dump >/dev/null 2>&1; then
    PGPASSWORD="${DB_PASSWORD}" pg_dump \
      --host="${DB_HOST}" \
      --port="${DB_PORT}" \
      --username="${DB_USER}" \
      --dbname="${DB_NAME}" \
      --format=plain \
      --no-owner \
      --no-privileges

    return
  fi

  if command -v docker >/dev/null 2>&1 && docker compose ps postgres >/dev/null 2>&1; then
    docker compose exec -T postgres env PGPASSWORD="${DB_PASSWORD}" pg_dump \
      --host=127.0.0.1 \
      --port=5432 \
      --username="${DB_USER}" \
      --dbname="${DB_NAME}" \
      --format=plain \
      --no-owner \
      --no-privileges

    return
  fi

  echo "Unable to run pg_dump. Install postgres client tools or start docker compose postgres service." >&2
  exit 1
}

run_pg_dump | gzip > "${BACKUP_FILE}"

echo "Backup created: ${BACKUP_FILE}"

if [[ -n "${S3_BUCKET}" ]] && command -v aws >/dev/null 2>&1; then
  aws s3 cp "${BACKUP_FILE}" "s3://${S3_BUCKET}/${S3_PREFIX}/$(basename "${BACKUP_FILE}")"
  echo "Backup uploaded to s3://${S3_BUCKET}/${S3_PREFIX}/"
fi

find "${BACKUP_DIR}" -type f -name 'backup_*.sql.gz' -mtime +"${KEEP_DAILY_DAYS}" -delete

if [[ -n "${S3_BUCKET}" ]] && command -v aws >/dev/null 2>&1; then
  aws s3 ls "s3://${S3_BUCKET}/${S3_PREFIX}/" | while read -r line; do
    file_date=$(echo "$line" | awk '{print $1}')
    file_name=$(echo "$line" | awk '{print $4}')
    if [[ -z "${file_name}" ]]; then
      continue
    fi

    file_epoch=$(date -d "${file_date}" +%s)
    cutoff_epoch=$(date -d "-${KEEP_WEEKLY_WEEKS} weeks" +%s)

    if [[ "${file_epoch}" -lt "${cutoff_epoch}" ]]; then
      aws s3 rm "s3://${S3_BUCKET}/${S3_PREFIX}/${file_name}"
    fi
  done
fi

echo "Rotation complete"
echo "Suggested cron: 0 2 * * * /path/to/scripts/backup.sh >> /var/log/koomky-backup.log 2>&1"
