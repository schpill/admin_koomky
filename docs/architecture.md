# Architecture Overview

Koomky is a monorepo application composed of a Laravel API and a Next.js dashboard, running on PostgreSQL, Redis, and Meilisearch.

## 1. High-Level Topology

```text
Browser
  -> Nginx reverse proxy
    -> Next.js frontend (UI)
    -> Laravel API (/api/v1)
      -> PostgreSQL (system of record)
      -> Redis (cache, queue, session)
      -> Meilisearch (search index)
      -> Queue worker / scheduler
```

## 2. Repository Layout

- `frontend/`: Next.js app-router dashboard.
- `backend/`: Laravel API, jobs, commands, services.
- `docker/`: Nginx/PHP/Node container definitions.
- `docs/`: specs, phase docs, deployment and API documentation.
- `scripts/`: backup/restore and load test helpers.

## 3. Backend Architecture (Laravel)

### Layers

- Controllers: request orchestration and API envelope responses.
- Form Requests: validation and authorization boundaries.
- Services: domain operations (reports, import/export, account deletion, PDF generation).
- Jobs/Commands: async and scheduled workloads.
- Models/Policies: persistence rules and access control.

### Cross-Cutting Middleware

- `SecurityHeadersMiddleware`: CSP + security headers on responses.
- `RequestTelemetryMiddleware`: request ID, duration metrics, slow request warnings.
- `RequireTwoFactorAuthentication`: enforce 2FA checks on protected routes.

### Key Reliability Patterns

- Graceful fallback to DB search when Meilisearch is unavailable.
- Cache fallback logic in dashboard/report services when Redis fails.
- Health endpoint exposes status per dependency.
- Structured logging channel for machine-readable observability.

## 4. Frontend Architecture (Next.js)

### Main Patterns

- App Router + client/server component split.
- State stores (Zustand) for auth and feature modules.
- Reusable UI primitives for consistent interaction states.
- Dynamic imports for heavy components (charts, Kanban, drawers) to reduce initial JS.
- Accessibility improvements: skip links, live regions, keyboard shortcuts help.

### Performance-Oriented Choices

- Static auth layout (`dynamic = "force-static"`).
- Asset cache headers via `frontend/next.config.ts`.
- Virtualized rendering for large outstanding report tables (>100 rows).

## 5. Data and Domain Model

Primary business aggregates:

- CRM: `users`, `clients`, `contacts`, `tags`, `activities`.
- Delivery: `projects`, `tasks`, `task_dependencies`, `time_entries`, attachments.
- Billing: `invoices`, `quotes`, `credit_notes`, `line_items`, `payments`.
- Marketing: `segments`, `campaigns`, `campaign_templates`, recipients, campaign events.

## 6. Security Model

- Sanctum token auth for API requests.
- 2FA support and enforcement for protected actions.
- Encrypted casts for sensitive user fields (`bank_details`, 2FA secrets/recovery codes).
- Security headers at middleware and proxy levels.
- Rate limits for auth and webhook endpoints.

## 7. Deployment Architecture

Production stack in `docker-compose.prod.yml` includes:

- `nginx`, `api`, `frontend`, `postgres`, `redis`, `meilisearch`, `queue-worker`, `scheduler`.
- Health checks per service.
- Resource limits and restart policies.
- Registry-driven image updates via GitHub Actions deploy workflow.

## 8. Operational Flows

### Import/Export

- Full account export: streamed ZIP containing JSON snapshot.
- CSV imports for projects/invoices/contacts with row-level error reporting.

### Account Deletion

- Soft-delete user and associated data context.
- Schedule permanent purge timestamp (`deletion_scheduled_at`) with 30-day grace window.

### Queue Failure Monitoring

- Scheduled command `queue:monitor-failures` checks failed jobs over rolling 1-hour window.
- Warning emitted when threshold is exceeded.

