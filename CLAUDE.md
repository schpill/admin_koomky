# Koomky — Project Guidelines

## Project Overview

Koomky is a self-hosted Freelance CRM built as a monorepo:
- **Backend**: Laravel 12 (PHP 8.3+) — `backend/`
- **Frontend**: Next.js 15 / React 19 / shadcn/ui — `frontend/`
- **Database**: PostgreSQL 16
- **Cache/Queue**: Redis 7
- **Search**: Meilisearch
- **Infra**: Docker Compose (9 services)

## Current Implementation Snapshot

- **Phase 2 Sprints 5 and 6 are implemented and merged** (projects/tasks/time tracking + invoices/payments/settings).
- **Public signup is disabled**:
  - Backend route `POST /api/v1/auth/register` is removed.
  - Frontend `/auth/register` page and middleware exposure are removed.
- **User provisioning is now admin-only via CLI command**:
  - `php artisan users:create`
  - Asks for email (if not provided as argument), creates user, prints generated password in clear text.
  - Password policy enforced by generator: at least 8 chars, with lowercase, uppercase, number, and special char.
- **CI gates are green on current merged work** with backend and frontend checks.

## Task Tracking

Task tracking files live in `docs/dev/phase{1,2,3,4}.md`. These are the **source of truth** for task progress across all contributors (humans and AI agents).

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

- `PRD.md` — Full product requirements (v1.1.0)
- `docs/phases/phase{1,2,3,4}.md` — Detailed specs per phase
- `docs/dev/phase{1,2,3,4}.md` — Task tracking per phase
