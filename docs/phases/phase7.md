# Phase 7 — Accounting Integration, Public API & Prospect Pipeline (v1.3)

| Field               | Value                                              |
|---------------------|----------------------------------------------------|
| **Phase**           | 7 of 7                                             |
| **Name**            | Accounting Integration, Public API & Prospect Pipeline |
| **Duration**        | Weeks 41–51 (11 weeks)                             |
| **Milestone**       | M7 — v1.3.0 Release                               |
| **PRD Sections**    | §4.9 FR-ACC, §4.10 FR-WBH, §4.11 FR-LEAD          |
| **Prerequisite**    | Phase 6 fully completed and validated              |
| **Status**          | Planned                                            |

---

## 1. Phase Objectives

| ID       | Objective                                                                                           |
|----------|-----------------------------------------------------------------------------------------------------|
| P7-OBJ-1 | Deliver FEC-compliant accounting export and VAT declaration report for French tax compliance        |
| P7-OBJ-2 | Provide accounting software export (Pennylane/Sage-compatible CSV) and fiscal year closing summary  |
| P7-OBJ-3 | Expose a secure Public API via Personal Access Tokens with scope-based authorization                |
| P7-OBJ-4 | Deliver outbound webhooks with HMAC-SHA256 signatures, retry logic, and delivery logs              |
| P7-OBJ-5 | Deliver a full prospect/lead pipeline with Kanban view, activity log, and client conversion         |
| P7-OBJ-6 | Maintain >= 80% test coverage on both back-end and front-end                                       |

---

## 2. Entry Criteria

- Phase 6 exit criteria 100% satisfied.
- All Phase 6 CI checks green on `main`.
- v1.2.0 tagged and deployed to production.
- Client portal, Stripe payments, and expense tracking stable in production.

---

## 3. Scope — Requirement Traceability

| Feature                                    | Priority | Included |
|--------------------------------------------|----------|----------|
| FEC accounting export (French compliance)  | High     | Yes      |
| VAT declaration report (CA3-style)         | High     | Yes      |
| Accounting software CSV export             | Medium   | Yes      |
| Personal Access Tokens (Public API)        | High     | Yes      |
| Outbound webhooks with retry & HMAC        | High     | Yes      |
| OpenAPI 3.1 spec                           | Medium   | Yes      |
| Prospect/lead pipeline (Kanban)            | High     | Yes      |
| Lead-to-client conversion                  | High     | Yes      |
| Pipeline analytics                         | Medium   | Yes      |

---

## 4. Detailed Sprint Breakdown

### 4.1 Sprint 24 — Accounting & Tax Compliance (Weeks 41–44)

#### 4.1.1 Database Migrations

| Migration                              | Description                                                                                    |
|----------------------------------------|-----------------------------------------------------------------------------------------------|
| `add_accounting_settings_to_settings`  | Add to existing user settings table: `accounting_journal_sales` (VARCHAR 10, default 'VTE'), `accounting_journal_purchases` (VARCHAR 10, default 'ACH'), `accounting_journal_bank` (VARCHAR 10, default 'BQ'), `accounting_auxiliary_prefix` (VARCHAR 10, nullable), `fiscal_year_start_month` (TINYINT, default 1 — January). Index: user_id (already exists). |

> No new standalone table is required for accounting — FEC and VAT reports are computed on-the-fly from invoices, credit notes, expenses, and payment records.

#### 4.1.2 Back-end Tasks

