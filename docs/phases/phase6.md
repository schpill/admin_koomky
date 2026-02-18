# Phase 6 — Client Portal & Expense Tracking (v1.2)

| Field               | Value                                          |
|---------------------|------------------------------------------------|
| **Phase**           | 6 of 6                                         |
| **Name**            | Client Portal & Expense Tracking               |
| **Duration**        | Weeks 33–40 (8 weeks)                          |
| **Milestone**       | M6 — v1.2.0 Release                            |
| **PRD Sections**    | Post-Release Roadmap v1.2                       |
| **Prerequisite**    | Phase 5 fully completed and validated           |
| **Status**          | Implemented on feature branch                  |

---

## 1. Phase Objectives

| ID       | Objective                                                                                    |
|----------|----------------------------------------------------------------------------------------------|
| P6-OBJ-1 | Deliver a self-service client portal where clients can view invoices, quotes, and make payments |
| P6-OBJ-2 | Implement online payment integration (Stripe) for invoice payments from the portal           |
| P6-OBJ-3 | Deliver expense tracking with receipt upload, categorization, and project allocation          |
| P6-OBJ-4 | Integrate expenses into financial reports (profit/loss, per-project profitability)            |
| P6-OBJ-5 | Maintain >= 80% test coverage on both back-end and front-end                                |

---

## 2. Entry Criteria

- Phase 5 exit criteria 100% satisfied.
- All Phase 5 CI checks green on `main`.
- v1.1.0 tagged and deployed to production.
- Recurring invoices, multi-currency, and calendar features stable in production.

---

## 3. Scope — Requirement Traceability

| Feature                              | Priority | Included |
|--------------------------------------|----------|----------|
| Client portal (view/pay invoices)    | Medium   | Yes      |
| Expense tracking                     | Medium   | Yes      |

---

## 4. Detailed Sprint Breakdown

### 4.1 Sprint 20 — Client Portal: Foundation & Invoice Viewing (Weeks 33–35)

#### 4.1.1 Database Migrations

| Migration                               | Description                                              |
|-----------------------------------------|----------------------------------------------------------|
| `create_portal_access_tokens_table`     | id (UUID), client_id (FK), token (VARCHAR 64, UNIQUE, indexed), email (VARCHAR 255), expires_at (TIMESTAMP), last_used_at (TIMESTAMP, nullable), is_active (BOOLEAN, default true), created_by_user_id (FK), timestamps. Index: token, client_id. |
| `create_portal_activity_logs_table`     | id (UUID), client_id (FK), portal_access_token_id (FK), action ENUM('login', 'view_invoice', 'view_quote', 'download_pdf', 'make_payment', 'accept_quote', 'reject_quote'), entity_type (VARCHAR, nullable), entity_id (UUID, nullable), ip_address (VARCHAR 45), user_agent (TEXT, nullable), timestamps. Index: client_id, created_at. |
| `create_portal_settings_table`          | id (UUID), user_id (FK), portal_enabled (BOOLEAN, default false), custom_logo (VARCHAR 500, nullable), custom_color (VARCHAR 7, nullable — hex), welcome_message (TEXT, nullable), payment_enabled (BOOLEAN, default false), quote_acceptance_enabled (BOOLEAN, default true), timestamps. Index: user_id. |

