# Phase 1 — Foundation and Core CRM

| Field               | Value                                     |
|---------------------|-------------------------------------------|
| **Phase**           | 1 of 4                                    |
| **Name**            | Foundation and Core CRM                   |
| **Duration**        | Weeks 1–6 (6 weeks)                       |
| **Milestone**       | M1 — Functional CRM                       |
| **PRD Sections**    | §4.1, §4.2, §4.3, §4.7, §4.8, §6, §11   |
| **Status**          | Not Started                               |

---

## 1. Phase Objectives

| ID      | Objective                                                                                         |
|---------|---------------------------------------------------------------------------------------------------|
| P1-OBJ-1 | Establish the full development infrastructure (Docker, CI/CD, monorepo structure)               |
| P1-OBJ-2 | Implement secure authentication with JWT, refresh tokens, and optional 2FA                      |
| P1-OBJ-3 | Deliver complete client management (CRUD, contacts, tags, timeline)                             |
| P1-OBJ-4 | Integrate Meilisearch for global search across clients                                          |
| P1-OBJ-5 | Build a basic dashboard with client metrics and recent activity                                  |
| P1-OBJ-6 | Achieve >= 80% test coverage on both back-end and front-end                                     |
| P1-OBJ-7 | Validate the full CI pipeline (lint, test, coverage gate) before any merge to `main`            |

---

## 2. Entry Criteria

- PRD v1.0.0 approved and committed to repository.
- Development environment prerequisites available (Docker Desktop, Node 20+, PHP 8.3+, Composer, GitHub account).
- Domain name and GitHub repository created.

---

## 3. Scope — Requirement Traceability

| PRD Requirement       | IDs                                    | Included |
|-----------------------|----------------------------------------|----------|
| Authentication        | FR-AUTH-001 → FR-AUTH-009              | Yes      |
| Dashboard             | FR-DASH-001 → FR-DASH-008             | Partial (FR-DASH-006/007 basic only) |
| Client CRUD           | FR-CLI-001 → FR-CLI-008               | Yes      |
| Client Contacts       | FR-CLI-009 → FR-CLI-012               | Yes      |
| Client Timeline       | FR-CLI-013 → FR-CLI-016               | Yes      |
| Client Tags           | FR-CLI-017 → FR-CLI-020               | Yes      |
| Global Search         | FR-SRC-001 → FR-SRC-007               | Yes (clients only) |
| Settings              | FR-SET-001 → FR-SET-002               | Yes      |
| Settings (others)     | FR-SET-003 → FR-SET-008               | No (Phase 2+) |

---

## 4. Detailed Sprint Breakdown

### 4.1 Sprint 1 — Infrastructure & Scaffolding (Weeks 1–2)

#### 4.1.1 Objectives
- Fully operational Docker development environment.
- Laravel API skeleton with routing, middleware, and base configuration.
- Nuxt.js front-end skeleton with Tailwind CSS, Pinia, and layout system.
- PostgreSQL database initialized with UUID extensions.
- Redis connected for cache and sessions.
- Meilisearch container running and accessible.
- CI pipeline on GitHub Actions running lint + tests on every push.
- Mailpit container for development email testing.

#### 4.1.2 Back-end Tasks

| ID        | Task                                                                                  | PRD Ref  |
|-----------|---------------------------------------------------------------------------------------|----------|
| P1-BE-001 | Create monorepo structure: `backend/`, `frontend/`, `docker/`, `.github/`             | §11.2    |
| P1-BE-002 | Write `docker-compose.yml` with all 9 services (nginx, api, frontend, postgres, redis, meilisearch, queue-worker, scheduler, mailpit) | §15.4 |
| P1-BE-003 | Write `docker/php/Dockerfile` — PHP 8.3-FPM with extensions: pdo_pgsql, redis, gd, intl, zip, bcmath, pcntl | §6.2.1 |
| P1-BE-004 | Write `docker/node/Dockerfile` — Node 20 with pnpm                                   | §6.2.2   |
| P1-BE-005 | Write `docker/nginx/default.conf` — reverse proxy routing `/api/*` → Laravel, `/*` → Nuxt | §6.2.6 |
| P1-BE-006 | Write `docker/postgres/init.sql` — enable `uuid-ossp` and `pg_trgm` extensions       | §6.2.3   |
| P1-BE-007 | Initialize Laravel 12.x project inside `backend/`                                     | §6.2.1   |
| P1-BE-008 | Configure Laravel: database (pgsql), cache (redis), session (redis), queue (redis)    | §6.2.1   |
| P1-BE-009 | Install and configure Pest for testing                                                | §6.2.1   |
| P1-BE-010 | Install and configure PHPStan (level 8) + Laravel Pint                                | §6.2.1   |
| P1-BE-011 | Install Laravel Scout + Meilisearch driver                                            | §6.2.4   |
| P1-BE-012 | Install Laravel Sanctum for API token authentication                                  | §6.2.1   |
| P1-BE-013 | Create base API response trait/helper (success, error, paginated formats per §8.3.2)  | §8.3.2   |
| P1-BE-014 | Create `Makefile` with commands: `make up`, `make down`, `make test`, `make lint`, `make fresh` | §11.2 |
| P1-BE-015 | Create `.env.example` with all environment variables                                  | §15.3    |
| P1-BE-016 | Create health check endpoint: `GET /api/v1/health` (returns service status for postgres, redis, meilisearch) | NFR-REL-006 |