| ID        | Task                                                                                        | PRD Ref    |
|-----------|---------------------------------------------------------------------------------------------|------------|
| P7-BE-001 | Create `FecExportService` — build FEC-compliant semicolon-delimited file from invoices, credit notes, payments, and expenses: | FR-ACC-001 |
|           | — Invoice issued → Debit 411xxx (client) / Credit 706xxx (revenue) + Credit 44571 (TVA collectée) | |
|           | — Invoice payment → Debit 512 (bank) / Credit 411xxx (client) | |
|           | — Credit note → reverse of invoice entry | |
|           | — Expense → Debit 6xx (charge category) + Debit 44566 (TVA déductible) / Credit 401 or 512 | |
|           | — Columns: JournalCode, JournalLib, EcritureNum, EcritureDate, CompteNum, CompteLib, CompAuxNum, CompAuxLib, PieceRef, PieceDate, EcritureLib, Debit, Credit, EcritureLet, DateLet, ValidDate, Montantdevise, Idevise | |
| P7-BE-002 | Create `FecExportController` — `GET /api/v1/accounting/fec` (query params: fiscal_year or date_from/date_to) — streams UTF-8 file download | FR-ACC-001 |
| P7-BE-003 | Create `VatDeclarationService` — compute per-period VAT report:                             | FR-ACC-006 |
|           | — TVA collectée: group by rate (0%, 5.5%, 10%, 20%) from invoices and credit notes         | |
|           | — TVA déductible: sum from eligible expenses with VAT                                       | |
|           | — Net TVA due = collectée − déductible                                                      | |
|           | — Period breakdown (monthly or quarterly)                                                    | |
| P7-BE-004 | Create `VatDeclarationController` — `GET /api/v1/accounting/vat` (params: period_type, year) + PDF and CSV export | FR-ACC-006 |
| P7-BE-005 | Create `AccountingExportService` — generate Pennylane-compatible CSV and Sage-compatible CSV: | FR-ACC-009 |
|           | — Configurable column mapping per software target                                            | |
|           | — Include: date, piece_ref, account_code, label, debit, credit, currency, client_ref        | |
| P7-BE-006 | Create `AccountingExportController` — `GET /api/v1/accounting/export` (params: format=pennylane|sage|generic, date range) | FR-ACC-009 |
| P7-BE-007 | Create `FiscalYearSummaryService` — compute closing summary:                                | FR-ACC-010 |
|           | — Total revenue (paid invoices in base currency)                                             | |
|           | — Total expenses (in base currency)                                                          | |
|           | — Net profit, margin percentage                                                              | |
|           | — TVA position (net due)                                                                     | |
|           | — Outstanding receivables (unpaid invoices)                                                  | |
| P7-BE-008 | Create `FiscalYearSummaryController` — `GET /api/v1/accounting/fiscal-year` (param: year)  | FR-ACC-010 |
| P7-BE-009 | Update `AccountingSettingsController` — CRUD accounting journal codes and fiscal year start | FR-ACC-011 |

#### 4.1.3 Back-end Tests (TDD)

| Test File                                                        | Test Cases                                                              |
|------------------------------------------------------------------|-------------------------------------------------------------------------|
| `tests/Unit/Services/FecExportServiceTest.php`                   | Invoice entry (debit/credit), payment entry, credit note entry, expense entry, date filtering, correct French number format (comma decimal), UTF-8 encoding |
| `tests/Unit/Services/VatDeclarationServiceTest.php`              | TVA collectée by rate, TVA déductible, net VAT due, monthly split, quarterly split, zero-VAT invoice handling |
| `tests/Unit/Services/AccountingExportServiceTest.php`            | Pennylane format columns, Sage format columns, date range filtering, empty period handling |
| `tests/Unit/Services/FiscalYearSummaryServiceTest.php`           | Revenue total, expense total, net profit, margin, outstanding, multi-currency with base conversion |
| `tests/Feature/Accounting/FecExportTest.php`                     | Export full year, export date range, file is valid UTF-8, correct number of lines, unauthorized access rejected |
| `tests/Feature/Accounting/VatDeclarationTest.php`                | Correct totals per period, CSV export, PDF export, empty period |
| `tests/Feature/Accounting/AccountingExportTest.php`              | Pennylane CSV downloads, Sage CSV downloads, custom date range |

#### 4.1.4 Front-end Tasks

| ID        | Task                                                                                          | PRD Ref    |
|-----------|-----------------------------------------------------------------------------------------------|------------|
| P7-FE-001 | Create `app/accounting/page.tsx` — accounting hub:                                            | FR-ACC-001 |
|           | — Cards linking to FEC export, VAT declaration, accounting export, fiscal year summary        | |
|           | — Current fiscal year KPIs: revenue, expenses, net profit, TVA due                           | |
| P7-FE-002 | Create `app/accounting/fec/page.tsx` — FEC export wizard:                                    | FR-ACC-001 |
|           | — Fiscal year selector or custom date range                                                  | |
|           | — Preview of entry count before download                                                     | |
|           | — Download button triggering file stream                                                      | |
|           | — Warning if SIRET/SIREN not configured in business settings                                  | |
| P7-FE-003 | Create `app/accounting/vat/page.tsx` — VAT declaration report:                               | FR-ACC-006 |
|           | — Period selector (month or quarter)                                                          | |
|           | — Summary table: TVA collectée by rate, TVA déductible, net due                              | |
|           | — Monthly timeline chart                                                                      | |
|           | — PDF and CSV export buttons                                                                  | |
| P7-FE-004 | Create `app/accounting/export/page.tsx` — accounting software export:                        | FR-ACC-009 |
|           | — Target software selector (Pennylane, Sage, Generic CSV)                                    | |
|           | — Date range picker                                                                            | |
|           | — Column preview table                                                                         | |
|           | — Download button                                                                              | |
| P7-FE-005 | Create `app/accounting/fiscal-year/page.tsx` — fiscal year closing summary:                  | FR-ACC-010 |
|           | — Fiscal year selector                                                                        | |
|           | — Summary cards: revenue, expenses, profit, margin, TVA due, outstanding                     | |
|           | — Printable / PDF export                                                                      | |
| P7-FE-006 | Create `app/settings/accounting/page.tsx` — accounting settings:                             | FR-ACC-011 |
|           | — Journal codes configuration (VTE, ACH, BQ)                                                 | |
|           | — Fiscal year start month selector                                                            | |
| P7-FE-007 | Add Accounting entry to sidebar navigation (under Reports section)                           | FR-ACC-001 |

