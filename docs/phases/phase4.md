# Phase 4 — Polish, Optimization, and Production Release

| Field               | Value                                          |
|---------------------|------------------------------------------------|
| **Phase**           | 4 of 4                                         |
| **Name**            | Polish, Optimization, and Production Release   |
| **Duration**        | Weeks 21–24 (4 weeks)                          |
| **Milestone**       | M4 — Production-Ready Release v1.0.0           |
| **PRD Sections**    | §5, §7, §9.4, §10, §11                         |
| **Prerequisite**    | Phase 3 fully completed and validated           |
| **Status**          | Not Started                                    |

---

## 1. Phase Objectives

| ID       | Objective                                                                                    |
|----------|----------------------------------------------------------------------------------------------|
| P4-OBJ-1 | Polish the UI/UX: dark mode, responsive refinement, micro-interactions, empty states         |
| P4-OBJ-2 | Conduct and remediate WCAG 2.1 AA accessibility audit                                       |
| P4-OBJ-3 | Optimize back-end performance: query tuning, N+1 elimination, caching strategy               |
| P4-OBJ-4 | Optimize front-end performance: bundle size, lazy loading, image optimization                |
| P4-OBJ-5 | Implement data import/export for all entities (CSV/JSON)                                     |
| P4-OBJ-6 | Set up production deployment with Docker Compose, TLS, backups                               |
| P4-OBJ-7 | Create comprehensive documentation (README, deployment guide, API docs)                      |
| P4-OBJ-8 | Run full security audit and remediate findings                                               |
| P4-OBJ-9 | Maintain >= 80% test coverage, all E2E tests passing                                        |

---

## 2. Entry Criteria

- Phase 3 exit criteria 100% satisfied.
- All Phase 3 CI checks green on `main`.
- All functional features (CRM, projects, finances, campaigns) operational.
- Email and SMS integrations tested with production providers.

---

## 3. Scope — Requirement Traceability

| PRD Requirement            | IDs / Sections                         | Included |
|----------------------------|----------------------------------------|----------|
| Performance NFRs           | NFR-PERF-001 → 008                     | Yes      |
| Security NFRs              | NFR-SEC-001 → 012                      | Yes      |
| Scalability NFRs           | NFR-SCA-001 → 005                      | Yes      |
| Reliability NFRs           | NFR-REL-001 → 007                      | Yes      |
| Maintainability NFRs       | NFR-MNT-001 → 007                      | Yes      |
| Usability NFRs             | NFR-USA-001 → 008                      | Audit    |
| Accessibility NFRs         | NFR-ACC-001 → 007                      | Yes      |
| Data Import/Export          | FR-SET-007, FR-SET-008                 | Yes      |
| File Storage (S3)          | §9.4                                   | Yes      |
| CI/CD Deployment           | §11.3.3                                | Yes      |
| Monitoring & Logging       | §11.5                                  | Yes      |

---

## 4. Detailed Sprint Breakdown

### 4.1 Sprint 13 — UI/UX Polish & Accessibility (Weeks 21–22)

#### 4.1.1 Objectives
- Complete dark mode implementation across all pages and components.
- Responsive design audit and fixes for all breakpoints (375px–2560px).
- WCAG 2.1 AA accessibility audit and remediation.
- UI micro-interactions and animation refinement.
- Comprehensive empty state designs for all modules.

#### 4.1.2 Front-end Tasks — Dark Mode

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P4-FE-001 | Audit all pages/components for dark mode class coverage                                | §7.1          |
| P4-FE-002 | Implement dark mode color system in `tailwind.config.ts`:                               | §7.2          |
|           | — Background: `slate-50` (light) / `slate-900` (dark)                                  |              |
|           | — Cards: `white` / `slate-800`                                                        |              |
|           | — Text: `slate-900` / `slate-100`                                                     |              |
|           | — Borders: `slate-200` / `slate-700`                                                  |              |
|           | — Inputs: `white` / `slate-800` with `slate-300` / `slate-600` borders                |              |
| P4-FE-003 | Add `dark:` variants to all 15+ base components (`AppButton`, `AppInput`, `AppSelect`, `AppTextarea`, `AppBadge`, `AppModal`, `AppDrawer`, `AppToast`, `AppEmptyState`, `AppPagination`, `AppDataTable`, etc.) | §7.1 |
| P4-FE-004 | Dark mode for charts (Chart.js): grid lines, labels, tooltips in dark-compatible colors | §7.1 |
| P4-FE-005 | Dark mode for TipTap editor: toolbar, content area, dropdown menus                     | §7.1          |
| P4-FE-006 | Dark mode for PDF preview iframe (darken surrounding chrome, keep PDF white)            | §7.1          |
| P4-FE-007 | Implement system preference detection (`prefers-color-scheme: dark`) with manual override toggle | §7.1 |
| P4-FE-008 | Persist theme preference in `localStorage` and `useSettings` Pinia store               | §7.1          |

