# Phase 6 — Task Tracking

> **Status**: Completed (local branch)
> **Prerequisite**: Phase 5 fully merged
> **Spec**: [docs/phases/phase6.md](../phases/phase6.md)

---

## Sprint 20 — Client Portal: Foundation & Invoice Viewing (Weeks 33-35)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P6-BE-001 | Create PortalAccessToken model (token generation, scopes) | done | |
| P6-BE-002 | Create PortalActivityLog model | done | |
| P6-BE-003 | Create PortalSettings model | done | |
| P6-BE-004 | Create factories for all portal models | done | |
| P6-BE-005 | Create PortalAuthController (magic link, verify, logout) | done | |
| P6-BE-006 | Create PortalAuthMiddleware (JWT, client resolution) | done | |
| P6-BE-007 | Create PortalInvoiceController (list, detail, PDF download) | done | |
| P6-BE-008 | Create PortalQuoteController (list, detail, PDF, accept, reject) | done | |
| P6-BE-009 | Create PortalDashboardController (client summary) | done | |
| P6-BE-010 | Create PortalAccessTokenController (admin-side: generate, revoke, logs) | done | |
| P6-BE-011 | Create PortalSettingsController (admin-side: portal config) | done | |
| P6-BE-012 | Create PortalInvitationMail (magic link email) | done | |
| P6-BE-013 | Create QuoteAcceptedNotification (notify freelancer) | done | |
| P6-BE-014 | Implement portal activity logging | done | |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P6-FE-001 | Create app/portal/ route group with dedicated layout | done | |
| P6-FE-002 | Create app/portal/auth/page.tsx (magic link request) | done | |
| P6-FE-003 | Create app/portal/auth/verify/[token]/page.tsx | done | |
| P6-FE-004 | Create app/portal/dashboard/page.tsx (client dashboard) | done | |
| P6-FE-005 | Create app/portal/invoices/page.tsx | done | |
| P6-FE-006 | Create app/portal/invoices/[id]/page.tsx (detail + pay) | done | |
| P6-FE-007 | Create app/portal/quotes/page.tsx | done | |
| P6-FE-008 | Create app/portal/quotes/[id]/page.tsx (accept/reject) | done | |
| P6-FE-009 | Create components/portal/portal-header.tsx | done | |
| P6-FE-010 | Create app/clients/[id]/portal/page.tsx (admin: manage access) | done | |
| P6-FE-011 | Create app/settings/portal/page.tsx (portal configuration) | done | |

---

## Sprint 21 — Client Portal: Online Payments (Weeks 35-36)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P6-BE-020 | Create PaymentIntent model | done | |
| P6-BE-021 | Create PaymentIntentFactory | done | |
| P6-BE-022 | Create StripePaymentService (create intent, confirm, refund) | done | |
| P6-BE-023 | Create PortalPaymentController (pay, payment-status) | done | |
| P6-BE-024 | Create StripeWebhookController (succeeded, failed, refunded) | done | |
| P6-BE-025 | Update invoice payment recording for portal payments | done | |
| P6-BE-026 | Create PaymentReceivedNotification | done | |
| P6-BE-027 | Create PaymentFailedNotification | done | |
| P6-BE-028 | Add payment settings to PortalSettingsController | done | |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P6-FE-020 | Integrate Stripe.js and @stripe/react-stripe-js | done | |
| P6-FE-021 | Create components/portal/payment-form.tsx (Stripe Elements) | done | |
| P6-FE-022 | Create app/portal/invoices/[id]/pay/page.tsx | done | |
| P6-FE-023 | Add "Pay Now" button on portal invoice detail | done | |
| P6-FE-024 | Create payment history section on portal dashboard | done | |
| P6-FE-025 | Admin: add Stripe config to portal settings page | done | |
| P6-FE-026 | Admin: add portal payment badge on invoice list/detail | done | |

---

## Sprint 22 — Expense Tracking (Weeks 37-39)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P6-BE-040 | Create ExpenseCategory model | done | |
| P6-BE-041 | Create Expense model (relationships, scopes, Searchable) | done | |
| P6-BE-042 | Create ExpenseCategoryFactory and ExpenseFactory | done | |
| P6-BE-043 | Create ExpensePolicy | done | |
| P6-BE-044 | Create ExpenseCategoryController (CRUD) | done | |
| P6-BE-045 | Create ExpenseController (CRUD, filters, receipt upload/download) | done | |
| P6-BE-046 | Create StoreExpenseRequest (validation, receipt max 10MB) | done | |
| P6-BE-047 | Create ExpenseReceiptService (upload, thumbnail, storage) | done | |
| P6-BE-048 | Create ExpenseReportService (totals, breakdowns, billable split) | done | |
| P6-BE-049 | Create ExpenseReportController (report, CSV export) | done | |
| P6-BE-050 | Implement multi-currency expense support | done | |
| P6-BE-051 | Create ExpenseCategorySeeder (10 defaults) | done | |
| P6-BE-052 | Configure Meilisearch index for Expense | done | |
| P6-BE-053 | Add expenses to DataExportService (GDPR) | done | |
| P6-BE-054 | Add expenses to DataImportService (CSV) | done | |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P6-FE-030 | Create stores/expenses.ts Zustand store | done | |
| P6-FE-031 | Create stores/expense-categories.ts Zustand store | done | |
| P6-FE-032 | Create app/expenses/page.tsx (list with filters) | done | |
| P6-FE-033 | Create app/expenses/create/page.tsx (form with receipt upload) | done | |
| P6-FE-034 | Create app/expenses/[id]/page.tsx (detail with receipt preview) | done | |
| P6-FE-035 | Create app/expenses/[id]/edit/page.tsx | done | |
| P6-FE-036 | Create components/expenses/receipt-upload.tsx (drag-and-drop + camera) | done | |
| P6-FE-037 | Create app/expenses/report/page.tsx (charts, breakdown, export) | done | |
| P6-FE-038 | Create app/settings/expense-categories/page.tsx | done | |
| P6-FE-039 | Add Expenses entry to sidebar navigation | done | |
| P6-FE-040 | Add expense summary widget to dashboard | done | |

---

## Sprint 23 — Financial Integration & Polish (Weeks 39-40)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P6-BE-060 | Create ProfitLossReportService | done | |
| P6-BE-061 | Create ProjectProfitabilityService | done | |
| P6-BE-062 | Create ProfitLossController | done | |
| P6-BE-063 | Create ProjectProfitabilityController | done | |
| P6-BE-064 | Update DashboardController with P&L and expense widgets | done | |
| P6-BE-065 | Add project expenses endpoint (GET /api/v1/projects/{id}/expenses) | done | |
| P6-BE-066 | Add billable expenses to invoice generation | done | |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P6-FE-050 | Create app/reports/profit-loss/page.tsx | done | |
| P6-FE-051 | Create app/reports/project-profitability/page.tsx | done | |
| P6-FE-052 | Add project expenses tab on project detail page | done | |
| P6-FE-053 | Add "Invoice billable expenses" action on project detail | done | |
| P6-FE-054 | Update dashboard with P&L widget and expense summary | done | |
| P6-FE-055 | Run full Playwright E2E suite for portal and expense flows | done | |
| P6-FE-056 | Run Vitest coverage >= 80% | done | |