#### 4.1.5 Front-end Tests

| Test File                                                        | Test Cases                                                  |
|------------------------------------------------------------------|-------------------------------------------------------------|
| `tests/unit/stores/accounting.test.ts`                           | FEC fetch, VAT fetch, fiscal year fetch, error handling     |
| `tests/e2e/accounting/fec-export.spec.ts`                        | Select fiscal year, download FEC, verify file not empty     |
| `tests/e2e/accounting/vat-declaration.spec.ts`                   | View monthly VAT, view quarterly, export CSV, export PDF    |
| `tests/e2e/accounting/accounting-export.spec.ts`                 | Select Pennylane format, download, verify column headers    |

---

### 4.2 Sprint 25 — Public API & Outbound Webhooks (Weeks 45–47)

#### 4.2.1 Database Migrations

| Migration                              | Description                                                                                    |
|----------------------------------------|-----------------------------------------------------------------------------------------------|
| `create_webhook_endpoints_table`       | id (UUID), user_id (FK), name (VARCHAR 100), url (VARCHAR 500), secret (VARCHAR 64, UNIQUE, encrypted at rest), events (JSONB array of subscribed event names), is_active (BOOLEAN, default true), last_triggered_at (TIMESTAMP, nullable), timestamps. Index: user_id. |
| `create_webhook_deliveries_table`      | id (UUID), webhook_endpoint_id (FK), event (VARCHAR 100), payload (JSONB), response_status (SMALLINT, nullable), response_body (TEXT, nullable), attempt_count (SMALLINT, default 1), delivered_at (TIMESTAMP, nullable), failed_at (TIMESTAMP, nullable), next_retry_at (TIMESTAMP, nullable), created_at. Indexes: webhook_endpoint_id, event, created_at. |

> Personal Access Tokens use the existing Laravel Sanctum `personal_access_tokens` table. No additional migration is required; token abilities (scopes) are stored in the existing `abilities` column (JSON).

#### 4.2.2 Back-end Tasks

| ID        | Task                                                                                          | PRD Ref    |
|-----------|-----------------------------------------------------------------------------------------------|------------|
| P7-BE-020 | Create `PersonalAccessTokenController` (admin-side) — manage API tokens:                     | FR-WBH-001 |
|           | — `GET /api/v1/settings/api-tokens` — list existing tokens (name, scopes, last used, expiry) | |
|           | — `POST /api/v1/settings/api-tokens` — create named token with scopes + optional expiry, return token value once | |
|           | — `DELETE /api/v1/settings/api-tokens/{id}` — revoke token immediately                       | |
| P7-BE-021 | Define PAT scopes: `read:clients`, `write:clients`, `read:invoices`, `write:invoices`, `read:expenses`, `write:expenses`, `read:projects`, `read:leads`, `write:leads`, `read:reports` | FR-WBH-002 |
| P7-BE-022 | Create `WebhookEndpoint` model — relationships (user, deliveries), scopes (active)           | FR-WBH-006 |
| P7-BE-023 | Create `WebhookDelivery` model — relationships (endpoint), scopes (pending, failed, delivered) | FR-WBH-009 |
| P7-BE-024 | Create `WebhookEndpointController` (admin-side):                                             | FR-WBH-006 |
|           | — `GET /api/v1/settings/webhooks` — list endpoints                                           | |
|           | — `POST /api/v1/settings/webhooks` — register endpoint (URL, name, events)                   | |
|           | — `PUT /api/v1/settings/webhooks/{id}` — update endpoint                                     | |
|           | — `DELETE /api/v1/settings/webhooks/{id}` — delete endpoint                                  | |
|           | — `POST /api/v1/settings/webhooks/{id}/test` — send sample payload                           | |
| P7-BE-025 | Create `WebhookDispatchService` — core delivery logic:                                       | FR-WBH-007 |
|           | — Build JSON payload with `event`, `created_at`, `data` object                               | |
|           | — Sign payload: `X-Koomky-Signature: sha256=HMAC(secret, payload_body)`                      | |
|           | — Include headers: `X-Koomky-Event`, `X-Koomky-Delivery` (UUID), `Content-Type: application/json` | |
|           | — Record `WebhookDelivery` before dispatch                                                    | |
| P7-BE-026 | Create `WebhookDispatchJob` — queued delivery with retry:                                    | FR-WBH-009 |
|           | — Attempt HTTP POST to endpoint URL (timeout 10s)                                            | |
|           | — On success (2xx): mark delivery as delivered, update `last_triggered_at`                   | |
|           | — On failure: increment `attempt_count`, schedule `next_retry_at` with exponential backoff (1s, 5s, 30s, 5min, 30min), max 5 attempts | |
|           | — After 5 failures: mark delivery as `failed`, disable endpoint if last 10 consecutive deliveries failed | |
| P7-BE-027 | Create `WebhookDeliveryController` (admin-side):                                             | FR-WBH-010 |
|           | — `GET /api/v1/settings/webhooks/{id}/deliveries` — paginated delivery log with status, response code, attempt count | |
|           | — `POST /api/v1/settings/webhooks/{id}/deliveries/{deliveryId}/retry` — manually re-dispatch | |
| P7-BE-028 | Integrate `WebhookDispatchService` into application events — dispatch for:                   | FR-WBH-008 |
|           | — `invoice.created`, `invoice.sent`, `invoice.paid`, `invoice.overdue`, `invoice.cancelled`  | |
|           | — `quote.sent`, `quote.accepted`, `quote.rejected`, `quote.expired`                          | |
|           | — `expense.created`, `expense.updated`, `expense.deleted`                                    | |
|           | — `project.completed`, `project.cancelled`                                                   | |
|           | — `payment.received` (portal payment)                                                         | |
|           | — `lead.created`, `lead.status_changed`, `lead.converted`                                    | |
| P7-BE-029 | Integrate `dedoc/scramble` (or `l5-swagger`) to auto-generate OpenAPI 3.1 spec from route annotations — expose at `GET /api/docs` and `GET /api/docs.json` | FR-WBH-013 |
| P7-BE-030 | Scope-guard middleware: verify PAT abilities on API routes (read-only tokens cannot POST/PUT/DELETE) | FR-WBH-002 |