#### 4.1.3 Front-end Tasks

| ID        | Task                                                                                  | PRD Ref  |
|-----------|---------------------------------------------------------------------------------------|----------|
| P1-FE-001 | Initialize Nuxt 3.x project inside `frontend/` with TypeScript                       | §6.2.2   |
| P1-FE-002 | Configure Vite as the build tool                                                      | §6.2.2   |
| P1-FE-003 | Install and configure Tailwind CSS 3.x with custom theme (colors, fonts, border radius from §7.2) | §7.2, §7.3 |
| P1-FE-004 | Install Pinia for state management                                                    | §6.2.2   |
| P1-FE-005 | Install VeeValidate + Zod for form validation                                         | §6.2.2   |
| P1-FE-006 | Install Headless UI for accessible UI primitives                                      | §6.2.2   |
| P1-FE-007 | Configure Vitest + Vue Test Utils for unit/component testing                          | §6.2.2   |
| P1-FE-008 | Configure Playwright for E2E testing                                                  | §6.2.2   |
| P1-FE-009 | Configure ESLint + Prettier                                                           | §6.2.2   |
| P1-FE-010 | Create default layout with sidebar navigation, top bar, and main content area (§7.4)  | §7.4     |
| P1-FE-011 | Create `AuthLayout` (centered card, no sidebar) for login/register pages              | §7.4     |
| P1-FE-012 | Create reusable base components: `AppButton`, `AppInput`, `AppSelect`, `AppTextarea`, `AppBadge`, `AppModal`, `AppDrawer`, `AppToast`, `AppEmptyState`, `AppPagination`, `AppDataTable` | §7.5 |
| P1-FE-013 | Create `useApi` composable wrapping `ofetch` with JWT interceptor, refresh logic, error handling | §6.2.2 |
| P1-FE-014 | Create `useAuth` Pinia store (token storage, user state, login/logout actions)        | §6.2.2   |
| P1-FE-015 | Create `auth` middleware for protecting routes                                        | §6.2.2   |
| P1-FE-016 | Set up Inter font (headings/body) and JetBrains Mono (monospace) via `@fontsource`    | §7.2     |

#### 4.1.4 DevOps Tasks

| ID        | Task                                                                                  | PRD Ref  |
|-----------|---------------------------------------------------------------------------------------|----------|
| P1-DO-001 | Create `.github/workflows/ci.yml` — triggered on push to any branch and PR to `main`  | §11.3.1  |
| P1-DO-002 | CI job: Lint back-end (`laravel pint --test`, `phpstan`)                               | §11.3.1  |
| P1-DO-003 | CI job: Lint front-end (`eslint`, `prettier --check`)                                  | §11.3.1  |
| P1-DO-004 | CI job: Back-end tests (`pest --coverage --min=80`) with PostgreSQL service container  | §11.3.1  |
| P1-DO-005 | CI job: Front-end tests (`vitest run --coverage`)                                      | §11.3.1  |
| P1-DO-006 | CI job: Coverage threshold check (fail if < 80%)                                       | §11.3.1  |
| P1-DO-007 | Create `.github/PULL_REQUEST_TEMPLATE.md`                                              | §11.2    |
| P1-DO-008 | Configure branch protection on `main` (require CI checks, no direct push)              | §11.1    |
| P1-DO-009 | Create `.gitignore` for monorepo (vendor, node_modules, .env, storage, .nuxt)          | §11.2    |

