# Phase 5 — Advanced Financials & Integrations (v1.1)

| Field               | Value                                          |
|---------------------|------------------------------------------------|
| **Phase**           | 5 of 6                                         |
| **Name**            | Advanced Financials & Integrations             |
| **Duration**        | Weeks 25–32 (8 weeks)                          |
| **Milestone**       | M5 — v1.1.0 Release                            |
| **PRD Sections**    | Post-Release Roadmap v1.1                       |
| **Prerequisite**    | Phase 4 fully completed and validated           |
| **Status**          | Completed                                      |

---

## 1. Phase Objectives

| ID       | Objective                                                                                    |
|----------|----------------------------------------------------------------------------------------------|
| P5-OBJ-1 | Implement recurring invoices with configurable frequency, auto-generation, and notifications  |
| P5-OBJ-2 | Add multi-currency support with exchange rates and per-client currency preferences            |
| P5-OBJ-3 | Integrate Google Calendar and CalDAV for project deadlines, task reminders, and meetings      |
| P5-OBJ-4 | Deploy Prometheus + Grafana monitoring stack for application and infrastructure observability  |
| P5-OBJ-5 | Maintain >= 80% test coverage on both back-end and front-end                                |

---

## 2. Entry Criteria

- Phase 4 exit criteria 100% satisfied.
- All Phase 4 CI checks green on `main`.
- v1.0.0 tagged and deployed to production.
- All existing features (CRM, projects, finances, campaigns) stable in production.

---

## 3. Scope — Requirement Traceability

| Feature                              | Priority | Included |
|--------------------------------------|----------|----------|
| Recurring invoices                   | High     | Yes      |
| Multi-currency support               | Medium   | Yes      |
| Calendar integration (Google/CalDAV) | Medium   | Yes      |
| Prometheus + Grafana monitoring      | Low      | Yes      |

---

## 4. Detailed Sprint Breakdown

### 4.1 Sprint 16 — Recurring Invoices (Weeks 25–27)

#### 4.1.1 Database Migrations

| Migration                               | Description                                              |
|-----------------------------------------|----------------------------------------------------------|
| `create_recurring_invoice_profiles_table` | id (UUID), user_id (FK), client_id (FK), name (VARCHAR 255), frequency ENUM('weekly', 'biweekly', 'monthly', 'quarterly', 'semiannual', 'annual'), start_date (DATE), end_date (DATE, nullable), next_due_date (DATE), day_of_month (INT, nullable, 1–28), line_items (JSONB), notes (TEXT, nullable), payment_terms_days (INT, default 30), tax_rate (DECIMAL 5,2, nullable), discount_percent (DECIMAL 5,2, nullable), status ENUM('active', 'paused', 'completed', 'cancelled'), last_generated_at (TIMESTAMP, nullable), occurrences_generated (INT, default 0), max_occurrences (INT, nullable), auto_send (BOOLEAN, default false), currency (VARCHAR 3, default 'EUR'), timestamps. Indexes: user_id, client_id, status, next_due_date. |
| `add_recurring_profile_id_to_invoices` | Add `recurring_invoice_profile_id` (FK, nullable) to `invoices` table to link auto-generated invoices back to their recurring profile. |

#### 4.1.2 Back-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P5-BE-001 | Create `RecurringInvoiceProfile` model — UUID, relationships (user, client, invoices), casts (line_items as JSON array), scopes (active, due) | Roadmap |
| P5-BE-002 | Create `RecurringInvoiceProfileFactory`                                                | §10.3.1       |
| P5-BE-003 | Create `RecurringInvoiceProfilePolicy` — user owns profile                            | NFR-SEC-004   |
| P5-BE-004 | Create `RecurringInvoiceProfileController` — CRUD + pause/resume/cancel endpoints     | Roadmap       |
| P5-BE-005 | Create `StoreRecurringInvoiceProfileRequest` — validate frequency, start_date, line_items, client_id, day_of_month (1–28), optional end_date/max_occurrences | Roadmap |
| P5-BE-006 | Create `RecurringInvoiceGeneratorService` — generate invoice from profile:             | Roadmap       |
|           | — Copy line_items, notes, tax_rate, discount from profile                              |              |
|           | — Set due_date based on payment_terms_days                                             |              |
|           | — Increment occurrences_generated, update next_due_date                                |              |
|           | — Mark profile as completed if max_occurrences reached or end_date passed              |              |
| P5-BE-007 | Create `GenerateRecurringInvoicesCommand` — artisan command `invoices:generate-recurring`, runs daily via scheduler, finds profiles where next_due_date <= today and status == active | Roadmap |
| P5-BE-008 | Create `GenerateRecurringInvoiceJob` (queued) — generate single invoice from profile   | Roadmap       |
| P5-BE-009 | Implement auto-send: if profile.auto_send == true, automatically email the generated invoice to the client's primary contact | Roadmap |
| P5-BE-010 | Create `RecurringInvoiceGeneratedNotification` — notify user when a recurring invoice is generated (email + in-app) | Roadmap |
| P5-BE-011 | Add recurring invoice profile to Meilisearch index                                    | FR-SRC-002    |
| P5-BE-012 | Add recurring invoices summary to dashboard: active profiles count, next upcoming due dates, total recurring revenue/month | Roadmap |

