# Phase 2 — Project and Financial Management

| Field               | Value                                          |
|---------------------|------------------------------------------------|
| **Phase**           | 2 of 4                                         |
| **Name**            | Project and Financial Management               |
| **Duration**        | Weeks 7–14 (8 weeks)                           |
| **Milestone**       | M2 — Full Project & Financial Management       |
| **PRD Sections**    | §4.4, §4.5, §9.3                               |
| **Prerequisite**    | Phase 1 fully completed and validated           |
| **Status**          | In Progress (Sprints 5 and 6 backend + frontend completed) |

---

## 1. Phase Objectives

| ID       | Objective                                                                                    |
|----------|----------------------------------------------------------------------------------------------|
| P2-OBJ-1 | Deliver complete project management with task tracking, Kanban board, and time entries       |
| P2-OBJ-2 | Deliver full invoice lifecycle: create, send, track payments, mark overdue, PDF generation   |
| P2-OBJ-3 | Deliver quote management with status workflow and one-click conversion to invoice            |
| P2-OBJ-4 | Deliver credit note management linked to invoices with balance adjustment                    |
| P2-OBJ-5 | Deliver financial reporting (revenue, outstanding, VAT summary) with export                  |
| P2-OBJ-6 | Integrate PDF generation for all financial documents with branding                           |
| P2-OBJ-7 | Extend Meilisearch indexing to projects, tasks, invoices, and quotes                        |
| P2-OBJ-8 | Enhance dashboard with project deadlines, financial metrics, and revenue chart               |
| P2-OBJ-9 | Maintain >= 80% test coverage on both back-end and front-end                                |

---

## 2. Entry Criteria

- Phase 1 exit criteria 100% satisfied.
- All Phase 1 CI checks green on `main`.
- Docker development environment stable.
- Authentication, client management, and search operational.

---

## 3. Scope — Requirement Traceability

| PRD Requirement           | IDs                                    | Included |
|---------------------------|----------------------------------------|----------|
| Project CRUD              | FR-PRJ-001 → FR-PRJ-007               | Yes      |
| Task Management           | FR-PRJ-008 → FR-PRJ-015               | Yes      |
| Project Overview          | FR-PRJ-016 → FR-PRJ-018               | Yes      |
| Invoice Management        | FR-FIN-001 → FR-FIN-015               | Yes      |
| Quote Management          | FR-FIN-016 → FR-FIN-023               | Yes      |
| Credit Note Management    | FR-FIN-024 → FR-FIN-030               | Yes      |
| Financial Reporting       | FR-FIN-031 → FR-FIN-035               | Yes      |
| Settings (financial)      | FR-SET-003, FR-SET-004                 | Yes      |
| Dashboard (enhanced)      | FR-DASH-005, FR-DASH-006              | Yes      |
| Global Search (extended)  | FR-SRC-002 (projects, invoices, etc.)  | Yes      |

---

## 4. Detailed Sprint Breakdown

### 4.1 Sprint 5 — Project Management (Weeks 7–8)

#### 4.1.1 Database Migrations

| Migration                          | Description                                              |
|------------------------------------|----------------------------------------------------------|
| `create_projects_table`            | id (UUID), user_id (FK), client_id (FK), reference (VARCHAR 20, unique), name (VARCHAR 255), description (TEXT, nullable), status ENUM('draft', 'proposal_sent', 'in_progress', 'on_hold', 'completed', 'cancelled'), billing_type ENUM('hourly', 'fixed'), hourly_rate (DECIMAL 10,2, nullable), fixed_price (DECIMAL 12,2, nullable), estimated_hours (DECIMAL 10,2, nullable), start_date (DATE, nullable), deadline (DATE, nullable), completed_at (TIMESTAMP, nullable), timestamps. Indexes: user_id, client_id, status, deadline. |
| `create_tasks_table`              | id (UUID), project_id (FK), title (VARCHAR 255), description (TEXT, nullable), status ENUM('todo', 'in_progress', 'in_review', 'done', 'blocked'), priority ENUM('low', 'medium', 'high', 'urgent'), estimated_hours (DECIMAL 10,2, nullable), due_date (DATE, nullable), sort_order (INT, default 0), timestamps. Indexes: project_id, status, priority, due_date. |
| `create_task_dependencies_table`  | id (UUID), task_id (FK), depends_on_task_id (FK). Unique constraint (task_id, depends_on_task_id). |
| `create_time_entries_table`       | id (UUID), user_id (FK), task_id (FK), duration_minutes (INT, NOT NULL), date (DATE, NOT NULL), description (TEXT, nullable), timestamps. Indexes: task_id, user_id, date. |
| `create_task_attachments_table`   | id (UUID), task_id (FK), filename (VARCHAR 255), path (VARCHAR 500), mime_type (VARCHAR 100), size_bytes (INT), timestamps. Index: task_id. |