#### 4.1.5 Deliverables Checklist

- [ ] `docker compose up` starts all 9 services successfully.
- [ ] `http://localhost` serves the Nuxt.js app with the default layout.
- [ ] `http://localhost/api/v1/health` returns `200 OK` with service statuses.
- [ ] `make test` runs both back-end and front-end tests.
- [ ] CI pipeline passes on the initial scaffolding commit.
- [ ] Meilisearch admin UI accessible at `http://localhost:7700`.
- [ ] Mailpit UI accessible at `http://localhost:8025`.
- [ ] PHPStan runs at level 8 with zero errors.

---

### 4.2 Sprint 2 — Authentication System (Weeks 3–4)

#### 4.2.1 Objectives
- Complete authentication flow (register, login, logout, password reset, 2FA).
- User settings page (profile, business info).
- Auth middleware protecting all API routes.
- Front-end login/register pages with form validation.

#### 4.2.2 Database Migrations

| Migration                          | Description                                              |
|------------------------------------|----------------------------------------------------------|
| `create_users_table`               | All columns from §8.2.1 (id UUID, name, email, password, two_factor_secret, avatar_path, business fields, timestamps) |
| `create_personal_access_tokens_table` | Laravel Sanctum tokens                                |
| `create_audit_logs_table`          | Columns: id, user_id, event (login/logout/failed_login/password_reset), ip_address, user_agent, metadata JSON, created_at |

#### 4.2.3 Back-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P1-BE-020 | Create `User` model with UUID primary key, hidden attributes, casts                   | §8.2.1        |
| P1-BE-021 | Create `UserFactory` with Faker data for testing                                      | §10.3.1       |
| P1-BE-022 | Create `RegisterRequest` form request (validate name, email uniqueness, password complexity: min 12 chars, uppercase, lowercase, digit, special) | FR-AUTH-005 |
| P1-BE-023 | Create `LoginRequest` form request (validate email, password)                         | FR-AUTH-002   |
| P1-BE-024 | Create `AuthController` with methods: `register`, `login`, `logout`, `refresh`, `forgotPassword`, `resetPassword` | FR-AUTH-001 → 008 |
| P1-BE-025 | Implement JWT token generation via Sanctum (access token TTL: 15 min, configurable)   | NFR-SEC-003   |
| P1-BE-026 | Implement refresh token rotation — store refresh token hashed in DB, rotate on use    | FR-AUTH-004   |
| P1-BE-027 | Implement account lockout after 5 failed attempts for 15 minutes (use Redis for tracking) | FR-AUTH-007 |
| P1-BE-028 | Implement forgot password: generate time-limited token (1 hour), send email via queued job | FR-AUTH-008 |
| P1-BE-029 | Implement 2FA (TOTP) setup: generate secret, return QR code URL, verify code          | FR-AUTH-006   |
| P1-BE-030 | Implement 2FA verification on login (if enabled): require TOTP code after credentials validated | FR-AUTH-006 |
| P1-BE-031 | Create `AuditLog` model and `LogAuthEvent` listener to log all auth events            | FR-AUTH-009   |
| P1-BE-032 | Create rate limiting middleware on auth endpoints (max 10 requests/minute per IP)      | NFR-SEC-007   |
| P1-BE-033 | Create `UserSettingsController` — GET/PUT for profile and business information         | FR-SET-001, FR-SET-002 |
| P1-BE-034 | Create `UpdateProfileRequest` — validate name, email, avatar upload (max 2MB, jpeg/png) | FR-SET-001 |
| P1-BE-035 | Create `UpdateBusinessRequest` — validate business_name, address, siret (14 digits), ape_code, vat_number | FR-SET-002 |
| P1-BE-036 | Encrypt `two_factor_secret` and `bank_details` at rest using Laravel's `encrypted` cast | NFR-SEC-009 |

#### 4.2.4 Back-end Tests (TDD)