#### 4.1.3 Back-end Tests (TDD)

| Test File                                                        | Test Cases                                                  |
|------------------------------------------------------------------|-------------------------------------------------------------|
| `tests/Unit/Models/RecurringInvoiceProfileTest.php`              | Factory, relationships, scopes (active, due), line_items cast |
| `tests/Unit/Services/RecurringInvoiceGeneratorTest.php`          | Generate invoice from profile, correct line items, due date calculation, occurrences increment, max_occurrences completion, end_date completion, next_due_date for all frequencies (weekly through annual) |
| `tests/Feature/RecurringInvoice/RecurringInvoiceCrudTest.php`    | Create, read, update, delete, validation errors, pause, resume, cancel |
| `tests/Feature/RecurringInvoice/RecurringInvoiceGenerationTest.php` | Command generates invoices for due profiles, skips paused/cancelled, auto-send triggers email, notification dispatched |
| `tests/Feature/RecurringInvoice/RecurringInvoiceEdgeCasesTest.php` | Day 31 → last day of month, February handling, leap year, profile with no end generates indefinitely |

#### 4.1.4 Front-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P5-FE-001 | Create `stores/recurring-invoices.ts` Zustand store — CRUD, pause, resume, cancel     | §6.2.2        |
| P5-FE-002 | Create `app/invoices/recurring/page.tsx` — list: name, client, frequency badge, next due, status badge, occurrences, actions | Roadmap |
| P5-FE-003 | Create `app/invoices/recurring/create/page.tsx` — form:                                | Roadmap       |
|           | — Client selector, profile name                                                        |              |
|           | — Frequency selector (weekly → annual), day of month (if monthly+)                     |              |
|           | — Start date, optional end date or max occurrences                                      |              |
|           | — Line items editor (reuse existing invoice line items component)                       |              |
|           | — Tax rate, discount, payment terms, notes                                              |              |
|           | — Auto-send toggle                                                                      |              |
| P5-FE-004 | Create `app/invoices/recurring/[id]/page.tsx` — detail view: profile info, generated invoices list, timeline of generations | Roadmap |
| P5-FE-005 | Create `app/invoices/recurring/[id]/edit/page.tsx` — edit form                         | Roadmap       |
| P5-FE-006 | Add recurring invoice badge on generated invoices in the main invoices list             | Roadmap       |
| P5-FE-007 | Add recurring invoices widget to dashboard                                             | Roadmap       |

#### 4.1.5 Front-end Tests

| Test File                                                        | Test Cases                                                |
|------------------------------------------------------------------|-----------------------------------------------------------|
| `tests/unit/stores/recurring-invoices.test.ts`                   | CRUD, pause, resume, cancel, state management              |
| `tests/components/invoices/recurring-invoice-form.test.ts`       | Frequency selection, day of month visibility, date validation, line items |
| `tests/e2e/invoices/recurring-invoice-crud.spec.ts`              | Create recurring profile, verify next due, pause, resume, view generated invoices |

---

### 4.2 Sprint 17 — Multi-Currency Support (Weeks 27–29)

#### 4.2.1 Database Migrations

| Migration                          | Description                                              |
|------------------------------------|----------------------------------------------------------|
| `create_currencies_table`          | id (UUID), code (VARCHAR 3, UNIQUE, ISO 4217), name (VARCHAR 100), symbol (VARCHAR 10), decimal_places (INT, default 2), is_active (BOOLEAN, default true), timestamps. Seed with common currencies (EUR, USD, GBP, CHF, CAD, JPY, etc.). |
| `create_exchange_rates_table`      | id (UUID), base_currency (VARCHAR 3), target_currency (VARCHAR 3), rate (DECIMAL 12,6), fetched_at (TIMESTAMP), source (VARCHAR 50), timestamps. Unique index: (base_currency, target_currency, fetched_at::date). |
| `add_currency_to_financial_tables` | Add `currency` (VARCHAR 3, default 'EUR') to: `invoices`, `quotes`, `credit_notes`, `projects`. Add `preferred_currency` (VARCHAR 3, nullable) to `clients`. |
| `add_base_currency_to_settings`    | Add `base_currency` (VARCHAR 3, default 'EUR') to user settings (or use settings JSONB column). |