#### 4.1.2 Back-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P2-BE-001 | Create `Project` model — UUID, relationships (user, client, tasks, invoices, quotes), scopes (byStatus, byClient, active), status transitions, Searchable trait | FR-PRJ-001 |
| P2-BE-002 | Create `ProjectFactory` with Faker data                                               | §10.3.1       |
| P2-BE-003 | Create `ProjectPolicy` — user can only access own projects                            | NFR-SEC-004   |
| P2-BE-004 | Create `ProjectController` — full CRUD with filtering (status, client, date range), sorting, pagination | FR-PRJ-001 → 007 |
| P2-BE-005 | Create `StoreProjectRequest` — validate: name required, client_id exists and belongs to user, billing_type required, rate/price based on billing_type, dates valid | FR-PRJ-001 |
| P2-BE-006 | Implement reference auto-generation: PRJ-YYYY-NNNN (reuse `ReferenceGenerator` from Phase 1) | FR-PRJ-005 |
| P2-BE-007 | Implement project status transition validation (e.g., `cancelled` cannot go to `in_progress`) | FR-PRJ-002 |
| P2-BE-008 | Create `ProjectResource` / `ProjectCollection` — include client, task stats, time stats | §8.3.2 |
| P2-BE-009 | Create `Task` model — UUID, relationships (project, timeEntries, dependencies, attachments), scopes (byStatus, byPriority, overdue) | FR-PRJ-008 |
| P2-BE-010 | Create `TaskFactory`                                                                  | §10.3.1       |
| P2-BE-011 | Create `TaskController` — CRUD nested under project, bulk reorder (drag-and-drop support) | FR-PRJ-008 → 011 |
| P2-BE-012 | Create `StoreTaskRequest` — validate title, priority enum, due_date, estimated_hours  | FR-PRJ-008   |
| P2-BE-013 | Implement task dependency validation: prevent circular dependencies, prevent moving to `in_progress` if dependency not `done` | FR-PRJ-014 |
| P2-BE-014 | Create `TimeEntry` model, `TimeEntryController` — CRUD under task                     | FR-PRJ-012   |
| P2-BE-015 | Create `StoreTimeEntryRequest` — validate duration > 0, date not in future            | FR-PRJ-012   |
| P2-BE-016 | Implement computed fields on Project: `total_time_spent` (sum of all task time entries), `progress_percentage` (done tasks / total tasks × 100), `budget_consumed` | FR-PRJ-016 |
| P2-BE-017 | Implement task file attachment upload/download (max 10MB per file, 50MB per task)      | FR-PRJ-015   |
| P2-BE-018 | Create model observer: `ProjectObserver` — log activity to client timeline on create, status change, complete | FR-CLI-014 |
| P2-BE-019 | Configure Meilisearch indexes for Project and Task                                    | FR-SRC-002   |
| P2-BE-020 | Extend `SearchController` to include projects and tasks in global search results       | FR-SRC-002   |

#### 4.1.3 Back-end Tests (TDD)

| Test File                                            | Test Cases                                                  |
|------------------------------------------------------|-------------------------------------------------------------|
| `tests/Unit/Models/ProjectTest.php`                  | Factory, relationships, scopes, status transitions, computed fields |
| `tests/Unit/Models/TaskTest.php`                     | Factory, relationships, scopes, dependency validation        |
| `tests/Unit/Services/ReferenceGeneratorTest.php`     | Extended: PRJ prefix works alongside CLI prefix              |
| `tests/Feature/Project/ProjectCrudTest.php`          | Create, read, update, delete, filter by status/client/date, pagination, sort |
| `tests/Feature/Project/ProjectStatusTest.php`        | Valid transitions, invalid transitions return 422            |
| `tests/Feature/Task/TaskCrudTest.php`                | Create, update, delete, reorder, filter by status/priority   |
| `tests/Feature/Task/TaskDependencyTest.php`          | Add dependency, circular dependency rejected, blocked task cannot move to in_progress |
| `tests/Feature/Task/TimeEntryTest.php`               | Log time, update, delete, aggregation per task and project   |
| `tests/Feature/Task/TaskAttachmentTest.php`          | Upload file, download, delete, size limit validation         |
| `tests/Feature/Project/ProjectOverviewTest.php`      | Progress percentage, time spent, budget consumed calculations |

#### 4.1.4 Front-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P2-FE-001 | Create `stores/projects.ts` Pinia store — CRUD, filters, task management              | §6.2.2        |
| P2-FE-002 | Create `pages/projects/index.vue` — data table: reference, name, client, status badge, deadline, progress bar, actions | FR-PRJ-003 |
| P2-FE-003 | Create `pages/projects/create.vue` — form: name, client selector, description, billing type (toggle hourly/fixed), rates, dates | FR-PRJ-001 |
| P2-FE-004 | Create `pages/projects/[id].vue` — project detail with tabs: Overview, Tasks, Time Tracking, Files, Invoices | FR-PRJ-016 |
| P2-FE-005 | Create `components/projects/ProjectOverview.vue` — progress ring, time bar, budget bar, task stats | FR-PRJ-016 |
| P2-FE-006 | Create `components/projects/TaskKanbanBoard.vue` — columns for each status, drag-and-drop via `@vueuse/core` useDraggable or vuedraggable | FR-PRJ-010, 011 |
| P2-FE-007 | Create `components/projects/TaskListView.vue` — sortable table with inline status change | FR-PRJ-011 |
| P2-FE-008 | Create `components/projects/TaskDetailDrawer.vue` — slide-over with full task info, time entries, attachments, dependencies | FR-PRJ-008 |
| P2-FE-009 | Create `components/projects/TimeEntryForm.vue` — quick time log form (duration, date, description) | FR-PRJ-012 |
| P2-FE-010 | Create `components/projects/ProjectTimeline.vue` — Gantt-style horizontal bar chart showing tasks with dates and dependencies | FR-PRJ-017 |
| P2-FE-011 | Add project filter bar on list page: status multi-select, client selector, date range picker | FR-PRJ-003 |
| P2-FE-012 | Wire project data to client profile page (Projects tab)                               | FR-CLI-003   |