| Test File                                | Test Cases                                                      |
|------------------------------------------|-----------------------------------------------------------------|
| `tests/Unit/Models/UserTest.php`         | Hidden attributes, casts, encrypted fields, factory creation     |
| `tests/Feature/Auth/RegisterTest.php`    | Successful registration, duplicate email rejection, password complexity validation, response format |
| `tests/Feature/Auth/LoginTest.php`       | Successful login returns tokens, invalid credentials return 401, account lockout after 5 failures, lockout duration is 15 min |
| `tests/Feature/Auth/LogoutTest.php`      | Token invalidation, unauthenticated request after logout         |
| `tests/Feature/Auth/RefreshTokenTest.php`| Token refresh returns new access token, old refresh token is invalidated, expired refresh token rejected |
| `tests/Feature/Auth/ForgotPasswordTest.php` | Email sent on valid request, no error on non-existent email (security), token expires after 1 hour |
| `tests/Feature/Auth/ResetPasswordTest.php`  | Password updated with valid token, invalid/expired token rejected |
| `tests/Feature/Auth/TwoFactorTest.php`   | Enable 2FA returns secret + QR URL, login with 2FA requires code, invalid code rejected, disable 2FA |
| `tests/Feature/Auth/RateLimitTest.php`   | 11th request within 1 minute returns 429                        |
| `tests/Feature/Settings/ProfileTest.php` | Update name, update email, upload avatar, validation errors      |
| `tests/Feature/Settings/BusinessTest.php`| Update business info, SIRET validation (14 digits), VAT number format |

#### 4.2.5 Front-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P1-FE-020 | Create `pages/auth/login.vue` — email/password form, "forgot password" link, 2FA code input (conditional) | FR-AUTH-002, 006 |
| P1-FE-021 | Create `pages/auth/register.vue` — name, email, password, password confirmation       | FR-AUTH-001   |
| P1-FE-022 | Create `pages/auth/forgot-password.vue` — email input, success message                | FR-AUTH-008   |
| P1-FE-023 | Create `pages/auth/reset-password.vue` — new password form (token from URL query)     | FR-AUTH-008   |
| P1-FE-024 | Create `pages/settings/profile.vue` — name, email, avatar upload, password change     | FR-SET-001    |
| P1-FE-025 | Create `pages/settings/business.vue` — company name, address, SIRET, APE, VAT, logo  | FR-SET-002    |
| P1-FE-026 | Create `pages/settings/security.vue` — 2FA enable/disable with QR code display        | FR-AUTH-006   |
| P1-FE-027 | Implement `useAuth` store: login(), register(), logout(), refreshToken(), user state, isAuthenticated | §6.2.2 |
| P1-FE-028 | Implement `useApi` composable: auto-attach Bearer token, intercept 401 → attempt refresh → retry, redirect to login on refresh failure | §6.2.2 |
| P1-FE-029 | Implement `auth` route middleware: redirect to `/auth/login` if not authenticated     | §6.2.2        |
| P1-FE-030 | Implement `guest` route middleware: redirect to `/` if already authenticated           | §6.2.2        |
| P1-FE-031 | Create toast notification system (success/error/warning/info, auto-dismiss 5s)        | NFR-USA-004   |

#### 4.2.6 Front-end Tests

| Test File                                    | Test Cases                                                  |
|----------------------------------------------|-------------------------------------------------------------|
| `tests/unit/stores/auth.test.ts`             | login sets token + user, logout clears state, refresh updates token |
| `tests/unit/composables/useApi.test.ts`      | Attaches bearer token, retries on 401, redirects on refresh fail |
| `tests/components/auth/LoginForm.test.ts`    | Renders fields, validates required, calls login action, shows 2FA input |
| `tests/components/auth/RegisterForm.test.ts` | Renders fields, validates password complexity, calls register action |
| `tests/e2e/auth/login.spec.ts`              | Full login flow, invalid credentials, account lockout message |
| `tests/e2e/auth/register.spec.ts`           | Full registration flow, navigate to login after success      |

#### 4.2.7 Deliverables Checklist

- [ ] User can register with email and password.
- [ ] User can log in and receive JWT access + refresh tokens.
- [ ] Authenticated routes return 401 for unauthenticated requests.
- [ ] Password reset flow works end-to-end (email arrives in Mailpit).
- [ ] 2FA can be enabled, and login requires TOTP code when active.
- [ ] Account locks after 5 failed login attempts.
- [ ] User can update profile and business information.
- [ ] All auth API tests pass with >= 80% coverage.

---

### 4.3 Sprint 3 — Client Management (Weeks 4–5)

#### 4.3.1 Objectives
- Complete client CRUD with soft-delete/restore.
- Contact management per client.
- Tag system with filtering.
- Activity timeline per client.
- Meilisearch indexing for clients.

#### 4.3.2 Database Migrations

