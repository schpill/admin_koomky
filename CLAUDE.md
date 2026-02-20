# Koomky — Project Guidelines

## Project Overview

Koomky is a self-hosted Freelance CRM built as a monorepo:
- **Backend**: Laravel 12 (PHP 8.3+) — `backend/`
- **Frontend**: Next.js 15 / React 19 / shadcn/ui — `frontend/`
- **Database**: PostgreSQL 16
- **Cache/Queue**: Redis 7
- **Search**: Meilisearch
- **Infra**: Docker Compose (core + monitoring services)

## Current Implementation Snapshot

- **Phase 7 closed & released as `v1.3.0`** — PR #18 delivered the features; PR #19 (`fix/phase7-code-review`, merged 2026-02-20) applied the full code-review hardening (SSRF fix, LIKE escape, DI, LeadPolicy, webhook retry, service deduplication, 62 new frontend tests). Tag `v1.3.0` pushed to GitHub.
- **Delivered artifacts**:
  - Backend: webhook models/controllers, `WebhookDispatchService`/`WebhookDispatchJob`, PAT scope-guard middleware, `StoreLeadRequest`, webhook delivery logging + OpenAPI UI (`dedoc/scramble` @ `/api/docs`), lead-related observers, and comprehensive unit tests (including WebhookDispatchService/Job and LeadConversion).
  - Frontend: API tokens/webhook forms, lead Kanban/activities/convert dialog + zustand store, pipeline analytics page, dashboard pipeline widget, and their corresponding Playwright/Vitest specs.
  - Infrastructure: Meilisearch lead index, GDPR export inclusion, and coverage above the 80% gates for both backend and frontend.
- **Phase 6 is fully implemented and merged to `main`** (Client Portal & Expense Tracking roadmap scope).
- **Phase 6 scope delivered**:
  - Client portal (magic link auth, dashboard, invoice/quote viewing, quote accept/reject flows)
  - Online Stripe payments from portal (payment intents, webhook sync, notifications)
  - Expense tracking (categories, CRUD, receipt upload/download, reporting, CSV export, import/export integration)
  - Financial integration (profit/loss, project profitability, dashboard widgets, billable expense invoicing)
- **Post-merge hardening from PR #15 (`fix/phase6-code-review`) is now on `main`**:
  - Stripe webhook and payment service typing/null-safety fixes (PHPStan-compatible)
  - Deterministic backend tests for portal payments and payment notifications
  - Stripe service test fixtures aligned with `create`/`update` payment intent code paths
- **Phase 5 is implemented, merged to `main`, and released as `v1.1.0`** (tag + GitHub release).
- **Phase 5 scope delivered**:
  - Recurring invoices (profiles, generator jobs, scheduling, notifications, UI, tests)
  - Multi-currency support (currencies/rates services, conversion in documents/reports/dashboard, UI, tests)
  - Calendar integration (connections/events, sync drivers/jobs, auto-events, UI, tests)
  - Prometheus + Grafana monitoring stack (metrics endpoint/middleware/service, exporters, dashboards, docs)
- **Coverage gate policy**: backend and frontend thresholds remain **>= 80%**.
- **Phase 5 validation automation is available** via:
  - `scripts/validate-phase5.sh` (backend coverage, frontend coverage, CI status check, tag check)
- **No dedicated Phase 6 or Phase 7 validation scripts exist yet**:
  - Use phase-specific suites documented in `docs/dev/phase6.md` and `docs/dev/phase7.md`.
- **Public signup is disabled**:
  - Backend route `POST /api/v1/auth/register` is removed.
  - Frontend `/auth/register` page and middleware exposure are removed.
- **Email campaigns support Amazon SES in API mode**:
  - Per-user runtime credentials are supported via settings (`api_key`, `api_secret`, `api_region`).
  - Fallback to global `services.ses` config remains available if per-user credentials are absent.