#### 4.1.5 Front-end Tests

| Test File                                             | Test Cases                                                |
|-------------------------------------------------------|-----------------------------------------------------------|
| `tests/unit/stores/projects.test.ts`                  | Fetch, create, update, delete, task operations             |
| `tests/components/projects/TaskKanbanBoard.test.ts`   | Renders columns, drag reorder fires API, blocked tasks restricted |
| `tests/components/projects/ProjectOverview.test.ts`   | Displays progress, time, budget correctly                  |
| `tests/components/projects/TimeEntryForm.test.ts`     | Validates duration, submits, shows errors                  |
| `tests/e2e/projects/project-crud.spec.ts`             | Create project, add tasks, log time, complete project      |
| `tests/e2e/projects/kanban.spec.ts`                   | Drag task between columns, verify status change            |

#### 4.1.6 Deliverables Checklist

- [ ] Project CRUD with reference auto-generation.
- [ ] Task CRUD with Kanban and list views.
- [ ] Drag-and-drop task reordering.
- [ ] Task dependencies enforced.
- [ ] Time entry logging and aggregation.
- [ ] Project overview with computed metrics.
- [ ] File attachments on tasks.
- [ ] Projects appear on client profiles.
- [ ] Global search includes projects and tasks.

---

### 4.2 Sprint 6 — Invoice Management (Weeks 9–11)

#### 4.2.1 Database Migrations

| Migration                          | Description                                              |
|------------------------------------|----------------------------------------------------------|
| `create_invoices_table`            | All columns from §8.2.3. Status ENUM: draft, sent, viewed, paid, partially_paid, overdue, cancelled. |
| `create_line_items_table`          | All columns from §8.2.4. Polymorphic: documentable_id + documentable_type. |
| `create_payments_table`            | id (UUID), invoice_id (FK), amount (DECIMAL 12,2), payment_date (DATE), payment_method (VARCHAR 50, nullable), reference (VARCHAR 100, nullable), notes (TEXT, nullable), timestamps. Index: invoice_id. |

#### 4.2.2 Back-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P2-BE-030 | Create `Invoice` model — UUID, relationships (user, client, project, lineItems, payments, creditNotes), scopes (byStatus, overdue, byClient, byDateRange), computed (balance_due), Searchable | FR-FIN-001 |
| P2-BE-031 | Create `InvoiceFactory`, `LineItemFactory`, `PaymentFactory`                          | §10.3.1       |
| P2-BE-032 | Create `InvoicePolicy` — user owns invoice                                            | NFR-SEC-004   |
| P2-BE-033 | Create `InvoiceController` — index (paginated, filterable by status/client/date/project), store, show, update, destroy (draft only) | FR-FIN-001 → 011 |
| P2-BE-034 | Create `StoreInvoiceRequest` — validate client_id, issue_date, due_date >= issue_date, at least 1 line item, VAT rates valid, discount | FR-FIN-001, 005, 013 |
| P2-BE-035 | Implement auto-generated sequential invoice number: FAC-YYYY-NNNN (reuse `ReferenceGenerator`) | FR-FIN-002 |
| P2-BE-036 | Create `LineItem` model — polymorphic relationship, auto-calculate total (qty × unit_price) | §8.2.4 |
| P2-BE-037 | Create `InvoiceCalculationService` — compute subtotal, per-rate VAT amounts, discount application, grand total. Immutable once status != draft. | FR-FIN-004, 005, 013 |
| P2-BE-038 | Implement invoice status transitions with validation: draft → sent, sent → viewed/paid/partially_paid/cancelled, sent → overdue (via scheduler) | FR-FIN-003 |
| P2-BE-039 | Create `Payment` model, `PaymentController` — record payment against invoice, update amount_paid, auto-transition to paid/partially_paid | FR-FIN-008 |
| P2-BE-040 | Create `InvoicePdfService` — generate A4 PDF via DomPDF from Blade template: header (logo, business info), client info, line items table, VAT summary, totals, payment terms, legal mentions (SIRET, APE), footer | FR-FIN-006, 015 |
| P2-BE-041 | Create Blade template: `resources/views/pdf/invoice.blade.php` with inline Tailwind styles | FR-FIN-006 |
| P2-BE-042 | Create `SendInvoiceJob` (queued) — generate PDF, send email with PDF attachment to client | FR-FIN-007 |
| P2-BE-043 | Create email template: `resources/views/emails/invoice-sent.blade.php`                | FR-FIN-007   |
| P2-BE-044 | Create `MarkOverdueInvoicesCommand` — Artisan command run daily via scheduler, transitions sent/viewed invoices past due_date to overdue | FR-FIN-009 |
| P2-BE-045 | Register scheduler entry: `$schedule->command('invoices:mark-overdue')->dailyAt('01:00')` | FR-FIN-009 |
| P2-BE-046 | Implement invoice duplication: `POST /api/v1/invoices/{id}/duplicate` — clone line items, reset status to draft, new number | FR-FIN-010 |
| P2-BE-047 | Implement invoice from project time entries: `POST /api/v1/projects/{id}/generate-invoice` — create line items from unbilled time entries grouped by task | FR-FIN-012, FR-PRJ-018 |
| P2-BE-048 | Create settings endpoints for financial defaults: `FR-SET-003` (payment terms, bank details, footer, numbering) | FR-SET-003 |
| P2-BE-049 | Log invoice events to client activity timeline (created, sent, paid, overdue)         | FR-CLI-014   |
| P2-BE-050 | Configure Meilisearch index for Invoice: searchable [number, client name, notes], filterable [status, client_id], sortable [issue_date, total] | FR-SRC-002 |