| Migration                          | Description                                              |
|------------------------------------|----------------------------------------------------------|
| `create_clients_table`             | All columns from §8.2.2 (UUID PK, user_id FK, reference, company_name, first_name, last_name, email, phone, address, city, postal_code, country, siret, vat_number, website, notes, archived_at, timestamps). Indexes on user_id, reference, email, company_name, archived_at. |
| `create_contacts_table`           | id (UUID), client_id (FK), first_name, last_name, email, phone, role, is_primary (boolean, default false), notes, timestamps. Index on client_id. |
| `create_tags_table`               | id (UUID), user_id (FK), name (VARCHAR 50), color (VARCHAR 7, hex), timestamps. Unique constraint on (user_id, name). |
| `create_client_tag_table`         | client_id (FK), tag_id (FK). Composite PK on (client_id, tag_id). |
| `create_activities_table`         | id (UUID), user_id (FK), client_id (FK, nullable), subject_type (VARCHAR), subject_id (UUID, nullable), type ENUM('financial', 'project', 'communication', 'note', 'system'), description (TEXT), metadata (JSONB), created_at. Indexes on client_id, type, created_at. |

#### 4.3.3 Back-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P1-BE-040 | Create `Client` model — UUID, relationships (user, contacts, tags, activities, invoices, projects), scopes (active, archived, byTag), Searchable trait for Meilisearch | §8.2.2 |
| P1-BE-041 | Create `ClientFactory` with realistic Faker data                                      | §10.3.1       |
| P1-BE-042 | Create `ClientPolicy` — authorization: user can only access own clients               | NFR-SEC-004   |
| P1-BE-043 | Create `ClientController` — index (paginated, filterable, sortable), store, show, update, destroy (soft-delete), restore | FR-CLI-001 → 006 |
| P1-BE-044 | Create `StoreClientRequest` — validate all fields, auto-generate reference (CLI-YYYY-NNNN) | FR-CLI-001, 008 |
| P1-BE-045 | Create `UpdateClientRequest` — validate updated fields, email format, phone format    | FR-CLI-007    |
| P1-BE-046 | Create `ClientResource` / `ClientCollection` (JSON:API-inspired response format)      | §8.3.2        |
| P1-BE-047 | Implement reference number auto-generation service: `ReferenceGenerator` — pattern: `{PREFIX}-{YEAR}-{SEQUENTIAL_4_DIGITS}` | FR-CLI-008 |
| P1-BE-048 | Implement client list filtering: `?filter[status]=active\|archived`, `?filter[tags]=vip,tech`, `?filter[search]=keyword` | §8.3.1 |
| P1-BE-049 | Implement client list sorting: `?sort=company_name`, `?sort=-created_at`               | §8.3.1        |
| P1-BE-050 | Prevent hard deletion of clients with associated invoices or projects (return 422)     | FR-CLI-006    |
| P1-BE-051 | Create `Contact` model — UUID, relationships (client), scope (primary)                 | FR-CLI-009    |
| P1-BE-052 | Create `ContactController` — CRUD nested under client route                            | FR-CLI-009 → 012 |
| P1-BE-053 | Enforce single primary contact per client (auto-toggle on set)                         | FR-CLI-011    |
| P1-BE-054 | Create `Tag` model — UUID, relationships (clients via pivot), unique name per user     | FR-CLI-017    |
| P1-BE-055 | Create `TagController` — CRUD, assign/detach tags from clients                         | FR-CLI-017 → 020 |
| P1-BE-056 | Create `Activity` model — polymorphic subject, types enum, relationships               | FR-CLI-013    |
| P1-BE-057 | Create `ActivityService` — `log(client, type, description, subject?)` method            | FR-CLI-014    |
| P1-BE-058 | Create `ActivityController` — index with filtering by type, paginated                  | FR-CLI-016    |
| P1-BE-059 | Create model observers to auto-log activities: `ClientObserver` (created, updated, archived), `ContactObserver` (created) | FR-CLI-014 |
| P1-BE-060 | Configure Meilisearch index for Client: searchable attributes [company_name, first_name, last_name, email, reference, notes], filterable [tags, archived_at], sortable [company_name, created_at] | FR-SRC-002 |
| P1-BE-061 | Create `SearchController` — `GET /api/v1/search?q=&type=` querying Meilisearch         | FR-SRC-001 → 007 |

#### 4.3.4 Back-end Tests (TDD)

