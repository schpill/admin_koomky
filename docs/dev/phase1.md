# Phase 1 — Task Tracking

> **Status**: In Progress
> **Spec**: [docs/phases/phase1.md](../phases/phase1.md)
> **Last audit**: 2026-02-15 (code verification against repo)

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
| P1-BE-008 | Configure Laravel (pgsql, redis cache/session/queue) | merged | claude |
| P1-BE-009 | Install and configure Pest | merged | junior |
| P1-BE-010 | Install and configure PHPStan (level 8) + Pint | merged | claude |
| P1-BE-011 | Install Laravel Scout + Meilisearch driver | merged | claude |
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
| P1-FE-003 | Install and configure Tailwind CSS 4.x with custom theme | merged | junior |
| P1-FE-004 | Install Zustand for state management | merged | claude |
| P1-FE-005 | Install react-hook-form + Zod for form validation | merged | claude |
| P1-FE-006 | Install and configure shadcn/ui (Radix UI + Tailwind) | merged | claude |
| P1-FE-007 | Configure Vitest + React Testing Library | merged | junior |
| P1-FE-008 | Configure Playwright for E2E testing | merged | claude |
| P1-FE-009 | Configure ESLint (eslint-config-next) | merged | junior |
| P1-FE-010 | Create default layout (sidebar + topbar + content area) | merged | claude |
| P1-FE-011 | Create auth layout (centered card, no sidebar) | merged | claude |
| P1-FE-012 | Create base components via shadcn/ui (Button, Input, etc.) | merged | claude |
| P1-FE-013 | Create useApi hook (fetch + JWT interceptor + refresh) | merged | claude |
| P1-FE-014 | Create useAuthStore Zustand store | merged | claude |
| P1-FE-015 | Create auth middleware (Next.js middleware) | merged | claude |
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
| P1-DO-007 | Create .github/PULL_REQUEST_TEMPLATE.md | merged | claude |
| P1-DO-008 | Configure branch protection on main (via CODEOWNERS) | done | claude |
| P1-DO-009 | Create .gitignore for monorepo | merged | junior |

---

## Sprint 2 — Authentication System (Weeks 3-4)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P1-BE-020 | Create User model (UUID PK, hidden attrs, casts) | done | claude |
| P1-BE-021 | Create UserFactory with Faker data | done | claude |
| P1-BE-022 | Create RegisterRequest (password complexity) | done | claude |
| P1-BE-023 | Create LoginRequest | done | claude |
| P1-BE-024 | Create AuthController (register, login, logout, refresh, forgot/reset password) | done | claude |
| P1-BE-025 | Implement JWT token generation via Sanctum (TTL 15 min) | done | claude |
| P1-BE-026 | Implement refresh token rotation | done | claude |
| P1-BE-027 | Implement account lockout (5 failures, 15 min) | done | claude |
| P1-BE-028 | Implement forgot password (time-limited token, queued email) | done | claude |
| P1-BE-029 | Implement 2FA TOTP setup (secret, QR code) | done | claude |
| P1-BE-030 | Implement 2FA verification on login | done | claude |
| P1-BE-031 | Create AuditLog model + LogAuthEvent listener | done | claude |
| P1-BE-032 | Create rate limiting middleware (10 req/min on auth) | done | claude |
| P1-BE-033 | Create UserSettingsController (GET/PUT profile + business) | done | claude |
| P1-BE-034 | Create UpdateProfileRequest | done | claude |
| P1-BE-035 | Create UpdateBusinessRequest | done | claude |
| P1-BE-036 | Encrypt two_factor_secret + bank_details at rest | done | claude |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P1-FE-020 | Create app/auth/login/page.tsx | done | claude |
| P1-FE-021 | Create app/auth/register/page.tsx | done | claude |
| P1-FE-022 | Create app/auth/forgot-password/page.tsx | done | claude |
| P1-FE-023 | Create app/auth/reset-password/page.tsx | done | claude |
| P1-FE-024 | Create app/settings/profile/page.tsx | done | claude |
| P1-FE-025 | Create app/settings/business/page.tsx | done | claude |
| P1-FE-026 | Create app/settings/security/page.tsx (2FA) | done | claude |
| P1-FE-027 | Implement useAuthStore Zustand store | done | claude |
| P1-FE-028 | Implement useApi hook (Bearer token, 401 refresh) | done | claude |
| P1-FE-029 | Implement Next.js auth middleware | done | claude |
| P1-FE-030 | Implement guest guard (redirect if authenticated) | done | claude |
| P1-FE-031 | Configure Sonner for toast notifications | done | claude |

---