#### 4.2.3 Back-end Tests (TDD)

| Test File                                            | Test Cases                                                  |
|------------------------------------------------------|-------------------------------------------------------------|
| `tests/Unit/Models/InvoiceTest.php`                  | Factory, relationships, scopes, balance_due computation      |
| `tests/Unit/Services/InvoiceCalculationTest.php`     | Subtotal, multi-rate VAT, percentage discount, fixed discount, grand total, rounding |
| `tests/Unit/Services/InvoicePdfTest.php`             | PDF generation returns valid PDF binary, contains expected text |
| `tests/Feature/Invoice/InvoiceCrudTest.php`          | Create with line items, read, update (draft only), delete (draft only), filter, sort, paginate |
| `tests/Feature/Invoice/InvoiceNumberingTest.php`     | Sequential numbering, year rollover, no gaps on deletion     |
| `tests/Feature/Invoice/InvoiceStatusTest.php`        | Valid transitions, immutable fields when sent, overdue marking |
| `tests/Feature/Invoice/InvoicePaymentTest.php`       | Record full payment → status paid, partial payment → partially_paid, overpayment rejected |
| `tests/Feature/Invoice/InvoiceSendTest.php`          | Email queued with PDF attachment, status changes to sent, Mailpit receives email |
| `tests/Feature/Invoice/InvoiceDuplicateTest.php`     | Cloned with new number, draft status, same line items        |
| `tests/Feature/Invoice/InvoiceFromProjectTest.php`   | Line items from time entries, grouped by task, unbilled entries marked |
| `tests/Feature/Command/MarkOverdueTest.php`          | Overdue invoices transitioned, non-overdue unchanged, already paid unchanged |

#### 4.2.4 Front-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P2-FE-020 | Create `stores/invoices.ts` Pinia store — CRUD, status actions, payments              | §6.2.2        |
| P2-FE-021 | Create `pages/finances/invoices/index.vue` — data table: number, client, issue date, due date, total, status badge, actions. Status color coding: green=paid, yellow=sent, red=overdue, gray=draft. | FR-FIN-001 → 003 |
| P2-FE-022 | Create `pages/finances/invoices/create.vue` — client selector, date pickers, dynamic line items table (add row, remove row, auto-calculate per line and totals), discount toggle, notes, payment terms | FR-FIN-001 |
| P2-FE-023 | Create `components/invoices/LineItemsEditor.vue` — dynamic rows: description, quantity input, unit price input, VAT rate selector (0%, 5.5%, 10%, 20%), line total (computed). Add row / remove row buttons. Running subtotal, VAT breakdown, grand total at bottom. | FR-FIN-001, 004, 005 |
| P2-FE-024 | Create `components/invoices/InvoicePdfPreview.vue` — side panel showing PDF preview (iframe or rendered HTML) | §7.6.1 |
| P2-FE-025 | Create `pages/finances/invoices/[id].vue` — invoice detail: header with status badge + actions (send, record payment, download PDF, duplicate), client info, line items table, payment history, linked credit notes | FR-FIN-001 → 010 |
| P2-FE-026 | Create `components/invoices/RecordPaymentModal.vue` — amount, date, method, reference, notes | FR-FIN-008 |
| P2-FE-027 | Create `components/invoices/SendInvoiceModal.vue` — preview email, edit subject/body, confirm send | FR-FIN-007 |
| P2-FE-028 | Implement invoice filter bar: status multi-select, client selector, date range         | FR-FIN-001   |
| P2-FE-029 | Create `pages/settings/invoicing.vue` — default payment terms, bank details, invoice footer, numbering pattern preview | FR-SET-003 |
| P2-FE-030 | Wire invoices to client profile page (Finances tab) and project detail page (Invoices tab) | FR-FIN-011 |

#### 4.2.5 Front-end Tests