#### 4.1.3 Front-end Tasks — Responsive Design

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P4-FE-010 | Audit all pages at breakpoints: 375px (iPhone SE), 390px (iPhone 14), 768px (iPad), 1024px (iPad Pro), 1280px, 1536px, 2560px (4K) | NFR-USA-001 |
| P4-FE-011 | Sidebar: collapsible to icon-only on tablets, hidden behind hamburger on mobile        | NFR-USA-001   |
| P4-FE-012 | Data tables: horizontal scroll on mobile, prioritize key columns, hide secondary columns below `md:` | NFR-USA-001 |
| P4-FE-013 | Dashboard: 1-column (mobile), 2-column (tablet), 3-column (desktop) grid              | NFR-USA-001   |
| P4-FE-014 | Forms: full-width on mobile, 2-column on desktop where appropriate                     | NFR-USA-001   |
| P4-FE-015 | Modals and drawers: full-screen on mobile, standard on desktop                         | NFR-USA-001   |
| P4-FE-016 | Kanban board: horizontal scroll on mobile, full-width columns on small screens         | NFR-USA-001   |
| P4-FE-017 | Campaign wizard: vertical stepper on mobile instead of horizontal                      | NFR-USA-001   |

#### 4.1.4 Front-end Tasks — Accessibility

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P4-FE-020 | Run automated accessibility audit using `axe-core` on all pages                        | NFR-ACC-001   |
| P4-FE-021 | Ensure all interactive elements are keyboard-accessible (Tab, Enter, Space, Escape)    | NFR-ACC-002   |
| P4-FE-022 | Add `aria-label` or `alt` text to all icons, images, and non-text elements             | NFR-ACC-003   |
| P4-FE-023 | Verify color contrast ratios meet AA minimums (4.5:1 normal, 3:1 large) — fix violations | NFR-ACC-004 |
| P4-FE-024 | Associate all form labels with inputs via `for`/`id`; link errors via `aria-describedby` | NFR-ACC-005 |
| P4-FE-025 | Implement `aria-live` regions for: toast notifications, search results, form validation errors, campaign sending progress | NFR-ACC-006 |
| P4-FE-026 | Implement focus management: trap focus in modals/drawers, return focus on close, skip-to-content link | NFR-ACC-007 |
| P4-FE-027 | Test with screen reader (VoiceOver or NVDA) on key flows: login, create client, create invoice, send campaign | NFR-ACC-001 |

#### 4.1.5 Front-end Tasks — UI Polish

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P4-FE-030 | Add skeleton loaders for all data-fetching states (replace generic spinners)           | NFR-USA-006   |
| P4-FE-031 | Add subtle CSS transitions on: button hover, card hover, sidebar expand/collapse, modal open/close, drawer slide, toast appear/dismiss | §7.5 |
| P4-FE-032 | Design and implement unique empty states for all list pages:                            | NFR-USA-008   |
|           | — Clients: illustration + "Add your first client" CTA                                  |              |
|           | — Projects: illustration + "Start a new project" CTA                                   |              |
|           | — Invoices: illustration + "Create your first invoice" CTA                              |              |
|           | — Campaigns: illustration + "Launch your first campaign" CTA                            |              |
| P4-FE-033 | Add breadcrumbs to all detail/edit pages                                               | §7.5          |
| P4-FE-034 | Add keyboard shortcuts documentation (accessible via `?` key): Ctrl+K (search), Ctrl+N (new), Escape (close) | NFR-USA-005 |
| P4-FE-035 | Add confirmation dialog for all destructive actions (ensure no delete without confirmation) | NFR-USA-002 |

#### 4.1.6 Tests