#### 4.2.2 Back-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P5-BE-020 | Create `Currency` model — relationships, scopes (active)                               | Roadmap       |
| P5-BE-021 | Create `ExchangeRate` model — relationships, scopes (latest)                           | Roadmap       |
| P5-BE-022 | Create `CurrencySeeder` — seed 15+ common currencies (EUR, USD, GBP, CHF, CAD, JPY, AUD, SEK, NOK, DKK, PLN, CZK, HUF, BRL, CNY) | Roadmap |
| P5-BE-023 | Create `ExchangeRateService` — interface for fetching rates                            | Roadmap       |
| P5-BE-024 | Create `OpenExchangeRatesDriver` — fetch rates from Open Exchange Rates API (free tier) | Roadmap |
| P5-BE-025 | Create `EcbExchangeRatesDriver` — alternative: European Central Bank free API          | Roadmap       |
| P5-BE-026 | Create `FetchExchangeRatesCommand` — artisan command `exchange-rates:fetch`, runs daily via scheduler, fetches and stores rates for all active currencies | Roadmap |
| P5-BE-027 | Create `CurrencyConversionService` — convert amount between currencies:                | Roadmap       |
|           | — `convert(amount, fromCurrency, toCurrency, date = today)`                            |              |
|           | — Uses latest available rate for the given date                                         |              |
|           | — Handles inverse rates (if EUR→USD exists, derive USD→EUR)                            |              |
| P5-BE-028 | Create `CurrencyController` — `GET /api/v1/currencies` (list active), `GET /api/v1/currencies/rates` (latest rates for base currency) | Roadmap |
| P5-BE-029 | Update `InvoiceController` — accept currency parameter, store amounts in document currency, compute base_currency_total using exchange rate at invoice date | Roadmap |
| P5-BE-030 | Update `QuoteController` — same multi-currency support as invoices                    | Roadmap       |
| P5-BE-031 | Update `CreditNoteController` — same multi-currency support                           | Roadmap       |
| P5-BE-032 | Update financial reports — aggregate in base currency using exchange rates; show original currency alongside base | Roadmap |
| P5-BE-033 | Update PDF generation — display amounts in document currency with correct symbol and decimal formatting | Roadmap |
| P5-BE-034 | Add base currency setting to `UserSettingsController` — `PUT /api/v1/settings/currency` | Roadmap |
| P5-BE-035 | Update dashboard financial widgets — convert all amounts to base currency              | Roadmap       |

#### 4.2.3 Back-end Tests (TDD)

| Test File                                                        | Test Cases                                                  |
|------------------------------------------------------------------|-------------------------------------------------------------|
| `tests/Unit/Services/CurrencyConversionTest.php`                 | Direct conversion, inverse conversion, same currency returns same amount, missing rate throws exception, date-specific rate used |
| `tests/Unit/Services/ExchangeRateServiceTest.php`                | OpenExchangeRates driver fetches and stores, ECB driver fetches and stores, handles API errors gracefully |
| `tests/Feature/Currency/CurrencyListTest.php`                    | List active currencies, filter inactive, rates endpoint returns latest |
| `tests/Feature/Invoice/MultiCurrencyInvoiceTest.php`             | Create invoice in USD, base_currency_total computed in EUR, PDF shows USD amounts, financial report aggregates in EUR |
| `tests/Feature/Reports/MultiCurrencyReportTest.php`              | Revenue report aggregates across currencies, shows breakdown by currency |

#### 4.2.4 Front-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P5-FE-010 | Create `stores/currencies.ts` Zustand store — currencies list, rates, base currency   | §6.2.2        |
| P5-FE-011 | Create `components/shared/currency-selector.tsx` — searchable dropdown with flag/code/name | Roadmap |
| P5-FE-012 | Create `components/shared/currency-amount.tsx` — format amount with correct symbol, decimal places, locale | Roadmap |
| P5-FE-013 | Update invoice create/edit forms — add currency selector, show live conversion to base currency | Roadmap |
| P5-FE-014 | Update quote create/edit forms — add currency selector                                | Roadmap       |
| P5-FE-015 | Update credit note create/edit forms — add currency selector                          | Roadmap       |
| P5-FE-016 | Update client detail/edit — add preferred currency field                              | Roadmap       |
| P5-FE-017 | Update financial reports — show base currency totals with currency breakdown toggle   | Roadmap       |
| P5-FE-018 | Create `app/settings/currency/page.tsx` — base currency selector, exchange rate provider config, manual rate override | Roadmap |
| P5-FE-019 | Update dashboard financial widgets — display in base currency                         | Roadmap       |