#### 4.1.2 Back-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P6-BE-001 | Create `PortalAccessToken` model — relationships (client, logs), scopes (active, notExpired), token generation (crypto-safe random 64 chars) | Roadmap |
| P6-BE-002 | Create `PortalActivityLog` model — relationships, scopes (byClient, byAction)         | Roadmap       |
| P6-BE-003 | Create `PortalSettings` model — relationships (user)                                  | Roadmap       |
| P6-BE-004 | Create factories for all portal models                                                | §10.3.1       |
| P6-BE-005 | Create `PortalAuthController` — magic link authentication:                             | Roadmap       |
|           | — `POST /portal/auth/request` — email input, generate token, send magic link email     |              |
|           | — `GET /portal/auth/verify/{token}` — verify token, create session (stateless JWT)     |              |
|           | — `POST /portal/auth/logout` — invalidate portal session                               |              |
| P6-BE-006 | Create `PortalAuthMiddleware` — verify portal JWT, resolve client, check token active/not expired | Roadmap |
| P6-BE-007 | Create `PortalInvoiceController` — client-scoped, read-only:                           | Roadmap       |
|           | — `GET /portal/invoices` — list client's invoices (sent, paid, overdue only)           |              |
|           | — `GET /portal/invoices/{id}` — view invoice detail                                    |              |
|           | — `GET /portal/invoices/{id}/pdf` — download invoice PDF                                |              |
| P6-BE-008 | Create `PortalQuoteController` — client-scoped:                                        | Roadmap       |
|           | — `GET /portal/quotes` — list client's quotes (sent, accepted, rejected)               |              |
|           | — `GET /portal/quotes/{id}` — view quote detail                                        |              |
|           | — `GET /portal/quotes/{id}/pdf` — download quote PDF                                   |              |
|           | — `POST /portal/quotes/{id}/accept` — accept quote (updates status)                    |              |
|           | — `POST /portal/quotes/{id}/reject` — reject quote with optional reason                |              |
| P6-BE-009 | Create `PortalDashboardController` — client summary:                                   | Roadmap       |
|           | — Outstanding invoices count and total                                                  |              |
|           | — Recent invoices (last 5)                                                              |              |
|           | — Recent quotes (last 5)                                                                |              |
| P6-BE-010 | Create `PortalAccessTokenController` (admin-side) — CRUD tokens for a client:         | Roadmap       |
|           | — Generate portal access for a client contact (sends magic link email)                  |              |
|           | — Revoke access tokens                                                                  |              |
|           | — View access logs                                                                      |              |
| P6-BE-011 | Create `PortalSettingsController` (admin-side) — configure portal appearance and features | Roadmap |
| P6-BE-012 | Create `PortalInvitationMail` — email template with magic link to access portal       | Roadmap       |
| P6-BE-013 | Create `QuoteAcceptedNotification` — notify freelancer when client accepts/rejects quote | Roadmap |
| P6-BE-014 | Log all portal activity via `PortalActivityLog`                                       | Roadmap       |

#### 4.1.3 Back-end Tests (TDD)

| Test File                                                        | Test Cases                                                  |
|------------------------------------------------------------------|-------------------------------------------------------------|
| `tests/Feature/Portal/PortalAuthTest.php`                        | Request magic link, verify valid token, reject expired token, reject inactive token, logout invalidates session |
| `tests/Feature/Portal/PortalInvoiceTest.php`                     | List invoices (only own client), view invoice detail, download PDF, cannot see draft invoices, cannot see other clients' invoices |
| `tests/Feature/Portal/PortalQuoteTest.php`                       | List quotes, view detail, accept quote (status changes), reject quote with reason, cannot accept already accepted quote |
| `tests/Feature/Portal/PortalDashboardTest.php`                   | Dashboard returns correct counts and totals, recent items |
| `tests/Feature/Portal/PortalAccessTokenTest.php`                 | Admin creates token, revokes token, views logs              |
| `tests/Feature/Portal/PortalSettingsTest.php`                    | Update settings, portal disabled returns 403                |
| `tests/Feature/Portal/PortalActivityLogTest.php`                 | Actions logged with IP, user agent, entity references       |

#### 4.1.4 Front-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P6-FE-001 | Create `app/portal/` route group with dedicated layout (minimal, branded):             | Roadmap       |
|           | — Header with custom logo / Koomky branding                                            |              |
|           | — Sidebar: Dashboard, Invoices, Quotes                                                 |              |
|           | — No access to admin features                                                          |              |
| P6-FE-002 | Create `app/portal/auth/page.tsx` — magic link request form (email input)              | Roadmap       |
| P6-FE-003 | Create `app/portal/auth/verify/[token]/page.tsx` — token verification, redirect to portal dashboard | Roadmap |
| P6-FE-004 | Create `app/portal/dashboard/page.tsx` — client dashboard:                             | Roadmap       |
|           | — Outstanding balance card                                                              |              |
|           | — Recent invoices list                                                                  |              |
|           | — Recent quotes list                                                                    |              |
|           | — Welcome message from freelancer                                                       |              |
| P6-FE-005 | Create `app/portal/invoices/page.tsx` — invoices list with status badges, amounts, due dates | Roadmap |
| P6-FE-006 | Create `app/portal/invoices/[id]/page.tsx` — invoice detail with line items, totals, PDF download, pay button | Roadmap |
| P6-FE-007 | Create `app/portal/quotes/page.tsx` — quotes list with status, validity date           | Roadmap       |
| P6-FE-008 | Create `app/portal/quotes/[id]/page.tsx` — quote detail with accept/reject buttons    | Roadmap       |
| P6-FE-009 | Create `components/portal/portal-header.tsx` — branded header with client name, logout | Roadmap       |
| P6-FE-010 | Create admin page `app/clients/[id]/portal/page.tsx` — manage client portal access:   | Roadmap       |
|           | — Generate/revoke access tokens                                                        |              |
|           | — View access activity log                                                              |              |
| P6-FE-011 | Create `app/settings/portal/page.tsx` — portal configuration:                          | Roadmap       |
|           | — Enable/disable portal                                                                 |              |
|           | — Custom logo upload, custom color picker                                               |              |
|           | — Welcome message editor                                                                |              |
|           | — Payment and quote acceptance toggles                                                  |              |