- **User provisioning is now admin-only via CLI command**:
  - `php artisan users:create`
  - Asks for email (if not provided as argument), creates user, prints generated password in clear text.
  - Password policy enforced by generator: at least 8 chars, with lowercase, uppercase, number, and special char.
- **CI gates are green on current merged work** with backend and frontend checks.
- **Local pre-push checks are enforced via Husky**:
  - Frontend: `pnpm --dir frontend format:check` (Prettier)
  - Backend: `./vendor/bin/pint --test` and `./vendor/bin/phpstan analyse --memory-limit=1G` (executed in the `api` container)

## Task Tracking

Task tracking files live in `docs/dev/phase{1,2,3,4,5,6}.md`. These are the **source of truth** for task progress across all contributors (humans and AI agents).

### Status values

| Status   | Meaning |
|----------|---------|
| `todo`   | Not started |
| `wip`    | Work in progress |
| `done`   | Code complete locally |
| `pr`     | Pull request open |
| `merged` | Merged to main |

### Rules

1. **Before starting a task**: set its status to `wip` and add your name in the Owner column.
2. **After completing a task**: set its status to `done`.
3. **Never skip a sprint**: complete Sprint N before starting Sprint N+1.
4. **Phase gating**: Phase N must be fully `merged` before starting Phase N+1.
5. **Keep it concise**: only update status and owner, do not modify task descriptions.

### Format

Each task line follows this pattern:
```
| ID | Task description | Status | Owner |
```

## Development Workflow

- **Branch naming**: `feat/{short-description}` or `fix/{short-description}`
- **Commits**: Conventional Commits (`feat:`, `fix:`, `test:`, `chore:`, `refactor:`, `docs:`)
- **TDD**: Write tests first, then implementation (Red-Green-Refactor)
- **Coverage gate**: >= 80% on both backend and frontend
- **CI must pass** before any merge to `main`

## Auth and User Provisioning

- Koomky is a private CRM instance. Do not re-enable self-registration without explicit product decision.
- Create accounts with:
  ```bash
  cd backend
  php artisan users:create
  # or
  php artisan users:create owner@example.com
  ```
- The command stores a hashed password in DB and prints the generated plain password once in console output.

## Key Commands

```bash
make up          # Start all Docker services
make down        # Stop services
make test        # Run all tests (backend + frontend)
make test-be     # Run backend tests only
make test-fe     # Run frontend tests only
make lint        # Run all linters (Pint, PHPStan, ESLint)
cd frontend && pnpm prettier --write .   # Auto-fix frontend formatting
make fresh       # Reset database
make seed        # Seed database
cd backend && php artisan users:create   # Create a private CRM user account
```

## Architecture Decisions

- **API format**: JSON:API-inspired with `data`, `meta`, `links` structure
- **Auth**: Laravel Sanctum (JWT-style tokens)
- **State management**: Zustand (frontend)
- **Forms**: react-hook-form + Zod
- **UI components**: shadcn/ui (Radix UI + Tailwind CSS)
- **Testing backend**: Pest
- **Testing frontend**: Vitest + React Testing Library
- **E2E**: Playwright
- **Static analysis**: PHPStan level 8 (Larastan)
- **Code style**: Laravel Pint (backend), ESLint (frontend)

## CI Notes

- Backend CI runs against PostgreSQL database name `koomky` in GitHub Actions.
- Frontend CI enforces:
  - `pnpm lint`
  - `pnpm format:check`
  - `pnpm vitest run --coverage`
- Global coverage thresholds (Vitest): lines/functions/branches/statements >= 80%.

## Reference Documents

- `PRD.md` — Full product requirements (v1.1.0 baseline + v1.2/v1.3 roadmap)
- `docs/phases/phase{1,2,3,4,5,6,7}.md` — Detailed specs per phase
- `docs/dev/phase{1,2,3,4,5,6,7}.md` — Task tracking per phase
- `scripts/validate-phase5.sh` — Automated local validation for Phase 5 gates