| Test File                                        | Test Cases                                                  |
|--------------------------------------------------|-------------------------------------------------------------|
| `tests/Unit/Models/ClientTest.php`               | Factory, relationships, scopes (active, archived, byTag), reference generation |
| `tests/Unit/Services/ReferenceGeneratorTest.php` | Sequential numbering, year rollover, prefix configuration    |
| `tests/Feature/Client/ListClientsTest.php`       | Pagination (25 per page), filter by status, filter by tags, sort by name, sort by date, only own clients |
| `tests/Feature/Client/CreateClientTest.php`      | Successful creation with all fields, reference auto-generated, email validation, phone validation, unauthorized user gets 403 |
| `tests/Feature/Client/ShowClientTest.php`        | Returns client with contacts/tags/recent activities, 404 for non-existent, 403 for other user's client |
| `tests/Feature/Client/UpdateClientTest.php`      | Update individual fields, email uniqueness scoped to user, validation errors |
| `tests/Feature/Client/DeleteClientTest.php`      | Soft-delete sets archived_at, restore clears archived_at, prevent hard-delete with invoices, activity logged |
| `tests/Feature/Contact/ContactCrudTest.php`      | Create contact, primary flag toggling, update, delete, list contacts for client |
| `tests/Feature/Tag/TagCrudTest.php`              | Create tag with color, unique name per user, assign to client, detach, filter clients by tag |
| `tests/Feature/Activity/ActivityTimelineTest.php`| Auto-logged on client create, filter by type, pagination, manual activity creation |
| `tests/Feature/Search/GlobalSearchTest.php`      | Search by client name, by email, typo tolerance, filter by type, empty query returns nothing |

#### 4.3.5 Front-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P1-FE-040 | Create `stores/clients.ts` Pinia store — CRUD actions, pagination state, filters      | §6.2.2        |
| P1-FE-041 | Create `pages/clients/index.vue` — data table with columns: reference, company, name, email, phone, tags, created date. Sortable, filterable, paginated. | FR-CLI-002 |
| P1-FE-042 | Create `pages/clients/create.vue` — form with all client fields, tag selector         | FR-CLI-001    |
| P1-FE-043 | Create `pages/clients/[id].vue` — client detail page with tabs: Overview, Contacts, Timeline, Projects (empty), Finances (empty) | FR-CLI-003 |
| P1-FE-044 | Create `pages/clients/[id]/edit.vue` — edit form pre-filled with client data          | FR-CLI-004    |
| P1-FE-045 | Create `components/clients/ClientContactList.vue` — list contacts, add/edit/delete inline or via drawer | FR-CLI-009 → 012 |
| P1-FE-046 | Create `components/clients/ClientTimeline.vue` — chronological activity feed with type icons and filter chips | FR-CLI-013 → 016 |
| P1-FE-047 | Create `components/clients/ClientTagSelector.vue` — multi-select dropdown with color dots, create new tag inline | FR-CLI-017 → 019 |
| P1-FE-048 | Create `components/common/ConfirmationModal.vue` — reusable for destructive actions   | NFR-USA-002   |
| P1-FE-049 | Implement soft-delete UI: archive button with confirmation, "Archived" badge, restore action | FR-CLI-005 |
| P1-FE-050 | Implement client list filter bar: status toggle (active/archived/all), tag filter multi-select, search input | FR-CLI-002, 019 |
| P1-FE-051 | Create `components/search/CommandPalette.vue` — Ctrl+K triggered modal, debounced search, results grouped by type, keyboard navigation | FR-SRC-001, 005 |
| P1-FE-052 | Integrate command palette into default layout (accessible from any page)              | FR-SRC-001    |
| P1-FE-053 | Implement empty states for: no clients, no contacts, no activities, no search results | NFR-USA-008   |

#### 4.3.6 Front-end Tests

| Test File                                          | Test Cases                                                |
|----------------------------------------------------|-----------------------------------------------------------|
| `tests/unit/stores/clients.test.ts`                | Fetch clients, create/update/delete, pagination, filters   |
| `tests/components/clients/ClientContactList.test.ts` | Render contacts, add new, set primary, delete            |
| `tests/components/clients/ClientTimeline.test.ts`  | Render activities, filter by type, empty state             |
| `tests/components/clients/ClientTagSelector.test.ts` | Select tags, create inline, remove tag                  |
| `tests/components/search/CommandPalette.test.ts`   | Opens on Ctrl+K, searches on input, navigates results, closes on Escape |
| `tests/e2e/clients/client-crud.spec.ts`            | Create client, view in list, edit, archive, restore        |
| `tests/e2e/clients/client-contacts.spec.ts`        | Add contact, set primary, delete contact                   |
| `tests/e2e/search/global-search.spec.ts`           | Open palette, search client name, navigate to result       |

