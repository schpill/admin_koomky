# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Koomky** is a Freelance CRM system designed for independent developers to manage clients, projects, invoices, quotes, and marketing campaigns.
**Current Status**: Phase 1 Foundation Complete - Authentication, Client Management, Dashboard, and Infrastructure implemented (142 files, 11,485+ LOC).

## Repository Structure

```
/home/gerald/admin_koomky/
├── PRD.md              # Complete Product Requirements Document
├── backend/            # Laravel 12.x API
│   ├── app/
│   │   ├── Http/Controllers/    # API endpoints
│   │   ├── Http/Requests/       # Form validation
│   │   ├── Http/Resources/      # API resources
│   │   ├── Models/              # Eloquent models
│   │   ├── Services/            # Business logic
│   │   └── Exceptions/Handler.php
│   ├── database/migrations/     # DB schema
│   ├── tests/                   # Pest tests
│   └── composer.json
├── frontend/           # Nuxt 3.x SPA
│   ├── composables/     # Vue composables (useApi, useAuth, useToast)
│   ├── components/      # Vue components
│   ├── layouts/         # Nuxt layouts
│   ├── middleware/      # Route middleware (auth, guest)
│   ├── pages/           # File-based routing
│   ├── tests/           # Vitest + Playwright tests
│   └── package.json
├── docker/             # Docker build files
├── docker-compose.yml  # 9 services orchestration
├── docs/
│   └── phases/
│       ├── phase1.md    # ✓ COMPLETED - Foundation and Core CRM
│       ├── phase2.md    # NEXT - Project and Financial Management
│       ├── phase3.md    # Marketing and Communication Campaigns
│       └── phase4.md    # Polish, Optimization, Production Release
├── .github/workflows/  # CI/CD (tests.yml, ci.yml)
└── CLAUDE.md          # This file
```

## Tech Stack

### Backend
- **Framework**: Laravel 12.x
- **PHP Version**: 8.4+
- **Database**: PostgreSQL 16.x with `uuid-ossp` and `pg_trgm` extensions
- **Cache/Queue/Session**: Redis 7.x
- **Search**: Meilisearch 1.x (via Laravel Scout)
- **Authentication**: Laravel Sanctum with JWT tokens
- **PDF Generation**: DomPDF or Browsershot
- **Testing**: PHPUnit + Pest
- **Static Analysis**: PHPStan level 8
- **Code Style**: Laravel Pint (PSR-12)

### Frontend
- **Framework**: Nuxt 3.x with Vue 3 Composition API
- **Build Tool**: Vite
- **Styling**: Tailwind CSS 3.x
- **State Management**: Pinia
- **HTTP Client**: ofetch (Nuxt built-in)
- **Form Validation**: VeeValidate + Zod
- **UI Components**: Headless UI (Vue) + custom components
- **Testing**: Vitest + Vue Test Utils (unit), Playwright (E2E)
- **Linting**: ESLint + Prettier

### Infrastructure
- **Containerization**: Docker Compose with 9 services
- **Reverse Proxy**: Nginx (TLS termination, routing)
- **Services**: nginx, api (PHP-FPM), frontend (Node), postgres, redis, meilisearch, queue-worker, scheduler, mailpit

## Architecture Overview

```
Browser → Nginx → Nuxt.js (SSR/SPA)
                → Laravel API → PostgreSQL
                             → Redis
                             → Meilisearch
```

The application follows a **decoupled architecture** with:
- RESTful JSON API (Laravel) at `/api/v1/*`
- SPA/SSR Frontend (Nuxt.js) for all other routes
- All services orchestrated via Docker Compose

## Key Design Patterns

### API Response Format
All API responses follow a JSON:API-inspired structure:
- **Success**: `{ data: {...}, meta: {...}, links: {...} }`
- **Collection**: `{ data: [...], meta: { current_page, last_page, total }, links: {...} }`
- **Error**: `{ error: { status, message, errors: {...} }`

### Reference Numbering Pattern
Auto-generated references follow pattern: `{PREFIX}-{YEAR}-{SEQUENTIAL_4_DIGITS}`
- Clients: `CLI-2024-0001`
- Projects: `PRJ-2024-0001`
- Invoices: `FAC-2024-0001`
- Quotes: `DEV-2024-0001`
- Credit Notes: `AVO-2024-0001`

### Database Conventions
- **Primary Keys**: UUID (not auto-increment integers)
- **Soft Deletes**: `archived_at` (not `deleted_at`) for entities like Clients
- **Timestamps**: `created_at`, `updated_at` on all tables
- **JSONB Columns**: Used for filters (segments), settings, metadata

### Implemented Features (Phase 1)

**Authentication System** (`backend/app/Http/Controllers/AuthController.php`)
- JWT-based authentication (access: 15min, refresh: 30 days)
- Two-factor authentication (TOTP + recovery codes)
- Password reset via email
- Audit logging for all auth events

**Client Management** (`backend/app/Http/Controllers/ClientController.php`)
- Full CRUD operations
- Meilisearch integration for full-text search
- Tag system for categorization
- Activity timeline
- CSV import/export
- Soft delete with `archived_at`

**Dashboard** (`frontend/pages/index.vue`)
- KPI widgets (total clients, active projects, revenue)
- Recent activity feed
- Command palette (Ctrl+K)

**UI Components** (`frontend/components/`)
- AppButton, AppInput, AppModal, AppDrawer
- AppCard, AppBadge, AppSelect, AppTextarea
- AppDataTable, AppPagination, AppEmptyState
- CommandPalette, AppToast