| Test File                                              | Test Cases                                                |
|--------------------------------------------------------|-----------------------------------------------------------|
| `tests/e2e/accessibility/a11y-audit.spec.ts`          | Run axe-core on: login, dashboard, clients, invoices, campaigns — zero critical violations |
| `tests/e2e/responsive/mobile-navigation.spec.ts`      | Hamburger menu on mobile, sidebar collapse on tablet       |
| `tests/e2e/dark-mode/theme-toggle.spec.ts`            | Toggle dark mode, verify colors change, persists on reload |

---

### 4.2 Sprint 14 — Performance Optimization (Weeks 22–23)

#### 4.2.1 Back-end Performance Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P4-BE-001 | Install Laravel Debugbar (dev only) and profile all API endpoints                     | §11.5         |
| P4-BE-002 | Eliminate N+1 queries — add eager loading (`with()`) to all controller queries:        | NFR-PERF-007  |
|           | — Clients: contacts, tags                                                              |              |
|           | — Projects: client, tasks (count), time entries (sum)                                  |              |
|           | — Invoices: client, line items, payments                                               |              |
|           | — Campaigns: segment, recipients (count by status)                                     |              |
| P4-BE-003 | Review and optimize all database indexes:                                              | NFR-PERF-007  |
|           | — Add composite indexes for common filter combinations                                 |              |
|           | — Add partial indexes for status filters (e.g., `WHERE archived_at IS NULL`)           |              |
|           | — Verify GIN indexes on JSONB columns (segments.filters)                               |              |
| P4-BE-004 | Implement Redis caching strategy:                                                      | NFR-PERF-008  |
|           | — Dashboard metrics (TTL: 5 min)                                                       |              |
|           | — Client count, project count (TTL: 5 min)                                             |              |
|           | — Meilisearch index sync status (TTL: 1 min)                                           |              |
|           | — Financial report summaries (TTL: 15 min)                                             |              |
|           | Cache invalidation on relevant model events                                            |              |
| P4-BE-005 | Implement query result caching for expensive aggregations (revenue reports, VAT summary) | NFR-PERF-008 |
| P4-BE-006 | Optimize PDF generation: pre-compile Blade templates, cache logo image, batch generate for multiple invoices | NFR-PERF-004 |
| P4-BE-007 | Implement database connection pooling via PgBouncer container (optional)               | NFR-SCA-005   |
| P4-BE-008 | Profile and optimize campaign sending: ensure 100 emails/min throughput sustained       | NFR-PERF-006  |
| P4-BE-009 | Implement graceful degradation:                                                        | NFR-REL-003, 004 |
|           | — Meilisearch unavailable → fall back to PostgreSQL `pg_trgm` LIKE search              |              |
|           | — Redis unavailable → fall back to database sessions, skip caching                     |              |
| P4-BE-010 | Add response time logging: log requests > 500ms as warnings                            | NFR-PERF-001  |
| P4-BE-011 | Run load test with k6 or Artillery: 100 concurrent users, verify p95 < 200ms          | §10.3.3       |

#### 4.2.2 Front-end Performance Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P4-FE-040 | Analyze production bundle with `npx vite-bundle-visualizer`                            | §10.3.3       |
| P4-FE-041 | Implement route-level code splitting (Nuxt auto-handles via file-based routing, verify) | §10.3.3 |
| P4-FE-042 | Lazy-load heavy components: TipTap editor, Chart.js charts, Kanban drag-drop library   | §10.3.3       |
| P4-FE-043 | Optimize images: convert to WebP, lazy-load below-fold images, set explicit width/height | §10.3.3 |
| P4-FE-044 | Implement virtual scrolling for large data tables (> 100 rows)                         | §10.3.3       |
| P4-FE-045 | Configure Nuxt `routeRules` for static pre-rendering of auth pages                     | §10.3.3       |
| P4-FE-046 | Set HTTP caching headers for static assets (1 year for hashed assets, no-cache for HTML) | §10.3.3 |
| P4-FE-047 | Run Lighthouse CI: target Performance > 90, Accessibility > 90, Best Practices > 90   | §10.3.3       |
| P4-FE-048 | Verify JS bundle < 300KB gzipped, CSS < 50KB gzipped                                   | §10.3.3       |

#### 4.2.3 Tests

