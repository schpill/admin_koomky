# Product Requirements Document (PRD)

## Freelance CRM — "Koomky"

| Field             | Value                                      |
|-------------------|--------------------------------------------|
| **Document Version** | 1.4.0                                   |
| **Status**           | Draft                                   |
| **Author**           | Senior Freelance Developer              |
| **Date**             | 2026-02-11                              |
| **Last Updated**     | 2026-02-21                              |
| **Confidentiality**  | Internal                                |

---

## Table of Contents

1. [Executive Summary and Project Overview](#1-executive-summary-and-project-overview)
2. [Problem Statement and Objectives](#2-problem-statement-and-objectives)
3. [Target Audience and User Personas](#3-target-audience-and-user-personas)
4. [Functional Requirements](#4-functional-requirements)
5. [Non-Functional Requirements](#5-non-functional-requirements)
6. [Technical Architecture and Tech Stack](#6-technical-architecture-and-tech-stack)
7. [UI/UX Design Guidelines and Branding](#7-uiux-design-guidelines-and-branding)
8. [Data Models and API Specifications](#8-data-models-and-api-specifications)
9. [Integration Requirements](#9-integration-requirements)
10. [Testing Strategy](#10-testing-strategy)
11. [Deployment and DevOps Requirements](#11-deployment-and-devops-requirements)
12. [Timeline and Milestones](#12-timeline-and-milestones)
13. [Success Metrics and KPIs](#13-success-metrics-and-kpis)
14. [Risks and Mitigations](#14-risks-and-mitigations)
15. [Appendices](#15-appendices)

---

## 1. Executive Summary and Project Overview

### 1.1 Executive Summary

**Koomky** is a comprehensive, self-hosted Customer Relationship Management (CRM) platform purpose-built for freelance professionals. It consolidates client management, project tracking, financial document generation (invoices, quotes, credit notes), and marketing campaign orchestration (email and SMS) into a single, unified application.

The platform is architected as a modern, containerized web application leveraging Laravel for the back-end API, Next.js 15 (React 19) for the front-end, PostgreSQL for data persistence, Meilisearch for full-text search, and Redis for caching and session management. All services run within Docker containers, ensuring reproducible environments across development, staging, and production.

### 1.2 Project Overview

| Attribute               | Detail                                                        |
|-------------------------|---------------------------------------------------------------|
| **Product Name**        | Koomky                                                        |
| **Product Type**        | Self-hosted SaaS-style Web Application                        |
| **Primary Developer**   | Senior freelance developer                                    |
| **Initial Audience**    | The developer themselves, expanding to other freelancers      |
| **Core Value Proposition** | Eliminate tool fragmentation by unifying CRM, project management, invoicing, and marketing campaigns |
| **Development Methodology** | Test-Driven Development (TDD), phased delivery              |
| **Deployment Model**    | Docker-based, self-hosted                                     |

### 1.3 Key Features at a Glance

- **Client Management** — Full lifecycle management of client records, contacts, notes, and interaction history.
- **Project Management** — Task-based project tracking with deadlines, status workflows, and resource allocation.
- **Financial Management** — Generation and tracking of invoices, quotes (devis), and credit notes (avoirs) with PDF export and status workflows.
- **Marketing Campaigns** — Email and SMS prospecting campaign builder with contact segmentation, scheduling, and analytics.
- **Global Search** — Typo-tolerant, instant search across all entities via Meilisearch.
- **Dashboard** — Real-time overview of business health with key metrics, upcoming deadlines, and recent activity.

---

## 2. Problem Statement and Objectives

### 2.1 Problem Statement

Freelance professionals face a fragmented tooling landscape that forces them to juggle multiple disconnected applications for CRM, project management, invoicing, and marketing. This fragmentation leads to:

1. **Data Silos** — Client information, project data, financial records, and marketing contacts live in separate systems, preventing a holistic view of business relationships.
2. **Administrative Overhead** — Manually synchronizing data across tools (e.g., copying client emails from CRM to campaign tool) wastes productive hours.
3. **Inconsistent Branding** — Using multiple third-party tools for invoicing and marketing makes it difficult to maintain a consistent professional brand.
4. **Cost Accumulation** — Subscribing to separate SaaS products for each function quickly becomes expensive for independent professionals.
5. **Limited Customization** — Generic tools rarely cater to the specific workflows of freelance developers and consultants.
6. **Data Ownership Concerns** — Storing sensitive client and financial data across multiple third-party platforms raises privacy and control issues.

### 2.2 SMART Objectives

| ID    | Objective                                                                                                             | Metric                                  | Target     | Timeframe    |
|-------|-----------------------------------------------------------------------------------------------------------------------|-----------------------------------------|------------|--------------|
| OBJ-1 | Consolidate all client, project, financial, and campaign management into a single platform                           | Number of external tools replaced        | >= 4       | Phase 3      |
| OBJ-2 | Reduce average time spent on administrative tasks (invoicing, client lookup, campaign setup) per week                 | Hours saved per week                     | >= 5 hours | 3 months post-launch |
| OBJ-3 | Achieve minimum 80% automated test coverage across both back-end and front-end codebases                             | Test coverage percentage                 | >= 80%     | Each phase   |
| OBJ-4 | Deliver a fully functional MVP (client management + invoicing) within the first development phase                    | Features delivered                       | 100% of Phase 1 scope | Phase 1 end |
| OBJ-5 | Maintain sub-200ms average API response time for standard CRUD operations under normal load                          | p95 API latency                          | < 200ms    | Production   |
| OBJ-6 | Ensure zero critical security vulnerabilities in production deployment                                                | Critical CVE count                       | 0          | Ongoing      |
| OBJ-7 | Enable full-text search across all primary entities with results returned in under 50ms                               | Meilisearch query latency                | < 50ms     | Phase 2      |

---

## 3. Target Audience and User Personas

### 3.1 Primary Target Audience

| Segment                      | Description                                                                 |
|------------------------------|-----------------------------------------------------------------------------|
| **Freelance Developers**     | Independent software developers managing multiple client projects           |
| **Independent Consultants**  | Business, IT, or management consultants tracking engagements and billing    |
| **Small Agency Owners**      | 1-5 person agencies needing lightweight CRM and project tracking            |
| **Creative Freelancers**     | Designers, copywriters, and marketers managing client relationships         |

### 3.2 User Persona 1 — "Marc, the Full-Stack Freelancer"

| Attribute        | Detail                                                                                 |
|------------------|----------------------------------------------------------------------------------------|
| **Name**         | Marc Dupont                                                                            |
| **Age**          | 34                                                                                     |
| **Role**         | Senior Full-Stack Freelance Developer                                                  |
| **Location**     | Lyon, France                                                                           |
| **Experience**   | 8 years freelancing, 12 years total in software development                            |
| **Clients**      | 8-12 active clients at any given time                                                  |
| **Tech Comfort** | Very high — comfortable with CLI tools, Docker, self-hosting                           |

**Goals:**
- Maintain a single source of truth for all client information and project history.
- Generate professional invoices and quotes quickly, with automatic numbering and PDF export.
- Track project progress and deadlines without relying on heavyweight project management tools.
- Run periodic email campaigns to past clients for upselling and referral generation.

**Pain Points:**
- Currently uses 5+ separate tools (Notion for projects, Excel for invoicing, Mailchimp for emails, a CRM spreadsheet, and a notes app for client details).
- Loses track of unpaid invoices because they are managed in a disconnected spreadsheet.
- Spends ~6 hours per week on administrative tasks that could be automated.
- Cannot easily search across all client-related data from a single interface.

**Key Needs:**
- Unified dashboard showing business health at a glance.
- Fast, keyboard-driven interface for efficiency.
- Self-hosted solution for full data ownership and GDPR compliance.
- Customizable invoice templates matching personal branding.

### 3.3 User Persona 2 — "Sophie, the Independent Consultant"

| Attribute        | Detail                                                                                 |
|------------------|----------------------------------------------------------------------------------------|
| **Name**         | Sophie Martin                                                                          |
| **Age**          | 41                                                                                     |
| **Role**         | Independent Management Consultant                                                      |
| **Location**     | Paris, France                                                                          |
| **Experience**   | 5 years freelancing, 15 years in management consulting                                 |
| **Clients**      | 3-5 active clients, longer engagements                                                 |
| **Tech Comfort** | Moderate — comfortable with web applications, less so with DevOps                      |

**Goals:**
- Track long-running consulting engagements with multiple deliverables per project.
- Send professional quotes before engagements and convert them to invoices upon completion.
- Maintain a prospect pipeline and run SMS campaigns for lead generation.
- Generate financial reports for quarterly tax declarations.

**Pain Points:**
- Current invoicing tool does not support French "devis" and "avoir" workflows natively.
- Cannot convert a quote directly to an invoice, requiring duplicate data entry.
- SMS campaigns require a separate platform with its own contact list, creating sync issues.
- Lacks visibility into which prospects have been contacted and when.

**Key Needs:**
- Native support for French financial document types (devis, facture, avoir).
- Quote-to-invoice conversion workflow.
- Integrated contact management across CRM and campaign features.
- Clean, modern UI that is easy to learn without technical documentation.

---

## 4. Functional Requirements

### 4.1 Authentication and Authorization

#### FR-AUTH-001: User Registration and Login

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-AUTH-001 | The system SHALL support user registration with email and password.                            |
| FR-AUTH-002 | The system SHALL support secure login with email and password.                                 |
| FR-AUTH-003 | The system SHALL implement JWT-based authentication for API access.                            |
| FR-AUTH-004 | The system SHALL support refresh token rotation for session continuity.                        |
| FR-AUTH-005 | The system SHALL enforce password complexity rules (min 12 characters, uppercase, lowercase, digit, special character). |
| FR-AUTH-006 | The system SHALL support optional Two-Factor Authentication (2FA) via TOTP.                    |
| FR-AUTH-007 | The system SHALL lock accounts after 5 consecutive failed login attempts for 15 minutes.       |
| FR-AUTH-008 | The system SHALL provide a "forgot password" flow via email with time-limited tokens (1 hour). |
| FR-AUTH-009 | The system SHALL log all authentication events (login, logout, failed attempts) for audit.     |

**User Story:**
> As a freelancer, I want to securely log into my CRM so that only I can access my business data.

**Acceptance Criteria:**
- Given valid credentials, when I submit the login form, then I receive a JWT access token and a refresh token.
- Given invalid credentials, when I submit the login form 5 times, then my account is locked for 15 minutes.
- Given I have 2FA enabled, when I log in with valid credentials, then I am prompted for a TOTP code before receiving tokens.

---

### 4.2 Dashboard

#### FR-DASH-001: Main Dashboard

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-DASH-001 | The system SHALL display a dashboard as the default landing page after login.                   |
| FR-DASH-002 | The dashboard SHALL show total revenue for the current month, quarter, and year.               |
| FR-DASH-003 | The dashboard SHALL display the count of active projects, overdue tasks, and pending invoices.  |
| FR-DASH-004 | The dashboard SHALL list the 5 most recent activities (client interactions, invoice status changes, campaign events). |
| FR-DASH-005 | The dashboard SHALL show upcoming deadlines for the next 7 days.                               |
| FR-DASH-006 | The dashboard SHALL display a revenue trend chart (monthly, last 12 months).                   |
| FR-DASH-007 | The dashboard SHALL show campaign performance summary (open rates, click rates) for active campaigns. |
| FR-DASH-008 | All dashboard widgets SHALL be loadable independently to avoid blocking the entire page.        |

**User Story:**
> As a freelancer, I want to see a dashboard summarizing my business health so that I can quickly identify items requiring my attention.

**Acceptance Criteria:**
- Given I am logged in, when the dashboard loads, then I see revenue metrics, active project count, overdue tasks, and pending invoices.
- Given there are upcoming deadlines within 7 days, when the dashboard loads, then they are listed in chronological order.
- Given a campaign is active, when the dashboard loads, then its open rate and click rate are visible.

---

### 4.3 Client Management

#### FR-CLI-001: Client CRUD Operations

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-CLI-001  | The system SHALL allow creating a new client record with: company name, first name, last name, email, phone, address, city, postal code, country, SIRET/SIREN number, VAT number, website, and notes. |
| FR-CLI-002  | The system SHALL allow viewing a paginated list of all clients with sorting and filtering.      |
| FR-CLI-003  | The system SHALL allow viewing a single client's full profile, including all associated data.   |
| FR-CLI-004  | The system SHALL allow updating any client field.                                              |
| FR-CLI-005  | The system SHALL allow soft-deleting a client (archiving), with the ability to restore.        |
| FR-CLI-006  | The system SHALL prevent hard deletion of clients with associated invoices or projects.         |
| FR-CLI-007  | The system SHALL validate email format and phone number format on client creation/update.       |
| FR-CLI-008  | The system SHALL auto-generate a unique client reference number (e.g., CLI-2026-0001).         |

#### FR-CLI-002: Client Contacts

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-CLI-009  | The system SHALL allow adding multiple contact persons per client.                             |
| FR-CLI-010  | Each contact SHALL have: first name, last name, email, phone, role/title, and "primary" flag.  |
| FR-CLI-011  | The system SHALL designate one contact as the primary contact per client.                      |
| FR-CLI-012  | The system SHALL allow notes to be attached to individual contacts.                            |

#### FR-CLI-003: Client Activity Timeline

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-CLI-013  | The system SHALL maintain a chronological activity timeline for each client.                   |
| FR-CLI-014  | The timeline SHALL automatically record: invoices sent, quotes created, projects started/completed, emails sent, notes added. |
| FR-CLI-015  | The system SHALL allow adding manual timeline entries (e.g., phone call notes, meeting summaries). |
| FR-CLI-016  | Timeline entries SHALL be filterable by type (financial, project, communication, note).         |

#### FR-CLI-004: Client Tags and Segmentation

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-CLI-017  | The system SHALL allow assigning multiple tags to a client (e.g., "VIP", "tech", "prospect").  |
| FR-CLI-018  | The system SHALL allow creating custom tags with name and color.                               |
| FR-CLI-019  | The system SHALL allow filtering clients by one or more tags.                                  |
| FR-CLI-020  | Tags SHALL be reusable across clients and available for campaign segmentation.                  |

**User Story:**
> As a freelancer, I want to manage all my client information in one place so that I can quickly access their details, history, and associated projects and invoices.

**Acceptance Criteria:**
- Given I fill in all required fields, when I submit the client creation form, then a new client is created with an auto-generated reference number.
- Given a client has 3 associated projects and 2 invoices, when I view the client profile, then all 3 projects and 2 invoices are listed.
- Given I add a tag "VIP" to 5 clients, when I filter by "VIP", then exactly those 5 clients are returned.
- Given I soft-delete a client, when I view the active clients list, then the deleted client is not shown, but it appears in the archived clients view.

---

### 4.4 Project Management

#### FR-PRJ-001: Project CRUD

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-PRJ-001  | The system SHALL allow creating a project with: name, description, client (required), start date, deadline, estimated hours, hourly rate, fixed price, and status. |
| FR-PRJ-002  | The system SHALL support the following project statuses: `draft`, `proposal_sent`, `in_progress`, `on_hold`, `completed`, `cancelled`. |
| FR-PRJ-003  | The system SHALL allow viewing a paginated list of projects with filters by status, client, and date range. |
| FR-PRJ-004  | The system SHALL allow updating project details and status.                                    |
| FR-PRJ-005  | The system SHALL auto-generate a unique project reference (e.g., PRJ-2026-0001).              |
| FR-PRJ-006  | The system SHALL support both hourly-rate and fixed-price billing models per project.           |
| FR-PRJ-007  | The system SHALL track actual hours logged against estimated hours.                            |

#### FR-PRJ-002: Task Management

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-PRJ-008  | The system SHALL allow creating tasks within a project with: title, description, priority (low, medium, high, urgent), due date, estimated hours, and status. |
| FR-PRJ-009  | The system SHALL support task statuses: `todo`, `in_progress`, `in_review`, `done`, `blocked`. |
| FR-PRJ-010  | The system SHALL allow reordering tasks via drag-and-drop within a Kanban board view.          |
| FR-PRJ-011  | The system SHALL provide both list view and Kanban board view for tasks.                       |
| FR-PRJ-012  | The system SHALL allow logging time entries against a task with: duration, date, and description. |
| FR-PRJ-013  | The system SHALL calculate total time spent per task and per project from time entries.         |
| FR-PRJ-014  | The system SHALL allow setting task dependencies (task B cannot start until task A is done).    |
| FR-PRJ-015  | The system SHALL allow attaching files to tasks (max 10MB per file, 50MB total per task).      |

#### FR-PRJ-003: Project Overview

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-PRJ-016  | The system SHALL display a project overview showing: progress percentage, time spent vs estimated, budget consumed vs total, and task completion stats. |
| FR-PRJ-017  | The system SHALL display a project timeline (Gantt-style) showing task durations and dependencies. |
| FR-PRJ-018  | The system SHALL allow generating an invoice directly from a project's logged time entries.     |

**User Story:**
> As a freelancer, I want to track my projects and their tasks so that I can stay organized, meet deadlines, and accurately bill my clients.

**Acceptance Criteria:**
- Given I create a project linked to a client, when I view the client profile, then the project appears in the client's project list.
- Given a project has 10 tasks of which 7 are "done", when I view the project overview, then the progress shows 70%.
- Given I log 5 hours on a task, when I view the project time tracking, then the total reflects those 5 hours against the estimate.
- Given task B depends on task A, when task A's status is not "done", then task B cannot be moved to "in_progress".

---

### 4.5 Financial Management

#### FR-FIN-001: Invoice Management

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-FIN-001  | The system SHALL allow creating an invoice with: client, invoice date, due date, line items (description, quantity, unit price, VAT rate), payment terms, and notes. |
| FR-FIN-002  | The system SHALL auto-generate sequential invoice numbers following a configurable pattern (e.g., FAC-2026-0001). |
| FR-FIN-003  | The system SHALL support invoice statuses: `draft`, `sent`, `viewed`, `paid`, `partially_paid`, `overdue`, `cancelled`. |
| FR-FIN-004  | The system SHALL automatically calculate subtotal, VAT amounts (per rate), and grand total.    |
| FR-FIN-005  | The system SHALL support multiple VAT rates within a single invoice (e.g., 0%, 5.5%, 10%, 20%). |
| FR-FIN-006  | The system SHALL generate a PDF version of the invoice with the user's branding.               |
| FR-FIN-007  | The system SHALL allow sending the invoice via email directly from the platform.               |
| FR-FIN-008  | The system SHALL track payment status and allow recording partial payments.                    |
| FR-FIN-009  | The system SHALL automatically mark invoices as "overdue" when the due date passes without full payment. |
| FR-FIN-010  | The system SHALL allow duplicating an existing invoice as a starting point for a new one.      |
| FR-FIN-011  | The system SHALL allow linking an invoice to a project.                                        |
| FR-FIN-012  | The system SHALL support creating an invoice from a project's logged time entries.             |
| FR-FIN-013  | The system SHALL support adding a discount (percentage or fixed amount) to an invoice.         |
| FR-FIN-014  | The system SHALL allow configuring default payment terms and bank details in settings.          |
| FR-FIN-015  | The system SHALL display legal mentions required by French law on invoices (SIRET, APE code, etc.). |

#### FR-FIN-002: Quote (Devis) Management

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-FIN-016  | The system SHALL allow creating a quote with the same line-item structure as invoices.          |
| FR-FIN-017  | The system SHALL auto-generate sequential quote numbers (e.g., DEV-2026-0001).                 |
| FR-FIN-018  | The system SHALL support quote statuses: `draft`, `sent`, `accepted`, `rejected`, `expired`.   |
| FR-FIN-019  | The system SHALL allow setting a validity period for quotes (default: 30 days).                |
| FR-FIN-020  | The system SHALL allow converting an accepted quote directly into an invoice with one click.    |
| FR-FIN-021  | The system SHALL generate a PDF version of the quote with branding.                            |
| FR-FIN-022  | The system SHALL allow sending the quote via email directly from the platform.                 |
| FR-FIN-023  | The system SHALL automatically mark quotes as "expired" after the validity period ends.        |

#### FR-FIN-003: Credit Note (Avoir) Management

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-FIN-024  | The system SHALL allow creating a credit note linked to a specific invoice.                    |
| FR-FIN-025  | The system SHALL auto-generate sequential credit note numbers (e.g., AVO-2026-0001).           |
| FR-FIN-026  | The system SHALL support credit note statuses: `draft`, `sent`, `applied`.                     |
| FR-FIN-027  | The system SHALL allow partial credit notes (crediting a portion of the original invoice).     |
| FR-FIN-028  | The system SHALL update the linked invoice's balance when a credit note is applied.            |
| FR-FIN-029  | The system SHALL generate a PDF version of the credit note with branding.                      |
| FR-FIN-030  | The system SHALL allow sending the credit note via email directly from the platform.           |

#### FR-FIN-004: Financial Reporting

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-FIN-031  | The system SHALL provide a revenue report filterable by date range, client, and project.       |
| FR-FIN-032  | The system SHALL provide an outstanding payments report showing all unpaid/overdue invoices.   |
| FR-FIN-033  | The system SHALL provide a VAT summary report for tax declaration purposes.                    |
| FR-FIN-034  | The system SHALL allow exporting financial reports in CSV and PDF formats.                     |
| FR-FIN-035  | The system SHALL display a yearly financial summary with monthly breakdown.                    |

**User Story:**
> As a freelancer, I want to generate professional invoices, quotes, and credit notes so that I can manage my billing efficiently and maintain compliance with French regulations.

**Acceptance Criteria:**
- Given I create an invoice with 3 line items at different VAT rates, when the invoice is generated, then the VAT is correctly calculated per rate and the total is accurate.
- Given a quote is accepted, when I click "Convert to Invoice", then a new invoice is created pre-filled with all the quote's line items and client information.
- Given I create a credit note for 50% of an invoice, when the credit note is applied, then the invoice balance reflects the reduction.
- Given an invoice's due date has passed and it is not fully paid, when the daily scheduler runs, then the invoice status is updated to "overdue".

---

### 4.6 Marketing and Communication Campaigns

#### FR-CAM-001: Campaign Management

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-CAM-001  | The system SHALL allow creating a campaign with: name, type (email or SMS), description, and target audience (contact segment). |
| FR-CAM-002  | The system SHALL support campaign statuses: `draft`, `scheduled`, `sending`, `sent`, `paused`, `cancelled`. |
| FR-CAM-003  | The system SHALL allow scheduling campaigns for a specific date and time.                      |
| FR-CAM-004  | The system SHALL allow pausing an in-progress campaign.                                        |
| FR-CAM-005  | The system SHALL allow duplicating a campaign for reuse.                                       |
| FR-CAM-006  | The system SHALL prevent sending a campaign to contacts who have unsubscribed.                 |

#### FR-CAM-002: Contact Segmentation

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-CAM-007  | The system SHALL allow creating contact segments based on: client tags, last interaction date, project history, revenue generated, and custom filters. |
| FR-CAM-008  | The system SHALL support combining filter criteria with AND/OR logic.                          |
| FR-CAM-009  | The system SHALL display a real-time preview of matching contacts when building a segment.     |
| FR-CAM-010  | The system SHALL allow saving and naming segments for reuse.                                   |
| FR-CAM-011  | Segments SHALL dynamically update as client data changes.                                      |

#### FR-CAM-003: Email Campaigns

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-CAM-012  | The system SHALL provide a rich-text email editor with template support.                       |
| FR-CAM-013  | The system SHALL support personalization variables (e.g., {{first_name}}, {{company}}).         |
| FR-CAM-014  | The system SHALL allow sending a test email before launching the campaign.                     |
| FR-CAM-015  | The system SHALL track email metrics: sent count, delivered, opened, clicked, bounced, unsubscribed. |
| FR-CAM-016  | The system SHALL include an unsubscribe link in all marketing emails (GDPR compliance).        |
| FR-CAM-017  | The system SHALL throttle email sending to respect provider rate limits (configurable).        |
| FR-CAM-018  | The system SHALL allow attaching files to campaign emails (max 5MB total attachments).         |

#### FR-CAM-004: SMS Campaigns

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-CAM-019  | The system SHALL allow composing SMS messages with a character counter (160 chars per segment). |
| FR-CAM-020  | The system SHALL support personalization variables in SMS messages.                             |
| FR-CAM-021  | The system SHALL allow sending a test SMS before launching the campaign.                       |
| FR-CAM-022  | The system SHALL track SMS metrics: sent count, delivered, failed, opt-out.                    |
| FR-CAM-023  | The system SHALL include an opt-out instruction in all SMS messages.                           |
| FR-CAM-024  | The system SHALL throttle SMS sending based on provider limits (configurable).                 |
| FR-CAM-025  | The system SHALL validate phone numbers before sending (E.164 format).                        |

#### FR-CAM-005: Campaign Analytics

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-CAM-026  | The system SHALL provide a campaign analytics dashboard showing key metrics per campaign.      |
| FR-CAM-027  | The system SHALL display metrics over time (e.g., opens per hour after sending).               |
| FR-CAM-028  | The system SHALL allow comparing performance across campaigns.                                 |
| FR-CAM-029  | The system SHALL allow exporting campaign analytics in CSV format.                             |

**User Story:**
> As a freelancer, I want to run targeted email and SMS campaigns to my contacts so that I can generate leads and maintain relationships with past clients.

**Acceptance Criteria:**
- Given I create a segment based on the tag "tech" and "last interaction > 6 months", when I preview the segment, then only matching contacts are shown.
- Given I schedule an email campaign for tomorrow at 9:00 AM, when the scheduled time arrives, then the campaign begins sending.
- Given a contact has unsubscribed, when I send a campaign to a segment containing that contact, then the contact is automatically excluded.
- Given an email campaign has been sent to 100 contacts, when I view the analytics, then I see delivered, opened, clicked, bounced, and unsubscribed counts.

---

### 4.7 Global Search

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-SRC-001  | The system SHALL provide a global search bar accessible from any page via keyboard shortcut (Ctrl+K / Cmd+K). |
| FR-SRC-002  | The system SHALL index and search across: clients, contacts, projects, tasks, invoices, quotes, credit notes, and campaigns. |
| FR-SRC-003  | The system SHALL return results in under 50ms for typical queries.                             |
| FR-SRC-004  | The system SHALL support typo-tolerant search (Meilisearch).                                   |
| FR-SRC-005  | The system SHALL display results grouped by entity type with direct navigation links.          |
| FR-SRC-006  | The system SHALL support search filters by entity type.                                        |
| FR-SRC-007  | The system SHALL highlight matching terms in search results.                                   |

---

### 4.8 Settings and Configuration

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-SET-001  | The system SHALL allow configuring user profile: name, email, password, avatar.                |
| FR-SET-002  | The system SHALL allow configuring business information: company name, address, SIRET, APE code, VAT number, logo. |
| FR-SET-003  | The system SHALL allow configuring default invoice settings: payment terms, bank details, footer text, numbering pattern. |
| FR-SET-004  | The system SHALL allow configuring email settings: sender name, sender email, SMTP or API credentials. |
| FR-SET-005  | The system SHALL allow configuring SMS settings: sender name, API credentials.                 |
| FR-SET-006  | The system SHALL allow configuring notification preferences (email, in-app).                   |
| FR-SET-007  | The system SHALL allow importing clients from CSV files.                                       |
| FR-SET-008  | The system SHALL allow exporting all data in standard formats (CSV, JSON) for data portability. |

---

### 4.9 Accounting & Tax Compliance

#### FR-ACC-001: FEC Export (Fichier des Écritures Comptables)

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-ACC-001  | The system SHALL generate a FEC-compliant export file (semicolon-delimited, UTF-8) covering all transactions in a selected fiscal period. |
| FR-ACC-002  | The FEC export SHALL include journal entries for: invoices issued, invoice payments received, credit notes issued, and expenses recorded. |
| FR-ACC-003  | The FEC export SHALL use Plan Comptable Général account numbers (411, 706, 44571, 44566, 401, 512) configurable per user. |
| FR-ACC-004  | The system SHALL allow selecting the fiscal year (or custom date range) for the FEC export.    |
| FR-ACC-005  | The FEC file SHALL comply with Article L47 A of the Livre des Procédures Fiscales (LPF), including all mandatory columns (JournalCode, EcritureDate, CompteNum, Debit, Credit, etc.). |

#### FR-ACC-002: VAT Declaration Report

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-ACC-006  | The system SHALL provide a VAT declaration report (CA3-style) showing: TVA collectée by rate (0%, 5.5%, 10%, 20%), TVA déductible from eligible expenses, and net TVA due. |
| FR-ACC-007  | The VAT report SHALL be filterable by fiscal period (monthly, quarterly).                     |
| FR-ACC-008  | The system SHALL allow exporting the VAT report as PDF and CSV.                               |

#### FR-ACC-003: Accounting Software Export & Fiscal Year Summary

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-ACC-009  | The system SHALL provide a CSV export compatible with Pennylane and Sage (configurable column mapping per target software). |
| FR-ACC-010  | The system SHALL generate a fiscal year closing summary: total revenue, total expenses, net profit, margin percentage, and VAT position. |
| FR-ACC-011  | All accounting exports SHALL include the user's SIRET, SIREN, and VAT number in file headers. |

**User Story:**
> As a French freelancer, I want to export my accounting data in FEC format so that I can hand it to my accountant or submit it during a tax audit without manual re-entry.

**Acceptance Criteria:**
- Given a fiscal year with invoices and expenses, when I export the FEC, then a valid semicolon-delimited UTF-8 file is generated with one debit and one credit entry per transaction line.
- Given a quarter with €5,000 in services at 20% VAT and €500 in deductible expenses with VAT, when I view the VAT report, then TVA collectée = €1,000, TVA déductible = €100, net TVA due = €900.
- Given I export to Pennylane CSV format, when I import the file in Pennylane, then all invoices and expenses are imported without mapping errors.

---

### 4.10 Public API & Outbound Webhooks

#### FR-WBH-001: Personal Access Tokens

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-WBH-001  | The system SHALL allow users to create named Personal Access Tokens (PAT) for API access by external tools. |
| FR-WBH-002  | Each PAT SHALL have configurable scopes (read:invoices, write:expenses, read:clients, etc.).  |
| FR-WBH-003  | Each PAT SHALL support an optional expiry date.                                               |
| FR-WBH-004  | The system SHALL display the token value only once upon creation.                              |
| FR-WBH-005  | The system SHALL allow revoking any PAT at any time; revoked tokens SHALL be rejected immediately. |

#### FR-WBH-002: Outbound Webhooks

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-WBH-006  | The system SHALL allow registering external HTTPS webhook endpoints with a list of subscribed events. |
| FR-WBH-007  | The system SHALL sign all outbound webhook payloads with HMAC-SHA256 using a per-endpoint secret, delivered in the `X-Koomky-Signature` header. |
| FR-WBH-008  | The system SHALL dispatch webhook events for: invoice.created/sent/paid/overdue, quote.sent/accepted/rejected/expired, expense.created, project.completed, payment.received, lead.created/status_changed/converted. |
| FR-WBH-009  | Failed webhook deliveries SHALL be retried up to 5 times with exponential backoff (1s, 5s, 30s, 5 min, 30 min). |
| FR-WBH-010  | The system SHALL provide a delivery log showing status, HTTP response code, attempt count, and payload for each delivery attempt. |
| FR-WBH-011  | The system SHALL allow manually retrying a failed webhook delivery from the log.              |
| FR-WBH-012  | The system SHALL provide a "test delivery" action that sends a sample payload to a registered endpoint. |

#### FR-WBH-003: API Documentation

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-WBH-013  | The system SHALL auto-generate and expose an OpenAPI 3.1 specification at `/api/docs`.        |
| FR-WBH-014  | The OpenAPI spec SHALL document all authenticated endpoints with request and response schemas. |

**User Story:**
> As a freelancer using n8n for automation, I want to receive a webhook when an invoice is paid so that I can automatically update my accounting spreadsheet without manual intervention.

**Acceptance Criteria:**
- Given I create a PAT with scope `read:invoices`, when I call `GET /api/v1/invoices` with that PAT, then I receive 200; when I call `POST /api/v1/invoices`, then I receive 403.
- Given I register a webhook endpoint subscribed to `invoice.paid`, when an invoice is marked as paid, then my endpoint receives a signed POST within 5 seconds.
- Given a webhook delivery fails with a 500 response, when 5 retries are exhausted, then the delivery is marked as failed and visible in the log.

---

### 4.11 Prospect & Lead Management

#### FR-LEAD-001: Lead CRUD

| ID           | Requirement                                                                                   |
|--------------|-----------------------------------------------------------------------------------------------|
| FR-LEAD-001  | The system SHALL allow creating a lead with: company name, contact name, email, phone, source, estimated value, currency, probability (0–100%), and expected close date. |
| FR-LEAD-002  | The system SHALL support lead statuses: `new`, `contacted`, `qualified`, `proposal_sent`, `negotiating`, `won`, `lost`. |
| FR-LEAD-003  | The system SHALL allow viewing leads in a Kanban pipeline view grouped by status.             |
| FR-LEAD-004  | The system SHALL allow reordering leads within a status column via drag-and-drop.             |
| FR-LEAD-005  | The system SHALL allow viewing leads in a filterable list view (status, source, date range, search). |
| FR-LEAD-006  | The system SHALL allow transitioning a lead to any status via a status selector.              |
| FR-LEAD-007  | When a lead is moved to `lost`, the system SHALL prompt for a mandatory lost reason.          |

#### FR-LEAD-002: Lead Activity Log

| ID           | Requirement                                                                                   |
|--------------|-----------------------------------------------------------------------------------------------|
| FR-LEAD-008  | The system SHALL allow logging activities on a lead: note, email sent, call, meeting, follow-up scheduled. |
| FR-LEAD-009  | The system SHALL display a chronological activity timeline on the lead detail page.           |

#### FR-LEAD-003: Lead Conversion

| ID           | Requirement                                                                                   |
|--------------|-----------------------------------------------------------------------------------------------|
| FR-LEAD-010  | The system SHALL allow converting a `won` lead into a Client record in a single action.       |
| FR-LEAD-011  | The conversion SHALL pre-fill the new Client form with data from the lead (company, contact, email, phone). |
| FR-LEAD-012  | After conversion, the lead SHALL be linked to the created Client and its status set to `won`. |

#### FR-LEAD-004: Pipeline Analytics

| ID           | Requirement                                                                                   |
|--------------|-----------------------------------------------------------------------------------------------|
| FR-LEAD-013  | The system SHALL display pipeline analytics: total pipeline value, leads by stage, win rate, average deal value, and average time to close. |
| FR-LEAD-014  | The system SHALL allow filtering pipeline analytics by date range and lead source.            |

**User Story:**
> As a freelancer, I want to manage my prospect pipeline so that I can track which leads I am nurturing, see the total potential revenue, and convert won deals into clients without duplicating data entry.

**Acceptance Criteria:**
- Given 5 leads in `proposal_sent` with a total estimated value of €30,000, when I view the Kanban, then the `proposal_sent` column shows 5 cards and €30,000.
- Given a lead is moved to `won`, when I click "Convert to Client", then a new Client is created pre-filled with the lead's data and the lead is linked to that client.
- Given a lead is moved to `lost`, when I confirm, then the system prompts for a lost reason before updating the status.

---

### 4.12 Document Management (GED)

#### FR-GED-001: Document Library

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| FR-GED-001  | The system SHALL allow uploading documents with optional title, tags, and client association. |
| FR-GED-002  | The system SHALL provide a library view with grid and list toggles, sorting, and pagination.  |
| FR-GED-003  | The system SHALL auto-detect document types (PDF, spreadsheet, image, script, etc.).         |
| FR-GED-004  | The system SHALL support full-text search on title and tags via Meilisearch.                  |
| FR-GED-005  | The system SHALL support document versioning by allowing file re-uploads (overwriting).       |
| FR-GED-006  | The system SHALL allow sending documents as email attachments directly from the UI.           |
| FR-GED-007  | The system SHALL provide inline previews for PDF, images, text, and script files.             |
| FR-GED-008  | The system SHALL enforce per-user storage quotas (default 512 MB).                            |

**User Story:**
> As a freelancer, I want to store all my business documents (contracts, receipts, scripts) in one place so that I can easily retrieve, preview, and share them with clients.

**Acceptance Criteria:**
- Given I upload a file without a title, when it is saved, then the filename is used as the default title.
- Given I re-upload a file for an existing document, when it is saved, then the version number is incremented.
- Given a PDF document, when I click on it, then I can see its content in an inline preview panel.
- Given my storage quota is full, when I try to upload a new document, then the system rejects it with an error.

---

## 5. Non-Functional Requirements

### 5.1 Performance

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| NFR-PERF-001 | Standard CRUD API endpoints SHALL respond in under 200ms (p95) under normal load (single user). |
| NFR-PERF-002 | Search queries via Meilisearch SHALL return results in under 50ms (p95).                      |
| NFR-PERF-003 | Dashboard page SHALL fully load (including all widgets) in under 2 seconds.                    |
| NFR-PERF-004 | PDF generation for invoices/quotes/credit notes SHALL complete in under 3 seconds.             |
| NFR-PERF-005 | The system SHALL handle up to 100 concurrent API requests without degradation.                 |
| NFR-PERF-006 | Campaign email sending SHALL sustain a throughput of at least 100 emails per minute.           |
| NFR-PERF-007 | Database queries SHALL be optimized with proper indexing; no query SHALL exceed 100ms.         |
| NFR-PERF-008 | Redis cache hit ratio SHALL be at least 80% for frequently accessed data.                     |

### 5.2 Security

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| NFR-SEC-001  | All data in transit SHALL be encrypted using TLS 1.2 or higher.                               |
| NFR-SEC-002  | Passwords SHALL be hashed using bcrypt with a cost factor of at least 12.                     |
| NFR-SEC-003  | JWT tokens SHALL have a maximum lifetime of 15 minutes; refresh tokens SHALL expire after 7 days. |
| NFR-SEC-004  | All API endpoints SHALL require authentication except login, registration, and password reset.  |
| NFR-SEC-005  | The system SHALL implement CSRF protection for all state-changing requests.                    |
| NFR-SEC-006  | The system SHALL sanitize all user inputs to prevent XSS and SQL injection attacks.            |
| NFR-SEC-007  | The system SHALL implement rate limiting on authentication endpoints (max 10 requests/minute). |
| NFR-SEC-008  | The system SHALL comply with GDPR requirements: data export, data deletion, consent management. |
| NFR-SEC-009  | Sensitive data (bank details, API keys) SHALL be encrypted at rest using AES-256.              |
| NFR-SEC-010  | The system SHALL log all security-relevant events to an audit trail.                           |
| NFR-SEC-011  | Dependencies SHALL be regularly scanned for known vulnerabilities (e.g., via `composer audit`, `npm audit`). |
| NFR-SEC-012  | The system SHALL implement Content Security Policy (CSP) headers.                             |

### 5.3 Scalability

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| NFR-SCA-001  | The system architecture SHALL support horizontal scaling of the API layer via container replication. |
| NFR-SCA-002  | The database SHALL support up to 100,000 clients, 500,000 invoices, and 1,000,000 time entries without performance degradation. |
| NFR-SCA-003  | Meilisearch index SHALL support up to 1,000,000 documents across all entity types.            |
| NFR-SCA-004  | Background job processing (Laravel queues) SHALL be scalable by adding worker containers.      |
| NFR-SCA-005  | The system SHALL use database connection pooling to manage concurrent connections efficiently.  |

### 5.4 Reliability and Availability

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| NFR-REL-001  | The system SHALL target 99.5% uptime (excluding scheduled maintenance windows).               |
| NFR-REL-002  | The system SHALL implement automated database backups daily, with 30-day retention.            |
| NFR-REL-003  | The system SHALL implement graceful degradation: if Meilisearch is unavailable, fallback to database search. |
| NFR-REL-004  | The system SHALL implement graceful degradation: if Redis is unavailable, fall back to database sessions and skip caching. |
| NFR-REL-005  | Failed background jobs SHALL be retried up to 3 times with exponential backoff.               |
| NFR-REL-006  | The system SHALL implement health check endpoints for all services.                           |
| NFR-REL-007  | The system SHALL support zero-downtime deployments via rolling container updates.              |

### 5.5 Maintainability

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| NFR-MNT-001  | The codebase SHALL follow PSR-12 coding standard for PHP (back-end).                          |
| NFR-MNT-002  | The codebase SHALL follow the React/Next.js conventions and ESLint rules for the front-end.   |
| NFR-MNT-003  | All public API methods SHALL have PHPDoc annotations.                                         |
| NFR-MNT-004  | All React components SHALL have TypeScript type definitions for props.                         |
| NFR-MNT-005  | The project SHALL use database migrations for all schema changes (never manual SQL).           |
| NFR-MNT-006  | The project SHALL maintain a CHANGELOG.md following Keep a Changelog format.                  |
| NFR-MNT-007  | Code complexity (cyclomatic) per method SHALL not exceed 10 (enforced via static analysis).    |

### 5.6 Usability

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| NFR-USA-001  | The application SHALL be fully responsive and functional on screens from 375px to 2560px wide. |
| NFR-USA-002  | All destructive actions SHALL require explicit confirmation.                                   |
| NFR-USA-003  | Form validation errors SHALL be displayed inline next to the relevant field.                   |
| NFR-USA-004  | The application SHALL provide toast notifications for successful actions.                      |
| NFR-USA-005  | The application SHALL support keyboard navigation for all primary workflows.                   |
| NFR-USA-006  | Loading states SHALL be indicated with skeleton loaders or spinners.                           |
| NFR-USA-007  | The application SHALL maintain a consistent navigation structure across all modules.           |
| NFR-USA-008  | Empty states SHALL include helpful guidance on how to get started.                             |

### 5.7 Accessibility

| ID          | Requirement                                                                                    |
|-------------|-----------------------------------------------------------------------------------------------|
| NFR-ACC-001  | The application SHALL conform to WCAG 2.1 Level AA standards.                                 |
| NFR-ACC-002  | All interactive elements SHALL be accessible via keyboard.                                    |
| NFR-ACC-003  | All images and icons SHALL have appropriate alt text or aria-labels.                           |
| NFR-ACC-004  | Color contrast ratios SHALL meet WCAG 2.1 AA minimum (4.5:1 for normal text, 3:1 for large text). |
| NFR-ACC-005  | Form fields SHALL have associated labels and error messages linked via aria-describedby.       |
| NFR-ACC-006  | Dynamic content updates SHALL be announced to screen readers via aria-live regions.            |
| NFR-ACC-007  | Focus management SHALL be handled correctly for modals, drawers, and dynamic content.          |

---

## 6. Technical Architecture and Tech Stack

### 6.1 Architecture Overview

The application follows a **decoupled, containerized architecture** with a clear separation between the front-end SPA (Single Page Application) and the back-end API. All services are orchestrated via Docker Compose.

```
┌─────────────────────────────────────────────────────────────────────┐
│                          Docker Network                             │
│                                                                     │
│  ┌──────────────┐   ┌──────────────┐   ┌──────────────────────┐    │
│  │   Nginx      │   │   Next.js    │   │   Laravel API        │    │
│  │   Reverse    │──▶│   Front-end  │   │   (PHP-FPM)          │    │
│  │   Proxy      │──▶│   (SSR/SPA)  │   │                      │    │
│  │   :80/:443   │   │   :3000      │   │   :8000              │    │
│  └──────────────┘   └──────────────┘   └──────────┬───────────┘    │
│                                                     │               │
│                          ┌──────────────────────────┼───────┐       │
│                          │                          │       │       │
│                   ┌──────▼──────┐  ┌────────────────▼┐ ┌────▼────┐ │
│                   │ PostgreSQL  │  │   Redis          │ │ Meili-  │ │
│                   │ :5432       │  │   :6379          │ │ search  │ │
│                   │             │  │                  │ │ :7700   │ │
│                   └─────────────┘  └──────────────────┘ └─────────┘ │
│                                                                     │
│  ┌──────────────────────┐  ┌──────────────────────┐                 │
│  │ Laravel Queue Worker  │  │ Laravel Scheduler    │                 │
│  │ (Background Jobs)     │  │ (Cron Tasks)         │                 │
│  └──────────────────────┘  └──────────────────────┘                 │
│                                                                     │
│  ┌──────────────────────┐                                           │
│  │ Mailpit (Dev only)   │                                           │
│  │ :1025 / :8025        │                                           │
│  └──────────────────────┘                                           │
└─────────────────────────────────────────────────────────────────────┘
```

### 6.2 Tech Stack Details

#### 6.2.1 Back-end: Laravel

| Component          | Technology                | Rationale                                                       |
|--------------------|---------------------------|-----------------------------------------------------------------|
| **Framework**      | Laravel 12.x              | Mature ecosystem, excellent ORM (Eloquent), built-in queue system, robust ecosystem for API development |
| **PHP Version**    | 8.3+                      | Latest performance improvements, typed properties, enums, fibers |
| **API Style**      | RESTful JSON API           | Industry standard, well-supported by Next.js and any HTTP client |
| **Authentication** | Laravel Sanctum + JWT      | Token-based auth suitable for SPA front-end, API token support  |
| **Queue System**   | Laravel Queues with Redis driver | Asynchronous processing for emails, SMS, PDF generation, search indexing |
| **Scheduler**      | Laravel Task Scheduling    | Cron-based jobs for overdue invoice marking, campaign scheduling, backup triggers |
| **Mail**           | Laravel Mail (SMTP/API)    | Configurable mail drivers for development (Mailpit) and production (Mailgun/SES) |
| **PDF Generation** | Laravel + DomPDF or Browsershot | Professional invoice/quote/credit note PDF generation      |
| **Validation**     | Laravel Form Requests      | Centralized validation logic with reusable request classes       |
| **Testing**        | PHPUnit + Pest             | Comprehensive test suite with expressive syntax (Pest)           |
| **Static Analysis**| PHPStan (Level 8+)         | Catch type errors and logic issues before runtime                |
| **Code Style**     | Laravel Pint (PSR-12)      | Automated code formatting                                        |

#### 6.2.2 Front-end: Next.js

| Component          | Technology                | Rationale                                                       |
|--------------------|---------------------------|-----------------------------------------------------------------|
| **Framework**      | Next.js 15.x (App Router) | React meta-framework with SSR/SSG, file-based routing, Server Components |
| **React Version**  | React 19.x                | Latest concurrent features, Server Components, improved performance |
| **Build Tool**     | Turbopack (via Next.js)    | Blazing fast HMR, optimized production builds                    |
| **Styling**        | Tailwind CSS 4.x           | Utility-first CSS, rapid prototyping, consistent design system   |
| **State Management** | Zustand                  | Lightweight, TypeScript-first, minimal boilerplate               |
| **HTTP Client**    | fetch (native) + custom hooks | Native browser API, Server Actions for mutations              |
| **Form Handling**  | react-hook-form + Zod      | Schema-based validation with TypeScript integration              |
| **UI Components**  | shadcn/ui + Radix UI       | Accessible, copy-paste components built on Radix primitives, styled with Tailwind CSS |
| **Icons**          | Lucide React               | Consistent, tree-shakeable icon library                          |
| **Charts**         | Recharts                   | React-native charting library for dashboard and analytics        |
| **Rich Text Editor** | TipTap                  | Modern, extensible WYSIWYG for email campaign editor             |
| **Internationalization** | next-intl            | Internationalization support for multi-language UI                |
| **Theming**        | next-themes                | Dark mode support with system preference detection               |
| **Toasts**         | Sonner                     | Lightweight, accessible toast notifications                      |
| **Testing**        | Vitest + React Testing Library | Fast unit testing with Vite-native runner                    |
| **E2E Testing**    | Playwright                 | Cross-browser end-to-end testing                                 |
| **Linting**        | ESLint (eslint-config-next) | Consistent code style enforcement with Next.js best practices   |
| **TypeScript**     | Full TypeScript support    | Type safety across the entire front-end codebase                 |

#### 6.2.3 Database: PostgreSQL

| Aspect             | Detail                                                                |
|--------------------|-----------------------------------------------------------------------|
| **Version**        | PostgreSQL 16.x                                                       |
| **Rationale**      | ACID compliance, advanced JSON support, excellent indexing (GIN, GiST), window functions, CTEs, robust ecosystem |
| **Extensions**     | `uuid-ossp` (UUID generation), `pg_trgm` (trigram matching for fallback search) |
| **Migrations**     | All schema changes via Laravel migrations                              |
| **Indexing Strategy** | B-tree indexes on foreign keys and frequently filtered columns; GIN indexes for full-text search fallback |
| **Connection Pooling** | PgBouncer (optional, for production scaling)                      |

#### 6.2.4 Search: Meilisearch

| Aspect             | Detail                                                                |
|--------------------|-----------------------------------------------------------------------|
| **Version**        | Meilisearch 1.x (latest stable)                                      |
| **Rationale**      | Sub-50ms search, typo tolerance, faceted search, easy integration with Laravel Scout |
| **Integration**    | Laravel Scout driver for automatic index synchronization              |
| **Indexed Entities** | Clients, contacts, projects, tasks, invoices, quotes, credit notes, campaigns |
| **Configuration**  | Searchable attributes, filterable attributes, sortable attributes, and ranking rules configured per index |

#### 6.2.5 Cache: Redis

| Aspect             | Detail                                                                |
|--------------------|-----------------------------------------------------------------------|
| **Version**        | Redis 7.x                                                            |
| **Rationale**      | In-memory data store for low-latency caching, session storage, and queue backend |
| **Usage**          | Application cache (query results, computed data), session storage, queue driver for Laravel jobs, rate limiting storage |
| **Eviction Policy** | `allkeys-lru` for cache, separate database for sessions              |

#### 6.2.6 Reverse Proxy: Nginx

| Aspect             | Detail                                                                |
|--------------------|-----------------------------------------------------------------------|
| **Role**           | TLS termination, request routing, static asset serving, rate limiting |
| **Routing**        | `/api/*` → Laravel, `/*` → Next.js server                            |
| **TLS**            | Let's Encrypt certificates (production), self-signed (development)    |

### 6.3 Service Communication

```
Browser ──HTTPS──▶ Nginx ──HTTP──▶ Next.js (SSR)
                          ──HTTP──▶ Laravel API ──TCP──▶ PostgreSQL
                                                ──TCP──▶ Redis
                                                ──HTTP─▶ Meilisearch
                                                ──SMTP─▶ Mail Provider
                                                ──HTTP─▶ SMS Provider

Next.js (Client-side) ──HTTPS──▶ Nginx ──HTTP──▶ Laravel API
```

All inter-service communication occurs within the Docker network (no external exposure except Nginx on ports 80/443).

---

## 7. UI/UX Design Guidelines and Branding

### 7.1 Design Philosophy

The application adopts a **sober, modern, and professional** aesthetic that prioritizes clarity, efficiency, and minimal visual noise. The design philosophy follows these principles:

- **Content-first:** UI elements serve the data, not the other way around.
- **Consistent patterns:** Reusable component patterns across all modules for rapid learnability.
- **Whitespace as structure:** Generous spacing to reduce cognitive load.
- **Progressive disclosure:** Show essential information upfront; details on demand.
- **Dark mode support:** Optional dark theme for developer comfort during extended use.

### 7.2 Branding Elements

| Element            | Specification                                                      |
|--------------------|--------------------------------------------------------------------|
| **Product Name**   | Koomky                                                             |
| **Logo**           | [Placeholder: Minimalist wordmark in the primary color with a subtle geometric icon representing connectivity/organization] |
| **Primary Color**  | `#2563EB` (Blue-600) — Trust, professionalism, reliability         |
| **Secondary Color**| `#0F172A` (Slate-900) — Depth, sophistication                      |
| **Accent Color**   | `#10B981` (Emerald-500) — Success, growth, positive actions        |
| **Warning Color**  | `#F59E0B` (Amber-500) — Attention, pending states                  |
| **Danger Color**   | `#EF4444` (Red-500) — Errors, destructive actions                  |
| **Background**     | `#F8FAFC` (Slate-50, light) / `#0F172A` (Slate-900, dark)         |
| **Typography**     | Inter (headings and body text) — clean, geometric, highly readable |
| **Monospace Font** | JetBrains Mono — for code snippets, reference numbers              |
| **Border Radius**  | `0.5rem` (8px) — rounded but not overly soft                      |
| **Shadows**        | Subtle, layered shadows for cards and elevated elements            |

### 7.3 Tailwind CSS Configuration

Tailwind CSS provides the following benefits for this project:

- **Rapid development** — Utility classes eliminate context-switching between HTML and CSS files.
- **Design consistency** — Predefined spacing, color, and typography scales enforce consistency.
- **Small bundle size** — PurgeCSS removes unused classes in production builds.
- **Responsive design** — Built-in responsive prefixes (`sm:`, `md:`, `lg:`, `xl:`, `2xl:`).
- **Dark mode** — Native `dark:` variant for theme switching.
- **Custom design tokens** — Extended via `tailwind.config.ts` with brand colors, fonts, and spacing.

### 7.4 Layout Structure

```
┌─────────────────────────────────────────────────────────────────┐
│  Top Bar: Logo │ Global Search (Ctrl+K) │ Notifications │ User │
├──────────┬──────────────────────────────────────────────────────┤
│          │                                                      │
│  Side    │  Main Content Area                                   │
│  Nav     │                                                      │
│          │  ┌──────────────────────────────────────────────┐    │
│  - Dash  │  │  Page Header: Title │ Actions (buttons)     │    │
│  - Clients│  ├──────────────────────────────────────────────┤    │
│  - Projects│ │                                              │    │
│  - Finance│  │  Content: Tables, forms, cards, charts       │    │
│  - Campaigns│ │                                             │    │
│  - Settings│ │                                              │    │
│          │  └──────────────────────────────────────────────┘    │
│          │                                                      │
└──────────┴──────────────────────────────────────────────────────┘
```

### 7.5 Key UI Patterns

| Pattern               | Description                                                          |
|-----------------------|----------------------------------------------------------------------|
| **Data Tables**       | Sortable columns, row actions (view/edit/delete), bulk selection, pagination, column visibility toggle |
| **Forms**             | Single-column layout, floating labels, inline validation, auto-save for drafts |
| **Modals/Drawers**    | Slide-over drawers for quick edits; centered modals for confirmations |
| **Cards**             | Used for dashboard widgets, entity summaries, and campaign previews   |
| **Toasts**            | Bottom-right positioned, auto-dismiss after 5 seconds, action buttons for undo |
| **Empty States**      | Illustration + heading + description + primary CTA button             |
| **Status Badges**     | Color-coded chips for statuses (green=active, yellow=pending, red=overdue, gray=archived) |
| **Breadcrumbs**       | Shown on detail/edit pages for hierarchical navigation                |
| **Command Palette**   | Ctrl+K powered global search and quick actions                        |

### 7.6 Key User Flows

#### 7.6.1 Invoice Creation Flow
```
Dashboard → Finances → Invoices → "New Invoice" button
    → Select Client (autocomplete)
    → Add Line Items (description, qty, unit price, VAT rate)
    → Add Discount (optional)
    → Preview PDF (side panel)
    → Save as Draft / Send via Email
```

#### 7.6.2 Quote-to-Invoice Conversion
```
Quote Detail Page → Status: "Accepted"
    → "Convert to Invoice" button
    → Review pre-filled invoice (editable)
    → Save / Send
```

#### 7.6.3 Campaign Creation Flow
```
Campaigns → "New Campaign" button
    → Choose Type: Email / SMS
    → Select or Create Segment
    → Compose Content (editor with variables)
    → Preview & Test Send
    → Schedule or Send Now
    → Monitor Analytics
```

---

## 8. Data Models and API Specifications

### 8.1 Entity-Relationship Overview

```
┌────────────┐       ┌────────────┐       ┌────────────┐
│   User     │       │   Client   │       │  Contact   │
│            │──1:N──│            │──1:N──│            │
│ id         │       │ id         │       │ id         │
│ name       │       │ user_id    │       │ client_id  │
│ email      │       │ reference  │       │ first_name │
│ password   │       │ company    │       │ last_name  │
│ ...        │       │ email      │       │ email      │
└────────────┘       │ phone      │       │ phone      │
                     │ address    │       │ role       │
                     │ tags       │       │ is_primary │
                     │ ...        │       └────────────┘
                     └─────┬──────┘
                           │
              ┌────────────┼────────────┐
              │            │            │
       ┌──────▼─────┐ ┌───▼──────┐ ┌──▼──────────┐
       │  Project    │ │ Invoice  │ │   Quote     │
       │             │ │          │ │   (Devis)   │
       │ id          │ │ id       │ │ id          │
       │ client_id   │ │ client_id│ │ client_id   │
       │ name        │ │ project_id││ project_id  │
       │ status      │ │ number   │ │ number      │
       │ deadline    │ │ status   │ │ status      │
       │ ...         │ │ total    │ │ valid_until │
       └──────┬──────┘ │ ...     │ │ ...         │
              │         └────┬────┘ └─────────────┘
              │              │
       ┌──────▼──────┐ ┌────▼──────────┐
       │   Task      │ │ Credit Note   │
       │             │ │ (Avoir)       │
       │ id          │ │ id            │
       │ project_id  │ │ invoice_id    │
       │ title       │ │ number        │
       │ status      │ │ status        │
       │ priority    │ │ amount        │
       │ due_date    │ │ ...           │
       │ ...         │ └───────────────┘
       └──────┬──────┘
              │
       ┌──────▼──────┐
       │ Time Entry  │
       │             │
       │ id          │
       │ task_id     │
       │ duration    │
       │ date        │
       │ description │
       └─────────────┘

┌────────────┐       ┌──────────────┐       ┌───────────────┐
│  Campaign  │──1:N──│ Campaign     │──N:1──│ Contact       │
│            │       │ Recipient    │       │               │
│ id         │       │ id           │       │               │
│ name       │       │ campaign_id  │       │               │
│ type       │       │ contact_id   │       │               │
│ status     │       │ sent_at      │       │               │
│ content    │       │ opened_at    │       │               │
│ scheduled  │       │ clicked_at   │       │               │
│ ...        │       │ bounced      │       │               │
└────────────┘       └──────────────┘       └───────────────┘
```

### 8.2 Core Data Models

#### 8.2.1 User

| Column              | Type         | Constraints                    | Description              |
|---------------------|--------------|--------------------------------|--------------------------|
| `id`                | UUID         | PK                             | Unique identifier        |
| `name`              | VARCHAR(255) | NOT NULL                       | Full name                |
| `email`             | VARCHAR(255) | NOT NULL, UNIQUE               | Login email              |
| `email_verified_at` | TIMESTAMP    | NULLABLE                       | Email verification date  |
| `password`          | VARCHAR(255) | NOT NULL                       | Hashed password          |
| `two_factor_secret` | TEXT         | NULLABLE, ENCRYPTED            | TOTP secret              |
| `avatar_path`       | VARCHAR(500) | NULLABLE                       | Profile picture path     |
| `business_name`     | VARCHAR(255) | NULLABLE                       | Company/business name    |
| `business_address`  | TEXT         | NULLABLE                       | Business address         |
| `siret`             | VARCHAR(14)  | NULLABLE                       | SIRET number             |
| `ape_code`          | VARCHAR(6)   | NULLABLE                       | APE/NAF code             |
| `vat_number`        | VARCHAR(20)  | NULLABLE                       | EU VAT number            |
| `default_payment_terms` | INTEGER  | DEFAULT 30                     | Default payment terms (days) |
| `bank_details`      | TEXT         | NULLABLE, ENCRYPTED            | Bank account details     |
| `invoice_footer`    | TEXT         | NULLABLE                       | Default invoice footer text |
| `created_at`        | TIMESTAMP    | NOT NULL                       | Record creation time     |
| `updated_at`        | TIMESTAMP    | NOT NULL                       | Last update time         |

#### 8.2.2 Client

| Column         | Type         | Constraints                       | Description              |
|----------------|--------------|-----------------------------------|--------------------------|
| `id`           | UUID         | PK                                | Unique identifier        |
| `user_id`      | UUID         | FK → users.id, NOT NULL           | Owner user               |
| `reference`    | VARCHAR(20)  | NOT NULL, UNIQUE                  | Auto-generated ref (CLI-YYYY-NNNN) |
| `company_name` | VARCHAR(255) | NULLABLE                          | Company name             |
| `first_name`   | VARCHAR(100) | NOT NULL                          | First name               |
| `last_name`    | VARCHAR(100) | NOT NULL                          | Last name                |
| `email`        | VARCHAR(255) | NULLABLE                          | Primary email            |
| `phone`        | VARCHAR(20)  | NULLABLE                          | Primary phone            |
| `address`      | TEXT         | NULLABLE                          | Street address           |
| `city`         | VARCHAR(100) | NULLABLE                          | City                     |
| `postal_code`  | VARCHAR(10)  | NULLABLE                          | Postal/ZIP code          |
| `country`      | VARCHAR(2)   | DEFAULT 'FR'                      | ISO 3166-1 alpha-2       |
| `siret`        | VARCHAR(14)  | NULLABLE                          | Client SIRET             |
| `vat_number`   | VARCHAR(20)  | NULLABLE                          | Client VAT number        |
| `website`      | VARCHAR(500) | NULLABLE                          | Client website URL       |
| `notes`        | TEXT         | NULLABLE                          | Free-form notes          |
| `archived_at`  | TIMESTAMP    | NULLABLE                          | Soft-delete timestamp    |
| `created_at`   | TIMESTAMP    | NOT NULL                          | Record creation time     |
| `updated_at`   | TIMESTAMP    | NOT NULL                          | Last update time         |

**Indexes:** `user_id`, `reference`, `email`, `company_name`, `archived_at`

#### 8.2.3 Invoice

| Column           | Type           | Constraints                      | Description              |
|------------------|----------------|----------------------------------|--------------------------|
| `id`             | UUID           | PK                               | Unique identifier        |
| `user_id`        | UUID           | FK → users.id, NOT NULL          | Owner user               |
| `client_id`      | UUID           | FK → clients.id, NOT NULL        | Billed client            |
| `project_id`     | UUID           | FK → projects.id, NULLABLE       | Associated project       |
| `number`         | VARCHAR(20)    | NOT NULL, UNIQUE                 | Sequential number (FAC-YYYY-NNNN) |
| `status`         | ENUM           | NOT NULL, DEFAULT 'draft'        | Current status           |
| `issue_date`     | DATE           | NOT NULL                         | Invoice date             |
| `due_date`       | DATE           | NOT NULL                         | Payment due date         |
| `subtotal`       | DECIMAL(12,2)  | NOT NULL, DEFAULT 0              | Pre-tax total            |
| `tax_amount`     | DECIMAL(12,2)  | NOT NULL, DEFAULT 0              | Total tax                |
| `discount_type`  | ENUM('percentage','fixed') | NULLABLE              | Discount type            |
| `discount_value` | DECIMAL(12,2)  | NULLABLE                         | Discount amount          |
| `total`          | DECIMAL(12,2)  | NOT NULL, DEFAULT 0              | Grand total              |
| `amount_paid`    | DECIMAL(12,2)  | NOT NULL, DEFAULT 0              | Amount received          |
| `currency`       | VARCHAR(3)     | DEFAULT 'EUR'                    | Currency code            |
| `notes`          | TEXT           | NULLABLE                         | Additional notes         |
| `payment_terms`  | TEXT           | NULLABLE                         | Payment terms text       |
| `pdf_path`       | VARCHAR(500)   | NULLABLE                         | Generated PDF path       |
| `sent_at`        | TIMESTAMP      | NULLABLE                         | When sent to client      |
| `paid_at`        | TIMESTAMP      | NULLABLE                         | When fully paid          |
| `created_at`     | TIMESTAMP      | NOT NULL                         | Record creation time     |
| `updated_at`     | TIMESTAMP      | NOT NULL                         | Last update time         |

**Indexes:** `user_id`, `client_id`, `project_id`, `number`, `status`, `due_date`, `issue_date`

#### 8.2.4 Line Item (shared by Invoice, Quote, Credit Note)

| Column           | Type           | Constraints                      | Description              |
|------------------|----------------|----------------------------------|--------------------------|
| `id`             | UUID           | PK                               | Unique identifier        |
| `documentable_id` | UUID          | NOT NULL                         | Polymorphic: invoice/quote/credit note ID |
| `documentable_type` | VARCHAR(50) | NOT NULL                         | Polymorphic type         |
| `description`    | TEXT           | NOT NULL                         | Item description         |
| `quantity`       | DECIMAL(10,2)  | NOT NULL, DEFAULT 1              | Quantity                 |
| `unit_price`     | DECIMAL(12,2)  | NOT NULL                         | Price per unit           |
| `vat_rate`       | DECIMAL(5,2)   | NOT NULL, DEFAULT 20.00          | VAT percentage           |
| `total`          | DECIMAL(12,2)  | NOT NULL                         | Line total (qty × price) |
| `sort_order`     | INTEGER        | NOT NULL, DEFAULT 0              | Display order            |
| `created_at`     | TIMESTAMP      | NOT NULL                         | Record creation time     |
| `updated_at`     | TIMESTAMP      | NOT NULL                         | Last update time         |

### 8.3 API Specifications

#### 8.3.1 API Design Principles

- **RESTful** — Resource-oriented URLs, standard HTTP methods.
- **JSON:API-inspired** — Consistent response structure with `data`, `meta`, and `links`.
- **Versioned** — All endpoints prefixed with `/api/v1/`.
- **Authenticated** — Bearer token (JWT) required for all endpoints except auth routes.
- **Paginated** — List endpoints return paginated results (default: 25 items, max: 100).
- **Filterable** — Query parameters for filtering (`?filter[status]=active`).
- **Sortable** — Query parameter for sorting (`?sort=-created_at`).
- **Includable** — Related resources via `?include=client,project`.

#### 8.3.2 Standard Response Format

**Success (single resource):**
```json
{
  "data": {
    "id": "uuid",
    "type": "invoice",
    "attributes": { ... }
  }
}
```

**Success (collection):**
```json
{
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 25,
    "total": 112
  },
  "links": {
    "first": "/api/v1/invoices?page=1",
    "last": "/api/v1/invoices?page=5",
    "prev": null,
    "next": "/api/v1/invoices?page=2"
  }
}
```

**Error:**
```json
{
  "error": {
    "status": 422,
    "message": "Validation failed",
    "errors": {
      "email": ["The email field is required."]
    }
  }
}
```

#### 8.3.3 Key API Endpoints

**Authentication:**

| Method | Endpoint                     | Description              |
|--------|------------------------------|--------------------------|
| POST   | `/api/v1/auth/register`      | Register new user        |
| POST   | `/api/v1/auth/login`         | Login, return tokens     |
| POST   | `/api/v1/auth/refresh`       | Refresh access token     |
| POST   | `/api/v1/auth/logout`        | Invalidate tokens        |
| POST   | `/api/v1/auth/forgot-password` | Send reset email       |
| POST   | `/api/v1/auth/reset-password`  | Reset password         |

**Clients:**

| Method | Endpoint                          | Description                    |
|--------|-----------------------------------|--------------------------------|
| GET    | `/api/v1/clients`                 | List clients (paginated)       |
| POST   | `/api/v1/clients`                 | Create client                  |
| GET    | `/api/v1/clients/{id}`            | Get client details             |
| PUT    | `/api/v1/clients/{id}`            | Update client                  |
| DELETE | `/api/v1/clients/{id}`            | Soft-delete (archive) client   |
| POST   | `/api/v1/clients/{id}/restore`    | Restore archived client        |
| GET    | `/api/v1/clients/{id}/contacts`   | List client contacts           |
| POST   | `/api/v1/clients/{id}/contacts`   | Add contact to client          |
| GET    | `/api/v1/clients/{id}/timeline`   | Get client activity timeline   |

**Projects:**

| Method | Endpoint                             | Description                    |
|--------|--------------------------------------|--------------------------------|
| GET    | `/api/v1/projects`                   | List projects                  |
| POST   | `/api/v1/projects`                   | Create project                 |
| GET    | `/api/v1/projects/{id}`              | Get project details            |
| PUT    | `/api/v1/projects/{id}`              | Update project                 |
| DELETE | `/api/v1/projects/{id}`              | Delete project                 |
| GET    | `/api/v1/projects/{id}/tasks`        | List project tasks             |
| POST   | `/api/v1/projects/{id}/tasks`        | Create task in project         |
| PUT    | `/api/v1/projects/{id}/tasks/{tid}`  | Update task                    |
| DELETE | `/api/v1/projects/{id}/tasks/{tid}`  | Delete task                    |
| POST   | `/api/v1/tasks/{tid}/time-entries`   | Log time entry                 |

**Invoices:**

| Method | Endpoint                              | Description                    |
|--------|---------------------------------------|--------------------------------|
| GET    | `/api/v1/invoices`                    | List invoices                  |
| POST   | `/api/v1/invoices`                    | Create invoice                 |
| GET    | `/api/v1/invoices/{id}`               | Get invoice details            |
| PUT    | `/api/v1/invoices/{id}`               | Update invoice                 |
| DELETE | `/api/v1/invoices/{id}`               | Delete draft invoice           |
| POST   | `/api/v1/invoices/{id}/send`          | Send invoice via email         |
| POST   | `/api/v1/invoices/{id}/payments`      | Record payment                 |
| GET    | `/api/v1/invoices/{id}/pdf`           | Download invoice PDF           |
| POST   | `/api/v1/invoices/{id}/duplicate`     | Duplicate invoice              |

**Quotes:**

| Method | Endpoint                              | Description                    |
|--------|---------------------------------------|--------------------------------|
| GET    | `/api/v1/quotes`                      | List quotes                    |
| POST   | `/api/v1/quotes`                      | Create quote                   |
| GET    | `/api/v1/quotes/{id}`                 | Get quote details              |
| PUT    | `/api/v1/quotes/{id}`                 | Update quote                   |
| DELETE | `/api/v1/quotes/{id}`                 | Delete draft quote             |
| POST   | `/api/v1/quotes/{id}/send`            | Send quote via email           |
| POST   | `/api/v1/quotes/{id}/convert`         | Convert quote to invoice       |
| GET    | `/api/v1/quotes/{id}/pdf`             | Download quote PDF             |

**Credit Notes:**

| Method | Endpoint                              | Description                    |
|--------|---------------------------------------|--------------------------------|
| GET    | `/api/v1/credit-notes`                | List credit notes              |
| POST   | `/api/v1/credit-notes`                | Create credit note             |
| GET    | `/api/v1/credit-notes/{id}`           | Get credit note details        |
| PUT    | `/api/v1/credit-notes/{id}`           | Update credit note             |
| POST   | `/api/v1/credit-notes/{id}/send`      | Send credit note via email     |
| POST   | `/api/v1/credit-notes/{id}/apply`     | Apply credit to invoice        |
| GET    | `/api/v1/credit-notes/{id}/pdf`       | Download credit note PDF       |

**Campaigns:**

| Method | Endpoint                              | Description                    |
|--------|---------------------------------------|--------------------------------|
| GET    | `/api/v1/campaigns`                   | List campaigns                 |
| POST   | `/api/v1/campaigns`                   | Create campaign                |
| GET    | `/api/v1/campaigns/{id}`              | Get campaign details           |
| PUT    | `/api/v1/campaigns/{id}`              | Update campaign                |
| DELETE | `/api/v1/campaigns/{id}`              | Delete draft campaign          |
| POST   | `/api/v1/campaigns/{id}/send`         | Launch campaign                |
| POST   | `/api/v1/campaigns/{id}/pause`        | Pause campaign                 |
| POST   | `/api/v1/campaigns/{id}/test`         | Send test email/SMS            |
| GET    | `/api/v1/campaigns/{id}/analytics`    | Get campaign analytics         |
| POST   | `/api/v1/campaigns/{id}/duplicate`    | Duplicate campaign             |

**Segments:**

| Method | Endpoint                              | Description                    |
|--------|---------------------------------------|--------------------------------|
| GET    | `/api/v1/segments`                    | List segments                  |
| POST   | `/api/v1/segments`                    | Create segment                 |
| GET    | `/api/v1/segments/{id}`               | Get segment details            |
| PUT    | `/api/v1/segments/{id}`               | Update segment                 |
| GET    | `/api/v1/segments/{id}/preview`       | Preview matching contacts      |

**Search:**

| Method | Endpoint                              | Description                    |
|--------|---------------------------------------|--------------------------------|
| GET    | `/api/v1/search?q={query}`            | Global search across entities  |
| GET    | `/api/v1/search?q={query}&type=client`| Search filtered by entity type |

**Settings:**

| Method | Endpoint                              | Description                    |
|--------|---------------------------------------|--------------------------------|
| GET    | `/api/v1/settings`                    | Get user settings              |
| PUT    | `/api/v1/settings`                    | Update settings                |
| POST   | `/api/v1/settings/logo`               | Upload business logo           |

**Reports:**

| Method | Endpoint                              | Description                    |
|--------|---------------------------------------|--------------------------------|
| GET    | `/api/v1/reports/revenue`             | Revenue report                 |
| GET    | `/api/v1/reports/outstanding`         | Outstanding payments report    |
| GET    | `/api/v1/reports/vat-summary`         | VAT summary report             |
| GET    | `/api/v1/reports/export`              | Export report (CSV/PDF)        |

---

## 9. Integration Requirements

### 9.1 Email Sending Service

| Attribute          | Detail                                                          |
|--------------------|-----------------------------------------------------------------|
| **Provider Options** | Mailgun (recommended), Amazon SES, SendGrid, SMTP             |
| **Development**    | Mailpit (local email testing container)                         |
| **Integration**    | Laravel Mail driver — configurable via environment variables     |
| **Requirements**   | Bounce handling (webhook), open tracking (tracking pixel), click tracking (link rewriting), unsubscribe webhook |
| **Rate Limits**    | Configurable per provider; default 100/minute                   |
| **Templates**      | HTML email templates stored in the application, rendered via Blade |

### 9.2 SMS Gateway

| Attribute          | Detail                                                          |
|--------------------|-----------------------------------------------------------------|
| **Provider Options** | Twilio (recommended), Vonage (Nexmo), OVH SMS                 |
| **Integration**    | REST API via Laravel HTTP client with a dedicated SMS service class |
| **Requirements**   | Delivery status callbacks (webhook), opt-out handling, E.164 phone number validation |
| **Rate Limits**    | Configurable per provider; default 30 SMS/minute                |
| **Sender ID**      | Configurable alphanumeric sender name (max 11 chars, provider-dependent) |

### 9.3 PDF Generation

| Attribute          | Detail                                                          |
|--------------------|-----------------------------------------------------------------|
| **Primary Option** | DomPDF (pure PHP, no external dependency)                       |
| **Alternative**    | Browsershot (Puppeteer-based, higher fidelity)                  |
| **Integration**    | Laravel service class wrapping the PDF library                  |
| **Templates**      | Blade templates with Tailwind CSS inline styles for invoices, quotes, credit notes |
| **Requirements**   | A4 format, custom header/footer, embedded logo, consistent rendering |

### 9.4 File Storage

| Attribute          | Detail                                                          |
|--------------------|-----------------------------------------------------------------|
| **Development**    | Local filesystem (Docker volume)                                |
| **Production**     | S3-compatible storage (AWS S3, MinIO, or DigitalOcean Spaces)   |
| **Integration**    | Laravel Filesystem (Flysystem) — driver configurable via environment |
| **Files Stored**   | Generated PDFs, task attachments, user avatars, business logos   |
| **Max File Size**  | 10MB per individual file                                        |

### 9.5 Calendar Integration (Future Phase)

| Attribute          | Detail                                                          |
|--------------------|-----------------------------------------------------------------|
| **Providers**      | Google Calendar, CalDAV                                         |
| **Integration**    | OAuth 2.0 for Google Calendar; CalDAV protocol for others       |
| **Requirements**   | Sync project deadlines and tasks as calendar events             |
| **Phase**          | Phase 4 (not in initial release)                                |

---

## 10. Testing Strategy

### 10.1 TDD Methodology

The project strictly follows **Test-Driven Development (TDD)** with the Red-Green-Refactor cycle:

1. **Red** — Write a failing test that describes the expected behavior.
2. **Green** — Write the minimal code to make the test pass.
3. **Refactor** — Improve the code structure while keeping tests green.

Every feature, bug fix, and refactoring effort begins with writing tests first.

### 10.2 Coverage Targets

| Codebase     | Tool               | Minimum Coverage | Target Coverage |
|--------------|---------------------|-----------------|-----------------|
| Back-end     | PHPUnit/Pest        | 80%             | 90%             |
| Front-end    | Vitest              | 80%             | 85%             |
| E2E          | Playwright          | Critical paths  | All user flows  |

### 10.3 Test Types

#### 10.3.1 Back-end Tests (Laravel)

| Test Type         | Scope                                              | Tools         | Count Target |
|-------------------|----------------------------------------------------|---------------|--------------|
| **Unit Tests**    | Models, services, value objects, helpers            | Pest/PHPUnit  | ~60% of tests |
| **Feature Tests** | API endpoints, middleware, jobs, commands            | Pest/PHPUnit  | ~30% of tests |
| **Integration Tests** | Database queries, external service interactions | Pest/PHPUnit  | ~10% of tests |

- **Database:** Tests use a dedicated PostgreSQL test database with transactions rolled back after each test (RefreshDatabase trait).
- **External Services:** Mocked using Laravel's built-in fakes (Mail::fake, Queue::fake, Http::fake) and Mockery.
- **Factories:** Every model has a corresponding factory for consistent test data generation.

#### 10.3.2 Front-end Tests (Next.js)

| Test Type         | Scope                                                    | Tools                        |
|-------------------|----------------------------------------------------------|------------------------------|
| **Unit Tests**    | Custom hooks, utility functions, Zustand stores           | Vitest                       |
| **Component Tests** | Individual React components (render, props, interactions) | Vitest + React Testing Library |
| **E2E Tests**     | Full user workflows in a browser                          | Playwright                   |

- **API Mocking:** MSW (Mock Service Worker) for mocking API responses in component tests.
- **Snapshot Tests:** Used sparingly for complex UI components to detect unintended visual changes.

#### 10.3.3 Performance Tests

| Aspect            | Tool               | Criteria                                    |
|-------------------|---------------------|---------------------------------------------|
| API Load Testing  | k6 or Artillery     | 100 concurrent users, p95 < 200ms           |
| Database Queries  | Laravel Debugbar    | No N+1 queries, max 100ms per query         |
| Front-end Bundle  | Vite build analyzer | JS bundle < 300KB (gzipped), CSS < 50KB     |
| Lighthouse Score  | Lighthouse CI       | Performance > 90, Accessibility > 90        |

#### 10.3.4 Security Tests

| Aspect            | Tool                          | Frequency       |
|-------------------|-------------------------------|-----------------|
| Dependency Audit  | `composer audit`, `npm audit` | Every CI run    |
| SAST              | PHPStan, ESLint Security      | Every CI run    |
| OWASP Top 10      | Manual review + automated     | Per release     |
| Penetration Tests | Manual                        | Quarterly       |

### 10.4 Quality Assurance Process

1. Developer writes tests (TDD) and implements the feature.
2. All tests pass locally before creating a PR.
3. CI pipeline runs the full test suite (unit, feature, integration, E2E).
4. Code coverage is checked against the 80% minimum threshold.
5. Static analysis (PHPStan, ESLint) runs without errors.
6. PR is reviewed by the developer (self-review for solo project; automated checks are the primary gate).
7. All checks must pass before the PR can be merged.

---

## 11. Deployment and DevOps Requirements

### 11.1 Version Control: GitHub

| Aspect              | Specification                                                 |
|---------------------|---------------------------------------------------------------|
| **Repository**      | Single monorepo containing back-end, front-end, and Docker configuration |
| **Branching Model** | GitHub Flow — `main` (production), feature branches (`feature/xxx`), bugfix branches (`fix/xxx`) |
| **Branch Protection** | `main` is protected: requires passing CI checks, no direct pushes |
| **Commit Convention** | Conventional Commits (`feat:`, `fix:`, `docs:`, `test:`, `chore:`, `refactor:`) |

### 11.2 Repository Structure

```
koomky/
├── .github/
│   ├── workflows/
│   │   ├── ci.yml              # Main CI pipeline
│   │   ├── deploy.yml          # Deployment workflow
│   │   └── security-audit.yml  # Weekly security audit
│   └── PULL_REQUEST_TEMPLATE.md
├── backend/                    # Laravel application
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── tests/
│   ├── composer.json
│   └── phpunit.xml
├── frontend/                   # Next.js application
│   ├── app/                   # App Router (pages, layouts, routes)
│   ├── components/            # Reusable React components (shadcn/ui + custom)
│   ├── hooks/                 # Custom React hooks
│   ├── lib/                   # Utilities, API client, Zustand stores
│   ├── tests/
│   ├── next.config.ts
│   ├── tsconfig.json
│   └── package.json
├── docker/
│   ├── nginx/
│   │   └── default.conf
│   ├── php/
│   │   └── Dockerfile
│   ├── node/
│   │   └── Dockerfile
│   └── postgres/
│       └── init.sql
├── docker-compose.yml          # Development environment
├── docker-compose.prod.yml     # Production environment
├── Makefile                    # Common development commands
├── CHANGELOG.md
├── README.md
└── PRD.md
```

### 11.3 CI/CD Pipeline

#### 11.3.1 Continuous Integration (`ci.yml`)

Triggered on: every push to any branch, every PR to `main`.

```yaml
# Conceptual pipeline stages:
stages:
  - lint-and-static-analysis
  - backend-tests
  - frontend-tests
  - e2e-tests
  - coverage-check
  - build-images
```

| Stage                     | Actions                                                        |
|---------------------------|----------------------------------------------------------------|
| **Lint & Static Analysis** | Run `laravel pint --test`, `phpstan`, `eslint`, `prettier --check` |
| **Back-end Tests**        | Run `pest --coverage` against PostgreSQL test DB (Docker service) |
| **Front-end Tests**       | Run `vitest run --coverage`                                     |
| **E2E Tests**             | Run `playwright test` against full Docker Compose stack          |
| **Coverage Check**        | Fail if back-end or front-end coverage < 80%                    |
| **Build Docker Images**   | Build and tag images (only on `main` branch merges)             |

> **Note:** `npm audit` references in the CI apply to `pnpm audit` as pnpm is the package manager.

#### 11.3.2 PR Workflow

1. Developer creates a feature branch from `main`.
2. Developer pushes commits to the feature branch.
3. Developer opens a PR via `gh pr create`.
4. CI pipeline is triggered automatically.
5. **All CI checks MUST pass** before the PR can be merged.
6. Developer merges the PR via `gh pr merge --squash`.
7. The deployment pipeline is triggered on merge to `main`.

#### 11.3.3 Continuous Deployment (`deploy.yml`)

Triggered on: merge to `main` (after CI passes).

| Stage                  | Actions                                                        |
|------------------------|----------------------------------------------------------------|
| **Build**              | Build production Docker images for Laravel, Next.js, Nginx     |
| **Tag**                | Tag images with commit SHA and `latest`                         |
| **Push**               | Push images to container registry (GitHub Container Registry)   |
| **Deploy**             | SSH to production server, pull new images, `docker compose up -d` |
| **Health Check**       | Verify all containers are healthy, run smoke tests              |
| **Rollback**           | If health check fails, automatically revert to previous images  |

#### 11.3.4 gh CLI Usage

| Command                              | Purpose                                      |
|--------------------------------------|----------------------------------------------|
| `gh pr create --fill`                | Create a PR with auto-filled title/body      |
| `gh pr checks`                       | View CI check status for a PR                |
| `gh pr merge --squash --delete-branch` | Merge PR with squash and clean up branch    |
| `gh release create vX.Y.Z`          | Create a tagged release                      |
| `gh workflow run deploy.yml`         | Manually trigger deployment                  |
| `gh issue create`                    | Create issue for bug tracking                |

### 11.4 Container Orchestration

| Environment   | Orchestration         | Details                                           |
|---------------|-----------------------|---------------------------------------------------|
| **Development** | Docker Compose       | All services defined in `docker-compose.yml`, hot-reload enabled for both back-end and front-end |
| **Production**  | Docker Compose (initial) | `docker-compose.prod.yml` with production configs, restart policies, resource limits |
| **Future**      | Kubernetes (optional) | If scaling beyond a single server becomes necessary |

### 11.5 Monitoring, Logging, and Alerting

| Aspect            | Tool/Approach                              | Details                           |
|-------------------|--------------------------------------------|-----------------------------------|
| **Application Logs** | Laravel Log (Monolog) → JSON format     | Structured logging with context   |
| **Front-end Errors** | Sentry (optional)                       | Client-side error tracking        |
| **Container Logs**   | Docker logging driver → JSON file        | Centralized via `docker logs`     |
| **Uptime Monitoring** | UptimeRobot or Healthchecks.io          | Health check endpoint monitoring  |
| **Performance**      | Laravel Telescope (dev) / Debugbar       | Query analysis, request profiling |
| **Alerting**         | Email/Slack notifications                | On deployment failure, downtime, error spike |
| **Metrics**          | Prometheus + Grafana (future)            | System and application metrics    |

---

## 12. Timeline and Milestones

### 12.1 Phased Development Approach

Each phase MUST be fully completed and validated (all tests passing, coverage >= 80%) before proceeding to the next.

#### Phase 1: Foundation and Core CRM (Weeks 1-6)

| Week  | Deliverables                                                          |
|-------|-----------------------------------------------------------------------|
| 1-2   | Project scaffolding: Docker setup, Laravel API skeleton, Next.js skeleton, PostgreSQL schema, Redis config, CI pipeline |
| 3-4   | Authentication system (registration, login, JWT, 2FA), user settings  |
| 4-5   | Client management (full CRUD, contacts, tags, timeline, search)       |
| 5-6   | Dashboard (basic version with client metrics), global search integration |

**Milestone 1:** Functional CRM with client management, authentication, and basic dashboard. All tests passing with >= 80% coverage.

#### Phase 2: Project and Financial Management (Weeks 7-14)

| Week  | Deliverables                                                          |
|-------|-----------------------------------------------------------------------|
| 7-8   | Project management (CRUD, statuses, billing models)                   |
| 9-10  | Task management (CRUD, Kanban, time entries, dependencies)            |
| 10-12 | Invoice management (CRUD, line items, PDF generation, email sending, status workflow) |
| 12-13 | Quote management (CRUD, PDF, email, quote-to-invoice conversion)      |
| 13-14 | Credit note management (CRUD, PDF, apply to invoice), financial reports |

**Milestone 2:** Full project and financial management. Invoices, quotes, and credit notes fully functional with PDF export and email delivery. All tests passing with >= 80% coverage.

#### Phase 3: Marketing Campaigns (Weeks 15-20)

| Week  | Deliverables                                                          |
|-------|-----------------------------------------------------------------------|
| 15-16 | Contact segmentation engine (filters, AND/OR logic, preview)          |
| 16-17 | Email campaign builder (editor, templates, variables, test send)      |
| 17-18 | Email campaign execution (scheduling, throttling, tracking)           |
| 18-19 | SMS campaign builder and execution (compose, test, send, tracking)    |
| 19-20 | Campaign analytics dashboard, enhanced main dashboard                 |

**Milestone 3:** Fully operational email and SMS campaign system with segmentation, scheduling, and analytics. All tests passing with >= 80% coverage.

#### Phase 4: Polish and Optimization (Weeks 21-24)

| Week  | Deliverables                                                          |
|-------|-----------------------------------------------------------------------|
| 21-22 | UI/UX polish, dark mode, responsive refinement, accessibility audit   |
| 22-23 | Performance optimization (query tuning, caching strategy, bundle size) |
| 23-24 | Data import/export, production deployment setup, documentation        |

**Milestone 4:** Production-ready release with polished UI, optimized performance, and comprehensive documentation.

#### Phase 5: Recurring Invoices, Multi-Currency & Calendar (Weeks 25–32)

| Week  | Deliverables                                                                   |
|-------|--------------------------------------------------------------------------------|
| 25-27 | Recurring invoice profiles, generator jobs, scheduling, notifications, UI      |
| 27-29 | Multi-currency support: currencies/rates services, conversion in documents and reports, UI |
| 29-32 | Calendar integration: Google Calendar + CalDAV connections, sync drivers, auto-events, UI |
| 32    | Prometheus + Grafana monitoring stack, v1.1.0 release                          |

**Milestone 5 (v1.1.0):** Recurring invoices, multi-currency, and calendar integration fully operational. Prometheus monitoring delivered.

#### Phase 6: Client Portal & Expense Tracking (Weeks 33–40)

| Week  | Deliverables                                                                   |
|-------|--------------------------------------------------------------------------------|
| 33-35 | Client portal: magic link auth, dashboard, invoice/quote viewing, accept/reject flows, portal settings |
| 35-36 | Stripe payment integration: payment intents, webhook sync, portal pay flow, notifications |
| 37-39 | Expense tracking: categories, CRUD, receipt upload, reporting, CSV export, import integration |
| 39-40 | Financial integration: P&L report, project profitability, dashboard widgets, billable expense invoicing, v1.2.0 release |

**Milestone 6 (v1.2.0):** Client portal with online payments, expense tracking, and financial reporting fully delivered.

#### Phase 7: Accounting Integration, Public API & Prospect Pipeline (Weeks 41–51)

| Week  | Deliverables                                                                   |
|-------|--------------------------------------------------------------------------------|
| 41-44 | FEC export, VAT declaration report, Pennylane/Sage accounting export, fiscal year closing summary |
| 45-47 | Personal Access Tokens (Public API), outbound webhooks with HMAC signing and retry, OpenAPI 3.1 spec |
| 48-51 | Prospect/lead pipeline: Kanban view, activity log, lead-to-client conversion, pipeline analytics, v1.3.0 release |

**Milestone 7 (v1.3.0):** French accounting compliance exports, external automation via webhooks and public API, and full prospect pipeline delivered.

#### Phase 8: GED (Document Management) (Weeks 52–57)

| Week  | Deliverables                                                                   |
|-------|--------------------------------------------------------------------------------|
| 52-54 | Storage service, type detection, backend CRUD, Scout integration, stats        |
| 55-57 | Document library UI (grid/list), preview panel, re-upload flow, email delivery |

**Milestone 8 (v1.4.0):** Document management system (GED) fully delivered with versioning and inline preview.

### 12.2 Milestone Summary

| Milestone | Name                                    | Target Completion | Version  | Key Deliverables                                                     |
|-----------|-----------------------------------------|-------------------|----------|----------------------------------------------------------------------|
| M1        | Core CRM                                | Week 6            | —        | Auth (incl. 2FA), clients, dashboard, global search                  |
| M2        | Projects & Finance                      | Week 14           | —        | Projects, tasks, time tracking, invoices, quotes, credit notes       |
| M3        | Marketing Campaigns                     | Week 20           | —        | Email campaigns, SMS campaigns, segmentation, analytics              |
| M4        | Production Release                      | Week 24           | v1.0.0   | Polish, dark mode, responsive, performance, data import/export, deployment |
| M5        | Recurring Invoices, Multi-Currency & Calendar | Week 32      | v1.1.0   | Recurring invoices, multi-currency, calendar integration, Prometheus monitoring |
| M6        | Client Portal & Expense Tracking        | Week 40           | v1.2.0   | Client portal, Stripe payments, expense tracking, P&L reports        |
| M7        | Accounting, Public API & Lead Pipeline  | Week 51           | v1.3.0   | FEC/VAT exports, webhooks, personal API tokens, prospect pipeline    |
| M8        | GED (Document Management)               | Week 57           | v1.4.0   | Document library, versioning, preview, email attachments             |

---

## 13. Success Metrics and KPIs

### 13.1 Product KPIs

| KPI                              | Metric                                        | Target                    | Measurement Method           |
|----------------------------------|------------------------------------------------|---------------------------|------------------------------|
| **Admin Time Reduction**         | Hours spent on admin tasks per week            | >= 50% reduction          | Self-reported time tracking  |
| **Tool Consolidation**           | Number of external tools replaced              | >= 4 tools replaced       | Tool inventory comparison    |
| **Invoice Processing Time**      | Time from project completion to invoice sent   | < 10 minutes              | Timestamp analysis           |
| **Campaign Engagement Rate**     | Email open rate across campaigns               | >= 25%                    | Campaign analytics           |
| **Search Efficiency**            | Average time to find a client/project/invoice  | < 3 seconds               | Search usage analytics       |
| **Quote Conversion Rate**        | Percentage of quotes converted to invoices     | >= 60%                    | Financial reports            |
| **Overdue Invoice Rate**         | Percentage of invoices that become overdue     | < 15%                     | Financial reports            |

### 13.2 Technical KPIs

| KPI                              | Metric                                        | Target                    | Measurement Method           |
|----------------------------------|------------------------------------------------|---------------------------|------------------------------|
| **API Response Time**            | p95 latency for CRUD operations                | < 200ms                   | Application monitoring       |
| **Test Coverage (Back-end)**     | PHPUnit/Pest line coverage                     | >= 80%                    | CI coverage report           |
| **Test Coverage (Front-end)**    | Vitest line coverage                           | >= 80%                    | CI coverage report           |
| **Build Success Rate**           | Percentage of CI builds that pass              | >= 95%                    | GitHub Actions metrics       |
| **Deployment Frequency**         | Number of production deployments per week      | >= 2                      | GitHub release count         |
| **Mean Time to Recovery (MTTR)** | Time from incident detection to resolution     | < 2 hours                 | Incident log                 |
| **Uptime**                       | Service availability percentage                | >= 99.5%                  | Uptime monitor               |
| **Search Latency**               | Meilisearch p95 query time                     | < 50ms                    | Meilisearch metrics          |

---

## 14. Risks and Mitigations

### 14.1 Risk Register

| ID    | Risk                                              | Probability | Impact  | Severity | Mitigation Strategy                                                                                         |
|-------|---------------------------------------------------|-------------|---------|----------|-------------------------------------------------------------------------------------------------------------|
| R-001 | **Scope creep** — Feature requests expanding beyond planned phases | High | Medium | High | Strict phase gating; new features logged as backlog items and only addressed after current phase completion. |
| R-002 | **Solo developer bottleneck** — Single point of failure for all development | High | High | Critical | Comprehensive documentation, TDD for safety net, modular architecture for potential future contributors.     |
| R-003 | **Third-party service outages** (email/SMS providers) | Medium | Medium | Medium | Implement retry logic with exponential backoff; support multiple provider backends; queue failed sends.      |
| R-004 | **Data loss** due to hardware failure or corruption | Low | Critical | High | Automated daily backups with 30-day retention; tested restore procedure; Docker volume backups.             |
| R-005 | **Security breach** — unauthorized access to sensitive client/financial data | Low | Critical | High | Encryption at rest and in transit; regular dependency audits; JWT rotation; rate limiting; OWASP compliance. |
| R-006 | **Performance degradation** as data volume grows | Medium | Medium | Medium | Database indexing strategy; Redis caching; Meilisearch for read-heavy search; query monitoring.             |
| R-007 | **Email deliverability issues** — campaign emails landing in spam | Medium | High | High | SPF/DKIM/DMARC configuration; dedicated sending domain; gradual warm-up; bounce handling.                   |
| R-008 | **GDPR non-compliance** — failure to meet data privacy regulations | Low | Critical | High | Data export/deletion features; consent management; data encryption; privacy-by-design architecture.         |
| R-009 | **Technology obsolescence** — framework major version changes | Low | Medium | Low | Pin major versions in dependencies; follow upgrade guides; modular architecture for incremental upgrades.    |
| R-010 | **Complex state management** across campaigns, invoices, and projects | Medium | Medium | Medium | Clear state machine definitions with explicit transitions; comprehensive integration tests for workflows.    |

---

## 15. Appendices

### 15.1 Glossary

| Term            | Definition                                                                                   |
|-----------------|----------------------------------------------------------------------------------------------|
| **Avoir**       | Credit note (French accounting term) — a document that reduces the amount owed on an invoice |
| **Devis**       | Quote/estimate (French accounting term) — a formal proposal with pricing before work begins  |
| **Facture**     | Invoice (French accounting term) — a billing document requesting payment for goods/services   |
| **SIRET**       | A 14-digit French business identification number                                             |
| **SIREN**       | A 9-digit French business identification number (first 9 digits of SIRET)                    |
| **APE Code**    | French economic activity code assigned to businesses by INSEE                                 |
| **TVA**         | Taxe sur la Valeur Ajoutee — French Value Added Tax (VAT)                                    |
| **JWT**         | JSON Web Token — a compact token format for secure API authentication                        |
| **TOTP**        | Time-based One-Time Password — used for two-factor authentication                            |
| **TDD**         | Test-Driven Development — writing tests before implementation code                           |
| **CI/CD**       | Continuous Integration / Continuous Deployment — automated build, test, and deploy pipeline   |
| **SPA**         | Single Page Application — a web application that loads a single HTML page and dynamically updates content |
| **SSR**         | Server-Side Rendering — rendering pages on the server before sending to the client           |
| **E.164**       | International telephone number format standard (e.g., +33612345678)                          |
| **WCAG**        | Web Content Accessibility Guidelines — international standard for web accessibility           |

### 15.2 Reference Documents

| Document                       | Purpose                                               |
|--------------------------------|-------------------------------------------------------|
| Laravel Documentation          | Back-end framework reference                          |
| Next.js Documentation          | Front-end framework reference                         |
| shadcn/ui Documentation        | UI component library reference                        |
| Tailwind CSS Documentation     | Styling framework reference                           |
| Meilisearch Documentation      | Search engine configuration and API reference         |
| PostgreSQL Documentation       | Database administration and query reference            |
| GDPR Official Text             | Data protection regulation compliance reference        |
| French Commercial Code (Code de commerce) | Legal requirements for invoices and business documents |
| OWASP Top 10                   | Security vulnerability reference                       |
| Conventional Commits           | Commit message format specification                    |

### 15.3 Environment Variables Reference

```env
# Application
APP_NAME=Koomky
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.koomky.com
APP_KEY=base64:...

# Database
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=koomky
DB_USERNAME=koomky
DB_PASSWORD=<secret>

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=<secret>

# Meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=<secret>
SCOUT_DRIVER=meilisearch

# Mail
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS=hello@koomky.com
MAIL_FROM_NAME="${APP_NAME}"

# SMS
SMS_PROVIDER=twilio
TWILIO_SID=<secret>
TWILIO_AUTH_TOKEN=<secret>
TWILIO_FROM=+33...

# Queue
QUEUE_CONNECTION=redis

# Session
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# JWT
JWT_SECRET=<secret>
JWT_TTL=15
JWT_REFRESH_TTL=10080

# Storage
FILESYSTEM_DISK=local
AWS_ACCESS_KEY_ID=<optional>
AWS_SECRET_ACCESS_KEY=<optional>
AWS_DEFAULT_REGION=eu-west-3
AWS_BUCKET=koomky-storage
```

### 15.4 Docker Compose Services Summary

| Service          | Image/Build                  | Ports (Host:Container) | Purpose                      |
|------------------|------------------------------|------------------------|------------------------------|
| `nginx`          | nginx:alpine                 | 80:80, 443:443         | Reverse proxy, TLS           |
| `api`            | Custom (PHP 8.3-FPM)        | (internal) 9000        | Laravel API                  |
| `frontend`       | Custom (Node 20)             | (internal) 3000        | Next.js SSR                  |
| `postgres`       | postgres:16-alpine           | (internal) 5432        | Primary database             |
| `redis`          | redis:7-alpine               | (internal) 6379        | Cache, sessions, queues      |
| `meilisearch`    | getmeili/meilisearch:latest  | (internal) 7700        | Search engine                |
| `queue-worker`   | Same as `api`                | —                      | Laravel queue worker         |
| `scheduler`      | Same as `api`                | —                      | Laravel task scheduler       |
| `mailpit`        | axllent/mailpit (dev only)   | 1025, 8025             | Email testing                |

---

*End of Product Requirements Document — Koomky v1.0.0*