#### 4.2.5 Front-end Tests

| Test File                                                        | Test Cases                                                |
|------------------------------------------------------------------|-----------------------------------------------------------|
| `tests/unit/stores/currencies.test.ts`                           | Fetch currencies, rates, base currency setting             |
| `tests/components/shared/currency-selector.test.ts`              | Search, select, display flag/code                          |
| `tests/components/shared/currency-amount.test.ts`                | Format EUR, USD, JPY (0 decimals), correct symbol placement |
| `tests/e2e/invoices/multi-currency-invoice.spec.ts`              | Create invoice in USD, verify conversion, PDF currency display |

---

### 4.3 Sprint 18 — Calendar Integration (Weeks 29–31)

#### 4.3.1 Database Migrations

| Migration                          | Description                                              |
|------------------------------------|----------------------------------------------------------|
| `create_calendar_connections_table` | id (UUID), user_id (FK), provider ENUM('google', 'caldav'), name (VARCHAR 255), credentials (TEXT, encrypted — OAuth tokens or CalDAV URL + credentials), calendar_id (VARCHAR 500, nullable), sync_enabled (BOOLEAN, default true), last_synced_at (TIMESTAMP, nullable), timestamps. Index: user_id. |
| `create_calendar_events_table`     | id (UUID), user_id (FK), calendar_connection_id (FK, nullable), external_id (VARCHAR 500, nullable), title (VARCHAR 255), description (TEXT, nullable), start_at (TIMESTAMP), end_at (TIMESTAMP), all_day (BOOLEAN, default false), location (VARCHAR 500, nullable), type ENUM('meeting', 'deadline', 'reminder', 'task', 'custom'), eventable_type (VARCHAR, nullable — polymorphic), eventable_id (UUID, nullable — polymorphic), recurrence_rule (VARCHAR 500, nullable — RRULE), sync_status ENUM('local', 'synced', 'conflict'), timestamps. Indexes: user_id, start_at, eventable. |

#### 4.3.2 Back-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P5-BE-040 | Create `CalendarConnection` model — encrypted credentials, relationships (user, events) | Roadmap |
| P5-BE-041 | Create `CalendarEvent` model — polymorphic eventable (project, task, invoice), relationships | Roadmap |
| P5-BE-042 | Create `CalendarConnectionFactory` and `CalendarEventFactory`                          | §10.3.1       |
| P5-BE-043 | Create `CalendarSyncService` interface — `fetchEvents(connection, dateRange)`, `pushEvent(connection, event)`, `deleteEvent(connection, eventId)` | Roadmap |
| P5-BE-044 | Create `GoogleCalendarDriver` — OAuth 2.0 flow, fetch/push/delete events via Google Calendar API v3 | Roadmap |
| P5-BE-045 | Create `CalDavDriver` — CalDAV protocol support (sabre/dav client), fetch/push/delete events | Roadmap |
| P5-BE-046 | Create `CalendarConnectionController` — CRUD connections, OAuth callback, test connection | Roadmap |
| P5-BE-047 | Create `CalendarEventController` — CRUD events, list by date range, filter by type    | Roadmap       |
| P5-BE-048 | Create `SyncCalendarEventsCommand` — artisan command `calendar:sync`, runs every 15 min via scheduler, bidirectional sync for all active connections | Roadmap |
| P5-BE-049 | Create `SyncCalendarJob` (queued) — sync single connection                            | Roadmap       |
| P5-BE-050 | Implement auto-event creation:                                                        | Roadmap       |
|           | — Project deadline → calendar event when project created/updated                       |              |
|           | — Task due date → calendar event when task created/updated                             |              |
|           | — Invoice due date → calendar reminder 3 days before                                   |              |
| P5-BE-051 | Implement conflict resolution: if event modified both locally and externally, keep most recent, flag conflict | Roadmap |
| P5-BE-052 | Create `CalendarEventReminderNotification` — notify user of upcoming events (email + in-app, configurable lead time) | Roadmap |

#### 4.3.3 Back-end Tests (TDD)