---

### 4.4 Sprint 4 — Dashboard & Phase Validation (Weeks 5–6)

#### 4.4.1 Objectives
- Dashboard with client count, recent activity, and basic metrics.
- Settings pages finalized.
- Full E2E test suite for Phase 1 flows.
- Coverage validation >= 80%.
- Documentation and cleanup.

#### 4.4.2 Back-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P1-BE-070 | Create `DashboardController` — returns aggregated metrics (total clients, active clients, clients created this month, recent activities) | FR-DASH-002 → 005 |
| P1-BE-071 | Create `DashboardService` — cacheable metric calculations with Redis (TTL: 5 minutes) | NFR-PERF-008  |
| P1-BE-072 | Implement upcoming deadlines placeholder (returns empty until Phase 2 adds projects)  | FR-DASH-005   |
| P1-BE-073 | Add CSV import endpoint for clients: `POST /api/v1/clients/import` — parse CSV, validate rows, create clients, return success/error report | FR-SET-007 |
| P1-BE-074 | Add CSV export endpoint for clients: `GET /api/v1/clients/export` — stream CSV download | FR-SET-008 |
| P1-BE-075 | Review and complete all PHPDoc annotations on public methods                          | NFR-MNT-003   |
| P1-BE-076 | Run `phpstan` at level 8, fix all reported issues                                    | §6.2.1        |
| P1-BE-077 | Run `pest --coverage`, ensure >= 80% line coverage                                   | §10.2         |

#### 4.4.3 Front-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P1-FE-060 | Create `pages/index.vue` (Dashboard) — widget grid layout                             | FR-DASH-001   |
| P1-FE-061 | Create `components/dashboard/MetricCard.vue` — icon, label, value, trend indicator    | FR-DASH-002, 003 |
| P1-FE-062 | Create `components/dashboard/RecentActivityWidget.vue` — last 5 activities with links | FR-DASH-004   |
| P1-FE-063 | Create `components/dashboard/UpcomingDeadlinesWidget.vue` — placeholder for Phase 2   | FR-DASH-005   |
| P1-FE-064 | Implement skeleton loaders for dashboard widgets (independent loading)                | FR-DASH-008, NFR-USA-006 |
| P1-FE-065 | Implement responsive layout for dashboard (1 col mobile, 2 col tablet, 3 col desktop) | NFR-USA-001 |
| P1-FE-066 | Implement dark mode toggle with `dark:` Tailwind classes, persist preference in localStorage | §7.1 |
| P1-FE-067 | Implement CSV import UI (file upload, preview, confirm) on clients page               | FR-SET-007    |
| P1-FE-068 | Run Vitest coverage, ensure >= 80%                                                    | §10.2         |
| P1-FE-069 | Run Lighthouse audit, address issues (target: Performance > 90, Accessibility > 90)   | §10.3.3       |

#### 4.4.4 Deliverables Checklist

- [ ] Dashboard loads with client metrics and recent activity.
- [ ] All dashboard widgets load independently (no full-page blocking).
- [ ] Dark mode toggle works and persists.
- [ ] CSV import creates clients from uploaded file.
- [ ] CSV export downloads client data.
- [ ] Command palette (Ctrl+K) searches clients from any page.
- [ ] Back-end test coverage >= 80%.
- [ ] Front-end test coverage >= 80%.
- [ ] PHPStan level 8 passes with zero errors.
- [ ] ESLint + Prettier pass with zero errors.
- [ ] CI pipeline passes on all checks.
- [ ] All E2E tests pass.

---

## 5. API Endpoints Delivered in Phase 1