| Test File                                              | Test Cases                                                |
|--------------------------------------------------------|-----------------------------------------------------------|
| `tests/Feature/Performance/ApiResponseTimeTest.php`    | Key endpoints respond < 200ms with 50 records in DB       |
| `tests/Feature/Performance/NoPlusOneTest.php`          | Assert query count for list endpoints (e.g., clients index: max 5 queries) |
| `tests/Feature/Degradation/MeilisearchFallbackTest.php`| Search works when Meilisearch unavailable, returns results from PostgreSQL |
| `tests/Feature/Degradation/RedisFallbackTest.php`      | App functions when Redis unavailable, sessions work via DB |

---

### 4.3 Sprint 15 — Data Management, Security & Production Setup (Weeks 23–24)

#### 4.3.1 Back-end Tasks — Data Import/Export

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P4-BE-020 | Create `DataExportService` — export all user data as JSON archive (GDPR compliance):   | FR-SET-008, NFR-SEC-008 |
|           | — Clients + contacts + tags                                                            |              |
|           | — Projects + tasks + time entries                                                      |              |
|           | — Invoices + line items + payments                                                     |              |
|           | — Quotes + line items                                                                  |              |
|           | — Credit notes + line items                                                            |              |
|           | — Campaigns + templates                                                                |              |
|           | — Settings                                                                             |              |
| P4-BE-021 | Create `DataExportController` — `GET /api/v1/export/full` — streamed ZIP download      | FR-SET-008   |
| P4-BE-022 | Create `DataImportService` — import entities from CSV:                                  | FR-SET-007   |
|           | — Projects CSV (name, client reference, dates, billing)                                |              |
|           | — Invoices CSV (client reference, line items)                                          |              |
|           | — Contacts CSV (client reference, contact details)                                     |              |
| P4-BE-023 | Create `DataImportController` — `POST /api/v1/import/{entity}` — validate, parse, create records, return success/error report | FR-SET-007 |
| P4-BE-024 | Implement GDPR data deletion: `DELETE /api/v1/account` — soft-delete user and all associated data, schedule permanent deletion after 30 days | NFR-SEC-008 |