| Test File                                             | Test Cases                                                |
|-------------------------------------------------------|-----------------------------------------------------------|
| `tests/unit/stores/invoices.test.ts`                  | CRUD, payment recording, status transitions                |
| `tests/components/invoices/LineItemsEditor.test.ts`   | Add/remove rows, VAT calculation, totals, discount         |
| `tests/components/invoices/RecordPaymentModal.test.ts`| Validates amount, submits, shows partial remaining         |
| `tests/e2e/invoices/invoice-crud.spec.ts`             | Create invoice, add line items, save draft, send, record payment |
| `tests/e2e/invoices/invoice-pdf.spec.ts`              | Download PDF, verify file exists                           |

#### 4.2.6 Deliverables Checklist

- [ ] Invoice CRUD with dynamic line items and auto-calculation.
- [ ] Multiple VAT rates within a single invoice.
- [ ] Discount (percentage and fixed) support.
- [ ] PDF generation with branding and legal mentions.
- [ ] Send invoice via email with PDF attachment.
- [ ] Payment recording with partial payment support.
- [ ] Automatic overdue marking via scheduler.
- [ ] Invoice duplication.
- [ ] Invoice from project time entries.
- [ ] Financial settings page (payment terms, bank details).
- [ ] Invoices visible on client and project detail pages.

---

### 4.3 Sprint 7 — Quote Management (Weeks 12–13)

#### 4.3.1 Database Migrations

| Migration                          | Description                                              |
|------------------------------------|----------------------------------------------------------|
| `create_quotes_table`              | id (UUID), user_id (FK), client_id (FK), project_id (FK, nullable), number (VARCHAR 20, unique), status ENUM('draft', 'sent', 'accepted', 'rejected', 'expired'), issue_date (DATE), valid_until (DATE), subtotal (DECIMAL 12,2), tax_amount (DECIMAL 12,2), discount_type ENUM, discount_value (DECIMAL 12,2, nullable), total (DECIMAL 12,2), currency (VARCHAR 3, default EUR), notes (TEXT, nullable), pdf_path (VARCHAR 500, nullable), sent_at (TIMESTAMP, nullable), accepted_at (TIMESTAMP, nullable), converted_invoice_id (UUID, FK nullable), timestamps. Indexes: user_id, client_id, status, valid_until. |

#### 4.3.2 Back-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P2-BE-060 | Create `Quote` model — UUID, relationships, scopes, status transitions, Searchable    | FR-FIN-016    |
| P2-BE-061 | Create `QuoteFactory`                                                                 | §10.3.1       |
| P2-BE-062 | Create `QuotePolicy`                                                                  | NFR-SEC-004   |
| P2-BE-063 | Create `QuoteController` — full CRUD, send, convert to invoice                        | FR-FIN-016 → 023 |
| P2-BE-064 | Create `StoreQuoteRequest` — same line-item structure as invoice                      | FR-FIN-016   |
| P2-BE-065 | Implement reference auto-generation: DEV-YYYY-NNNN                                    | FR-FIN-017   |
| P2-BE-066 | Implement quote status transitions: draft → sent → accepted/rejected, sent → expired (auto) | FR-FIN-018 |
| P2-BE-067 | Implement validity period (default 30 days from issue_date, configurable)             | FR-FIN-019   |
| P2-BE-068 | Create `ConvertQuoteToInvoiceService` — creates Invoice from Quote, copies all line items, links quote.converted_invoice_id, sets quote status to accepted if not already | FR-FIN-020 |
| P2-BE-069 | Create `QuotePdfService` — similar to invoice PDF, different header/footer, validity date displayed | FR-FIN-021 |
| P2-BE-070 | Create `SendQuoteJob` (queued) — generate PDF, email to client                        | FR-FIN-022   |
| P2-BE-071 | Create `MarkExpiredQuotesCommand` — scheduler command, daily, marks expired quotes     | FR-FIN-023   |
| P2-BE-072 | Log quote events to client activity timeline                                          | FR-CLI-014   |
| P2-BE-073 | Configure Meilisearch index for Quote                                                 | FR-SRC-002   |

#### 4.3.3 Back-end Tests (TDD)

| Test File                                            | Test Cases                                                  |
|------------------------------------------------------|-------------------------------------------------------------|
| `tests/Unit/Models/QuoteTest.php`                    | Factory, relationships, scopes, status transitions           |
| `tests/Unit/Services/ConvertQuoteToInvoiceTest.php`  | All line items copied, invoice linked, quote status updated, new invoice number generated |
| `tests/Feature/Quote/QuoteCrudTest.php`              | Create, read, update (draft only), delete (draft only), filter, paginate |
| `tests/Feature/Quote/QuoteStatusTest.php`            | Valid transitions, expiry marking, rejection                 |
| `tests/Feature/Quote/QuoteSendTest.php`              | Email sent with PDF, status to sent                          |
| `tests/Feature/Quote/QuoteConvertTest.php`           | Convert creates invoice, line items match, converted_invoice_id set |
| `tests/Feature/Command/MarkExpiredQuotesTest.php`    | Expired marked, valid unchanged, already accepted unchanged  |

