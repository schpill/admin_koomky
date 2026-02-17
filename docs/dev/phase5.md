# Phase 5 — Task Tracking

> **Status**: Not started
> **Prerequisite**: Phase 4 fully merged
> **Spec**: [docs/phases/phase5.md](../phases/phase5.md)

---

## Sprint 16 — Recurring Invoices (Weeks 25-27)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P5-BE-001 | Create RecurringInvoiceProfile model (UUID, relationships, scopes) | todo | |
| P5-BE-002 | Create RecurringInvoiceProfileFactory | todo | |
| P5-BE-003 | Create RecurringInvoiceProfilePolicy | todo | |
| P5-BE-004 | Create RecurringInvoiceProfileController (CRUD + pause/resume/cancel) | todo | |
| P5-BE-005 | Create StoreRecurringInvoiceProfileRequest | todo | |
| P5-BE-006 | Create RecurringInvoiceGeneratorService | todo | |
| P5-BE-007 | Create GenerateRecurringInvoicesCommand (daily scheduler) | todo | |
| P5-BE-008 | Create GenerateRecurringInvoiceJob (queued) | todo | |
| P5-BE-009 | Implement auto-send for generated invoices | todo | |
| P5-BE-010 | Create RecurringInvoiceGeneratedNotification | todo | |
| P5-BE-011 | Add RecurringInvoiceProfile to Meilisearch index | todo | |
| P5-BE-012 | Add recurring invoices summary to dashboard | todo | |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P5-FE-001 | Create stores/recurring-invoices.ts Zustand store | todo | |
| P5-FE-002 | Create app/invoices/recurring/page.tsx (list) | todo | |
| P5-FE-003 | Create app/invoices/recurring/create/page.tsx (form) | todo | |
| P5-FE-004 | Create app/invoices/recurring/[id]/page.tsx (detail) | todo | |
| P5-FE-005 | Create app/invoices/recurring/[id]/edit/page.tsx | todo | |
| P5-FE-006 | Add recurring invoice badge on generated invoices | todo | |
| P5-FE-007 | Add recurring invoices widget to dashboard | todo | |

---

## Sprint 17 — Multi-Currency Support (Weeks 27-29)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P5-BE-020 | Create Currency model | todo | |
| P5-BE-021 | Create ExchangeRate model | todo | |
| P5-BE-022 | Create CurrencySeeder (15+ currencies) | todo | |
| P5-BE-023 | Create ExchangeRateService interface | todo | |
| P5-BE-024 | Create OpenExchangeRatesDriver | todo | |
| P5-BE-025 | Create EcbExchangeRatesDriver | todo | |
| P5-BE-026 | Create FetchExchangeRatesCommand (daily scheduler) | todo | |
| P5-BE-027 | Create CurrencyConversionService | todo | |
| P5-BE-028 | Create CurrencyController (list currencies, rates) | todo | |
| P5-BE-029 | Update InvoiceController for multi-currency | todo | |
| P5-BE-030 | Update QuoteController for multi-currency | todo | |
| P5-BE-031 | Update CreditNoteController for multi-currency | todo | |
| P5-BE-032 | Update financial reports for multi-currency aggregation | todo | |
| P5-BE-033 | Update PDF generation for currency formatting | todo | |
| P5-BE-034 | Add base currency setting to UserSettingsController | todo | |
| P5-BE-035 | Update dashboard financial widgets for base currency | todo | |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P5-FE-010 | Create stores/currencies.ts Zustand store | todo | |
| P5-FE-011 | Create components/shared/currency-selector.tsx | todo | |
| P5-FE-012 | Create components/shared/currency-amount.tsx | todo | |
| P5-FE-013 | Update invoice create/edit forms with currency selector | todo | |
| P5-FE-014 | Update quote create/edit forms with currency selector | todo | |
| P5-FE-015 | Update credit note create/edit forms with currency selector | todo | |
| P5-FE-016 | Update client detail/edit with preferred currency | todo | |
| P5-FE-017 | Update financial reports with currency breakdown toggle | todo | |
| P5-FE-018 | Create app/settings/currency/page.tsx | todo | |
| P5-FE-019 | Update dashboard financial widgets for base currency | todo | |

---

## Sprint 18 — Calendar Integration (Weeks 29-31)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P5-BE-040 | Create CalendarConnection model (encrypted credentials) | todo | |
| P5-BE-041 | Create CalendarEvent model (polymorphic eventable) | todo | |
| P5-BE-042 | Create CalendarConnectionFactory and CalendarEventFactory | todo | |
| P5-BE-043 | Create CalendarSyncService interface | todo | |
| P5-BE-044 | Create GoogleCalendarDriver (OAuth 2.0) | todo | |
| P5-BE-045 | Create CalDavDriver (sabre/dav) | todo | |
| P5-BE-046 | Create CalendarConnectionController (CRUD, OAuth callback, test) | todo | |
| P5-BE-047 | Create CalendarEventController (CRUD, date range, type filter) | todo | |
| P5-BE-048 | Create SyncCalendarEventsCommand (every 15 min) | todo | |
| P5-BE-049 | Create SyncCalendarJob (queued) | todo | |
| P5-BE-050 | Implement auto-event creation (project deadlines, task dues, invoice reminders) | todo | |
| P5-BE-051 | Implement conflict resolution (last-writer-wins + flag) | todo | |
| P5-BE-052 | Create CalendarEventReminderNotification | todo | |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P5-FE-020 | Create stores/calendar.ts Zustand store | todo | |
| P5-FE-021 | Create app/calendar/page.tsx (month/week/day views) | todo | |
| P5-FE-022 | Create components/calendar/event-form-modal.tsx | todo | |
| P5-FE-023 | Create components/calendar/event-detail-popover.tsx | todo | |
| P5-FE-024 | Create app/settings/calendar/page.tsx (connections, auto-event config) | todo | |
| P5-FE-025 | Add calendar widget to dashboard | todo | |
| P5-FE-026 | Add Calendar entry to sidebar navigation | todo | |

---

## Sprint 19 — Prometheus + Grafana Monitoring (Weeks 31-32)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P5-BE-060 | Add Prometheus + Grafana services to Docker Compose | todo | |
| P5-BE-061 | Install promphp/prometheus_client_php, expose /metrics | todo | |
| P5-BE-062 | Create PrometheusMiddleware (HTTP request metrics) | todo | |
| P5-BE-063 | Create custom application metrics (users, invoices, campaigns, queue, emails) | todo | |
| P5-BE-064 | Create docker/prometheus/prometheus.yml (scrape config) | todo | |
| P5-BE-065 | Add postgres-exporter service | todo | |
| P5-BE-066 | Add redis-exporter service | todo | |
| P5-BE-067 | Add node-exporter service | todo | |
| P5-BE-068 | Create Grafana dashboards (app, business, infra, DB, queue) | todo | |
| P5-BE-069 | Configure Grafana alerting rules | todo | |
| P5-BE-070 | Create docker/grafana/ provisioning files | todo | |
| P5-BE-071 | Document monitoring setup in docs/monitoring.md | todo | |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P5-FE-030 | Add Grafana dashboard link in admin settings/sidebar | todo | |
