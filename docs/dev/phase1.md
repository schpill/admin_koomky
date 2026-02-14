# Phase 1 — Task Tracking

> **Status**: In Progress
> **Spec**: [docs/phases/phase1.md](../phases/phase1.md)

---

## Sprint 1 — Infrastructure & Scaffolding (Weeks 1-2)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P1-BE-001 | Create monorepo structure | merged | junior |
| P1-BE-002 | Write docker-compose.yml (9 services) | merged | junior |
| P1-BE-003 | Write docker/php/Dockerfile (PHP 8.3-FPM) | merged | junior |
| P1-BE-004 | Write docker/node/Dockerfile (Node 20 + pnpm) | merged | junior |
| P1-BE-005 | Write docker/nginx/default.conf (reverse proxy) | merged | junior |
| P1-BE-006 | Write docker/postgres/init.sql (uuid-ossp, pg_trgm) | merged | junior |
| P1-BE-007 | Initialize Laravel 12.x inside backend/ | merged | junior |
| P1-BE-008 | Configure Laravel (pgsql, redis cache/session/queue) | todo | |
| P1-BE-009 | Install and configure Pest | merged | junior |
| P1-BE-010 | Install and configure PHPStan (level 8) + Pint | merged | claude |
| P1-BE-011 | Install Laravel Scout + Meilisearch driver | todo | |
| P1-BE-012 | Install Laravel Sanctum | merged | junior |
| P1-BE-013 | Create base ApiResponse trait | merged | junior |
| P1-BE-014 | Create Makefile | merged | junior |
| P1-BE-015 | Create .env.example with all env vars | merged | claude |
| P1-BE-016 | Create health check endpoint GET /api/v1/health | merged | junior |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P1-FE-001 | Initialize Next.js 15.x with TypeScript + App Router | merged | junior |
| P1-FE-002 | Configure Turbopack for dev builds | merged | junior |
| P1-FE-003 | Install and configure Tailwind CSS 4.x with custom theme | wip | junior |
| P1-FE-004 | Install Zustand for state management | todo | |
| P1-FE-005 | Install react-hook-form + Zod for form validation | todo | |
| P1-FE-006 | Install and configure shadcn/ui (Radix UI + Tailwind) | todo | |
| P1-FE-007 | Configure Vitest + React Testing Library | merged | junior |
| P1-FE-008 | Configure Playwright for E2E testing | todo | |
| P1-FE-009 | Configure ESLint (eslint-config-next) | merged | junior |
| P1-FE-010 | Create default layout (sidebar + topbar + content area) | todo | |
| P1-FE-011 | Create auth layout (centered card, no sidebar) | todo | |
| P1-FE-012 | Create base components via shadcn/ui (Button, Input, etc.) | todo | |
| P1-FE-013 | Create useApi hook (fetch + JWT interceptor + refresh) | todo | |
| P1-FE-014 | Create useAuthStore Zustand store | todo | |
| P1-FE-015 | Create auth middleware (Next.js middleware) | todo | |
| P1-FE-016 | Set up Inter + JetBrains Mono fonts via next/font | merged | junior |

### DevOps

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P1-DO-001 | Create .github/workflows/ci.yml | merged | junior |
| P1-DO-002 | CI job: Lint backend (Pint + PHPStan) | merged | junior |
| P1-DO-003 | CI job: Lint frontend (ESLint) | merged | junior |
| P1-DO-004 | CI job: Backend tests (Pest + coverage) | merged | junior |
| P1-DO-005 | CI job: Frontend tests (Vitest + coverage) | merged | junior |
| P1-DO-006 | CI job: Coverage threshold check (>= 80%) | merged | junior |
| P1-DO-007 | Create .github/PULL_REQUEST_TEMPLATE.md | todo | |
| P1-DO-008 | Configure branch protection on main | todo | |
| P1-DO-009 | Create .gitignore for monorepo | merged | junior |

---

## Sprint 2 — Authentication System (Weeks 3-4)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P1-BE-020 | Create User model (UUID PK, hidden attrs, casts) | todo | |
| P1-BE-021 | Create UserFactory with Faker data | todo | |
| P1-BE-022 | Create RegisterRequest (password complexity) | todo | |
| P1-BE-023 | Create LoginRequest | todo | |
| P1-BE-024 | Create AuthController (register, login, logout, refresh, forgot/reset password) | todo | |
| P1-BE-025 | Implement JWT token generation via Sanctum (TTL 15 min) | todo | |
| P1-BE-026 | Implement refresh token rotation | todo | |
| P1-BE-027 | Implement account lockout (5 failures, 15 min) | todo | |
| P1-BE-028 | Implement forgot password (time-limited token, queued email) | todo | |
| P1-BE-029 | Implement 2FA TOTP setup (secret, QR code) | todo | |
| P1-BE-030 | Implement 2FA verification on login | todo | |
| P1-BE-031 | Create AuditLog model + LogAuthEvent listener | todo | |
| P1-BE-032 | Create rate limiting middleware (10 req/min on auth) | todo | |
| P1-BE-033 | Create UserSettingsController (GET/PUT profile + business) | todo | |
| P1-BE-034 | Create UpdateProfileRequest | todo | |
| P1-BE-035 | Create UpdateBusinessRequest | todo | |
| P1-BE-036 | Encrypt two_factor_secret + bank_details at rest | todo | |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P1-FE-020 | Create app/auth/login/page.tsx | todo | |
| P1-FE-021 | Create app/auth/register/page.tsx | todo | |
| P1-FE-022 | Create app/auth/forgot-password/page.tsx | todo | |
| P1-FE-023 | Create app/auth/reset-password/page.tsx | todo | |
| P1-FE-024 | Create app/settings/profile/page.tsx | todo | |
| P1-FE-025 | Create app/settings/business/page.tsx | todo | |
| P1-FE-026 | Create app/settings/security/page.tsx (2FA) | todo | |
| P1-FE-027 | Implement useAuthStore Zustand store | todo | |
| P1-FE-028 | Implement useApi hook (Bearer token, 401 refresh) | todo | |
| P1-FE-029 | Implement Next.js auth middleware | todo | |
| P1-FE-030 | Implement guest guard (redirect if authenticated) | todo | |
| P1-FE-031 | Configure Sonner for toast notifications | todo | |