#### 4.3.4 Front-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P2-FE-040 | Create `stores/quotes.ts` Pinia store                                                 | §6.2.2        |
| P2-FE-041 | Create `pages/finances/quotes/index.vue` — data table with status badges              | FR-FIN-016   |
| P2-FE-042 | Create `pages/finances/quotes/create.vue` — reuse `LineItemsEditor`, add validity date | FR-FIN-016   |
| P2-FE-043 | Create `pages/finances/quotes/[id].vue` — detail page with actions: send, accept, reject, convert to invoice | FR-FIN-016 → 023 |
| P2-FE-044 | Implement "Convert to Invoice" button — confirmation modal, redirect to new invoice    | FR-FIN-020   |
| P2-FE-045 | Create `components/quotes/QuotePdfPreview.vue`                                        | FR-FIN-021   |
| P2-FE-046 | Wire quotes to client profile page (Finances tab)                                     | FR-CLI-003   |

#### 4.3.5 Front-end Tests

| Test File                                             | Test Cases                                                |
|-------------------------------------------------------|-----------------------------------------------------------|
| `tests/unit/stores/quotes.test.ts`                    | CRUD, send, convert                                       |
| `tests/e2e/quotes/quote-crud.spec.ts`                 | Create quote, send, accept, convert to invoice             |
| `tests/e2e/quotes/quote-conversion.spec.ts`           | Convert creates invoice, verifies line items match         |

---

### 4.4 Sprint 8 — Credit Notes & Financial Reports (Weeks 13–14)

#### 4.4.1 Database Migrations

| Migration                          | Description                                              |
|------------------------------------|----------------------------------------------------------|
| `create_credit_notes_table`        | id (UUID), user_id (FK), client_id (FK), invoice_id (FK), number (VARCHAR 20, unique), status ENUM('draft', 'sent', 'applied'), issue_date (DATE), subtotal (DECIMAL 12,2), tax_amount (DECIMAL 12,2), total (DECIMAL 12,2), currency (VARCHAR 3, default EUR), reason (TEXT, nullable), pdf_path (VARCHAR 500, nullable), sent_at (TIMESTAMP, nullable), applied_at (TIMESTAMP, nullable), timestamps. Indexes: user_id, client_id, invoice_id, status. |

#### 4.4.2 Back-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P2-BE-080 | Create `CreditNote` model — UUID, relationships (user, client, invoice, lineItems), scopes, Searchable | FR-FIN-024 |
| P2-BE-081 | Create `CreditNoteFactory`                                                            | §10.3.1       |
| P2-BE-082 | Create `CreditNotePolicy`                                                             | NFR-SEC-004   |
| P2-BE-083 | Create `CreditNoteController` — CRUD, send, apply                                     | FR-FIN-024 → 030 |
| P2-BE-084 | Create `StoreCreditNoteRequest` — validate invoice_id exists, belongs to user, total <= invoice remaining balance | FR-FIN-024, 027 |
| P2-BE-085 | Implement reference auto-generation: AVO-YYYY-NNNN                                    | FR-FIN-025   |
| P2-BE-086 | Implement `ApplyCreditNoteService` — deduct credit from invoice balance, update invoice amount_paid, set credit note status to applied | FR-FIN-028 |
| P2-BE-087 | Create `CreditNotePdfService` — PDF generation referencing original invoice            | FR-FIN-029   |
| P2-BE-088 | Create `SendCreditNoteJob` (queued) — email with PDF                                  | FR-FIN-030   |
| P2-BE-089 | Create `ReportController` — revenue report, outstanding payments, VAT summary          | FR-FIN-031 → 035 |
| P2-BE-090 | Create `RevenueReportService` — filter by date range, client, project; aggregate by month; return totals and breakdown | FR-FIN-031 |
| P2-BE-091 | Create `OutstandingReportService` — list unpaid/overdue invoices with aging categories (0-30, 31-60, 61-90, 90+ days) | FR-FIN-032 |
| P2-BE-092 | Create `VatSummaryReportService` — aggregate VAT collected per rate for date range     | FR-FIN-033   |
| P2-BE-093 | Implement report export: CSV (streaming) and PDF formats                               | FR-FIN-034   |
| P2-BE-094 | Create `FinancialSummaryService` — yearly summary with monthly breakdown for dashboard | FR-FIN-035 |
| P2-BE-095 | Enhance `DashboardController` — add: total revenue (month/quarter/year), pending invoices count, overdue invoices count, revenue trend chart data (12 months) | FR-DASH-002, 003, 006 |
| P2-BE-096 | Enhance `DashboardController` — add: upcoming deadlines from projects (next 7 days)   | FR-DASH-005  |
| P2-BE-097 | Log credit note events to client timeline                                             | FR-CLI-014   |

#### 4.4.3 Back-end Tests (TDD)

