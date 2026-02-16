# Phase 2 — Task Tracking

> **Status**: In Progress
> **Prerequisite**: Phase 1 fully merged
> **Spec**: [docs/phases/phase2.md](../phases/phase2.md)

---

## Sprint 5 — Project Management (Weeks 7-8)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P2-BE-001 | Create Project model (UUID, relationships, scopes, Searchable) | done | claude |
| P2-BE-002 | Create ProjectFactory | done | claude |
| P2-BE-003 | Create ProjectPolicy | done | claude |
| P2-BE-004 | Create ProjectController (CRUD, filtering, sorting, pagination) | done | claude |
| P2-BE-005 | Create StoreProjectRequest | done | claude |
| P2-BE-006 | Implement reference auto-generation PRJ-YYYY-NNNN | done | claude |
| P2-BE-007 | Implement project status transition validation | done | claude |
| P2-BE-008 | Create ProjectResource / ProjectCollection | done | claude |
| P2-BE-009 | Create Task model (UUID, relationships, scopes) | done | claude |
| P2-BE-010 | Create TaskFactory | done | claude |
| P2-BE-011 | Create TaskController (CRUD + bulk reorder) | done | claude |
| P2-BE-012 | Create StoreTaskRequest | done | claude |
| P2-BE-013 | Implement task dependency validation (no circular deps) | done | claude |
| P2-BE-014 | Create TimeEntry model + TimeEntryController | done | claude |
| P2-BE-015 | Create StoreTimeEntryRequest | done | claude |
| P2-BE-016 | Implement computed fields on Project (time, progress, budget) | done | claude |
| P2-BE-017 | Implement task file attachment upload/download | done | claude |
| P2-BE-018 | Create ProjectObserver (log activity to client timeline) | done | claude |
| P2-BE-019 | Configure Meilisearch indexes for Project and Task | done | claude |
| P2-BE-020 | Extend SearchController for projects and tasks | done | claude |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P2-FE-001 | Create stores/projects.ts Zustand store | done | claude |
| P2-FE-002 | Create app/projects/page.tsx (data table) | done | claude |
| P2-FE-003 | Create app/projects/create/page.tsx | done | claude |
| P2-FE-004 | Create app/projects/[id]/page.tsx (tabs: Overview, Tasks, Time, Files, Invoices) | done | claude |
| P2-FE-005 | Create components/projects/project-overview.tsx | done | claude |
| P2-FE-006 | Create components/projects/task-kanban-board.tsx (drag-and-drop) | done | claude |
| P2-FE-007 | Create components/projects/task-list-view.tsx | done | claude |
| P2-FE-008 | Create components/projects/task-detail-drawer.tsx | done | claude |
| P2-FE-009 | Create components/projects/time-entry-form.tsx | done | claude |
| P2-FE-010 | Create components/projects/project-timeline.tsx (Gantt) | done | claude |
| P2-FE-011 | Add project filter bar (status, client, date range) | done | claude |
| P2-FE-012 | Wire project data to client profile page (Projects tab) | done | claude |

---

## Sprint 6 — Invoice Management (Weeks 9-11)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P2-BE-030 | Create Invoice model (UUID, relationships, scopes, Searchable) | done | codex |
| P2-BE-031 | Create InvoiceFactory, LineItemFactory, PaymentFactory | done | codex |
| P2-BE-032 | Create InvoicePolicy | done | codex |
| P2-BE-033 | Create InvoiceController (CRUD, filterable, draft-only delete) | done | codex |
| P2-BE-034 | Create StoreInvoiceRequest | done | codex |
| P2-BE-035 | Implement auto-generated invoice number FAC-YYYY-NNNN | done | codex |
| P2-BE-036 | Create LineItem model (polymorphic) | done | codex |
| P2-BE-037 | Create InvoiceCalculationService (subtotal, VAT, discount, total) | done | codex |
| P2-BE-038 | Implement invoice status transitions | done | codex |
| P2-BE-039 | Create Payment model + PaymentController | done | codex |
| P2-BE-040 | Create InvoicePdfService (DomPDF + Blade template) | done | codex |
| P2-BE-041 | Create Blade template for invoice PDF | done | codex |
| P2-BE-042 | Create SendInvoiceJob (queued, PDF attachment) | done | codex |
| P2-BE-043 | Create email template invoice-sent.blade.php | done | codex |
| P2-BE-044 | Create MarkOverdueInvoicesCommand (daily scheduler) | done | codex |
| P2-BE-045 | Register scheduler entry for overdue marking | done | codex |
| P2-BE-046 | Implement invoice duplication | done | codex |
| P2-BE-047 | Implement invoice from project time entries | done | codex |
| P2-BE-048 | Create settings endpoints for financial defaults | done | codex |
| P2-BE-049 | Log invoice events to client activity timeline | done | codex |
| P2-BE-050 | Configure Meilisearch index for Invoice | done | codex |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P2-FE-020 | Create stores/invoices.ts Zustand store | done | codex |
| P2-FE-021 | Create app/finances/invoices/page.tsx (data table) | done | codex |
| P2-FE-022 | Create app/finances/invoices/create/page.tsx | done | codex |
| P2-FE-023 | Create components/invoices/line-items-editor.tsx | done | codex |
| P2-FE-024 | Create components/invoices/invoice-pdf-preview.tsx | done | codex |
| P2-FE-025 | Create app/finances/invoices/[id]/page.tsx (detail + actions) | done | codex |
| P2-FE-026 | Create components/invoices/record-payment-modal.tsx | done | codex |
| P2-FE-027 | Create components/invoices/send-invoice-modal.tsx | done | codex |
| P2-FE-028 | Implement invoice filter bar (status, client, date range) | done | codex |
| P2-FE-029 | Create app/settings/invoicing/page.tsx | done | codex |
| P2-FE-030 | Wire invoices to client profile + project detail | done | codex |