| Test File                                                        | Test Cases                                                  |
|------------------------------------------------------------------|-------------------------------------------------------------|
| `tests/Unit/Services/GoogleCalendarDriverTest.php`               | Fetch events, push event, delete event, handle expired token refresh |
| `tests/Unit/Services/CalDavDriverTest.php`                       | Fetch events, push event, delete event, authentication failure handling |
| `tests/Feature/Calendar/CalendarConnectionTest.php`              | CRUD connections, OAuth flow, test connection, encrypted credentials |
| `tests/Feature/Calendar/CalendarEventCrudTest.php`               | Create, read, update, delete events, filter by date range and type |
| `tests/Feature/Calendar/CalendarSyncTest.php`                    | Bidirectional sync, conflict resolution, auto-event creation from project/task/invoice |
| `tests/Feature/Calendar/CalendarAutoEventTest.php`               | Project deadline creates event, task due date creates event, invoice reminder created |

#### 4.3.4 Front-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P5-FE-020 | Create `stores/calendar.ts` Zustand store — events, connections, date range            | §6.2.2        |
| P5-FE-021 | Create `app/calendar/page.tsx` — full calendar view:                                   | Roadmap       |
|           | — Monthly, weekly, daily views (using a calendar library like FullCalendar or react-big-calendar) |  |
|           | — Color-coded by event type (meetings, deadlines, reminders, tasks)                    |              |
|           | — Click to create event, drag to reschedule                                            |              |
|           | — Sidebar with mini-calendar and upcoming events list                                  |              |
| P5-FE-022 | Create `components/calendar/event-form-modal.tsx` — create/edit event: title, description, start/end datetime, all-day toggle, type, location, linked entity | Roadmap |
| P5-FE-023 | Create `components/calendar/event-detail-popover.tsx` — quick view on click with edit/delete actions | Roadmap |
| P5-FE-024 | Create `app/settings/calendar/page.tsx` — manage connections:                          | Roadmap       |
|           | — Add Google Calendar (OAuth flow)                                                     |              |
|           | — Add CalDAV (URL + credentials)                                                       |              |
|           | — Enable/disable sync per connection                                                   |              |
|           | — Configure auto-event rules (project deadlines, task dues, invoice reminders)         |              |
| P5-FE-025 | Add calendar widget to dashboard — today's events, upcoming deadlines                 | Roadmap       |
| P5-FE-026 | Add sidebar navigation entry for Calendar                                             | Roadmap       |

#### 4.3.5 Front-end Tests

| Test File                                                        | Test Cases                                                |
|------------------------------------------------------------------|-----------------------------------------------------------|
| `tests/unit/stores/calendar.test.ts`                             | Fetch events, create, update, delete, date range filtering |
| `tests/components/calendar/event-form-modal.test.ts`             | Create event, edit event, all-day toggle, type selection   |
| `tests/e2e/calendar/calendar-views.spec.ts`                      | Monthly view, weekly view, daily view, navigate months, create event, drag reschedule |
| `tests/e2e/calendar/calendar-sync.spec.ts`                       | Add connection, verify sync status, auto-events from project deadline |

---

### 4.4 Sprint 19 — Prometheus + Grafana Monitoring (Weeks 31–32)

#### 4.4.1 Back-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P5-BE-060 | Add Prometheus and Grafana services to `docker-compose.yml` and `docker-compose.prod.yml` | Roadmap |
| P5-BE-061 | Install and configure `promphp/prometheus_client_php` — expose `/metrics` endpoint     | Roadmap       |
| P5-BE-062 | Create `PrometheusMiddleware` — collect HTTP request metrics:                           | Roadmap       |
|           | — `http_requests_total` (counter, labels: method, path, status)                        |              |
|           | — `http_request_duration_seconds` (histogram, labels: method, path)                    |              |
|           | — `http_request_size_bytes` (histogram)                                                |              |
| P5-BE-063 | Create custom application metrics:                                                     | Roadmap       |
|           | — `koomky_active_users_total` (gauge)                                                  |              |
|           | — `koomky_invoices_generated_total` (counter)                                          |              |
|           | — `koomky_campaigns_sent_total` (counter)                                              |              |
|           | — `koomky_queue_jobs_processed_total` (counter, labels: queue, status)                 |              |
|           | — `koomky_queue_jobs_waiting` (gauge, labels: queue)                                   |              |
|           | — `koomky_emails_sent_total` (counter, labels: type — campaign, invoice, notification) |              |
| P5-BE-064 | Create `docker/prometheus/prometheus.yml` — scrape config for Laravel app, PostgreSQL exporter, Redis exporter, Node exporter | Roadmap |
| P5-BE-065 | Add `postgres-exporter` service to Docker Compose — expose PostgreSQL metrics          | Roadmap       |
| P5-BE-066 | Add `redis-exporter` service to Docker Compose — expose Redis metrics                  | Roadmap       |
| P5-BE-067 | Add `node-exporter` service to Docker Compose — expose host system metrics             | Roadmap       |
| P5-BE-068 | Create Grafana dashboards (provisioned via JSON):                                      | Roadmap       |
|           | — **Application Overview**: request rate, error rate, p95 latency, active users        |              |
|           | — **Business Metrics**: invoices generated, campaigns sent, revenue trends              |              |
|           | — **Infrastructure**: CPU, memory, disk, network per container                          |              |
|           | — **Database**: connections, query duration, cache hit ratio, table sizes               |              |
|           | — **Queue**: jobs waiting, processing rate, failure rate                                |              |
| P5-BE-069 | Configure Grafana alerting rules:                                                      | Roadmap       |
|           | — Error rate > 5% over 5 min                                                           |              |
|           | — p95 latency > 500ms over 5 min                                                       |              |
|           | — Queue depth > 1000 jobs                                                              |              |
|           | — Disk usage > 80%                                                                     |              |
|           | — Database connections > 80% of max                                                    |              |
| P5-BE-070 | Create `docker/grafana/` directory with provisioning files: datasources, dashboards, alerting | Roadmap |
| P5-BE-071 | Document monitoring setup in `docs/monitoring.md`                                      | Roadmap       |