| Test File                                            | Test Cases                                                  |
|------------------------------------------------------|-------------------------------------------------------------|
| `tests/Unit/Models/CreditNoteTest.php`               | Factory, relationships, scopes                               |
| `tests/Unit/Services/ApplyCreditNoteTest.php`        | Full credit applied, partial credit, credit exceeds balance rejected, invoice balance updated |
| `tests/Feature/CreditNote/CreditNoteCrudTest.php`    | Create linked to invoice, validation, CRUD, draft-only delete |
| `tests/Feature/CreditNote/CreditNoteApplyTest.php`   | Apply updates invoice, status changed, cannot apply twice    |
| `tests/Feature/CreditNote/CreditNoteSendTest.php`    | Email sent with PDF                                          |
| `tests/Feature/Report/RevenueReportTest.php`         | Filter by date, by client, monthly aggregation, totals correct |
| `tests/Feature/Report/OutstandingReportTest.php`     | Groups by aging, excludes paid, includes overdue             |
| `tests/Feature/Report/VatSummaryTest.php`            | Per-rate aggregation, date range filtering                   |
| `tests/Feature/Report/ExportTest.php`                | CSV download valid, PDF download valid                       |
| `tests/Feature/Dashboard/DashboardTest.php`          | Revenue metrics, pending/overdue counts, trend data, deadlines |

#### 4.4.4 Front-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P2-FE-050 | Create `stores/creditNotes.ts` Pinia store                                            | §6.2.2        |
| P2-FE-051 | Create `pages/finances/credit-notes/index.vue` — data table                           | FR-FIN-024   |
| P2-FE-052 | Create `pages/finances/credit-notes/create.vue` — select invoice, line items (pre-filled from invoice, editable), reason | FR-FIN-024, 027 |
| P2-FE-053 | Create `pages/finances/credit-notes/[id].vue` — detail, actions: send, apply          | FR-FIN-024 → 030 |
| P2-FE-054 | Create `pages/finances/reports.vue` — tabs: Revenue, Outstanding, VAT Summary. Each tab has date range picker, filters, chart + table, export buttons (CSV, PDF) | FR-FIN-031 → 035 |
| P2-FE-055 | Create `components/reports/RevenueChart.vue` — bar chart (monthly revenue, last 12 months) via Chart.js | FR-FIN-035 |
| P2-FE-056 | Create `components/reports/OutstandingTable.vue` — aging table with color-coded rows   | FR-FIN-032   |
| P2-FE-057 | Create `components/reports/VatSummaryTable.vue` — per-rate breakdown                  | FR-FIN-033   |
| P2-FE-058 | Enhance dashboard: add revenue metrics cards, pending/overdue invoice counts, revenue trend chart (reuse RevenueChart), upcoming deadlines widget with project links | FR-DASH-002 → 006 |
| P2-FE-059 | Create finances sidebar navigation: Invoices, Quotes, Credit Notes, Reports            | §7.4         |
| P2-FE-060 | Wire credit notes to invoice detail page (linked credit notes section)                 | FR-FIN-024   |

#### 4.4.5 Front-end Tests

| Test File                                             | Test Cases                                                |
|-------------------------------------------------------|-----------------------------------------------------------|
| `tests/unit/stores/creditNotes.test.ts`               | CRUD, apply, send                                         |
| `tests/components/reports/RevenueChart.test.ts`       | Renders chart with data, empty state, date range change    |
| `tests/e2e/credit-notes/credit-note-flow.spec.ts`     | Create from invoice, send, apply, verify invoice balance   |
| `tests/e2e/reports/financial-reports.spec.ts`          | View revenue report, filter, export CSV                    |
| `tests/e2e/dashboard/enhanced-dashboard.spec.ts`      | Revenue metrics visible, chart renders, deadlines listed   |

---

## 5. API Endpoints Delivered in Phase 2