| Method | Endpoint                            | Controller              |
|--------|-------------------------------------|-------------------------|
| GET    | `/api/v1/health`                    | HealthController        |
| POST   | `/api/v1/auth/register`             | AuthController          |
| POST   | `/api/v1/auth/login`                | AuthController          |
| POST   | `/api/v1/auth/logout`               | AuthController          |
| POST   | `/api/v1/auth/refresh`              | AuthController          |
| POST   | `/api/v1/auth/forgot-password`      | AuthController          |
| POST   | `/api/v1/auth/reset-password`       | AuthController          |
| POST   | `/api/v1/auth/2fa/enable`           | TwoFactorController     |
| POST   | `/api/v1/auth/2fa/verify`           | TwoFactorController     |
| DELETE | `/api/v1/auth/2fa/disable`          | TwoFactorController     |
| GET    | `/api/v1/settings`                  | UserSettingsController  |
| PUT    | `/api/v1/settings`                  | UserSettingsController  |
| POST   | `/api/v1/settings/avatar`           | UserSettingsController  |
| GET    | `/api/v1/clients`                   | ClientController        |
| POST   | `/api/v1/clients`                   | ClientController        |
| GET    | `/api/v1/clients/{id}`              | ClientController        |
| PUT    | `/api/v1/clients/{id}`              | ClientController        |
| DELETE | `/api/v1/clients/{id}`              | ClientController        |
| POST   | `/api/v1/clients/{id}/restore`      | ClientController        |
| POST   | `/api/v1/clients/import`            | ClientImportController  |
| GET    | `/api/v1/clients/export`            | ClientExportController  |
| GET    | `/api/v1/clients/{id}/contacts`     | ContactController       |
| POST   | `/api/v1/clients/{id}/contacts`     | ContactController       |
| PUT    | `/api/v1/contacts/{id}`             | ContactController       |
| DELETE | `/api/v1/contacts/{id}`             | ContactController       |
| GET    | `/api/v1/clients/{id}/timeline`     | ActivityController      |
| POST   | `/api/v1/clients/{id}/timeline`     | ActivityController      |
| GET    | `/api/v1/tags`                      | TagController           |
| POST   | `/api/v1/tags`                      | TagController           |
| PUT    | `/api/v1/tags/{id}`                 | TagController           |
| DELETE | `/api/v1/tags/{id}`                 | TagController           |
| GET    | `/api/v1/search`                    | SearchController        |
| GET    | `/api/v1/dashboard`                 | DashboardController     |

---

## 6. Exit Criteria

All of the following MUST be satisfied before Phase 2 begins:

| #  | Criterion                                                                           | Validated |
|----|-------------------------------------------------------------------------------------|-----------|
| 1  | All Docker services start and pass health checks                                    | [ ]       |
| 2  | Authentication flow complete (register, login, logout, refresh, reset, 2FA)         | [ ]       |
| 3  | Client CRUD fully operational with soft-delete/restore                               | [ ]       |
| 4  | Contact management functional per client                                             | [ ]       |
| 5  | Tags assignable to clients, filterable in list                                       | [ ]       |
| 6  | Activity timeline populated automatically on client/contact events                   | [ ]       |
| 7  | Global search returns clients via Meilisearch with typo tolerance                   | [ ]       |
| 8  | Dashboard renders client metrics and recent activity                                 | [ ]       |
| 9  | CSV import/export operational for clients                                            | [ ]       |
| 10 | Back-end test coverage >= 80% (verified by CI)                                       | [ ]       |
| 11 | Front-end test coverage >= 80% (verified by CI)                                      | [ ]       |
| 12 | PHPStan level 8 — zero errors                                                       | [ ]       |
| 13 | ESLint + Prettier — zero errors                                                     | [ ]       |
| 14 | All E2E tests pass                                                                   | [ ]       |
| 15 | CI pipeline fully green on `main`                                                    | [ ]       |
| 16 | All commits follow Conventional Commits format                                       | [ ]       |

---

## 7. Risks Specific to Phase 1

| Risk                                                     | Mitigation                                                    |
|----------------------------------------------------------|---------------------------------------------------------------|
| Docker environment inconsistency across OS               | Pin exact image versions; document known issues in README      |
| Meilisearch indexing delay on first large import         | Index in batches via queued job; test with 1000+ records       |
| JWT refresh race condition (multiple tabs)               | Implement token refresh queue/lock on front-end                |
| Tailwind CSS purge removing needed classes               | Use `safelist` for dynamic classes; test production build      |
| PHPStan level 8 too restrictive for initial development  | Start at level 6, progressively increase; configure baselines  |

---

## 8. Dependencies on External Services

| Service     | Phase 1 Usage                        | Fallback if Unavailable         |
|-------------|--------------------------------------|---------------------------------|
| PostgreSQL  | Primary data store                   | None (required)                 |
| Redis       | Cache, sessions, queue               | Database fallback (degraded)    |
| Meilisearch | Client search                        | PostgreSQL `pg_trgm` fallback   |
| Mailpit     | Dev email testing (password reset)   | Log driver (emails to log file) |

---

*End of Phase 1 — Foundation and Core CRM*