#### 4.1.5 Front-end Tests

| Test File                                                        | Test Cases                                                |
|------------------------------------------------------------------|-----------------------------------------------------------|
| `tests/e2e/portal/portal-auth.spec.ts`                           | Request magic link, verify token, access portal, logout    |
| `tests/e2e/portal/portal-invoices.spec.ts`                       | View invoices list, view detail, download PDF              |
| `tests/e2e/portal/portal-quotes.spec.ts`                         | View quotes, accept quote, reject quote with reason        |
| `tests/components/portal/portal-header.test.ts`                  | Renders branded header, client name, logout action         |

---

### 4.2 Sprint 21 — Client Portal: Online Payments (Weeks 35–36)

#### 4.2.1 Database Migrations

| Migration                               | Description                                              |
|-----------------------------------------|----------------------------------------------------------|
| `create_payment_intents_table`          | id (UUID), invoice_id (FK), client_id (FK), stripe_payment_intent_id (VARCHAR 255, nullable), amount (DECIMAL 12,2), currency (VARCHAR 3), status ENUM('pending', 'processing', 'succeeded', 'failed', 'cancelled', 'refunded'), payment_method (VARCHAR 50, nullable), failure_reason (TEXT, nullable), paid_at (TIMESTAMP, nullable), refunded_at (TIMESTAMP, nullable), metadata (JSONB, nullable), timestamps. Indexes: invoice_id, stripe_payment_intent_id, status. |
| `add_stripe_fields_to_settings`         | Add `stripe_publishable_key` (TEXT, nullable, encrypted), `stripe_secret_key` (TEXT, nullable, encrypted), `stripe_webhook_secret` (TEXT, nullable, encrypted), `payment_methods_enabled` (JSONB, default '["card"]') to user settings or portal_settings. |

#### 4.2.2 Back-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P6-BE-020 | Create `PaymentIntent` model — relationships (invoice, client), scopes (byStatus)     | Roadmap       |
| P6-BE-021 | Create `PaymentIntentFactory`                                                         | §10.3.1       |
| P6-BE-022 | Create `StripePaymentService` — manage Stripe integration:                             | Roadmap       |
|           | — `createPaymentIntent(invoice)` — create Stripe PaymentIntent, return client_secret   |              |
|           | — `confirmPayment(paymentIntentId)` — check payment status                             |              |
|           | — `refundPayment(paymentIntentId, amount)` — create refund                             |              |
|           | — Dynamically configure Stripe keys from user settings (per-freelancer)                |              |
| P6-BE-023 | Create `PortalPaymentController` — portal-side endpoints:                              | Roadmap       |
|           | — `POST /portal/invoices/{id}/pay` — create payment intent, return client_secret       |              |
|           | — `GET /portal/invoices/{id}/payment-status` — check payment status                    |              |
| P6-BE-024 | Create `StripeWebhookController` — handle Stripe webhooks:                             | Roadmap       |
|           | — `payment_intent.succeeded` — mark PaymentIntent succeeded, record payment on invoice, update invoice status to paid |  |
|           | — `payment_intent.payment_failed` — update PaymentIntent status, log failure reason    |              |
|           | — `charge.refunded` — update PaymentIntent, reverse invoice payment if full refund     |              |
|           | — Verify webhook signature using stripe_webhook_secret                                  |              |
| P6-BE-025 | Update invoice payment recording — link portal payments to invoice payment records     | Roadmap       |
| P6-BE-026 | Create `PaymentReceivedNotification` — notify freelancer when client pays via portal   | Roadmap       |
| P6-BE-027 | Create `PaymentFailedNotification` — notify client when payment fails (retry suggestion) | Roadmap |
| P6-BE-028 | Add payment settings to `PortalSettingsController` — Stripe keys configuration         | Roadmap       |

