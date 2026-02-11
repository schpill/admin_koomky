# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Koomky** is a Freelance CRM system designed for independent developers to manage clients, projects, invoices, quotes, and marketing campaigns.

**Current Status**: Documentation/Planning Phase - This repository contains PRD and phase breakdown documents. No implementation code exists yet.

## Repository Structure

```
/home/gerald/admin_koomky/
├── PRD.md              # Complete Product Requirements Document
├── docs/
│   └── phases/
│       ├── phase1.md    # Foundation and Core CRM (Weeks 1-6)
│       ├── phase2.md    # Project and Financial Management (Weeks 7-14)
│       ├── phase3.md    # Marketing and Communication Campaigns (Weeks 15-20)
│       └── phase4.md    # Polish, Optimization, Production Release (Weeks 21-24)
└── CLAUDE.md          # This file
```

## Tech Stack (Planned)

### Backend
- **Framework**: Laravel 12.x
- **PHP Version**: 8.3+
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

### Status Workflows
Many entities have enforced status transitions:
- **Invoice**: draft → sent → (viewed|paid|partially_paid|overdue) → cancelled
- **Project**: draft → proposal_sent → in_progress → (on_hold|completed|cancelled)
- **Quote**: draft → sent → (accepted|rejected|expired)

## Common Development Commands (When Code Exists)

### Docker
```bash
make up          # Start all services
make down        # Stop all services
make fresh       # Rebuild from scratch
make logs        # View logs
make shell       # SSH into API container
make shell-fe    # SSH into frontend container
```

### Backend (Laravel)
```bash
make test        # Run all tests (Pest)
make test-coverage # Run tests with coverage report
make lint        # Run Laravel Pint + PHPStan
make migrate     # Run database migrations
make seed        # Seed database with test data
make tinker      # Open Laravel Tinker REPL
```

### Frontend (Nuxt)
```bash
cd frontend
pnpm install        # Install dependencies
pnpm dev           # Development server
pnpm build         # Production build
pnpm test           # Run Vitest unit tests
pnpm test:e2e       # Run Playwright E2E tests
pnpm lint          # Run ESLint + Prettier
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