#### 4.2.3 Back-end Tests (TDD)

| Test File                                                             | Test Cases                                                                      |
|-----------------------------------------------------------------------|---------------------------------------------------------------------------------|
| `tests/Feature/API/PersonalAccessTokenTest.php`                       | Create token with scopes, token authenticates, read-only token cannot write, revoke token, expired token rejected |
| `tests/Unit/Services/WebhookDispatchServiceTest.php`                  | Payload structure, HMAC signature correct, delivery recorded before dispatch, test payload format |
| `tests/Feature/Webhooks/WebhookEndpointTest.php`                      | Create endpoint, update, delete, list, test delivery sends sample                |
| `tests/Feature/Webhooks/WebhookDispatchTest.php`                      | Invoice paid triggers dispatch to subscribed endpoint, unsubscribed event not dispatched, endpoint not subscribed not called |
| `tests/Feature/Webhooks/WebhookRetryTest.php`                         | Failed delivery retried up to 5 times, exponential backoff timing, marked failed after max attempts |
| `tests/Feature/Webhooks/WebhookDeliveryLogTest.php`                   | Delivery log paginated, manual retry re-dispatches, response body stored        |

#### 4.2.4 Front-end Tasks

| ID        | Task                                                                                          | PRD Ref    |
|-----------|-----------------------------------------------------------------------------------------------|------------|
| P7-FE-020 | Create `app/settings/api-tokens/page.tsx` — manage Personal Access Tokens:                   | FR-WBH-001 |
|           | — Table: name, scopes, last used, expires at, revoke action                                  | |
|           | — "Create token" dialog: name, scope checkboxes, optional expiry date                       | |
|           | — One-time token display with copy-to-clipboard and warning banner                           | |
| P7-FE-021 | Create `app/settings/webhooks/page.tsx` — manage webhook endpoints:                          | FR-WBH-006 |
|           | — Table: name, URL, subscribed events count, active status, last triggered, actions          | |
|           | — "Add endpoint" form: name, URL, event checkboxes                                           | |
|           | — Secret display on creation (one-time, copy-to-clipboard)                                   | |
|           | — Test delivery button                                                                        | |
| P7-FE-022 | Create `app/settings/webhooks/[id]/deliveries/page.tsx` — delivery log:                      | FR-WBH-010 |
|           | — Table: event name, timestamp, attempt count, response status, status badge                 | |
|           | — Expandable row: request payload (JSON), response body                                       | |
|           | — "Retry" action on failed deliveries                                                         | |
| P7-FE-023 | Create `components/settings/webhook-form.tsx` — endpoint form with event selector            | FR-WBH-006 |
| P7-FE-024 | Create `components/settings/api-token-form.tsx` — token creation form with scope checkboxes  | FR-WBH-001 |
| P7-FE-025 | Add "API & Webhooks" section to settings sidebar                                              | FR-WBH-001 |