#### 4.2.3 Back-end Tests (TDD)

| Test File                                                        | Test Cases                                                  |
|------------------------------------------------------------------|-------------------------------------------------------------|
| `tests/Unit/Services/StripePaymentServiceTest.php`               | Create payment intent, confirm payment, refund, handle Stripe API errors, dynamic key configuration |
| `tests/Feature/Portal/PortalPaymentTest.php`                     | Create payment for invoice, check status, cannot pay already-paid invoice, cannot pay other client's invoice |
| `tests/Feature/Portal/StripeWebhookTest.php`                     | Payment succeeded → invoice marked paid, payment failed → status updated, refund processed, invalid signature rejected |
| `tests/Feature/Portal/PaymentNotificationTest.php`               | Freelancer notified on payment, client notified on failure  |

#### 4.2.4 Front-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P6-FE-020 | Integrate Stripe.js and `@stripe/react-stripe-js` in portal                           | Roadmap       |
| P6-FE-021 | Create `components/portal/payment-form.tsx` — Stripe Elements:                        | Roadmap       |
|           | — Card element (or Payment Element for multi-method support)                           |              |
|           | — Amount display with currency                                                         |              |
|           | — Pay button with loading state                                                        |              |
|           | — Success/failure states                                                                |              |
| P6-FE-022 | Create `app/portal/invoices/[id]/pay/page.tsx` — payment page:                        | Roadmap       |
|           | — Invoice summary (number, amount, due date)                                            |              |
|           | — Payment form                                                                          |              |
|           | — Success confirmation with receipt                                                     |              |
| P6-FE-023 | Add "Pay Now" button on portal invoice detail page (only for unpaid/overdue)           | Roadmap       |
| P6-FE-024 | Create payment history section on portal dashboard — recent payments with status       | Roadmap       |
| P6-FE-025 | Admin-side: add Stripe configuration to portal settings page                           | Roadmap       |
| P6-FE-026 | Admin-side: add payment status indicators on invoice list and detail (paid via portal badge) | Roadmap |

#### 4.2.5 Front-end Tests

| Test File                                                        | Test Cases                                                |
|------------------------------------------------------------------|-----------------------------------------------------------|
| `tests/components/portal/payment-form.test.ts`                   | Renders Stripe elements, handles payment success, handles failure, loading state |
| `tests/e2e/portal/portal-payment.spec.ts`                        | Navigate to pay, complete payment (Stripe test mode), verify invoice status updated |

---

### 4.3 Sprint 22 — Expense Tracking (Weeks 37–39)

#### 4.3.1 Database Migrations

| Migration                          | Description                                              |
|------------------------------------|----------------------------------------------------------|
| `create_expense_categories_table`  | id (UUID), user_id (FK), name (VARCHAR 255), color (VARCHAR 7, nullable — hex), icon (VARCHAR 50, nullable), is_default (BOOLEAN, default false), timestamps. Index: user_id. Seed defaults: Travel, Software, Hardware, Office Supplies, Meals, Professional Services, Marketing, Training, Subscriptions, Other. |
| `create_expenses_table`            | id (UUID), user_id (FK), expense_category_id (FK), project_id (FK, nullable), client_id (FK, nullable), description (VARCHAR 500), amount (DECIMAL 12,2), currency (VARCHAR 3, default 'EUR'), base_currency_amount (DECIMAL 12,2, nullable), tax_amount (DECIMAL 12,2, default 0), tax_rate (DECIMAL 5,2, nullable), date (DATE), payment_method ENUM('cash', 'card', 'bank_transfer', 'other'), is_billable (BOOLEAN, default false), is_reimbursable (BOOLEAN, default false), reimbursed_at (TIMESTAMP, nullable), vendor (VARCHAR 255, nullable), reference (VARCHAR 255, nullable — receipt number, transaction ID), notes (TEXT, nullable), receipt_path (VARCHAR 500, nullable), receipt_filename (VARCHAR 255, nullable), receipt_mime_type (VARCHAR 100, nullable), status ENUM('pending', 'approved', 'rejected'), timestamps. Indexes: user_id, expense_category_id, project_id, client_id, date, status. |