---

## Sprint 7 — Quote Management (Weeks 12-13)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P2-BE-060 | Create Quote model (UUID, relationships, scopes, Searchable) | done | codex |
| P2-BE-061 | Create QuoteFactory | done | codex |
| P2-BE-062 | Create QuotePolicy | done | codex |
| P2-BE-063 | Create QuoteController (CRUD, send, convert) | done | codex |
| P2-BE-064 | Create StoreQuoteRequest | done | codex |
| P2-BE-065 | Implement reference auto-generation DEV-YYYY-NNNN | done | codex |
| P2-BE-066 | Implement quote status transitions | done | codex |
| P2-BE-067 | Implement validity period (default 30 days) | done | codex |
| P2-BE-068 | Create ConvertQuoteToInvoiceService | done | codex |
| P2-BE-069 | Create QuotePdfService | done | codex |
| P2-BE-070 | Create SendQuoteJob (queued) | done | codex |
| P2-BE-071 | Create MarkExpiredQuotesCommand (daily scheduler) | done | codex |
| P2-BE-072 | Log quote events to client activity timeline | done | codex |
| P2-BE-073 | Configure Meilisearch index for Quote | done | codex |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P2-FE-040 | Create stores/quotes.ts Zustand store | done | codex |
| P2-FE-041 | Create app/finances/quotes/page.tsx (data table) | done | codex |
| P2-FE-042 | Create app/finances/quotes/create/page.tsx | done | codex |
| P2-FE-043 | Create app/finances/quotes/[id]/page.tsx (detail + actions) | done | codex |
| P2-FE-044 | Implement "Convert to Invoice" button + confirmation | done | codex |
| P2-FE-045 | Create components/quotes/quote-pdf-preview.tsx | done | codex |
| P2-FE-046 | Wire quotes to client profile (Finances tab) | done | codex |

---

## Sprint 8 — Credit Notes & Financial Reports (Weeks 13-14)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P2-BE-080 | Create CreditNote model (UUID, relationships, scopes, Searchable) | done | codex |
| P2-BE-081 | Create CreditNoteFactory | done | codex |
| P2-BE-082 | Create CreditNotePolicy | done | codex |
| P2-BE-083 | Create CreditNoteController (CRUD, send, apply) | done | codex |
| P2-BE-084 | Create StoreCreditNoteRequest | done | codex |
| P2-BE-085 | Implement reference auto-generation AVO-YYYY-NNNN | done | codex |
| P2-BE-086 | Implement ApplyCreditNoteService | done | codex |
| P2-BE-087 | Create CreditNotePdfService | done | codex |
| P2-BE-088 | Create SendCreditNoteJob (queued) | done | codex |
| P2-BE-089 | Create ReportController (revenue, outstanding, VAT) | done | codex |
| P2-BE-090 | Create RevenueReportService | done | codex |
| P2-BE-091 | Create OutstandingReportService | done | codex |
| P2-BE-092 | Create VatSummaryReportService | done | codex |
| P2-BE-093 | Implement report export (CSV + PDF) | done | codex |
| P2-BE-094 | Create FinancialSummaryService (yearly summary) | done | codex |
| P2-BE-095 | Enhance DashboardController (financial metrics, revenue trend) | done | codex |
| P2-BE-096 | Enhance DashboardController (upcoming deadlines) | done | codex |
| P2-BE-097 | Log credit note events to client timeline | done | codex |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P2-FE-050 | Create stores/creditNotes.ts Zustand store | done | codex |
| P2-FE-051 | Create app/finances/credit-notes/page.tsx | done | codex |
| P2-FE-052 | Create app/finances/credit-notes/create/page.tsx | done | codex |
| P2-FE-053 | Create app/finances/credit-notes/[id]/page.tsx | done | codex |
| P2-FE-054 | Create app/finances/reports/page.tsx (tabs: Revenue, Outstanding, VAT) | done | codex |
| P2-FE-055 | Create components/reports/revenue-chart.tsx (Recharts) | done | codex |
| P2-FE-056 | Create components/reports/outstanding-table.tsx | done | codex |
| P2-FE-057 | Create components/reports/vat-summary-table.tsx | done | codex |
| P2-FE-058 | Enhance dashboard (financial widgets + revenue chart + deadlines) | done | codex |
| P2-FE-059 | Create finances sidebar navigation | done | codex |
| P2-FE-060 | Wire credit notes to invoice detail page | done | codex |