#### 4.2.5 Front-end Tests

| Test File                                                        | Test Cases                                                  |
|------------------------------------------------------------------|-------------------------------------------------------------|
| `tests/components/settings/api-token-form.test.ts`               | Renders scope checkboxes, submits with correct data, shows one-time token |
| `tests/components/settings/webhook-form.test.ts`                 | Renders event checkboxes, URL validation, submits           |
| `tests/e2e/settings/api-tokens.spec.ts`                          | Create token, copy token, revoke token, verify revoked token rejected |
| `tests/e2e/settings/webhooks.spec.ts`                            | Create endpoint, test delivery, view delivery log, retry failed |

---

### 4.3 Sprint 26 — Prospect & Lead Pipeline (Weeks 48–51)

#### 4.3.1 Database Migrations

| Migration                          | Description                                                                                    |
|------------------------------------|-----------------------------------------------------------------------------------------------|
| `create_leads_table`               | id (UUID), user_id (FK), company_name (VARCHAR 255, nullable), first_name (VARCHAR 100), last_name (VARCHAR 100), email (VARCHAR 255, nullable), phone (VARCHAR 50, nullable), source ENUM('manual', 'referral', 'website', 'campaign', 'other', default 'manual'), status ENUM('new', 'contacted', 'qualified', 'proposal_sent', 'negotiating', 'won', 'lost', default 'new'), estimated_value (DECIMAL 12,2, nullable), currency (VARCHAR 3, default 'EUR'), probability (TINYINT UNSIGNED, nullable, 0–100), expected_close_date (DATE, nullable), notes (TEXT, nullable), lost_reason (VARCHAR 500, nullable), won_client_id (FK → clients, nullable), converted_at (TIMESTAMP, nullable), pipeline_position (SMALLINT, default 0), timestamps. Indexes: user_id, status, expected_close_date, (user_id, status). |
| `create_lead_activities_table`     | id (UUID), lead_id (FK), type ENUM('note', 'email_sent', 'call', 'meeting', 'follow_up'), content (TEXT, nullable), scheduled_at (TIMESTAMP, nullable), completed_at (TIMESTAMP, nullable), timestamps. Index: lead_id, created_at. |

#### 4.3.2 Back-end Tasks

| ID        | Task                                                                                          | PRD Ref     |
|-----------|-----------------------------------------------------------------------------------------------|-------------|
| P7-BE-040 | Create `Lead` model — relationships (user, activities, wonClient), scopes (byStatus, bySource, openDeals, closedDeals), Searchable | FR-LEAD-001 |
| P7-BE-041 | Create `LeadActivity` model — relationships (lead)                                           | FR-LEAD-008 |
| P7-BE-042 | Create `LeadFactory` and `LeadActivityFactory`                                                | §10.3.1     |
| P7-BE-043 | Create `LeadPolicy` — user owns lead                                                          | NFR-SEC-004 |
| P7-BE-044 | Create `LeadController` — full CRUD with filtering:                                           | FR-LEAD-001 |
|           | — `GET /api/v1/leads` — list with filters: status, source, date range, search                | |
|           | — `POST /api/v1/leads` — create lead                                                          | |
|           | — `GET /api/v1/leads/{id}` — lead detail with activities                                     | |
|           | — `PUT /api/v1/leads/{id}` — update lead                                                     | |
|           | — `DELETE /api/v1/leads/{id}` — delete lead                                                  | |
|           | — `PATCH /api/v1/leads/{id}/status` — transition status, enforce lost_reason when moving to `lost` | |
|           | — `PATCH /api/v1/leads/{id}/position` — reorder within column (update pipeline_position)     | |
| P7-BE-045 | Create `StoreLeadRequest` — validate email format, phone E.164, probability 0-100, estimated_value >= 0 | FR-LEAD-001 |
| P7-BE-046 | Create `LeadConversionService` — convert `won` lead to Client:                               | FR-LEAD-010 |
|           | — Create Client record pre-filled from lead (company_name, first_name, last_name, email, phone) | |
|           | — Set lead `won_client_id`, `converted_at`, status = `won`                                    | |
|           | — Log activity on new client timeline                                                         | |
| P7-BE-047 | Create `LeadConversionController` — `POST /api/v1/leads/{id}/convert` — returns created client | FR-LEAD-010 |
| P7-BE-048 | Create `LeadPipelineController` — `GET /api/v1/leads/pipeline` — groups leads by status, includes count and total estimated_value per column, ordered by pipeline_position | FR-LEAD-003 |
| P7-BE-049 | Create `LeadAnalyticsService` — compute pipeline analytics:                                  | FR-LEAD-013 |
|           | — Total pipeline value (open deals only)                                                      | |
|           | — Leads count by status                                                                       | |
|           | — Win rate = won / (won + lost) × 100 in period                                              | |
|           | — Average deal value (won deals)                                                              | |
|           | — Average time to close (created_at to converted_at on won deals)                            | |
|           | — Pipeline by source                                                                           | |
| P7-BE-050 | Create `LeadAnalyticsController` — `GET /api/v1/leads/analytics` (params: date_from, date_to, source) | FR-LEAD-013 |
| P7-BE-051 | Create `LeadActivityController` — nested under lead:                                         | FR-LEAD-008 |
|           | — `GET /api/v1/leads/{id}/activities` — chronological list                                   | |
|           | — `POST /api/v1/leads/{id}/activities` — log activity                                        | |
|           | — `DELETE /api/v1/leads/{id}/activities/{activityId}` — delete activity                      | |
| P7-BE-052 | Dispatch webhooks on lead events: `lead.created`, `lead.status_changed`, `lead.converted`    | FR-WBH-008  |
| P7-BE-053 | Configure Meilisearch index for Lead (searchable: company_name, first_name, last_name, email, notes) | FR-SRC-002 |
| P7-BE-054 | Add leads to `DataExportService` (GDPR full export)                                          | NFR-SEC-008 |