#### 4.4.2 Back-end Tests (TDD)

| Test File                                                        | Test Cases                                                  |
|------------------------------------------------------------------|-------------------------------------------------------------|
| `tests/Feature/Monitoring/PrometheusMetricsTest.php`             | `/metrics` endpoint returns valid Prometheus format, HTTP metrics present, custom app metrics present |
| `tests/Feature/Monitoring/PrometheusMiddlewareTest.php`          | Request counter incremented, duration histogram recorded, labels correct |
| `tests/Unit/Services/PrometheusMetricsServiceTest.php`           | Increment counter, set gauge, observe histogram, metric registration |

#### 4.4.3 Front-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P5-FE-030 | Add link to Grafana dashboard in admin settings / sidebar (external link)             | Roadmap       |

---

## 5. API Endpoints Delivered in Phase 5

| Method | Endpoint                                         | Controller                        |
|--------|--------------------------------------------------|-----------------------------------|
| GET    | `/api/v1/recurring-invoices`                     | RecurringInvoiceProfileController |
| POST   | `/api/v1/recurring-invoices`                     | RecurringInvoiceProfileController |
| GET    | `/api/v1/recurring-invoices/{id}`                | RecurringInvoiceProfileController |
| PUT    | `/api/v1/recurring-invoices/{id}`                | RecurringInvoiceProfileController |
| DELETE | `/api/v1/recurring-invoices/{id}`                | RecurringInvoiceProfileController |
| POST   | `/api/v1/recurring-invoices/{id}/pause`          | RecurringInvoiceProfileController |
| POST   | `/api/v1/recurring-invoices/{id}/resume`         | RecurringInvoiceProfileController |
| POST   | `/api/v1/recurring-invoices/{id}/cancel`         | RecurringInvoiceProfileController |
| GET    | `/api/v1/currencies`                             | CurrencyController                |
| GET    | `/api/v1/currencies/rates`                       | CurrencyController                |
| PUT    | `/api/v1/settings/currency`                      | UserSettingsController            |
| GET    | `/api/v1/calendar-connections`                   | CalendarConnectionController      |
| POST   | `/api/v1/calendar-connections`                   | CalendarConnectionController      |
| GET    | `/api/v1/calendar-connections/{id}`              | CalendarConnectionController      |
| PUT    | `/api/v1/calendar-connections/{id}`              | CalendarConnectionController      |
| DELETE | `/api/v1/calendar-connections/{id}`              | CalendarConnectionController      |
| POST   | `/api/v1/calendar-connections/{id}/test`         | CalendarConnectionController      |
| GET    | `/api/v1/calendar-connections/google/callback`   | CalendarConnectionController      |
| GET    | `/api/v1/calendar-events`                        | CalendarEventController           |
| POST   | `/api/v1/calendar-events`                        | CalendarEventController           |
| GET    | `/api/v1/calendar-events/{id}`                   | CalendarEventController           |
| PUT    | `/api/v1/calendar-events/{id}`                   | CalendarEventController           |
| DELETE | `/api/v1/calendar-events/{id}`                   | CalendarEventController           |
| GET    | `/metrics`                                        | PrometheusController              |

---

## 6. Exit Criteria

Legend: `Pre-check` = validation technique disponible aujourd'hui; `Validated` = case finale de recette manuelle.

