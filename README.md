# Koomky CRM

Self-hosted CRM for freelancers and small agencies, built as a monorepo with a Laravel API and a Next.js frontend.

## Highlights

- Client management: contacts, tags, activity timeline, archive/restore.
- Project delivery: tasks, Kanban/list views, dependencies, time tracking.
- Billing: invoices, quotes, credit notes, PDF generation, payment tracking.
- Campaigns: audience segments, email/SMS campaigns, analytics.
- Operations: health checks, structured logs, graceful fallbacks, CI/CD workflows.
- Data governance: full export (ZIP/JSON), CSV imports, account deletion scheduling.

## Tech Stack

- Backend: Laravel 12, Sanctum, Pest, PHPStan, Pint.
- Frontend: Next.js 15, React 19, Tailwind, Vitest.
- Data and infra: PostgreSQL 16, Redis 7, Meilisearch, Docker Compose.

## Repository Structure

- `backend/`: Laravel API and business logic.
- `frontend/`: Next.js dashboard app.
- `docs/`: phase specs and operational documentation.
- `docker/`: local and production Nginx/PHP/Node assets.
- `scripts/`: backup/restore and load testing helpers.

## Quick Start (Local)

### Prerequisites

- Docker + Docker Compose
- GNU Make

### 1. Start services

```bash
make up
```

### 2. Install dependencies

```bash
make install
```

### 3. Prepare backend app key and database

```bash
docker compose run --rm api php artisan key:generate
docker compose run --rm api php artisan migrate
```

### 4. Access apps

- Frontend: `http://localhost:6680`
- API health: `http://localhost:6680/api/v1/health`
- Meilisearch: `http://localhost:7700`
- Mailpit: `http://localhost:8025`

## Quality Commands

```bash
make lint
make test
```

Targeted suites:

```bash
make test-be
make test-fe
```

Pre-push formatting gate (Husky):

```bash
pnpm --dir frontend format:check
```

If formatting fails, run:

```bash
pnpm --dir frontend exec prettier --write <file>
```

## Production

- Deployment stack: `docker-compose.prod.yml`
- Reverse proxy: `docker/nginx/prod.conf`
- CI/CD workflow: `.github/workflows/deploy.yml`
- Security audit workflow: `.github/workflows/security-audit.yml`

See `docs/deployment.md` for the full production guide.

## Additional Docs

- API reference: `docs/api.md`
- Architecture: `docs/architecture.md`
- Import/export specs: `docs/import-export.md`
- Phase specification: `docs/phases/phase4.md`