### Status Workflows (Planned - Phase 2)
Many entities will have enforced status transitions:
- **Invoice**: draft → sent → (viewed|paid|partially_paid|overdue) → cancelled
- **Project**: draft → proposal_sent → in_progress → (on_hold|completed|cancelled)
- **Quote**: draft → sent → (accepted|rejected|expired)

## Development Commands

### Docker
```bash
make up          # Start all services
make down        # Stop all services
make restart     # Restart services
make logs        # View logs
```

### Backend (Laravel) - Run from `backend/` directory
```bash
cd backend
composer install                    # Install dependencies
php artisan migrate                 # Run migrations
php artisan migrate:fresh --seed    # Reset DB with seeders
php artisan serve                   # Start dev server (port 8000)
vendor/bin/pint                     # Fix code style (PSR-12)
vendor/bin/phpstan analyse          # Static analysis (level 8)
vendor/bin/pest                     # Run all tests
vendor/bin/pest --filter test_name  # Run single test
vendor/bin/pest --coverage          # Run with coverage (80%+ required)
```

### Frontend (Nuxt) - Run from `frontend/` directory
```bash
cd frontend
pnpm install           # Install dependencies
pnpm run dev           # Start dev server (port 3000)
pnpm run build         # Production build
pnpm lint              # ESLint check
pnpm lint:fix          # ESLint auto-fix
pnpm format            # Prettier fix
pnpm typecheck         # TypeScript check
pnpm test              # Unit tests (vitest)
pnpm test:coverage     # Unit tests with coverage
pnpm test:e2e          # E2E tests (playwright)
```

## Testing Requirements

- **Coverage Target**: ≥ 80% for both backend and frontend
- **Test Framework**: Pest (backend), Vitest (frontend)
- **E2E Framework**: Playwright
- **CI Gate**: All tests must pass before merging to `main`

## Important Documentation References

| Document | Location | Purpose |
|-----------|------------|----------|
| PRD | `PRD.md` | Complete requirements, data models, API specs, NFRs |
| Phase 1 | `docs/phases/phase1.md` | Foundation: Auth, Clients, Dashboard, Search |
| Phase 2 | `docs/phases/phase2.md` | Projects, Tasks, Invoices, Quotes, Credit Notes, Reports |
| Phase 3 | `docs/phases/phase3.md` | Segments, Email/SMS Campaigns, Analytics |
| Phase 4 | `docs/phases/phase4.md` | Polish, Performance, Security, Deployment |

## Key Integration Points

| Service | Purpose | Configuration |
|----------|----------|---------------|
| **Mailgun/SES** | Email sending | Settings → Email |
| **Twilio** | SMS sending | Settings → SMS |
| **Meilisearch** | Global search | Docker service, Scout driver |
| **S3-compatible** | File storage | env: `FILESYSTEM_DISK=s3` |
| **Mailpit** | Dev email testing | http://localhost:8025 |

## Security Considerations

- **Authentication**: JWT access tokens (15min TTL) + refresh token rotation
- **Rate Limiting**: 10/min auth, 100/min API (configurable)
- **2FA**: TOTP-based (optional)
- **Data Encryption**: `two_factor_secret`, `bank_details` encrypted at rest
- **GDPR**: Data export (JSON) and account deletion endpoints
- **Consent**: Email/SMS consent tracking on contacts

## CI/CD Workflow Notes

**Critical Configuration Details** (learned from debugging):
- Use `shivammathur/setup-php@v2` (NOT `shivammalhotra`)
- Backend jobs must set `defaults: run: working-directory: ./backend`
- Frontend jobs must set `defaults: run: working-directory: ./frontend`
- pnpm cache requires uppercase `STORE_PATH` output variable
- Use `--no-color` flag for `pnpm store path` to avoid ANSI codes
- PHP 8.4 required for Laravel 12.x
- PCOV coverage driver is used (`coverage: pcov` in workflow)
- Pest coverage command: `vendor/bin/pest --coverage --coverage-clover=coverage/clover.xml --min=80`

**Active Workflows**:
- `.github/workflows/tests.yml` - Main testing workflow (PRs to main)
- `.github/workflows/ci.yml` - CI for all branches

## Code Style Guidelines

### Backend (PHP)
- PSR-12 code style via Laravel Pint
- PHPStan level 8 (strict type checking)
- All routes prefixed with `/api/v1/`
- Form Request classes for validation
- Policy classes for authorization
- Resource classes for API responses
- Observer classes for model events (logging)

### Frontend (Vue/TypeScript)
- Composition API with `<script setup lang="ts">`
- Tailwind CSS for styling (no inline styles)
- Pinia stores for state management
- Zod schemas for validation
- Auto-imports: components, composables, utilities
- Type-safe API calls via `useApi` composable

**Key Composables**:
- `useApi()` - HTTP client with 401 auto-refresh interceptor
- `useAuth()` - Pinia store for authentication state
- `useToast()` - Toast notification system

**Key Services** (Backend):
- `AuthService` - Login, logout, token management
- `JWTService` - JWT token generation and validation
- `TwoFactorAuthService` - 2FA setup, verification, recovery codes
- `HealthCheckService` - System health status
- `ExportService` - CSV/Excel export
- `ImportService` - CSV import with validation

## Known Issues / Current Work

**Frontend Linting Issues** (need fixing):
- TypeScript errors in `layouts/auth.vue` and `layouts/default.vue` (line 19, 94: ',' expected)
- Unused variables in components (`props`, `computed`, `config`)
- `@typescript-eslint/no-explicit-any` violations in several files
- Unused imports (`ofetch`, `Ref`) in composables

Run `pnpm lint:fix` to auto-fix what's possible, then manually address remaining issues.