| #  | Criterion                                                                           | Pre-check                                                                 | Validated |
|----|-------------------------------------------------------------------------------------|---------------------------------------------------------------------------|-----------|
| 1  | Recurring invoice profiles CRUD with all frequencies (weekly → annual)             | E2E CRUD profile OK (create + pause/resume/cancel), fréquences à valider | [ ]       |
| 2  | Scheduler auto-generates invoices on due date                                      | Couverture feature/backend présente, à confirmer en run scheduler réel    | [ ]       |
| 3  | Auto-send emails generated invoices when configured                                | Couverture backend présente, SMTP provider réel à confirmer               | [ ]       |
| 4  | Multi-currency: create invoices/quotes/credit notes in any active currency         | E2E invoice OK; quotes/credit notes à confirmer manuellement              | [ ]       |
| 5  | Exchange rates fetched daily and used for base currency conversion                 | Couverture backend présente, cron quotidien à confirmer                   | [ ]       |
| 6  | Financial reports aggregate in base currency with currency breakdown               | Implémentation présente, validation métier manuelle requise               | [ ]       |
| 7  | Google Calendar OAuth connection and bidirectional sync                             | Feature/unit tests OK (mock), OAuth réel à confirmer                      | [ ]       |
| 8  | CalDAV connection and bidirectional sync                                            | Implémentation présente, serveur CalDAV réel à confirmer                  | [ ]       |
| 9  | Auto-events from project deadlines, task due dates, invoice reminders              | Feature tests OK + persistance UI/API des règles ajoutée                  | [ ]       |
| 10 | Calendar UI with month/week/day views, drag-to-reschedule                          | E2E month/week/day OK; drag-to-reschedule à confirmer                     | [ ]       |
| 11 | Prometheus `/metrics` endpoint exposes HTTP + application metrics                  | Tests monitoring présents, scrape réel à confirmer                        | [ ]       |
| 12 | Grafana dashboards provisioned: application, business, infrastructure, DB, queue   | Dashboards JSON présents, import/affichage réel à confirmer               | [ ]       |
| 13 | Grafana alerting rules configured for critical thresholds                           | Fichiers/provisioning présents, trigger réel à confirmer                  | [ ]       |
| 14 | Back-end test coverage >= 80%                                                      | Non mesuré sur ce passage                                                 | [ ]       |
| 15 | Front-end test coverage >= 80%                                                     | Non mesuré sur ce passage (scope coverage à vérifier)                     | [ ]       |
| 16 | CI pipeline fully green on `main`                                                  | Non vérifiable localement                                                 | [ ]       |
| 17 | Version tagged as `v1.1.0` on GitHub                                               | Non vérifiable localement                                                 | [ ]       |

### 6.1 Manual Validation Checklist (Criterion-by-Criterion)

#### 1) Recurring invoice profiles CRUD (all frequencies)
- [ ] Créer 6 profils (`weekly`, `biweekly`, `monthly`, `quarterly`, `semiannual`, `annual`).
- [ ] Vérifier `create`, `read`, `update`, `delete`, `pause`, `resume`, `cancel`.
- [ ] Vérifier la cohérence `next_due_date` après modification de fréquence.
- [ ] Preuves: captures UI + réponses API.

#### 2) Scheduler auto-generates invoices
- [ ] Exécuter le scheduler sur un jeu de profils arrivés à échéance.
- [ ] Vérifier création unique des factures (idempotence).
- [ ] Vérifier incrément `occurrences_generated` et mise à jour `next_due_date`.
- [ ] Preuves: logs scheduler + enregistrements DB.

#### 3) Auto-send generated invoices
- [ ] Activer `auto_send` sur un profil.
- [ ] Forcer une génération et vérifier envoi mail.
- [ ] Vérifier gestion d'erreur provider (retry/log).
- [ ] Preuves: logs de queue + provider mail + statut facture.

#### 4) Multi-currency across invoices/quotes/credit notes
- [ ] Créer invoice, quote et credit note avec 3 devises actives différentes.
- [ ] Vérifier montants document + conversion devise de base.
- [ ] Vérifier arrondis et affichage symbole/code.
- [ ] Preuves: captures UI + payload API.

#### 5) Daily exchange rates and conversion usage
- [ ] Déclencher fetch des taux et vérifier stockage.
- [ ] Vérifier utilisation des taux dans calculs en devise de base.
- [ ] Vérifier comportement fallback en cas d'échec provider.
- [ ] Preuves: logs fetch rates + valeurs stockées + écran de conversion.

#### 6) Financial reports in base currency
- [ ] Générer des documents multi-devises.
- [ ] Ouvrir les rapports financiers et vérifier agrégation en devise de base.
- [ ] Vérifier présence du breakdown par devise.
- [ ] Preuves: captures rapports + calcul de contrôle.

