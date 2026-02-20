# Phase 7 — Task Tracking

> **Status**: Planned
> **Prerequisite**: Phase 6 fully merged
> **Spec**: [docs/phases/phase7.md](../phases/phase7.md)

---

## Sprint 24 — Accounting & Tax Compliance (Weeks 41–44)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P7-BE-001 | Create FecExportService (journal entries from invoices, credit notes, payments, expenses) | todo | |
| P7-BE-002 | Create FecExportController (GET /api/v1/accounting/fec) | todo | |
| P7-BE-003 | Create VatDeclarationService (TVA collectée/déductible/net, monthly/quarterly) | todo | |
| P7-BE-004 | Create VatDeclarationController (GET /api/v1/accounting/vat, PDF + CSV export) | todo | |
| P7-BE-005 | Create AccountingExportService (Pennylane CSV, Sage CSV, generic CSV) | todo | |
| P7-BE-006 | Create AccountingExportController (GET /api/v1/accounting/export) | todo | |
| P7-BE-007 | Create FiscalYearSummaryService (revenue, expenses, profit, VAT position) | todo | |
| P7-BE-008 | Create FiscalYearSummaryController (GET /api/v1/accounting/fiscal-year) | todo | |
| P7-BE-009 | Update AccountingSettingsController (journal codes, fiscal year start month) | todo | |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P7-FE-001 | Create app/accounting/page.tsx (accounting hub with KPI cards) | todo | |
| P7-FE-002 | Create app/accounting/fec/page.tsx (FEC export wizard) | todo | |
| P7-FE-003 | Create app/accounting/vat/page.tsx (VAT declaration report + charts) | todo | |
| P7-FE-004 | Create app/accounting/export/page.tsx (accounting software export) | todo | |
| P7-FE-005 | Create app/accounting/fiscal-year/page.tsx (fiscal year closing summary) | todo | |
| P7-FE-006 | Create app/settings/accounting/page.tsx (journal codes, fiscal year start) | todo | |
| P7-FE-007 | Add Accounting entry to sidebar navigation (under Reports) | todo | |

---

## Sprint 25 — Public API & Outbound Webhooks (Weeks 45–47)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P7-BE-020 | Create PersonalAccessTokenController (list, create with scopes + expiry, revoke) | todo | |
| P7-BE-021 | Define PAT scopes (read/write per entity) and scope-guard middleware | todo | |
| P7-BE-022 | Create WebhookEndpoint model (relationships, scopes) | todo | |
| P7-BE-023 | Create WebhookDelivery model (relationships, scopes) | todo | |
| P7-BE-024 | Create WebhookEndpointController (CRUD + test delivery) | todo | |
| P7-BE-025 | Create WebhookDispatchService (payload build, HMAC-SHA256 signing, delivery recording) | todo | |
| P7-BE-026 | Create WebhookDispatchJob (queued, exponential backoff retry, max 5 attempts) | todo | |
| P7-BE-027 | Create WebhookDeliveryController (delivery log, manual retry) | todo | |
| P7-BE-028 | Integrate WebhookDispatchService into all documented application events (14 events) | todo | |
| P7-BE-029 | Integrate dedoc/scramble to auto-generate OpenAPI 3.1 spec at /api/docs | todo | |
| P7-BE-030 | Scope-guard middleware: enforce PAT abilities on API routes | todo | |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P7-FE-020 | Create app/settings/api-tokens/page.tsx (manage PATs with one-time token display) | todo | |
| P7-FE-021 | Create app/settings/webhooks/page.tsx (manage webhook endpoints) | todo | |
| P7-FE-022 | Create app/settings/webhooks/[id]/deliveries/page.tsx (delivery log with retry) | todo | |
| P7-FE-023 | Create components/settings/webhook-form.tsx (URL + event checkboxes) | todo | |
| P7-FE-024 | Create components/settings/api-token-form.tsx (name + scope checkboxes + expiry) | todo | |
| P7-FE-025 | Add "API & Webhooks" section to settings sidebar | todo | |

---

## Sprint 26 — Prospect & Lead Pipeline (Weeks 48–51)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P7-BE-040 | Create Lead model (relationships, scopes, Searchable) | todo | |
| P7-BE-041 | Create LeadActivity model (relationships to Lead) | todo | |
| P7-BE-042 | Create LeadFactory and LeadActivityFactory | todo | |
| P7-BE-043 | Create LeadPolicy (user owns lead) | todo | |
| P7-BE-044 | Create LeadController (CRUD, status transition, position reorder) | todo | |
| P7-BE-045 | Create StoreLeadRequest (validation: email, phone E.164, probability, value) | todo | |
| P7-BE-046 | Create LeadConversionService (create Client from Lead, link, set converted_at) | todo | |
| P7-BE-047 | Create LeadConversionController (POST /api/v1/leads/{id}/convert) | todo | |
| P7-BE-048 | Create LeadPipelineController (GET /api/v1/leads/pipeline — grouped by status) | todo | |
| P7-BE-049 | Create LeadAnalyticsService (win rate, avg deal, avg time to close, pipeline value) | todo | |
| P7-BE-050 | Create LeadAnalyticsController (GET /api/v1/leads/analytics) | todo | |
| P7-BE-051 | Create LeadActivityController (list + create + delete, nested under lead) | todo | |
| P7-BE-052 | Dispatch webhooks for lead events (lead.created, lead.status_changed, lead.converted) | todo | |
| P7-BE-053 | Configure Meilisearch index for Lead | todo | |
| P7-BE-054 | Add leads to DataExportService (GDPR export) | todo | |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P7-FE-030 | Create stores/leads.ts Zustand store (CRUD, pipeline, analytics, activities) | todo | |
| P7-FE-031 | Create app/leads/page.tsx (Kanban/list toggle, filters, quick-create) | todo | |
| P7-FE-032 | Create app/leads/create/page.tsx (lead creation form) | todo | |
| P7-FE-033 | Create app/leads/[id]/page.tsx (detail + activity timeline + status transition + convert button) | todo | |
| P7-FE-034 | Create app/leads/[id]/edit/page.tsx (edit lead form) | todo | |
| P7-FE-035 | Create app/leads/analytics/page.tsx (funnel chart, win rate, source breakdown) | todo | |
| P7-FE-036 | Create components/leads/lead-kanban.tsx (dnd-kit Kanban board) | todo | |
| P7-FE-037 | Create components/leads/lead-activity-form.tsx (inline activity logger) | todo | |
| P7-FE-038 | Create components/leads/convert-to-client-dialog.tsx (conversion confirmation) | todo | |
| P7-FE-039 | Add Leads entry to sidebar navigation (above Clients, with pipeline value badge) | todo | |
| P7-FE-040 | Add pipeline summary widget to dashboard (total value, funnel mini, win rate) | todo | |