---

## Sprint 3 — Client Management (Weeks 4-5)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P1-BE-040 | Create Client model (UUID, relationships, scopes, Searchable) | todo | |
| P1-BE-041 | Create ClientFactory | todo | |
| P1-BE-042 | Create ClientPolicy (user owns client) | todo | |
| P1-BE-043 | Create ClientController (index, store, show, update, destroy, restore) | todo | |
| P1-BE-044 | Create StoreClientRequest (auto-generate reference CLI-YYYY-NNNN) | todo | |
| P1-BE-045 | Create UpdateClientRequest | todo | |
| P1-BE-046 | Create ClientResource / ClientCollection | todo | |
| P1-BE-047 | Implement ReferenceGenerator service | todo | |
| P1-BE-048 | Implement client list filtering (status, tags, search) | todo | |
| P1-BE-049 | Implement client list sorting | todo | |
| P1-BE-050 | Prevent hard deletion with associated invoices/projects | todo | |
| P1-BE-051 | Create Contact model | todo | |
| P1-BE-052 | Create ContactController (CRUD nested under client) | todo | |
| P1-BE-053 | Enforce single primary contact per client | todo | |
| P1-BE-054 | Create Tag model (unique name per user) | todo | |
| P1-BE-055 | Create TagController (CRUD, assign/detach) | todo | |
| P1-BE-056 | Create Activity model (polymorphic) | todo | |
| P1-BE-057 | Create ActivityService (log method) | todo | |
| P1-BE-058 | Create ActivityController (index with filtering) | todo | |
| P1-BE-059 | Create model observers (ClientObserver, ContactObserver) | todo | |
| P1-BE-060 | Configure Meilisearch index for Client | todo | |
| P1-BE-061 | Create SearchController (GET /api/v1/search) | todo | |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P1-FE-040 | Create lib/stores/clients.ts Zustand store | todo | |
| P1-FE-041 | Create app/clients/page.tsx (data table) | todo | |
| P1-FE-042 | Create app/clients/create/page.tsx | todo | |
| P1-FE-043 | Create app/clients/[id]/page.tsx (detail + tabs) | todo | |
| P1-FE-044 | Create app/clients/[id]/edit/page.tsx | todo | |
| P1-FE-045 | Create components/clients/client-contact-list.tsx | todo | |
| P1-FE-046 | Create components/clients/client-timeline.tsx | todo | |
| P1-FE-047 | Create components/clients/client-tag-selector.tsx | todo | |
| P1-FE-048 | Create components/common/confirmation-dialog.tsx | todo | |
| P1-FE-049 | Implement soft-delete UI (archive, badge, restore) | todo | |
| P1-FE-050 | Implement client list filter bar | todo | |
| P1-FE-051 | Create components/search/command-palette.tsx (Ctrl+K, cmdk) | todo | |
| P1-FE-052 | Integrate command palette into default layout | todo | |
| P1-FE-053 | Implement empty states (no clients, contacts, activities, results) | todo | |

---

## Sprint 4 — Dashboard & Phase Validation (Weeks 5-6)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P1-BE-070 | Create DashboardController (aggregated metrics) | todo | |
| P1-BE-071 | Create DashboardService (Redis-cached calculations) | todo | |
| P1-BE-072 | Implement upcoming deadlines placeholder | todo | |
| P1-BE-073 | Add CSV import endpoint for clients | todo | |
| P1-BE-074 | Add CSV export endpoint for clients | todo | |
| P1-BE-075 | Complete all PHPDoc annotations | todo | |
| P1-BE-076 | Run PHPStan level 8 — fix all issues | todo | |
| P1-BE-077 | Run Pest coverage >= 80% | todo | |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P1-FE-060 | Create app/(dashboard)/page.tsx (widget grid) | todo | |
| P1-FE-061 | Create components/dashboard/metric-card.tsx | todo | |
| P1-FE-062 | Create components/dashboard/recent-activity-widget.tsx | todo | |
| P1-FE-063 | Create components/dashboard/upcoming-deadlines-widget.tsx | todo | |
| P1-FE-064 | Implement skeleton loaders for dashboard widgets | todo | |
| P1-FE-065 | Implement responsive layout (1/2/3 col) | todo | |
| P1-FE-066 | Implement dark mode toggle via next-themes | todo | |
| P1-FE-067 | Implement CSV import UI on clients page | todo | |
| P1-FE-068 | Run Vitest coverage >= 80% | todo | |
| P1-FE-069 | Run Lighthouse audit (perf > 90, a11y > 90) | todo | |