#### 4.3.2 Back-end Tasks — Security Hardening

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P4-BE-030 | Run `composer audit` — resolve all known vulnerabilities                               | NFR-SEC-011   |
| P4-BE-031 | Run `npm audit` — resolve all known vulnerabilities                                    | NFR-SEC-011   |
| P4-BE-032 | Verify all user inputs are sanitized (XSS prevention via Blade escaping, API input validation) | NFR-SEC-006 |
| P4-BE-033 | Verify CSRF protection active on all state-changing non-API routes                     | NFR-SEC-005   |
| P4-BE-034 | Add Content Security Policy (CSP) headers via middleware:                               | NFR-SEC-012   |
|           | `default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self';` |  |
| P4-BE-035 | Add security headers: `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `X-XSS-Protection: 0`, `Referrer-Policy: strict-origin-when-cross-origin` | NFR-SEC-012 |
| P4-BE-036 | Verify sensitive data encryption at rest: `two_factor_secret`, `bank_details`, API keys | NFR-SEC-009 |
| P4-BE-037 | Review rate limiting on all public endpoints (auth: 10/min, webhooks: 60/min, API: 120/min) | NFR-SEC-007 |
| P4-BE-038 | Implement request logging middleware: log all API requests (method, path, user_id, IP, response code, duration) for audit trail | NFR-SEC-010 |
| P4-BE-039 | Implement failed job monitoring: alert when > 10 failed jobs in 1 hour                 | §11.5         |

#### 4.3.3 Back-end Tasks — Production Deployment

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P4-BE-040 | Create `docker-compose.prod.yml`:                                                      | §11.4         |
|           | — Nginx with TLS (Let's Encrypt via certbot)                                           |              |
|           | — Laravel: production .env, `APP_DEBUG=false`, optimized autoloader                    |              |
|           | — Nuxt: production build, `NODE_ENV=production`                                        |              |
|           | — PostgreSQL: resource limits, shared_buffers, effective_cache_size                     |              |
|           | — Redis: maxmemory policy `allkeys-lru`, password required                             |              |
|           | — Meilisearch: master key required                                                     |              |
|           | — Restart policies: `restart: unless-stopped` on all services                          |              |
|           | — Health checks on all services                                                        |              |
|           | — Resource limits (CPU, memory) per container                                          |              |
| P4-BE-041 | Create `docker/nginx/prod.conf` — production Nginx config with:                        | §6.2.6        |
|           | — TLS 1.2+ only, modern cipher suite                                                  |              |
|           | — HSTS header                                                                          |              |
|           | — Gzip compression for text/json/css/js                                                |              |
|           | — Static asset caching (1 year for hashed files)                                       |              |
|           | — Rate limiting at proxy level                                                         |              |
| P4-BE-042 | Create `scripts/backup.sh` — automated database backup:                                | NFR-REL-002   |
|           | — `pg_dump` compressed to `.sql.gz`                                                    |              |
|           | — Upload to S3-compatible storage                                                      |              |
|           | — Rotate backups: keep daily for 30 days, weekly for 12 weeks                          |              |
|           | — Cron entry: daily at 02:00                                                           |              |
| P4-BE-043 | Create `scripts/restore.sh` — database restore from backup file                        | NFR-REL-002   |
| P4-BE-044 | Test backup and restore procedure end-to-end                                           | NFR-REL-002   |
| P4-BE-045 | Create `.github/workflows/deploy.yml`:                                                 | §11.3.3       |
|           | — Triggered on merge to `main`                                                         |              |
|           | — Build production Docker images                                                       |              |
|           | — Tag with commit SHA and `latest`                                                     |              |
|           | — Push to GitHub Container Registry (ghcr.io)                                          |              |
|           | — SSH deploy: pull images, `docker compose -f docker-compose.prod.yml up -d`           |              |
|           | — Run migrations: `docker compose exec api php artisan migrate --force`                 |              |
|           | — Health check: verify `/api/v1/health` returns 200                                     |              |
|           | — Rollback on failure: revert to previous images                                        |              |
| P4-BE-046 | Create `.github/workflows/security-audit.yml` — weekly cron: `composer audit` + `npm audit` | §11.3 |
| P4-BE-047 | Configure file storage for production: S3-compatible (AWS S3, MinIO, or DigitalOcean Spaces) | §9.4 |
| P4-BE-048 | Implement health check endpoint enhancement: verify all services (DB, Redis, Meilisearch, Queue, Storage) with individual status | NFR-REL-006 |
| P4-BE-049 | Set up uptime monitoring (UptimeRobot or Healthchecks.io) pointing to health endpoint  | §11.5         |
| P4-BE-050 | Configure Laravel structured logging: JSON format, include request_id, user_id, duration | §11.5 |
| P4-BE-051 | Implement zero-downtime deployment strategy: rolling container updates                 | NFR-REL-007   |

#### 4.3.4 Front-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P4-FE-050 | Create `pages/settings/data.vue` — data management page:                               | FR-SET-007, 008 |
|           | — Import section: entity selector (clients, projects, invoices, contacts), CSV upload, preview, confirm |  |
|           | — Export section: full export (JSON ZIP), per-entity export (CSV)                      |              |
|           | — Account deletion with confirmation and 30-day grace period notice                    | NFR-SEC-008   |
| P4-FE-051 | Create import progress indicator (upload → parsing → validating → creating → complete)  | FR-SET-007   |
| P4-FE-052 | Create import error report display (row number, field, error message)                  | FR-SET-007   |

#### 4.3.5 Back-end Tests

| Test File                                              | Test Cases                                                  |
|--------------------------------------------------------|-------------------------------------------------------------|
| `tests/Feature/Export/FullExportTest.php`              | ZIP contains all entities as JSON, data integrity verified   |
| `tests/Feature/Import/ProjectImportTest.php`           | Valid CSV imports, invalid rows reported, duplicate handling  |
| `tests/Feature/Import/InvoiceImportTest.php`           | Valid CSV imports, client reference resolved                  |
| `tests/Feature/Security/CspHeaderTest.php`             | CSP header present on all responses                          |
| `tests/Feature/Security/SecurityHeadersTest.php`       | All security headers present and correct                     |
| `tests/Feature/Security/InputSanitizationTest.php`     | XSS payloads in client name/notes are escaped                |
| `tests/Feature/Account/AccountDeletionTest.php`        | Soft-deletes user and all data, returns confirmation         |
| `tests/Feature/Health/HealthCheckTest.php`             | All services healthy, degraded when service down             |

---

## 5. Documentation Deliverables

| Document                                | Content                                                      | Location               |
|-----------------------------------------|--------------------------------------------------------------|------------------------|
| `README.md`                             | Project overview, quick start guide (Docker), feature list, tech stack, contributing guidelines | Repository root |
| `docs/deployment.md`                    | Step-by-step production deployment guide: server requirements, DNS setup, TLS, Docker Compose, environment variables, backups, monitoring | `docs/` |
| `docs/api.md`                           | Complete API reference: all endpoints, request/response examples, authentication, error codes | `docs/` |
| `docs/architecture.md`                  | Architecture overview, service diagram, data flow, tech decision rationale | `docs/` |
| `CHANGELOG.md`                          | Full changelog following Keep a Changelog format, all 4 phases | Repository root |
| `docs/import-export.md`                 | CSV format specifications for each entity, example files      | `docs/` |
| `.env.example`                          | Complete environment variable reference with descriptions     | Repository root |

---

## 6. Final Validation Checklist

### 6.1 Functional Validation

| #  | Feature                                                      | Status |
|----|--------------------------------------------------------------|--------|
| 1  | User registration, login, logout, password reset, 2FA       | [ ]    |
| 2  | Client CRUD, contacts, tags, timeline, archive/restore       | [ ]    |
| 3  | Project CRUD, status workflow, billing models                | [ ]    |
| 4  | Task CRUD, Kanban, time entries, dependencies, attachments   | [ ]    |
| 5  | Invoice CRUD, line items, VAT, discounts, PDF, email, payments, overdue | [ ] |
| 6  | Quote CRUD, validity, PDF, email, convert to invoice         | [ ]    |
| 7  | Credit note CRUD, PDF, email, apply to invoice               | [ ]    |
| 8  | Financial reports (revenue, outstanding, VAT) + export       | [ ]    |
| 9  | Segment builder with AND/OR filters + preview                | [ ]    |
| 10 | Email campaigns: compose, template, personalize, test, schedule, send, track | [ ] |
| 11 | SMS campaigns: compose, test, schedule, send, track          | [ ]    |
| 12 | Campaign analytics: metrics, time-series, comparison, export | [ ]    |
| 13 | Global search across all entities (Ctrl+K)                   | [ ]    |
| 14 | Dashboard with all widgets populated                         | [ ]    |
| 15 | Settings: profile, business, invoicing, email, SMS, notifications, data | [ ] |
| 16 | Data import (CSV) and export (CSV, JSON ZIP)                 | [ ]    |

### 6.2 Non-Functional Validation

| #  | Criterion                                                    | Target            | Status |
|----|--------------------------------------------------------------|-------------------|--------|
| 1  | API p95 response time (CRUD endpoints)                       | < 200ms           | [ ]    |
| 2  | Meilisearch p95 query time                                   | < 50ms            | [ ]    |
| 3  | Dashboard full load time                                     | < 2 seconds       | [ ]    |
| 4  | PDF generation time                                          | < 3 seconds       | [ ]    |
| 5  | Campaign email throughput                                    | >= 100 emails/min | [ ]    |
| 6  | JS bundle size (gzipped)                                     | < 300KB           | [ ]    |
| 7  | CSS bundle size (gzipped)                                    | < 50KB            | [ ]    |
| 8  | Lighthouse Performance score                                 | > 90              | [ ]    |
| 9  | Lighthouse Accessibility score                               | > 90              | [ ]    |
| 10 | WCAG 2.1 AA compliance (axe-core: 0 critical violations)    | Pass              | [ ]    |
| 11 | Back-end test coverage                                       | >= 80%            | [ ]    |
| 12 | Front-end test coverage                                      | >= 80%            | [ ]    |
| 13 | PHPStan level 8                                              | 0 errors          | [ ]    |
| 14 | ESLint + Prettier                                            | 0 errors          | [ ]    |
| 15 | `composer audit`                                             | 0 critical        | [ ]    |
| 16 | `npm audit`                                                  | 0 critical        | [ ]    |
| 17 | All E2E tests passing                                        | Pass              | [ ]    |
| 18 | CI pipeline fully green                                      | Pass              | [ ]    |

### 6.3 Production Readiness

| #  | Criterion                                                    | Status |
|----|--------------------------------------------------------------|--------|
| 1  | `docker-compose.prod.yml` tested on production server        | [ ]    |
| 2  | TLS certificate installed and working (HTTPS)                | [ ]    |
| 3  | Database backup script running on schedule                   | [ ]    |
| 4  | Database restore procedure tested                            | [ ]    |
| 5  | Deploy pipeline (`deploy.yml`) tested end-to-end             | [ ]    |
| 6  | Rollback procedure tested                                    | [ ]    |
| 7  | Health check endpoint monitored by uptime service            | [ ]    |
| 8  | Structured logging active in production                      | [ ]    |
| 9  | Security headers verified (CSP, HSTS, X-Frame-Options)       | [ ]    |
| 10 | S3-compatible storage configured for files                   | [ ]    |
| 11 | Redis configured with password and memory limits             | [ ]    |
| 12 | Meilisearch configured with master key                       | [ ]    |
| 13 | Environment variables secured (not in repository)            | [ ]    |
| 14 | README and deployment documentation complete                 | [ ]    |

---

## 7. Exit Criteria (Phase 4 / Production Release)

| #  | Criterion                                                                           | Validated |
|----|-------------------------------------------------------------------------------------|-----------|
| 1  | All functional features working end-to-end (16 feature areas)                       | [ ]       |
| 2  | Dark mode complete across all pages and components                                  | [ ]       |
| 3  | Responsive design validated at all breakpoints (375px–2560px)                       | [ ]       |
| 4  | WCAG 2.1 AA audit passed (0 critical violations)                                   | [ ]       |
| 5  | API p95 < 200ms verified under load                                                 | [ ]       |
| 6  | Lighthouse scores > 90 (Performance + Accessibility)                                | [ ]       |
| 7  | Graceful degradation tested (Meilisearch down, Redis down)                          | [ ]       |
| 8  | Data import/export operational for all entities                                     | [ ]       |
| 9  | Production deployment pipeline tested and operational                                | [ ]       |
| 10 | TLS, backups, and monitoring active in production                                   | [ ]       |
| 11 | Security audit passed: 0 critical vulnerabilities, all headers in place             | [ ]       |
| 12 | Back-end test coverage >= 80%                                                       | [ ]       |
| 13 | Front-end test coverage >= 80%                                                      | [ ]       |
| 14 | All CI pipeline checks green                                                        | [ ]       |
| 15 | Documentation complete (README, API, deployment, architecture, changelog)           | [ ]       |
| 16 | Version tagged as `v1.0.0` on GitHub                                                | [ ]       |

---

## 8. Risks Specific to Phase 4

| Risk                                                     | Mitigation                                                    |
|----------------------------------------------------------|---------------------------------------------------------------|
| Accessibility remediation scope larger than expected      | Prioritize critical violations first; accept minor issues as v1.1 backlog |
| Performance optimization revealing deep architectural issues | Focus on quick wins (indexes, caching, eager loading); defer major refactors to v1.1 |
| Production environment configuration differences         | Test full prod stack locally using `docker-compose.prod.yml`; staging environment if budget allows |
| Backup restore failure                                   | Test restore monthly; automate restore verification; keep multiple backup copies |
| Security audit revealing blocking vulnerabilities        | Allocate buffer time in schedule; prioritize OWASP Top 10; accept low-severity as known issues |
| TLS certificate renewal failure                          | Use certbot with auto-renewal; monitor certificate expiry      |
| Bundle size exceeding targets                            | Analyze with bundle visualizer; replace heavy dependencies with lighter alternatives; tree-shake aggressively |

---

## 9. Post-Release Roadmap (v1.1+)

Items intentionally deferred from v1.0.0 for future releases:

| Feature                                | Priority | Estimated Phase |
|----------------------------------------|----------|-----------------|
| Calendar integration (Google/CalDAV)   | Medium   | v1.1            |
| Recurring invoices                     | High     | v1.1            |
| Multi-currency support                 | Medium   | v1.1            |
| Client portal (view/pay invoices)      | Medium   | v1.2            |
| Expense tracking                       | Medium   | v1.2            |
| Multi-user support (team)              | Low      | v1.3            |
| Mobile app (Capacitor)                 | Low      | v2.0            |
| Prometheus + Grafana monitoring        | Low      | v1.1            |
| Kubernetes deployment                  | Low      | v2.0            |
| AI-powered insights (revenue forecast) | Low      | v2.0            |

---

*End of Phase 4 — Polish, Optimization, and Production Release*