## Sprint 3 — Client Management (Weeks 4-5)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P1-BE-040 | Create Client model (UUID, relationships, scopes, Searchable) | done | claude |
| P1-BE-041 | Create ClientFactory | done | claude |
| P1-BE-042 | Create ClientPolicy (user owns client) | done | claude |
| P1-BE-043 | Create ClientController (index, store, show, update, destroy, restore) | done | claude |
| P1-BE-044 | Create StoreClientRequest (auto-generate reference CLI-YYYY-NNNN) | done | claude |
| P1-BE-045 | Create UpdateClientRequest | done | claude |
| P1-BE-046 | Create ClientResource / ClientCollection | done | claude |
| P1-BE-047 | Implement ReferenceGenerator service | done | claude |
| P1-BE-048 | Implement client list filtering (status, tags, search) | done | claude |
| P1-BE-049 | Implement client list sorting | done | claude |
| P1-BE-050 | Prevent hard deletion with associated invoices/projects | done | claude |
| P1-BE-051 | Create Contact model | done | claude |
| P1-BE-052 | Create ContactController (CRUD nested under client) | done | claude |
| P1-BE-053 | Enforce single primary contact per client | done | claude |
| P1-BE-054 | Create Tag model (unique name per user) | done | claude |
| P1-BE-055 | Create TagController (CRUD, assign/detach) | done | claude |
| P1-BE-056 | Create Activity model (polymorphic) | done | claude |
| P1-BE-057 | Create ActivityService (log method) | done | claude |
| P1-BE-058 | Create ActivityController (index with filtering) | todo | |
| P1-BE-059 | Create model observers (ClientObserver, ContactObserver) | done | claude |
| P1-BE-060 | Configure Meilisearch index for Client | done | claude |
| P1-BE-061 | Create SearchController (GET /api/v1/search) | done | claude |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P1-FE-040 | Create lib/stores/clients.ts Zustand store | done | claude |
| P1-FE-041 | Create app/clients/page.tsx (data table) | done | claude |
| P1-FE-042 | Create app/clients/create/page.tsx | todo | |
| P1-FE-043 | Create app/clients/[id]/page.tsx (detail + tabs) | done | claude |
| P1-FE-044 | Create app/clients/[id]/edit/page.tsx | todo | |
| P1-FE-045 | Create components/clients/client-contact-list.tsx | todo | |
| P1-FE-046 | Create components/clients/client-timeline.tsx | todo | |
| P1-FE-047 | Create components/clients/client-tag-selector.tsx | todo | |
| P1-FE-048 | Create components/common/confirmation-dialog.tsx | done | claude |
| P1-FE-049 | Implement soft-delete UI (archive, badge, restore) | todo | |
| P1-FE-050 | Implement client list filter bar | todo | |
| P1-FE-051 | Create components/search/command-palette.tsx (Ctrl+K, cmdk) | done | claude |
| P1-FE-052 | Integrate command palette into default layout | done | claude |
| P1-FE-053 | Implement empty states (no clients, contacts, activities, results) | done | claude |

---

## Sprint 4 — Dashboard & Phase Validation (Weeks 5-6)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P1-BE-070 | Create DashboardController (aggregated metrics) | done | claude |
| P1-BE-071 | Create DashboardService (Redis-cached calculations) | done | claude |
| P1-BE-072 | Implement upcoming deadlines placeholder | done | claude |
| P1-BE-073 | Add CSV import endpoint for clients | done | claude |
| P1-BE-074 | Add CSV export endpoint for clients | done | claude |
| P1-BE-075 | Complete all PHPDoc annotations | todo | |
| P1-BE-076 | Run PHPStan level 8 — fix all issues | todo | |
| P1-BE-077 | Run Pest coverage >= 80% | todo | |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P1-FE-060 | Create app/(dashboard)/page.tsx (widget grid) | done | claude |
| P1-FE-061 | Create components/dashboard/metric-card.tsx | done | claude |
| P1-FE-062 | Create components/dashboard/recent-activity-widget.tsx | done | claude |
| P1-FE-063 | Create components/dashboard/upcoming-deadlines-widget.tsx | done | claude |
| P1-FE-064 | Implement skeleton loaders for dashboard widgets | todo | |
| P1-FE-065 | Implement responsive layout (1/2/3 col) | todo | |
| P1-FE-066 | Implement dark mode toggle via next-themes | done | claude |
| P1-FE-067 | Implement CSV import UI on clients page | done | claude |
| P1-FE-068 | Run Vitest coverage >= 80% | todo | |
| P1-FE-069 | Run Lighthouse audit (perf > 90, a11y > 90) | todo | |

---

## Audit Notes (2026-02-15)

Corrections apportees suite a verification du code source :

### Taches qui etaient marquees `done` mais absentes du code :
- **P1-BE-058** : Aucun `ActivityController.php` dans le repo. Remis a `todo`.
- **P1-FE-042** : Pas de page `clients/create/page.tsx` (un dialog existe, pas une page dediee). Remis a `todo`.
- **P1-FE-044** : Pas de page `clients/[id]/edit/page.tsx`. Remis a `todo`.
- **P1-FE-045** : Pas de composant `client-contact-list.tsx`. Remis a `todo`.
- **P1-FE-046** : Pas de composant `client-timeline.tsx`. Remis a `todo`.
- **P1-FE-047** : Pas de composant `client-tag-selector.tsx`. Remis a `todo`.
- **P1-FE-049** : Pas d'UI soft-delete (archive/restore) verifiable. Remis a `todo`.
- **P1-FE-050** : Pas de filter bar verifiable. Remis a `todo`.

### Taches qui etaient marquees `todo` mais presentes dans le code :
- **P1-FE-060** : `app/(dashboard)/page.tsx` existe. Passe a `done`.
- **P1-FE-061** : `components/dashboard/metric-card.tsx` existe. Passe a `done`.
- **P1-FE-062** : `components/dashboard/recent-activity-widget.tsx` existe. Passe a `done`.
- **P1-FE-063** : `components/dashboard/upcoming-deadlines-widget.tsx` existe. Passe a `done`.
- **P1-FE-066** : `next-themes` installe et wire dans `layout.tsx` + toggle dans header. Passe a `done`.
- **P1-FE-067** : `components/clients/csv-actions.tsx` existe. Passe a `done`.

### Tache remise en jeu :
- **P1-BE-026** : Refresh token rotation etait marque `skipped` sans justification. C'est une exigence PRD (FR-AUTH-004). Remis a `todo`.

### Tests frontend insuffisants :
- Seuls 3 tests unitaires de stores + 1 smoke E2E. Aucun test de composant. Loin du 80% de coverage requis.
