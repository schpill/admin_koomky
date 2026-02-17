# Deployment Guide

This document describes how to deploy Koomky in production using Docker Compose.

## 1. Prerequisites

- Linux server with Docker Engine + Docker Compose v2.
- Public DNS records pointing to the server.
- TLS certificates provisioned for your domain.
- Access to GHCR images (or your own registry).
- PostgreSQL persistent disk and regular backup target (S3-compatible recommended).

## 2. Required Files

- `docker-compose.prod.yml`
- `docker/nginx/prod.conf`
- `backend/.env` (production values)
- `.env.prod` (image tags + domain-level variables)

## 3. Environment Variables

### Backend (`backend/.env`)

At minimum, configure:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY=...`
- `DB_*`
- `REDIS_*`
- `MEILISEARCH_*`
- `FILESYSTEM_DISK=s3`
- `AWS_ACCESS_KEY_ID`
- `AWS_SECRET_ACCESS_KEY`
- `AWS_BUCKET`
- `AWS_DEFAULT_REGION`
- `AWS_ENDPOINT` (if MinIO/Spaces)
- `SLOW_REQUEST_THRESHOLD_MS`
- `FAILED_JOBS_ALERT_THRESHOLD`
- `DEBUGBAR_ENABLED` (dev only)
- `DB_HOST=pgbouncer` and `DB_PORT=6432` when PgBouncer is enabled

### Compose env (`.env.prod`)

Recommended keys:

- `API_IMAGE=ghcr.io/<owner>/koomky-api:<sha-or-tag>`
- `FRONTEND_IMAGE=ghcr.io/<owner>/koomky-frontend:<sha-or-tag>`
- `POSTGRES_PASSWORD`
- `REDIS_PASSWORD`
- `MEILISEARCH_KEY`
- `NEXT_PUBLIC_API_URL=https://<domain>/api/v1`

## 4. TLS and Nginx

`docker/nginx/prod.conf` expects certificate paths under:

- `/etc/letsencrypt/live/example.com/fullchain.pem`
- `/etc/letsencrypt/live/example.com/privkey.pem`

Update `server_name` and certificate path for your domain before first deploy.

## 5. First Deployment

```bash
# from repository root
cp backend/.env.example backend/.env
# edit backend/.env with production values

cp .env.prod.example .env.prod  # if you maintain one, otherwise create manually
# edit .env.prod

docker compose --env-file .env.prod -f docker-compose.prod.yml pull
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d --remove-orphans
docker compose --env-file .env.prod -f docker-compose.prod.yml exec -T api php artisan migrate --force
```

Health check:

```bash
curl -fsS https://<domain>/api/v1/health
```

## 6. CI/CD Workflow

`.github/workflows/deploy.yml` performs:

1. Build API and frontend Docker images.
2. Push images to GHCR tagged with commit SHA and `latest`.
3. SSH deploy on production host.
4. Run migrations.
5. Check `/api/v1/health`.
6. Attempt rollback if health check fails.
7. Use rolling-style update (`api`/`frontend` scaled to 2, then back to 1).

### Required GitHub Secrets

- `PROD_HOST`
- `PROD_USER`
- `PROD_SSH_KEY`
- `PROD_APP_PATH`
- `PROD_DOMAIN`
- `GHCR_TOKEN`

## 7. Backups and Restore

### Backup

```bash
./scripts/backup.sh
```

What it does:

- creates gzipped `pg_dump` backup,
- optionally uploads to S3-compatible storage,
- rotates local and remote backup files.

### Restore

```bash
./scripts/restore.sh /path/to/backup_YYYYMMDD_HHMMSS.sql.gz
```

### Backup/Restore E2E validation

```bash
./scripts/test-backup-restore.sh
```

## 8. Monitoring and Logging

- API health endpoint: `/api/v1/health`
- Request telemetry middleware adds `X-Request-Id` and logs slow requests.
- Structured logging is configured through `backend/config/logging.php` (`structured` channel).
- Failed jobs threshold monitoring command:

```bash
docker compose --env-file .env.prod -f docker-compose.prod.yml exec -T api php artisan queue:monitor-failures
```

- Scheduled uptime monitor workflow: `.github/workflows/uptime-monitor.yml`
- Manual uptime probe script: `./scripts/uptime-check.sh`
- Scheduled load test workflow: `.github/workflows/load-test.yml`

## 9. Security Checklist

- Confirm security headers on responses (CSP, X-Frame-Options, nosniff, Referrer-Policy).
- Keep `composer audit` and `pnpm audit` clean.
- Use strong secrets for Redis and Meilisearch.
- Ensure `.env` files are never committed.
- Restrict SSH and registry credentials.

## 10. Zero-Downtime Notes

Recommended approach for minimal downtime:

1. Pull new images.
2. Start updated containers with `up -d --remove-orphans --scale api=2 --scale frontend=2`.
3. Run migrations with backward-compatible schema changes.
4. Verify `/api/v1/health`.
5. Scale back to steady-state (`api=1`, `frontend=1`) once healthy.

For stricter zero-downtime, use blue/green or rolling orchestration (Swarm/Kubernetes).