#### 4.3.3 Back-end Tests (TDD)

| Test File                                                        | Test Cases                                                              |
|------------------------------------------------------------------|-------------------------------------------------------------------------|
| `tests/Unit/Models/LeadTest.php`                                 | Factory, scopes (byStatus, openDeals), relationships (activities, wonClient) |
| `tests/Unit/Services/LeadConversionServiceTest.php`              | Client created from lead, data pre-filled, lead linked, already-converted lead rejected |
| `tests/Unit/Services/LeadAnalyticsServiceTest.php`               | Win rate correct, avg deal value, avg time to close, pipeline value by status, empty pipeline |
| `tests/Feature/Lead/LeadCrudTest.php`                            | Create, read, update, delete, filter by status/source, search, ownership enforced |
| `tests/Feature/Lead/LeadStatusTest.php`                          | Valid transitions, lost requires lost_reason, won triggers conversion eligibility, cannot delete won lead |
| `tests/Feature/Lead/LeadConversionTest.php`                      | Convert lead to client, client data matches lead, lead linked, duplicate conversion rejected |
| `tests/Feature/Lead/LeadPipelineTest.php`                        | Pipeline groups by status, correct counts and totals, position ordering   |
| `tests/Feature/Lead/LeadAnalyticsTest.php`                       | Correct win rate, avg deal, avg time, date range filtering               |
| `tests/Feature/Lead/LeadActivityTest.php`                        | Log activity, list activities, delete activity, ownership enforced       |

#### 4.3.4 Front-end Tasks

| ID        | Task                                                                                          | PRD Ref     |
|-----------|-----------------------------------------------------------------------------------------------|-------------|
| P7-FE-030 | Create `stores/leads.ts` Zustand store — CRUD, pipeline data, analytics data, activity log   | §6.2.2      |
| P7-FE-031 | Create `app/leads/page.tsx` — dual-view leads page:                                          | FR-LEAD-003 |
|           | — Toggle: Kanban pipeline view / List view                                                    | |
|           | — Kanban: columns for each status (new, contacted, qualified, proposal_sent, negotiating), drag-and-drop cards | |
|           | — List view: data table with status badge, company, contact, estimated value, probability, close date, source | |
|           | — Filters: status, source, date range, search                                                 | |
|           | — Quick-create button                                                                         | |
| P7-FE-032 | Create `app/leads/create/page.tsx` — lead creation form:                                     | FR-LEAD-001 |
|           | — Company name, contact (first name, last name), email, phone                                | |
|           | — Source selector, estimated value with currency selector, probability slider, expected close date | |
|           | — Notes textarea                                                                              | |
| P7-FE-033 | Create `app/leads/[id]/page.tsx` — lead detail:                                              | FR-LEAD-008 |
|           | — Header: status badge, estimated value, probability, close date                              | |
|           | — Status transition selector with confirmation for `lost`                                    | |
|           | — Activity timeline (type icon, content, timestamp)                                           | |
|           | — "Log activity" inline form (type, content, scheduled_at for follow_up)                     | |
|           | — "Convert to Client" button (visible only when status = `won`)                              | |
| P7-FE-034 | Create `app/leads/[id]/edit/page.tsx` — edit lead form                                       | FR-LEAD-001 |
| P7-FE-035 | Create `app/leads/analytics/page.tsx` — pipeline analytics:                                  | FR-LEAD-013 |
|           | — Summary cards: total pipeline value, win rate, avg deal, avg time to close                 | |
|           | — Funnel chart: leads count by stage                                                          | |
|           | — Source breakdown bar chart                                                                  | |
|           | — Win/loss trend over time                                                                    | |
|           | — Date range and source filters                                                               | |
| P7-FE-036 | Create `components/leads/lead-kanban.tsx` — Kanban board:                                    | FR-LEAD-004 |
|           | — Columns: new, contacted, qualified, proposal_sent, negotiating (not won/lost — those are terminal) | |
|           | — Card: company/contact name, estimated value, probability, close date badge, source chip     | |
|           | — Drag-and-drop between columns (dnd-kit), triggers PATCH /status                            | |
|           | — Column footer: total estimated value for column                                             | |
| P7-FE-037 | Create `components/leads/lead-activity-form.tsx` — inline activity logger                    | FR-LEAD-008 |
| P7-FE-038 | Create `components/leads/convert-to-client-dialog.tsx` — conversion confirmation:            | FR-LEAD-010 |
|           | — Shows pre-filled client data from lead                                                      | |
|           | — Confirm button → POST /convert → redirect to new client page                               | |
| P7-FE-039 | Add Leads entry to sidebar navigation (above Clients, with pipeline value badge)             | FR-LEAD-001 |
| P7-FE-040 | Add pipeline summary widget to dashboard:                                                     | FR-LEAD-013 |
|           | — Total pipeline value card                                                                   | |
|           | — Mini funnel: count of leads per active stage                                                | |
|           | — Win rate percentage                                                                         | |