#### 4.3.2 Back-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P6-BE-040 | Create `ExpenseCategory` model — relationships (user, expenses), scopes (default, custom) | Roadmap |
| P6-BE-041 | Create `Expense` model — relationships (user, category, project, client), scopes (byDateRange, byCategory, byProject, billable, reimbursable), Searchable | Roadmap |
| P6-BE-042 | Create `ExpenseCategoryFactory` and `ExpenseFactory`                                  | §10.3.1       |
| P6-BE-043 | Create `ExpensePolicy` — user owns expense                                            | NFR-SEC-004   |
| P6-BE-044 | Create `ExpenseCategoryController` — CRUD for custom categories                       | Roadmap       |
| P6-BE-045 | Create `ExpenseController` — full CRUD with filtering:                                 | Roadmap       |
|           | — `GET /api/v1/expenses` — list with filters: date range, category, project, client, billable, status |  |
|           | — `POST /api/v1/expenses` — create with optional receipt upload                        |              |
|           | — `PUT /api/v1/expenses/{id}` — update                                                 |              |
|           | — `DELETE /api/v1/expenses/{id}` — delete                                               |              |
|           | — `POST /api/v1/expenses/{id}/receipt` — upload/replace receipt                        |              |
|           | — `GET /api/v1/expenses/{id}/receipt` — download receipt                                |              |
| P6-BE-046 | Create `StoreExpenseRequest` — validate amount, date, category, optional project/client, receipt file (max 10MB, image/PDF) | Roadmap |
| P6-BE-047 | Create `ExpenseReceiptService` — handle receipt upload to S3/local storage, generate thumbnail for images | Roadmap |
| P6-BE-048 | Create `ExpenseReportService` — generate expense reports:                              | Roadmap       |
|           | — Total expenses by period (daily, weekly, monthly, yearly)                             |              |
|           | — Breakdown by category (pie chart data)                                                |              |
|           | — Breakdown by project                                                                  |              |
|           | — Billable vs non-billable                                                              |              |
|           | — Tax deductible summary                                                                |              |
| P6-BE-049 | Create `ExpenseReportController` — report endpoints:                                   | Roadmap       |
|           | — `GET /api/v1/expenses/report` — aggregated report with date range filter             |              |
|           | — `GET /api/v1/expenses/report/export` — CSV export of expenses                        |              |
| P6-BE-050 | Implement multi-currency expense support — use CurrencyConversionService from Phase 5 to compute base_currency_amount | Roadmap |
| P6-BE-051 | Create `ExpenseCategorySeeder` — seed default categories                               | Roadmap       |
| P6-BE-052 | Configure Meilisearch index for Expense (searchable: description, vendor, reference, notes) | FR-SRC-002 |
| P6-BE-053 | Add expenses to data export (DataExportService) — include in GDPR full export          | NFR-SEC-008   |
| P6-BE-054 | Add expenses to data import (DataImportService) — CSV import for expenses              | FR-SET-007    |

#### 4.3.3 Back-end Tests (TDD)

| Test File                                                        | Test Cases                                                  |
|------------------------------------------------------------------|-------------------------------------------------------------|
| `tests/Unit/Models/ExpenseTest.php`                              | Factory, relationships, scopes (byDateRange, byCategory, billable) |
| `tests/Unit/Services/ExpenseReportServiceTest.php`               | Total by period, category breakdown, project breakdown, billable split, empty data handling |
| `tests/Unit/Services/ExpenseReceiptServiceTest.php`              | Upload receipt, generate thumbnail, delete receipt, invalid file rejected |
| `tests/Feature/Expense/ExpenseCategoryCrudTest.php`              | CRUD categories, cannot delete default, validation errors   |
| `tests/Feature/Expense/ExpenseCrudTest.php`                      | Create, read, update, delete, filter by date/category/project/billable, receipt upload/download |
| `tests/Feature/Expense/ExpenseReportTest.php`                    | Report aggregations correct, CSV export, date range filtering |
| `tests/Feature/Expense/ExpenseMultiCurrencyTest.php`             | Create expense in USD, base_currency_amount computed in EUR  |
| `tests/Feature/Expense/ExpenseImportExportTest.php`              | CSV import, GDPR export includes expenses                    |