#### 7) Google Calendar OAuth + bidirectional sync
- [ ] Connecter un compte Google réel via OAuth.
- [ ] Créer/modifier/supprimer un événement côté app puis vérifier côté Google.
- [ ] Créer/modifier/supprimer côté Google puis lancer sync et vérifier côté app.
- [ ] Preuves: captures des deux côtés + logs sync.

#### 8) CalDAV bidirectional sync
- [ ] Connecter un serveur CalDAV réel (ex: Nextcloud/Radicale).
- [ ] Vérifier sync bidirectionnelle create/update/delete.
- [ ] Vérifier gestion des erreurs auth/certificat et refresh.
- [ ] Preuves: captures client CalDAV + logs sync.

#### 9) Auto-events from projects/tasks/invoices
- [ ] Créer projet avec deadline, tâche avec due date, facture avec due date.
- [ ] Vérifier création auto des 3 types d'événements.
- [ ] Désactiver une règle auto-event en settings et vérifier absence de création.
- [ ] Preuves: captures calendrier + payload settings.

#### 10) Calendar UI month/week/day + drag-to-reschedule
- [ ] Vérifier navigation month/week/day.
- [ ] Vérifier création/édition/suppression d'événement depuis UI.
- [ ] Vérifier drag-to-reschedule et persistance backend.
- [ ] Preuves: enregistrement écran + payload update event.

#### 11) Prometheus metrics endpoint
- [ ] Appeler `/metrics` et vérifier format Prometheus valide.
- [ ] Vérifier présence métriques HTTP + application.
- [ ] Vérifier variation des compteurs après trafic simulé.
- [ ] Preuves: extrait `/metrics` + requêtes de test.

#### 12) Grafana dashboards provisioned
- [ ] Démarrer Grafana avec provisioning actif.
- [ ] Vérifier présence des 5 dashboards attendus.
- [ ] Vérifier au moins un panel alimenté par dashboard.
- [ ] Preuves: captures Grafana + export dashboard UID.

#### 13) Grafana alerting rules
- [ ] Vérifier présence et chargement des règles d'alerting.
- [ ] Simuler dépassement de seuil pour au moins 2 règles.
- [ ] Vérifier transition d'état et notification.
- [ ] Preuves: historique alertes + notifications.

#### 14) Back-end coverage >= 80%
- [ ] Exécuter la commande de coverage backend.
- [ ] Archiver le rapport (HTML/XML) et noter le pourcentage global.
- [ ] Valider que le seuil >= 80% est respecté.
- [ ] Preuves: rapport coverage backend.

#### 15) Front-end coverage >= 80%
- [ ] Exécuter la commande de coverage frontend.
- [ ] Vérifier que le périmètre inclus couvre aussi les composants requis.
- [ ] Valider que le seuil >= 80% est respecté.
- [ ] Preuves: rapport coverage frontend + config coverage utilisée.

#### 16) CI fully green on `main`
- [ ] Vérifier le dernier pipeline sur la branche `main`.
- [ ] Vérifier que tous les jobs obligatoires sont au vert.
- [ ] Vérifier absence de flaky/retry masquant des échecs.
- [ ] Preuves: lien pipeline + capture jobs.

#### 17) GitHub tag `v1.1.0`
- [ ] Vérifier présence du tag annoté `v1.1.0` sur GitHub.
- [ ] Vérifier que le tag pointe sur le commit release attendu.
- [ ] Vérifier publication des release notes associées.
- [ ] Preuves: page release/tag + SHA.

---

## 7. Risks Specific to Phase 5

| Risk                                                     | Mitigation                                                    |
|----------------------------------------------------------|---------------------------------------------------------------|
| Recurring invoice generation missing a cycle             | Idempotent generation (check if already generated for period); catch-up logic on next run; alert on missed generations |
| Exchange rate API downtime                               | Cache last known rates; support multiple providers (OpenExchangeRates + ECB); manual override option |
| Currency rounding discrepancies                          | Use bcmath for all currency arithmetic; round only at display; store full precision |
| Google OAuth token expiration                            | Implement automatic refresh token rotation; notify user if refresh fails |
| CalDAV protocol compatibility variations                 | Test against popular servers (Nextcloud, Radicale, iCloud); graceful handling of unsupported features |
| Prometheus metric cardinality explosion                  | Limit path label to normalized route patterns (not raw URLs); cap label values |
| Calendar sync conflicts with external modifications      | Last-writer-wins with conflict flag; allow user to manually resolve conflicts |

---

*End of Phase 5 — Advanced Financials & Integrations (v1.1)*