#### 4.3.5 Front-end Tests

| Test File                                                        | Test Cases                                                  |
|------------------------------------------------------------------|-------------------------------------------------------------|
| `tests/unit/stores/leads.test.ts`                                | CRUD, pipeline fetch, analytics fetch, activity log         |
| `tests/components/leads/lead-kanban.test.ts`                     | Renders columns, renders cards, drag triggers status update, column totals |
| `tests/components/leads/convert-to-client-dialog.test.ts`        | Renders pre-filled data, confirm triggers conversion, redirect on success |
| `tests/e2e/leads/lead-crud.spec.ts`                              | Create lead, edit, view detail, delete, search              |
| `tests/e2e/leads/lead-pipeline.spec.ts`                          | View Kanban, drag card to next column, verify status updated, move to lost with reason |
| `tests/e2e/leads/lead-conversion.spec.ts`                        | Win lead, click convert, verify client created with lead data, lead linked |
| `tests/e2e/leads/lead-analytics.spec.ts`                         | View analytics, verify win rate, date range filter          |

---

## 5. API Endpoints Delivered in Phase 7

| Method | Endpoint                                                          | Controller                        |
|--------|-------------------------------------------------------------------|-----------------------------------|
| GET    | `/api/v1/accounting/fec`                                          | FecExportController               |
| GET    | `/api/v1/accounting/vat`                                          | VatDeclarationController          |
| GET    | `/api/v1/accounting/export`                                       | AccountingExportController        |
| GET    | `/api/v1/accounting/fiscal-year`                                  | FiscalYearSummaryController       |
| GET    | `/api/v1/settings/accounting`                                     | AccountingSettingsController      |
| PUT    | `/api/v1/settings/accounting`                                     | AccountingSettingsController      |
| GET    | `/api/v1/settings/api-tokens`                                     | PersonalAccessTokenController     |
| POST   | `/api/v1/settings/api-tokens`                                     | PersonalAccessTokenController     |
| DELETE | `/api/v1/settings/api-tokens/{id}`                                | PersonalAccessTokenController     |
| GET    | `/api/v1/settings/webhooks`                                       | WebhookEndpointController         |
| POST   | `/api/v1/settings/webhooks`                                       | WebhookEndpointController         |
| PUT    | `/api/v1/settings/webhooks/{id}`                                  | WebhookEndpointController         |
| DELETE | `/api/v1/settings/webhooks/{id}`                                  | WebhookEndpointController         |
| POST   | `/api/v1/settings/webhooks/{id}/test`                             | WebhookEndpointController         |
| GET    | `/api/v1/settings/webhooks/{id}/deliveries`                       | WebhookDeliveryController         |
| POST   | `/api/v1/settings/webhooks/{id}/deliveries/{deliveryId}/retry`    | WebhookDeliveryController         |
| GET    | `/api/v1/leads`                                                   | LeadController                    |
| POST   | `/api/v1/leads`                                                   | LeadController                    |
| GET    | `/api/v1/leads/pipeline`                                          | LeadPipelineController            |
| GET    | `/api/v1/leads/analytics`                                         | LeadAnalyticsController           |
| GET    | `/api/v1/leads/{id}`                                              | LeadController                    |
| PUT    | `/api/v1/leads/{id}`                                              | LeadController                    |
| DELETE | `/api/v1/leads/{id}`                                              | LeadController                    |
| PATCH  | `/api/v1/leads/{id}/status`                                       | LeadController                    |
| PATCH  | `/api/v1/leads/{id}/position`                                     | LeadController                    |
| POST   | `/api/v1/leads/{id}/convert`                                      | LeadConversionController          |
| GET    | `/api/v1/leads/{id}/activities`                                   | LeadActivityController            |
| POST   | `/api/v1/leads/{id}/activities`                                   | LeadActivityController            |
| DELETE | `/api/v1/leads/{id}/activities/{activityId}`                      | LeadActivityController            |
| GET    | `/api/docs`                                                       | OpenAPI UI (Scramble/Swagger)     |
| GET    | `/api/docs.json`                                                  | OpenAPI JSON spec                 |