#### 4.3.4 Front-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P6-FE-030 | Create `stores/expenses.ts` Zustand store — CRUD, filters, report data                | §6.2.2        |
| P6-FE-031 | Create `stores/expense-categories.ts` Zustand store — CRUD categories                 | §6.2.2        |
| P6-FE-032 | Create `app/expenses/page.tsx` — expenses list:                                        | Roadmap       |
|           | — Data table: date, description, category badge, amount, project, billable badge, receipt icon, status |  |
|           | — Filters: date range picker, category, project, billable toggle, status               |              |
|           | — Quick-add button                                                                      |              |
|           | — Bulk actions: categorize, delete                                                      |              |
| P6-FE-033 | Create `app/expenses/create/page.tsx` — expense form:                                  | Roadmap       |
|           | — Amount input with currency selector                                                   |              |
|           | — Category selector with color indicators                                               |              |
|           | — Date picker                                                                            |              |
|           | — Project selector (optional)                                                            |              |
|           | — Client selector (optional, auto-filled from project)                                   |              |
|           | — Billable / reimbursable toggles                                                        |              |
|           | — Receipt upload (drag-and-drop, camera capture on mobile)                               |              |
|           | — Vendor, reference, notes fields                                                        |              |
| P6-FE-034 | Create `app/expenses/[id]/page.tsx` — expense detail with receipt preview               | Roadmap       |
| P6-FE-035 | Create `app/expenses/[id]/edit/page.tsx` — edit expense                                | Roadmap       |
| P6-FE-036 | Create `components/expenses/receipt-upload.tsx` — drag-and-drop + camera:              | Roadmap       |
|           | — Image preview with zoom                                                                |              |
|           | — PDF preview                                                                            |              |
|           | — Replace/remove receipt                                                                 |              |
| P6-FE-037 | Create `app/expenses/report/page.tsx` — expense reports:                               | Roadmap       |
|           | — Summary cards: total, average/month, billable total, tax total                         |              |
|           | — Category breakdown pie chart                                                           |              |
|           | — Monthly trend bar chart                                                                |              |
|           | — Project allocation table                                                               |              |
|           | — CSV export button                                                                      |              |
| P6-FE-038 | Create `app/settings/expense-categories/page.tsx` — manage categories:                 | Roadmap       |
|           | — List with color swatches and icons                                                     |              |
|           | — Add custom category (name, color picker, icon selector)                                |              |
|           | — Edit/delete custom categories                                                          |              |
| P6-FE-039 | Add sidebar navigation entry for Expenses                                              | Roadmap       |
| P6-FE-040 | Add expense summary widget to dashboard — monthly total, top categories                | Roadmap       |

#### 4.3.5 Front-end Tests

| Test File                                                        | Test Cases                                                |
|------------------------------------------------------------------|-----------------------------------------------------------|
| `tests/unit/stores/expenses.test.ts`                             | CRUD, filters, report data fetching                        |
| `tests/unit/stores/expense-categories.test.ts`                   | CRUD categories                                            |
| `tests/components/expenses/receipt-upload.test.ts`               | Upload image, upload PDF, preview, drag-and-drop, remove   |
| `tests/e2e/expenses/expense-crud.spec.ts`                        | Create expense with receipt, edit, filter by category/date, delete |
| `tests/e2e/expenses/expense-report.spec.ts`                      | View report, verify charts, export CSV                     |

---

### 4.4 Sprint 23 — Financial Integration & Polish (Weeks 39–40)

#### 4.4.1 Back-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P6-BE-060 | Create `ProfitLossReportService` — compute profit/loss:                                | Roadmap       |
|           | — Revenue: sum of paid invoices in period (base currency)                               |              |
|           | — Expenses: sum of expenses in period (base currency)                                   |              |
|           | — Profit: revenue − expenses                                                            |              |
|           | — Margin: profit / revenue × 100                                                       |              |
|           | — Breakdown by month, by project, by client                                             |              |
| P6-BE-061 | Create `ProjectProfitabilityService` — per-project profitability:                      | Roadmap       |
|           | — Revenue: invoiced amount for project                                                  |              |
|           | — Time cost: hours × hourly rate (from project billing)                                 |              |
|           | — Expenses: sum of expenses allocated to project                                        |              |
|           | — Profit: revenue − time cost − expenses                                                |              |
| P6-BE-062 | Create `ProfitLossController` — `GET /api/v1/reports/profit-loss`                     | Roadmap       |
| P6-BE-063 | Create `ProjectProfitabilityController` — `GET /api/v1/reports/project-profitability`  | Roadmap       |
| P6-BE-064 | Update `DashboardController` — add profit/loss summary widget and expense overview     | Roadmap       |
| P6-BE-065 | Add project expenses tab: `GET /api/v1/projects/{id}/expenses` — list expenses allocated to project | Roadmap |
| P6-BE-066 | Add billable expenses to invoice generation — option to convert billable project expenses into invoice line items | Roadmap |

#### 4.4.2 Back-end Tests (TDD)