| Method | Endpoint                                    | Controller              |
|--------|---------------------------------------------|-------------------------|
| GET    | `/api/v1/projects`                          | ProjectController       |
| POST   | `/api/v1/projects`                          | ProjectController       |
| GET    | `/api/v1/projects/{id}`                     | ProjectController       |
| PUT    | `/api/v1/projects/{id}`                     | ProjectController       |
| DELETE | `/api/v1/projects/{id}`                     | ProjectController       |
| POST   | `/api/v1/projects/{id}/generate-invoice`    | ProjectController       |
| GET    | `/api/v1/projects/{id}/tasks`               | TaskController          |
| POST   | `/api/v1/projects/{id}/tasks`               | TaskController          |
| PUT    | `/api/v1/projects/{id}/tasks/{tid}`         | TaskController          |
| DELETE | `/api/v1/projects/{id}/tasks/{tid}`         | TaskController          |
| PUT    | `/api/v1/projects/{id}/tasks/reorder`       | TaskController          |
| POST   | `/api/v1/tasks/{tid}/time-entries`          | TimeEntryController     |
| PUT    | `/api/v1/time-entries/{id}`                 | TimeEntryController     |
| DELETE | `/api/v1/time-entries/{id}`                 | TimeEntryController     |
| POST   | `/api/v1/tasks/{tid}/attachments`           | TaskAttachmentController|
| DELETE | `/api/v1/attachments/{id}`                  | TaskAttachmentController|
| GET    | `/api/v1/invoices`                          | InvoiceController       |
| POST   | `/api/v1/invoices`                          | InvoiceController       |
| GET    | `/api/v1/invoices/{id}`                     | InvoiceController       |
| PUT    | `/api/v1/invoices/{id}`                     | InvoiceController       |
| DELETE | `/api/v1/invoices/{id}`                     | InvoiceController       |
| POST   | `/api/v1/invoices/{id}/send`                | InvoiceController       |
| POST   | `/api/v1/invoices/{id}/payments`            | PaymentController       |
| GET    | `/api/v1/invoices/{id}/pdf`                 | InvoiceController       |
| POST   | `/api/v1/invoices/{id}/duplicate`           | InvoiceController       |
| GET    | `/api/v1/quotes`                            | QuoteController         |
| POST   | `/api/v1/quotes`                            | QuoteController         |
| GET    | `/api/v1/quotes/{id}`                       | QuoteController         |
| PUT    | `/api/v1/quotes/{id}`                       | QuoteController         |
| DELETE | `/api/v1/quotes/{id}`                       | QuoteController         |
| POST   | `/api/v1/quotes/{id}/send`                  | QuoteController         |
| POST   | `/api/v1/quotes/{id}/convert`               | QuoteController         |
| GET    | `/api/v1/quotes/{id}/pdf`                   | QuoteController         |
| GET    | `/api/v1/credit-notes`                      | CreditNoteController    |
| POST   | `/api/v1/credit-notes`                      | CreditNoteController    |
| GET    | `/api/v1/credit-notes/{id}`                 | CreditNoteController    |
| PUT    | `/api/v1/credit-notes/{id}`                 | CreditNoteController    |
| POST   | `/api/v1/credit-notes/{id}/send`            | CreditNoteController    |
| POST   | `/api/v1/credit-notes/{id}/apply`           | CreditNoteController    |
| GET    | `/api/v1/credit-notes/{id}/pdf`             | CreditNoteController    |
| GET    | `/api/v1/reports/revenue`                   | ReportController        |
| GET    | `/api/v1/reports/outstanding`               | ReportController        |
| GET    | `/api/v1/reports/vat-summary`               | ReportController        |
| GET    | `/api/v1/reports/export`                    | ReportController        |
| PUT    | `/api/v1/settings/invoicing`                | UserSettingsController  |

---

## 6. Exit Criteria

| #  | Criterion                                                                           | Validated |
|----|-------------------------------------------------------------------------------------|-----------|
| 1  | Project CRUD with status workflow, reference numbering                               | [ ]       |
| 2  | Task CRUD with Kanban, list view, drag-and-drop reorder                              | [ ]       |
| 3  | Task dependencies enforced (blocked task cannot progress)                            | [ ]       |
| 4  | Time entry logging with aggregation per task and project                             | [ ]       |
| 5  | Project overview metrics (progress, time, budget) computed correctly                 | [ ]       |
| 6  | Invoice CRUD with line items, multi-rate VAT, discounts                              | [ ]       |
| 7  | Invoice PDF generated with branding and French legal mentions                        | [ ]       |
| 8  | Invoice email sending with PDF attachment                                            | [ ]       |
| 9  | Payment recording (full and partial), balance computation                            | [ ]       |
| 10 | Automatic overdue marking via scheduler                                              | [ ]       |
| 11 | Invoice duplication and generation from project time entries                          | [ ]       |
| 12 | Quote CRUD with validity period and status workflow                                  | [ ]       |
| 13 | Quote-to-invoice conversion (one-click, all data transferred)                        | [ ]       |
| 14 | Quote automatic expiry via scheduler                                                 | [ ]       |
| 15 | Credit note CRUD linked to specific invoice                                          | [ ]       |
| 16 | Credit note application updates invoice balance                                      | [ ]       |
| 17 | Financial reports (revenue, outstanding, VAT) with filters and export                | [ ]       |
| 18 | Dashboard enhanced with financial metrics, revenue chart, deadlines                  | [ ]       |
| 19 | Global search includes projects, tasks, invoices, quotes                              | [ ]       |
| 20 | Back-end test coverage >= 80%                                                        | [ ]       |
| 21 | Front-end test coverage >= 80%                                                       | [ ]       |
| 22 | CI pipeline fully green on `main`                                                    | [ ]       |

---

## 7. Risks Specific to Phase 2

| Risk                                                     | Mitigation                                                    |
|----------------------------------------------------------|---------------------------------------------------------------|
| Invoice calculation rounding errors with multi-rate VAT  | Use DECIMAL(12,2) everywhere, round per line item, test edge cases extensively |
| PDF rendering inconsistency across environments          | Use DomPDF (pure PHP, no browser dependency); test PDF content programmatically |
| Complex status machines across 4 entity types            | Define transitions as enums + state machine service; comprehensive integration tests |
| Scheduler commands failing silently                      | Log all scheduler runs, alert on failure, test commands in isolation |
| Polymorphic line items creating complex queries           | Add database indexes, use eager loading, monitor query performance with Debugbar |
| Quote-to-invoice data integrity                          | Use database transactions, test atomicity, verify FK constraints |

---

*End of Phase 2 — Project and Financial Management*