---

## 6. Exit Criteria

| #  | Criterion                                                                                    | Validated |
|----|----------------------------------------------------------------------------------------------|-----------|
| 1  | FEC export generates a valid semicolon-delimited UTF-8 file for any fiscal year             | [x]       |
| 2  | FEC entries balance (debit = credit) for all transaction types                              | [x]       |
| 3  | VAT declaration report shows correct TVA collectée, déductible, and net due                | [x]       |
| 4  | VAT report exports as PDF and CSV                                                           | [x]       |
| 5  | Pennylane-compatible CSV export functional for invoices and expenses                       | [x]       |
| 6  | Fiscal year closing summary shows correct revenue, expenses, profit, and TVA position       | [x]       |
| 7  | Personal Access Tokens: create with scopes, authenticate API calls, revoke                 | [x]       |
| 8  | Scope enforcement: read-only token cannot POST/PUT/DELETE                                   | [x]       |
| 9  | Outbound webhook endpoints: register, update, delete, test delivery                        | [x]       |
| 10 | Webhook payloads include correct HMAC-SHA256 signature                                     | [x]       |
| 11 | Webhook events fired for all 14 documented event types                                     | [x]       |
| 12 | Webhook retry with exponential backoff, max 5 attempts, delivery log updated               | [x]       |
| 13 | Manual retry from delivery log functional                                                   | [x]       |
| 14 | OpenAPI 3.1 spec accessible at /api/docs with all authenticated endpoints documented        | [x]       |
| 15 | Lead pipeline: full CRUD, status transitions, lost_reason enforcement                      | [x]       |
| 16 | Lead Kanban view renders with drag-and-drop status update                                  | [x]       |
| 17 | Lead-to-client conversion pre-fills client form and links the lead                         | [x]       |
| 18 | Pipeline analytics: correct win rate, avg deal value, avg time to close                    | [x]       |
| 19 | Leads indexed in Meilisearch and searchable via global search                              | [x]       |
| 20 | Leads included in GDPR data export                                                         | [x]       |
| 21 | Dashboard updated with pipeline summary widget                                              | [x]       |
| 22 | Back-end test coverage >= 80%                                                               | [x]       |
| 23 | Front-end test coverage >= 80%                                                              | [x]       |
| 24 | CI pipeline fully green on `main`                                                           | [x]       |
| 25 | Version tagged as `v1.3.0` on GitHub                                                        | [x]       |

---

## 7. Risks Specific to Phase 7

| Risk                                                          | Mitigation                                                               |
|---------------------------------------------------------------|--------------------------------------------------------------------------|
| FEC format correctness — French tax authority requirements are strict | Cross-validate output against official FEC spec (LPF art. L47 A); test with a sample from a real accountant |
| Plan Comptable Général account mapping may not match user's chart of accounts | Make account codes configurable per category in accounting settings; provide sensible defaults |
| VAT computation complexity at multiple rates                  | Strict unit tests for each VAT rate combination; test with 0%, 5.5%, 10%, 20% |
| Webhook delivery to untrusted external URLs (SSRF risk)       | Validate URL scheme (HTTPS only); block RFC1918 / loopback addresses; timeout 10s; no redirects |
| High webhook fanout volume overwhelming queue workers          | Dispatch per-endpoint jobs independently; monitor queue depth with Prometheus; configurable rate limit per endpoint |
| Drag-and-drop Kanban conflicts on concurrent updates          | Optimistic UI updates; server-side position reconciliation; conflict toast on 409 response |
| Lead data overlap with Client data (deduplication)            | Pre-conversion check for existing client with same email; warn user if duplicate found |
| Accounting export format lock-in (Pennylane CSV may change)   | Abstract export format behind interface; document column mapping; provide generic CSV fallback |

---

*End of Phase 7 — Accounting Integration, Public API & Prospect Pipeline (v1.3)*