| Test File                                                        | Test Cases                                                  |
|------------------------------------------------------------------|-------------------------------------------------------------|
| `tests/Unit/Services/ProfitLossReportServiceTest.php`            | Revenue calculation, expense calculation, profit, margin, monthly breakdown, zero revenue handling |
| `tests/Unit/Services/ProjectProfitabilityServiceTest.php`        | Revenue, time cost, expenses, profit per project, empty project handling |
| `tests/Feature/Reports/ProfitLossReportTest.php`                 | API returns correct data, date range filter, project breakdown |
| `tests/Feature/Reports/ProjectProfitabilityReportTest.php`       | Per-project profitability, sorts by profit, includes expenses |
| `tests/Feature/Invoice/BillableExpenseInvoiceTest.php`           | Convert billable expenses to invoice line items, amounts correct |

#### 4.4.3 Front-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P6-FE-050 | Create `app/reports/profit-loss/page.tsx` — P&L report:                               | Roadmap       |
|           | — Summary: revenue, expenses, profit, margin percentage                                 |              |
|           | — Monthly trend chart (revenue vs expenses bar chart with profit line)                  |              |
|           | — Category expense breakdown                                                            |              |
|           | — Client/project breakdown table                                                        |              |
| P6-FE-051 | Create `app/reports/project-profitability/page.tsx` — per-project table:               | Roadmap       |
|           | — Columns: project, client, revenue, time cost, expenses, profit, margin                |              |
|           | — Sortable, filterable by date range                                                    |              |
|           | — Color-coded: green (profitable), red (loss)                                            |              |
| P6-FE-052 | Add project expenses tab on project detail page — list and quick-add expenses          | Roadmap       |
| P6-FE-053 | Add "Invoice billable expenses" action on project detail — select expenses, generate invoice | Roadmap |
| P6-FE-054 | Update dashboard with profit/loss widget and expense summary                           | Roadmap       |
| P6-FE-055 | Run full Playwright E2E suite for all portal and expense flows                        | §10.3.2       |
| P6-FE-056 | Run Vitest coverage, ensure >= 80%                                                     | §10.2         |

#### 4.4.4 Front-end Tests

| Test File                                                        | Test Cases                                                |
|------------------------------------------------------------------|-----------------------------------------------------------|
| `tests/e2e/reports/profit-loss.spec.ts`                          | View P&L report, verify totals, date range filter          |
| `tests/e2e/reports/project-profitability.spec.ts`                | View project profitability, sort by margin                 |
| `tests/e2e/projects/project-expenses.spec.ts`                    | View project expenses, quick-add, invoice billable expenses |

---

## 5. API Endpoints Delivered in Phase 6

| Method | Endpoint                                         | Controller                        |
|--------|--------------------------------------------------|-----------------------------------|
| POST   | `/portal/auth/request`                           | PortalAuthController              |
| GET    | `/portal/auth/verify/{token}`                    | PortalAuthController              |
| POST   | `/portal/auth/logout`                            | PortalAuthController              |
| GET    | `/portal/dashboard`                              | PortalDashboardController         |
| GET    | `/portal/invoices`                               | PortalInvoiceController           |
| GET    | `/portal/invoices/{id}`                          | PortalInvoiceController           |
| GET    | `/portal/invoices/{id}/pdf`                      | PortalInvoiceController           |
| POST   | `/portal/invoices/{id}/pay`                      | PortalPaymentController           |
| GET    | `/portal/invoices/{id}/payment-status`           | PortalPaymentController           |
| GET    | `/portal/quotes`                                 | PortalQuoteController             |
| GET    | `/portal/quotes/{id}`                            | PortalQuoteController             |
| GET    | `/portal/quotes/{id}/pdf`                        | PortalQuoteController             |
| POST   | `/portal/quotes/{id}/accept`                     | PortalQuoteController             |
| POST   | `/portal/quotes/{id}/reject`                     | PortalQuoteController             |
| POST   | `/webhooks/stripe`                               | StripeWebhookController           |
| GET    | `/api/v1/clients/{id}/portal-access`             | PortalAccessTokenController       |
| POST   | `/api/v1/clients/{id}/portal-access`             | PortalAccessTokenController       |
| DELETE | `/api/v1/clients/{id}/portal-access/{tokenId}`   | PortalAccessTokenController       |
| GET    | `/api/v1/clients/{id}/portal-activity`           | PortalAccessTokenController       |
| GET    | `/api/v1/settings/portal`                        | PortalSettingsController          |
| PUT    | `/api/v1/settings/portal`                        | PortalSettingsController          |
| GET    | `/api/v1/expense-categories`                     | ExpenseCategoryController         |
| POST   | `/api/v1/expense-categories`                     | ExpenseCategoryController         |
| PUT    | `/api/v1/expense-categories/{id}`                | ExpenseCategoryController         |
| DELETE | `/api/v1/expense-categories/{id}`                | ExpenseCategoryController         |
| GET    | `/api/v1/expenses`                               | ExpenseController                 |
| POST   | `/api/v1/expenses`                               | ExpenseController                 |
| GET    | `/api/v1/expenses/{id}`                          | ExpenseController                 |
| PUT    | `/api/v1/expenses/{id}`                          | ExpenseController                 |
| DELETE | `/api/v1/expenses/{id}`                          | ExpenseController                 |
| POST   | `/api/v1/expenses/{id}/receipt`                  | ExpenseController                 |
| GET    | `/api/v1/expenses/{id}/receipt`                  | ExpenseController                 |
| GET    | `/api/v1/expenses/report`                        | ExpenseReportController           |
| GET    | `/api/v1/expenses/report/export`                 | ExpenseReportController           |
| GET    | `/api/v1/projects/{id}/expenses`                 | ProjectExpenseController          |
| GET    | `/api/v1/reports/profit-loss`                    | ProfitLossController              |
| GET    | `/api/v1/reports/project-profitability`          | ProjectProfitabilityController    |

