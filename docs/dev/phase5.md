# Phase 5 — Task Tracking

> **Status**: Completed
> **Prerequisite**: Phase 4 fully merged
> **Spec**: [docs/phases/phase5.md](../phases/phase5.md)

---

## Sprint 16 — Recurring Invoices (Weeks 25-27)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P5-BE-001 | Create RecurringInvoiceProfile model (UUID, relationships, scopes) | done | codex |
| P5-BE-002 | Create RecurringInvoiceProfileFactory | done | codex |
| P5-BE-003 | Create RecurringInvoiceProfilePolicy | done | codex |
| P5-BE-004 | Create RecurringInvoiceProfileController (CRUD + pause/resume/cancel) | done | codex |
| P5-BE-005 | Create StoreRecurringInvoiceProfileRequest | done | codex |
| P5-BE-006 | Create RecurringInvoiceGeneratorService | done | codex |
| P5-BE-007 | Create GenerateRecurringInvoicesCommand (daily scheduler) | done | codex |
| P5-BE-008 | Create GenerateRecurringInvoiceJob (queued) | done | codex |
| P5-BE-009 | Implement auto-send for generated invoices | done | codex |
| P5-BE-010 | Create RecurringInvoiceGeneratedNotification | done | codex |
| P5-BE-011 | Add RecurringInvoiceProfile to Meilisearch index | done | codex |
| P5-BE-012 | Add recurring invoices summary to dashboard | done | codex |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P5-FE-001 | Create stores/recurring-invoices.ts Zustand store | done | codex |
| P5-FE-002 | Create app/invoices/recurring/page.tsx (list) | done | codex |
| P5-FE-003 | Create app/invoices/recurring/create/page.tsx (form) | done | codex |
| P5-FE-004 | Create app/invoices/recurring/[id]/page.tsx (detail) | done | codex |
| P5-FE-005 | Create app/invoices/recurring/[id]/edit/page.tsx | done | codex |
| P5-FE-006 | Add recurring invoice badge on generated invoices | done | codex |
| P5-FE-007 | Add recurring invoices widget to dashboard | done | codex |

---

## Sprint 17 — Multi-Currency Support (Weeks 27-29)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P5-BE-020 | Create Currency model | done | codex |
| P5-BE-021 | Create ExchangeRate model | done | codex |
| P5-BE-022 | Create CurrencySeeder (15+ currencies) | done | codex |
| P5-BE-023 | Create ExchangeRateService interface | done | codex |
| P5-BE-024 | Create OpenExchangeRatesDriver | done | codex |
| P5-BE-025 | Create EcbExchangeRatesDriver | done | codex |
| P5-BE-026 | Create FetchExchangeRatesCommand (daily scheduler) | done | codex |
| P5-BE-027 | Create CurrencyConversionService | done | codex |
| P5-BE-028 | Create CurrencyController (list currencies, rates) | done | codex |
| P5-BE-029 | Update InvoiceController for multi-currency | done | codex |
| P5-BE-030 | Update QuoteController for multi-currency | done | codex |
| P5-BE-031 | Update CreditNoteController for multi-currency | done | codex |
| P5-BE-032 | Update financial reports for multi-currency aggregation | done | codex |
| P5-BE-033 | Update PDF generation for currency formatting | done | codex |
| P5-BE-034 | Add base currency setting to UserSettingsController | done | codex |
| P5-BE-035 | Update dashboard financial widgets for base currency | done | codex |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P5-FE-010 | Create stores/currencies.ts Zustand store | done | codex |
| P5-FE-011 | Create components/shared/currency-selector.tsx | done | codex |
| P5-FE-012 | Create components/shared/currency-amount.tsx | done | codex |
| P5-FE-013 | Update invoice create/edit forms with currency selector | done | codex |
| P5-FE-014 | Update quote create/edit forms with currency selector | done | codex |
| P5-FE-015 | Update credit note create/edit forms with currency selector | done | codex |
| P5-FE-016 | Update client detail/edit with preferred currency | done | codex |
| P5-FE-017 | Update financial reports with currency breakdown toggle | done | codex |
| P5-FE-018 | Create app/settings/currency/page.tsx | done | codex |
| P5-FE-019 | Update dashboard financial widgets for base currency | done | codex |

---

## Sprint 18 — Calendar Integration (Weeks 29-31)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P5-BE-040 | Create CalendarConnection model (encrypted credentials) | done | codex |
| P5-BE-041 | Create CalendarEvent model (polymorphic eventable) | done | codex |
| P5-BE-042 | Create CalendarConnectionFactory and CalendarEventFactory | done | codex |
| P5-BE-043 | Create CalendarSyncService interface | done | codex |
| P5-BE-044 | Create GoogleCalendarDriver (OAuth 2.0) | done | codex |
| P5-BE-045 | Create CalDavDriver (sabre/dav) | done | codex |
| P5-BE-046 | Create CalendarConnectionController (CRUD, OAuth callback, test) | done | codex |
| P5-BE-047 | Create CalendarEventController (CRUD, date range, type filter) | done | codex |
| P5-BE-048 | Create SyncCalendarEventsCommand (every 15 min) | done | codex |
| P5-BE-049 | Create SyncCalendarJob (queued) | done | codex |
| P5-BE-050 | Implement auto-event creation (project deadlines, task dues, invoice reminders) | done | codex |
| P5-BE-051 | Implement conflict resolution (last-writer-wins + flag) | done | codex |
| P5-BE-052 | Create CalendarEventReminderNotification | done | codex |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P5-FE-020 | Create stores/calendar.ts Zustand store | done | codex |
| P5-FE-021 | Create app/calendar/page.tsx (month/week/day views) | done | codex |
| P5-FE-022 | Create components/calendar/event-form-modal.tsx | done | codex |
| P5-FE-023 | Create components/calendar/event-detail-popover.tsx | done | codex |
| P5-FE-024 | Create app/settings/calendar/page.tsx (connections, auto-event config) | done | codex |
| P5-FE-025 | Add calendar widget to dashboard | done | codex |
| P5-FE-026 | Add Calendar entry to sidebar navigation | done | codex |

---

## Sprint 19 — Prometheus + Grafana Monitoring (Weeks 31-32)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P5-BE-060 | Add Prometheus + Grafana services to Docker Compose | done | codex |
| P5-BE-061 | Install promphp/prometheus_client_php, expose /metrics | done | codex |
| P5-BE-062 | Create PrometheusMiddleware (HTTP request metrics) | done | codex |
| P5-BE-063 | Create custom application metrics (users, invoices, campaigns, queue, emails) | done | codex |
| P5-BE-064 | Create docker/prometheus/prometheus.yml (scrape config) | done | codex |
| P5-BE-065 | Add postgres-exporter service | done | codex |
| P5-BE-066 | Add redis-exporter service | done | codex |
| P5-BE-067 | Add node-exporter service | done | codex |
| P5-BE-068 | Create Grafana dashboards (app, business, infra, DB, queue) | done | codex |
| P5-BE-069 | Configure Grafana alerting rules | done | codex |
| P5-BE-070 | Create docker/grafana/ provisioning files | done | codex |
| P5-BE-071 | Document monitoring setup in docs/monitoring.md | done | codex |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P5-FE-030 | Add Grafana dashboard link in admin settings/sidebar | done | codex |