---

## 6. Exit Criteria

| #  | Criterion                                                                           | Validated |
|----|-------------------------------------------------------------------------------------|-----------|
| 1  | Client portal: magic link authentication functional                                 | [x]       |
| 2  | Client portal: clients can view their invoices and download PDFs                    | [x]       |
| 3  | Client portal: clients can view, accept, and reject quotes                          | [x]       |
| 4  | Client portal: branded layout with custom logo and colors                           | [x]       |
| 5  | Stripe payment integration: clients can pay invoices online                         | [x]       |
| 6  | Stripe webhooks: payment status synced automatically                                | [x]       |
| 7  | Admin: manage portal access tokens per client                                       | [x]       |
| 8  | Admin: portal activity logging operational                                          | [x]       |
| 9  | Expense CRUD with categories, project allocation, and receipt upload                | [x]       |
| 10 | Expense reports: totals, category breakdown, project breakdown, CSV export          | [x]       |
| 11 | Multi-currency expenses using exchange rates from Phase 5                           | [x]       |
| 12 | Profit/loss report: revenue vs expenses with monthly trend                          | [x]       |
| 13 | Project profitability: per-project revenue, costs, margin                           | [x]       |
| 14 | Billable expenses convertible to invoice line items                                 | [x]       |
| 15 | Dashboard updated with expense and P&L widgets                                      | [x]       |
| 16 | Expenses included in data import/export                                             | [x]       |
| 17 | Back-end test coverage >= 80%                                                       | [x]       |
| 18 | Front-end test coverage >= 80%                                                      | [x]       |
| 19 | CI pipeline fully green on `main`                                                   | [ ]       |
| 20 | Version tagged as `v1.2.0` on GitHub                                                | [ ]       |

---

## 7. Risks Specific to Phase 6

| Risk                                                     | Mitigation                                                    |
|----------------------------------------------------------|---------------------------------------------------------------|
| Portal security: unauthorized access to client data      | Token-based auth with expiration; all queries scoped to client_id; penetration testing; rate limiting on auth endpoints |
| Stripe API changes or downtime                           | Pin Stripe API version; implement retry with exponential backoff; graceful error messages for clients |
| Payment disputes / chargebacks                           | Log all payment intents with metadata; link to invoice for audit trail; document dispute handling process |
| Receipt storage costs growing                            | Compress images on upload; set max file size (10MB); implement storage lifecycle policies |
| Expense categorization inconsistency                     | Provide sensible defaults; allow custom categories; consider AI-based categorization in future |
| Profit/loss accuracy depends on complete data entry      | Show warnings when data appears incomplete; provide data completeness indicators |
| Portal UX must work for non-technical clients            | Simple, clean design; minimal required actions; test with real users; mobile-responsive |
| Magic link email deliverability                          | Use same sending infrastructure as campaigns; include in SPF/DKIM; monitor bounce rates |

---

*End of Phase 6 — Client Portal & Expense Tracking (v1.2)*
