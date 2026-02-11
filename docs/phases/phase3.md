# Phase 3 — Marketing and Communication Campaigns

| Field               | Value                                          |
|---------------------|------------------------------------------------|
| **Phase**           | 3 of 4                                         |
| **Name**            | Marketing and Communication Campaigns          |
| **Duration**        | Weeks 15–20 (6 weeks)                          |
| **Milestone**       | M3 — Fully Operational Campaign System         |
| **PRD Sections**    | §4.6, §9.1, §9.2                               |
| **Prerequisite**    | Phase 2 fully completed and validated           |
| **Status**          | Not Started                                    |

---

## 1. Phase Objectives

| ID       | Objective                                                                                    |
|----------|----------------------------------------------------------------------------------------------|
| P3-OBJ-1 | Deliver contact segmentation engine with dynamic filtering and AND/OR logic                  |
| P3-OBJ-2 | Deliver email campaign builder with rich-text editor, templates, and personalization          |
| P3-OBJ-3 | Deliver email campaign execution with scheduling, throttling, and tracking (open/click/bounce)|
| P3-OBJ-4 | Deliver SMS campaign builder and execution with delivery tracking                            |
| P3-OBJ-5 | Deliver campaign analytics dashboard with per-campaign and comparative metrics                |
| P3-OBJ-6 | Integrate email sending service (Mailgun/SES) and SMS gateway (Twilio)                       |
| P3-OBJ-7 | Implement GDPR-compliant unsubscribe/opt-out mechanisms for both email and SMS               |
| P3-OBJ-8 | Enhance main dashboard with campaign performance summary                                     |
| P3-OBJ-9 | Maintain >= 80% test coverage on both back-end and front-end                                |

---

## 2. Entry Criteria

- Phase 2 exit criteria 100% satisfied.
- All Phase 2 CI checks green on `main`.
- Client management, project management, and financial modules operational.
- Email sending service account created (Mailgun or SES).
- SMS gateway account created (Twilio) with verified sender number.

---

## 3. Scope — Requirement Traceability

| PRD Requirement            | IDs                                    | Included |
|----------------------------|----------------------------------------|----------|
| Campaign Management        | FR-CAM-001 → FR-CAM-006               | Yes      |
| Contact Segmentation       | FR-CAM-007 → FR-CAM-011               | Yes      |
| Email Campaigns            | FR-CAM-012 → FR-CAM-018               | Yes      |
| SMS Campaigns              | FR-CAM-019 → FR-CAM-025               | Yes      |
| Campaign Analytics         | FR-CAM-026 → FR-CAM-029               | Yes      |
| Dashboard (campaigns)      | FR-DASH-007                            | Yes      |
| Settings (email/SMS)       | FR-SET-004, FR-SET-005                 | Yes      |
| Email Sending Integration  | §9.1                                   | Yes      |
| SMS Gateway Integration    | §9.2                                   | Yes      |
| Notification preferences   | FR-SET-006                             | Yes      |

---

## 4. Detailed Sprint Breakdown

### 4.1 Sprint 9 — Contact Segmentation Engine (Weeks 15–16)

#### 4.1.1 Database Migrations

| Migration                          | Description                                              |
|------------------------------------|----------------------------------------------------------|
| `create_segments_table`            | id (UUID), user_id (FK), name (VARCHAR 255), description (TEXT, nullable), filters (JSONB, NOT NULL), is_dynamic (BOOLEAN, default true), contact_count (INT, default 0), timestamps. Indexes: user_id. |
| `add_unsubscribed_at_to_contacts`  | Add `email_unsubscribed_at` (TIMESTAMP, nullable) and `sms_opted_out_at` (TIMESTAMP, nullable) to contacts table. |
| `add_consent_fields_to_contacts`   | Add `email_consent` (BOOLEAN, default false), `email_consent_date` (TIMESTAMP, nullable), `sms_consent` (BOOLEAN, default false), `sms_consent_date` (TIMESTAMP, nullable) to contacts table. |

#### 4.1.2 Back-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P3-BE-001 | Create `Segment` model — UUID, relationships (user, campaigns), casts (filters as JSON array) | FR-CAM-007 |
| P3-BE-002 | Create `SegmentFactory`                                                               | §10.3.1       |
| P3-BE-003 | Create `SegmentPolicy` — user owns segment                                            | NFR-SEC-004   |
| P3-BE-004 | Create `SegmentController` — CRUD + preview endpoint                                  | FR-CAM-007 → 011 |
| P3-BE-005 | Create `StoreSegmentRequest` — validate name, filters structure (array of filter groups) | FR-CAM-007 |
| P3-BE-006 | Create `SegmentFilterEngine` service — build Eloquent query from JSON filter definition | FR-CAM-007, 008 |
| P3-BE-007 | Implement filter criteria types:                                                       | FR-CAM-007   |
|           | — `tag` (client has tag X)                                                             |              |
|           | — `last_interaction` (before/after date, relative: "older than N months")              |              |
|           | — `project_status` (client has project with status X)                                  |              |
|           | — `revenue` (total invoiced > / < / = amount)                                          |              |
|           | — `location` (city, country)                                                           |              |
|           | — `created_at` (client created before/after date)                                      |              |
|           | — `custom_field` (contact email/phone exists or not)                                   |              |
| P3-BE-008 | Implement AND/OR logic combinator — filter groups are joined by AND, criteria within a group by OR (or vice versa, configurable) | FR-CAM-008 |
| P3-BE-009 | Implement `preview` endpoint: `GET /api/v1/segments/{id}/preview` — returns matching contacts with count, paginated | FR-CAM-009 |
| P3-BE-010 | Implement dynamic segment resolution: re-evaluate filters on every query (no static contact list) | FR-CAM-011 |
| P3-BE-011 | Implement segment contact count caching (Redis, TTL: 5 min) for display optimization   | NFR-PERF-008 |
| P3-BE-012 | Add unsubscribe/opt-out handling on Contact model: scopes `emailSubscribed`, `smsOptedIn` | FR-CAM-006, 016, 023 |
| P3-BE-013 | Create `UnsubscribeController` — public endpoint: `GET /unsubscribe/{token}` — verify signed URL, set email_unsubscribed_at | FR-CAM-016 |
| P3-BE-014 | Implement consent management fields on Contact and update contact CRUD to handle them  | NFR-SEC-008  |

#### 4.1.3 Back-end Tests (TDD)

| Test File                                              | Test Cases                                                  |
|--------------------------------------------------------|-------------------------------------------------------------|
| `tests/Unit/Models/SegmentTest.php`                    | Factory, relationships, filter casts                         |
| `tests/Unit/Services/SegmentFilterEngineTest.php`      | Filter by tag, by date, by revenue, by location, AND logic, OR logic, combined AND/OR, empty filter returns all, invalid filter throws exception |
| `tests/Feature/Segment/SegmentCrudTest.php`            | Create, read, update, delete, validation errors              |
| `tests/Feature/Segment/SegmentPreviewTest.php`         | Preview returns matching contacts, count correct, pagination works, excludes unsubscribed |
| `tests/Feature/Unsubscribe/UnsubscribeTest.php`        | Valid token unsubscribes, invalid token returns 403, already unsubscribed is idempotent |

#### 4.1.4 Front-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P3-FE-001 | Create `stores/segments.ts` Pinia store — CRUD, preview                               | §6.2.2        |
| P3-FE-002 | Create `pages/campaigns/segments/index.vue` — list segments with name, contact count, last updated | FR-CAM-010 |
| P3-FE-003 | Create `pages/campaigns/segments/create.vue` — segment builder UI                     | FR-CAM-007   |
| P3-FE-004 | Create `components/segments/SegmentBuilder.vue` — visual filter builder:               | FR-CAM-007 → 009 |
|           | — Filter group cards (AND between groups)                                              |              |
|           | — Criteria rows within each group (OR within group)                                    |              |
|           | — Criteria type dropdown, operator dropdown, value input (varies by type)              |              |
|           | — Add criteria / Add group buttons                                                     |              |
|           | — Real-time contact count preview badge                                                |              |
| P3-FE-005 | Create `components/segments/SegmentPreviewPanel.vue` — live table of matching contacts with basic info (name, email, phone, client company) | FR-CAM-009 |
| P3-FE-006 | Create `pages/campaigns/segments/[id]/edit.vue` — edit segment with preview            | FR-CAM-007   |

#### 4.1.5 Front-end Tests

| Test File                                              | Test Cases                                                |
|--------------------------------------------------------|-----------------------------------------------------------|
| `tests/unit/stores/segments.test.ts`                   | CRUD, preview fetch, filter serialization                  |
| `tests/components/segments/SegmentBuilder.test.ts`     | Add/remove criteria, add/remove groups, type selection, value inputs, count preview |
| `tests/e2e/segments/segment-crud.spec.ts`              | Create segment with filters, preview contacts, save, edit  |

#### 4.1.6 Deliverables Checklist

- [ ] Segment CRUD with JSON filter storage.
- [ ] Filter by: tags, last interaction, project status, revenue, location, creation date.
- [ ] AND/OR logic combinator functional.
- [ ] Live preview shows matching contacts with count.
- [ ] Segments are dynamic (re-evaluated on query).
- [ ] Unsubscribe endpoint operational.
- [ ] Consent fields on contacts.

---

### 4.2 Sprint 10 — Email Campaign Builder & Execution (Weeks 16–18)

#### 4.2.1 Database Migrations

| Migration                          | Description                                              |
|------------------------------------|----------------------------------------------------------|
| `create_campaigns_table`           | id (UUID), user_id (FK), segment_id (FK, nullable), name (VARCHAR 255), type ENUM('email', 'sms'), status ENUM('draft', 'scheduled', 'sending', 'sent', 'paused', 'cancelled'), subject (VARCHAR 255, nullable — email only), content (TEXT), scheduled_at (TIMESTAMP, nullable), started_at (TIMESTAMP, nullable), completed_at (TIMESTAMP, nullable), settings (JSONB — throttle rate, sender name, reply-to), timestamps. Indexes: user_id, status, type, scheduled_at. |
| `create_campaign_recipients_table` | id (UUID), campaign_id (FK), contact_id (FK), email (VARCHAR 255, nullable), phone (VARCHAR 20, nullable), status ENUM('pending', 'sent', 'delivered', 'opened', 'clicked', 'bounced', 'failed', 'unsubscribed'), sent_at (TIMESTAMP, nullable), delivered_at (TIMESTAMP, nullable), opened_at (TIMESTAMP, nullable), clicked_at (TIMESTAMP, nullable), bounced_at (TIMESTAMP, nullable), failed_at (TIMESTAMP, nullable), failure_reason (TEXT, nullable), timestamps. Indexes: campaign_id, contact_id, status. |
| `create_campaign_templates_table`  | id (UUID), user_id (FK), name (VARCHAR 255), subject (VARCHAR 255), content (TEXT), type ENUM('email', 'sms'), timestamps. Index: user_id. |
| `create_campaign_attachments_table`| id (UUID), campaign_id (FK), filename (VARCHAR 255), path (VARCHAR 500), mime_type (VARCHAR 100), size_bytes (INT), timestamps. Index: campaign_id. |

#### 4.2.2 Back-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P3-BE-020 | Create `Campaign` model — UUID, relationships (user, segment, recipients, attachments, template), scopes (byType, byStatus), Searchable | FR-CAM-001 |
| P3-BE-021 | Create `CampaignRecipient` model — relationships, status tracking                     | FR-CAM-015   |
| P3-BE-022 | Create `CampaignTemplate` model — reusable templates for email/SMS                    | FR-CAM-012   |
| P3-BE-023 | Create factories: `CampaignFactory`, `CampaignRecipientFactory`, `CampaignTemplateFactory` | §10.3.1 |
| P3-BE-024 | Create `CampaignPolicy` — user owns campaign                                          | NFR-SEC-004   |
| P3-BE-025 | Create `CampaignController` — CRUD, send, pause, duplicate, test send                 | FR-CAM-001 → 006 |
| P3-BE-026 | Create `StoreCampaignRequest` — validate name, type, segment_id (if provided), content required, subject required for email, scheduled_at in future | FR-CAM-001 |
| P3-BE-027 | Create `CampaignTemplateController` — CRUD for templates                               | FR-CAM-012   |
| P3-BE-028 | Create `PersonalizationService` — replace variables in content: `{{first_name}}`, `{{last_name}}`, `{{company}}`, `{{email}}`. Support custom variables from contact/client fields. | FR-CAM-013, 020 |
| P3-BE-029 | Create `SendEmailCampaignJob` (queued) — orchestrator job:                             | FR-CAM-012 → 018 |
|           | 1. Resolve segment → get contacts (exclude unsubscribed)                               | FR-CAM-006   |
|           | 2. Create CampaignRecipient records for each contact                                   |              |
|           | 3. Dispatch individual `SendCampaignEmailJob` per recipient                            |              |
|           | 4. Respect throttle rate (configurable, default 100/min)                               | FR-CAM-017   |
|           | 5. Update campaign status: sending → sent when all dispatched                          |              |
| P3-BE-030 | Create `SendCampaignEmailJob` (queued) — single email send:                            | FR-CAM-012   |
|           | 1. Personalize content for recipient                                                   |              |
|           | 2. Add tracking pixel for open tracking                                                |              |
|           | 3. Rewrite links for click tracking                                                    |              |
|           | 4. Append unsubscribe link (signed URL)                                                | FR-CAM-016   |
|           | 5. Send via configured mail driver                                                     |              |
|           | 6. Update recipient status to sent                                                     |              |
| P3-BE-031 | Create `EmailTrackingController` — public endpoints:                                   | FR-CAM-015   |
|           | — `GET /t/open/{token}` — 1x1 transparent pixel, record opened_at                     |              |
|           | — `GET /t/click/{token}` — record clicked_at, redirect to original URL                 |              |
| P3-BE-032 | Create `CampaignWebhookController` — handle provider webhooks:                         | §9.1         |
|           | — Bounce: update recipient status to bounced                                           |              |
|           | — Complaint: update recipient, auto-unsubscribe                                        |              |
|           | — Delivery: update recipient to delivered                                              |              |
| P3-BE-033 | Implement campaign scheduling: `DispatchScheduledCampaignsCommand` — runs every minute via scheduler, dispatches campaigns where scheduled_at <= now and status == scheduled | FR-CAM-003 |
| P3-BE-034 | Implement campaign pause: set status to paused, stop dispatching pending recipient jobs | FR-CAM-004 |
| P3-BE-035 | Implement campaign duplication: clone content, subject, segment, reset status to draft  | FR-CAM-005   |
| P3-BE-036 | Implement test send: send to a single specified email address (not tracked)            | FR-CAM-014   |
| P3-BE-037 | Implement campaign attachment handling (max 5MB total)                                 | FR-CAM-018   |
| P3-BE-038 | Configure email settings: `FR-SET-004` (sender name, sender email, SMTP/API credentials, reply-to) | FR-SET-004 |
| P3-BE-039 | Create `MailConfigService` — dynamically configure Laravel mail driver from user settings (support SMTP and Mailgun/SES API) | §9.1 |
| P3-BE-040 | Configure Meilisearch index for Campaign                                               | FR-SRC-002   |

#### 4.2.3 Back-end Tests (TDD)

| Test File                                              | Test Cases                                                  |
|--------------------------------------------------------|-------------------------------------------------------------|
| `tests/Unit/Models/CampaignTest.php`                   | Factory, relationships, scopes, status transitions           |
| `tests/Unit/Services/PersonalizationTest.php`          | Variable replacement, missing variable handled gracefully, HTML escaping |
| `tests/Unit/Services/MailConfigTest.php`               | SMTP configuration, API configuration, fallback handling     |
| `tests/Feature/Campaign/CampaignCrudTest.php`          | Create email campaign, CRUD, validation, filter by type/status |
| `tests/Feature/Campaign/CampaignSendTest.php`          | Send resolves segment, creates recipients, dispatches jobs, excludes unsubscribed, respects throttle |
| `tests/Feature/Campaign/CampaignScheduleTest.php`      | Schedule campaign, scheduler dispatches at correct time, pause stops dispatch |
| P3-BE-TEST-07 `tests/Feature/Campaign/CampaignDuplicateTest.php` | Cloned with draft status, content matches, new name |
| `tests/Feature/Campaign/CampaignTestSendTest.php`      | Test email sent to specified address, not tracked            |
| `tests/Feature/Campaign/EmailTrackingTest.php`         | Open pixel records opened_at, click redirect records clicked_at and redirects, duplicate tracking idempotent |
| `tests/Feature/Campaign/WebhookHandlerTest.php`        | Bounce updates status, complaint auto-unsubscribes, delivery updates status, invalid webhook returns 400 |
| `tests/Feature/Campaign/CampaignTemplateTest.php`      | CRUD templates, apply template to campaign                   |

#### 4.2.4 Front-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P3-FE-010 | Create `stores/campaigns.ts` Pinia store — CRUD, send, pause, duplicate, test send    | §6.2.2        |
| P3-FE-011 | Create `pages/campaigns/index.vue` — data table: name, type badge, status badge, segment, sent date, metrics summary (sent/opened/clicked), actions | FR-CAM-001 |
| P3-FE-012 | Create `pages/campaigns/create.vue` — multi-step wizard:                               | FR-CAM-001 → 018 |
|           | Step 1: Name, type (email), segment selector (or create new)                           |              |
|           | Step 2: Subject line, email content editor (TipTap rich text), template selector       |              |
|           | Step 3: Personalization variable picker (insert into editor), attachments               |              |
|           | Step 4: Preview (rendered email with sample data), test send                            |              |
|           | Step 5: Schedule (date/time picker) or send now, confirm                                |              |
| P3-FE-013 | Create `components/campaigns/EmailEditor.vue` — TipTap rich text editor with:          | FR-CAM-012   |
|           | — Toolbar: bold, italic, underline, link, image, heading, list, quote, code block      |              |
|           | — Variable insertion button (dropdown with available variables)                         |              |
|           | — HTML source toggle                                                                   |              |
|           | — Character/word count                                                                 |              |
| P3-FE-014 | Create `components/campaigns/TemplateSelector.vue` — choose from saved templates or start blank | FR-CAM-012 |
| P3-FE-015 | Create `components/campaigns/CampaignPreview.vue` — rendered preview with sample recipient data, swap between recipients | FR-CAM-014 |
| P3-FE-016 | Create `components/campaigns/TestSendModal.vue` — email input, send test button, success confirmation | FR-CAM-014 |
| P3-FE-017 | Create `pages/campaigns/[id].vue` — campaign detail: status, content preview, recipient list with statuses, actions (pause, duplicate) | FR-CAM-001 |
| P3-FE-018 | Create `components/campaigns/RecipientStatusTable.vue` — table: contact name, email, status badge, timestamps (sent, opened, clicked, bounced) | FR-CAM-015 |
| P3-FE-019 | Create `pages/settings/email.vue` — sender name, sender email, provider selector (SMTP/Mailgun/SES), credentials, test connection button | FR-SET-004 |

#### 4.2.5 Front-end Tests

| Test File                                              | Test Cases                                                |
|--------------------------------------------------------|-----------------------------------------------------------|
| `tests/unit/stores/campaigns.test.ts`                  | CRUD, send, pause, duplicate, test send                    |
| `tests/components/campaigns/EmailEditor.test.ts`       | Renders editor, toolbar actions, variable insertion, HTML toggle |
| `tests/components/campaigns/SegmentBuilder.test.ts`    | (Already covered in Sprint 9)                              |
| `tests/components/campaigns/CampaignPreview.test.ts`   | Renders personalized content, swap recipients              |
| `tests/e2e/campaigns/email-campaign.spec.ts`           | Create campaign, select segment, compose email, test send, schedule, verify recipient statuses |

---

### 4.3 Sprint 11 — SMS Campaigns (Weeks 18–19)

#### 4.3.1 Back-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P3-BE-050 | Create `SmsService` — interface for SMS providers: `send(to, message)`, `getDeliveryStatus(messageId)` | §9.2 |
| P3-BE-051 | Create `TwilioSmsDriver` — Twilio REST API integration using Laravel HTTP client       | §9.2         |
| P3-BE-052 | Create `VonageSmsDriver` — alternative driver (same interface)                         | §9.2         |
| P3-BE-053 | Create `SmsProviderManager` — factory pattern to resolve configured driver from settings | §9.2 |
| P3-BE-054 | Create `SendSmsCampaignJob` (queued) — orchestrator:                                   | FR-CAM-019 → 025 |
|           | 1. Resolve segment → get contacts with valid phone (E.164)                             | FR-CAM-025   |
|           | 2. Exclude opted-out contacts                                                          | FR-CAM-023   |
|           | 3. Create CampaignRecipient records                                                    |              |
|           | 4. Dispatch individual `SendCampaignSmsJob` per recipient                              |              |
|           | 5. Respect throttle rate (configurable, default 30/min)                                | FR-CAM-024   |
| P3-BE-055 | Create `SendCampaignSmsJob` (queued) — single SMS:                                     | FR-CAM-019   |
|           | 1. Personalize message for recipient                                                   | FR-CAM-020   |
|           | 2. Append opt-out instruction ("Reply STOP to unsubscribe")                            | FR-CAM-023   |
|           | 3. Send via SmsService                                                                 |              |
|           | 4. Update recipient status                                                             |              |
| P3-BE-056 | Create `SmsWebhookController` — handle Twilio/Vonage delivery status callbacks:        | §9.2         |
|           | — Delivered: update recipient status                                                   |              |
|           | — Failed: update with failure reason                                                   |              |
|           | — Opt-out (STOP keyword): auto set sms_opted_out_at on contact                         | FR-CAM-023   |
| P3-BE-057 | Implement phone number validation: E.164 format via `libphonenumber` PHP library       | FR-CAM-025   |
| P3-BE-058 | Implement SMS character counter helper: count segments (160 chars/segment, 70 for Unicode), return segment count and remaining chars | FR-CAM-019 |
| P3-BE-059 | Create test SMS send: send to specified phone number                                   | FR-CAM-021   |
| P3-BE-060 | Configure SMS settings: `FR-SET-005` (sender name, provider, API credentials)          | FR-SET-005   |

#### 4.3.2 Back-end Tests (TDD)

| Test File                                              | Test Cases                                                  |
|--------------------------------------------------------|-------------------------------------------------------------|
| `tests/Unit/Services/SmsServiceTest.php`               | Twilio driver sends SMS, Vonage driver sends SMS, invalid phone rejected |
| `tests/Unit/Services/SmsCharCounterTest.php`           | ASCII 160 chars = 1 segment, 161 = 2, Unicode 70 = 1, mixed |
| `tests/Unit/Services/PhoneValidationTest.php`          | Valid E.164 accepted, local format rejected, invalid rejected |
| `tests/Feature/Campaign/SmsCampaignSendTest.php`       | Send resolves segment, validates phones, excludes opted-out, dispatches jobs, respects throttle |
| `tests/Feature/Campaign/SmsWebhookTest.php`            | Delivery updates status, failure records reason, STOP keyword opts out contact |
| `tests/Feature/Campaign/SmsTestSendTest.php`           | Test SMS sent to specified number                            |

#### 4.3.3 Front-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P3-FE-030 | Create `components/campaigns/SmsComposer.vue` — textarea with character counter, segment counter, variable insertion button | FR-CAM-019, 020 |
| P3-FE-031 | Update campaign creation wizard (Step 1): if type == SMS, skip subject field, swap email editor for SMS composer | FR-CAM-019 |
| P3-FE-032 | Create `components/campaigns/SmsPreview.vue` — phone mockup showing rendered SMS with sample data | FR-CAM-021 |
| P3-FE-033 | Create `pages/settings/sms.vue` — provider selector (Twilio/Vonage/OVH), sender name, API credentials, test SMS button | FR-SET-005 |
| P3-FE-034 | Update `RecipientStatusTable` to show phone numbers and SMS-specific statuses (delivered, failed) | FR-CAM-022 |

#### 4.3.4 Front-end Tests

| Test File                                              | Test Cases                                                |
|--------------------------------------------------------|-----------------------------------------------------------|
| `tests/components/campaigns/SmsComposer.test.ts`      | Character count, segment count, variable insertion, max length warning |
| `tests/components/campaigns/SmsPreview.test.ts`       | Renders personalized message in phone mockup               |
| `tests/e2e/campaigns/sms-campaign.spec.ts`             | Create SMS campaign, compose message, test send, schedule  |

---

### 4.4 Sprint 12 — Campaign Analytics & Dashboard Enhancement (Weeks 19–20)

#### 4.4.1 Back-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P3-BE-070 | Create `CampaignAnalyticsService` — compute per-campaign metrics:                      | FR-CAM-026   |
|           | — Total recipients, sent count, delivered count                                        |              |
|           | — Open rate (opened / delivered × 100)                                                 |              |
|           | — Click rate (clicked / delivered × 100)                                               |              |
|           | — Bounce rate (bounced / sent × 100)                                                  |              |
|           | — Unsubscribe count                                                                   |              |
|           | — Failure count and reasons                                                            |              |
| P3-BE-071 | Create `CampaignAnalyticsController` — `GET /api/v1/campaigns/{id}/analytics`          | FR-CAM-026   |
| P3-BE-072 | Implement time-series analytics: opens/clicks per hour after campaign send (for charting) | FR-CAM-027 |
| P3-BE-073 | Implement campaign comparison: `GET /api/v1/campaigns/compare?ids=1,2,3` — return side-by-side metrics | FR-CAM-028 |
| P3-BE-074 | Implement analytics CSV export: `GET /api/v1/campaigns/{id}/analytics/export`          | FR-CAM-029   |
| P3-BE-075 | Enhance `DashboardController` — add campaign summary widget: active campaigns count, average open rate, average click rate for last 30 days | FR-DASH-007 |
| P3-BE-076 | Create notification preferences endpoint: `PUT /api/v1/settings/notifications` — configure which events trigger email/in-app notifications (invoice paid, campaign completed, task overdue) | FR-SET-006 |
| P3-BE-077 | Create `CampaignCompletedNotification` — notify user when all recipients processed     | FR-SET-006   |
| P3-BE-078 | Log campaign events to client timeline (campaign sent to contact)                      | FR-CLI-014   |

#### 4.4.2 Back-end Tests (TDD)

| Test File                                              | Test Cases                                                  |
|--------------------------------------------------------|-------------------------------------------------------------|
| `tests/Unit/Services/CampaignAnalyticsTest.php`        | Open rate, click rate, bounce rate calculations, zero division handling |
| `tests/Feature/Campaign/CampaignAnalyticsTest.php`     | Endpoint returns correct metrics, time-series data, comparison endpoint |
| `tests/Feature/Campaign/CampaignAnalyticsExportTest.php` | CSV download contains correct data, headers               |
| `tests/Feature/Dashboard/CampaignDashboardTest.php`    | Active campaigns count, average rates correct               |
| `tests/Feature/Settings/NotificationPreferencesTest.php` | Update preferences, validate options                      |

#### 4.4.3 Front-end Tasks

| ID        | Task                                                                                  | PRD Ref       |
|-----------|---------------------------------------------------------------------------------------|---------------|
| P3-FE-040 | Create `pages/campaigns/[id]/analytics.vue` — campaign analytics page:                 | FR-CAM-026   |
|           | — Summary cards: sent, delivered, opened, clicked, bounced, unsubscribed              |              |
|           | — Open rate and click rate as prominent percentages                                    |              |
|           | — Time-series chart (opens/clicks per hour) via Chart.js                              | FR-CAM-027   |
|           | — Recipient breakdown table (status distribution)                                      |              |
|           | — Export CSV button                                                                    | FR-CAM-029   |
| P3-FE-041 | Create `components/campaigns/AnalyticsSummaryCards.vue` — 6 metric cards with icons and color coding | FR-CAM-026 |
| P3-FE-042 | Create `components/campaigns/EngagementChart.vue` — line chart (opens) + bar chart (clicks) over time | FR-CAM-027 |
| P3-FE-043 | Create `pages/campaigns/compare.vue` — select 2-4 campaigns, show side-by-side metrics table + overlaid chart | FR-CAM-028 |
| P3-FE-044 | Enhance dashboard: add `CampaignSummaryWidget.vue` — active campaigns, avg open rate, avg click rate | FR-DASH-007 |
| P3-FE-045 | Create `pages/settings/notifications.vue` — checkboxes for notification types per channel (email, in-app) | FR-SET-006 |
| P3-FE-046 | Create in-app notification system: bell icon in top bar, dropdown with recent notifications, unread count badge, mark as read | FR-SET-006 |
| P3-FE-047 | Run full Playwright E2E suite for all campaign flows                                  | §10.3.2       |
| P3-FE-048 | Run Vitest coverage, ensure >= 80%                                                     | §10.2         |

#### 4.4.4 Front-end Tests

| Test File                                              | Test Cases                                                |
|--------------------------------------------------------|-----------------------------------------------------------|
| `tests/components/campaigns/AnalyticsSummaryCards.test.ts` | Renders all 6 metrics, handles zero values             |
| `tests/components/campaigns/EngagementChart.test.ts`   | Renders chart with data, empty state, date range           |
| `tests/e2e/campaigns/campaign-analytics.spec.ts`       | View analytics page, verify metrics, export CSV            |
| `tests/e2e/campaigns/campaign-compare.spec.ts`         | Select campaigns, view comparison                          |
| `tests/e2e/dashboard/campaign-widget.spec.ts`          | Dashboard shows campaign summary                           |

---

## 5. API Endpoints Delivered in Phase 3

| Method | Endpoint                                       | Controller                   |
|--------|-------------------------------------------------|------------------------------|
| GET    | `/api/v1/segments`                              | SegmentController            |
| POST   | `/api/v1/segments`                              | SegmentController            |
| GET    | `/api/v1/segments/{id}`                         | SegmentController            |
| PUT    | `/api/v1/segments/{id}`                         | SegmentController            |
| DELETE | `/api/v1/segments/{id}`                         | SegmentController            |
| GET    | `/api/v1/segments/{id}/preview`                 | SegmentController            |
| GET    | `/api/v1/campaigns`                             | CampaignController           |
| POST   | `/api/v1/campaigns`                             | CampaignController           |
| GET    | `/api/v1/campaigns/{id}`                        | CampaignController           |
| PUT    | `/api/v1/campaigns/{id}`                        | CampaignController           |
| DELETE | `/api/v1/campaigns/{id}`                        | CampaignController           |
| POST   | `/api/v1/campaigns/{id}/send`                   | CampaignController           |
| POST   | `/api/v1/campaigns/{id}/pause`                  | CampaignController           |
| POST   | `/api/v1/campaigns/{id}/test`                   | CampaignController           |
| POST   | `/api/v1/campaigns/{id}/duplicate`              | CampaignController           |
| GET    | `/api/v1/campaigns/{id}/analytics`              | CampaignAnalyticsController  |
| GET    | `/api/v1/campaigns/{id}/analytics/export`       | CampaignAnalyticsController  |
| GET    | `/api/v1/campaigns/compare`                     | CampaignAnalyticsController  |
| GET    | `/api/v1/campaign-templates`                    | CampaignTemplateController   |
| POST   | `/api/v1/campaign-templates`                    | CampaignTemplateController   |
| PUT    | `/api/v1/campaign-templates/{id}`               | CampaignTemplateController   |
| DELETE | `/api/v1/campaign-templates/{id}`               | CampaignTemplateController   |
| GET    | `/t/open/{token}`                               | EmailTrackingController      |
| GET    | `/t/click/{token}`                              | EmailTrackingController      |
| GET    | `/unsubscribe/{token}`                          | UnsubscribeController        |
| POST   | `/webhooks/email`                               | CampaignWebhookController    |
| POST   | `/webhooks/sms`                                 | SmsWebhookController         |
| PUT    | `/api/v1/settings/email`                        | UserSettingsController       |
| PUT    | `/api/v1/settings/sms`                          | UserSettingsController       |
| PUT    | `/api/v1/settings/notifications`                | UserSettingsController       |

---

## 6. Exit Criteria

| #  | Criterion                                                                           | Validated |
|----|-------------------------------------------------------------------------------------|-----------|
| 1  | Segment CRUD with filter builder (tags, dates, revenue, location)                   | [ ]       |
| 2  | AND/OR logic combinators in segment filters                                         | [ ]       |
| 3  | Dynamic segment preview shows matching contacts in real time                        | [ ]       |
| 4  | Email campaign creation with rich-text editor, templates, variables                 | [ ]       |
| 5  | Email test send delivers to specified address                                       | [ ]       |
| 6  | Email campaign send: resolves segment, excludes unsubscribed, throttles, tracks     | [ ]       |
| 7  | Open tracking (pixel) and click tracking (redirect) functional                      | [ ]       |
| 8  | Bounce and complaint webhooks update recipient statuses                             | [ ]       |
| 9  | Unsubscribe link in every email, functional unsubscribe endpoint                    | [ ]       |
| 10 | Campaign scheduling dispatches at configured time                                   | [ ]       |
| 11 | Campaign pause stops sending                                                        | [ ]       |
| 12 | SMS campaign creation with character counter and segment counter                    | [ ]       |
| 13 | SMS send via Twilio (or alternative), delivery status tracking                      | [ ]       |
| 14 | SMS opt-out handling (STOP keyword triggers opt-out)                                | [ ]       |
| 15 | Campaign analytics: sent, delivered, opened, clicked, bounced, unsubscribed         | [ ]       |
| 16 | Time-series engagement chart (per hour)                                             | [ ]       |
| 17 | Campaign comparison (side-by-side metrics)                                          | [ ]       |
| 18 | Analytics CSV export                                                                | [ ]       |
| 19 | Dashboard enhanced with campaign performance summary                                | [ ]       |
| 20 | In-app notification system operational                                              | [ ]       |
| 21 | Email and SMS settings pages functional                                             | [ ]       |
| 22 | Back-end test coverage >= 80%                                                       | [ ]       |
| 23 | Front-end test coverage >= 80%                                                      | [ ]       |
| 24 | CI pipeline fully green on `main`                                                   | [ ]       |

---

## 7. Risks Specific to Phase 3

| Risk                                                     | Mitigation                                                    |
|----------------------------------------------------------|---------------------------------------------------------------|
| Email deliverability — campaigns landing in spam         | Configure SPF/DKIM/DMARC; use dedicated sending domain; gradual warm-up; monitor bounce rates |
| Twilio API rate limits exceeded                          | Implement queue-based throttling; configurable rate per settings; monitor API response codes |
| Large segment resolution causing slow queries            | Cache segment counts (Redis); index filter columns; paginate resolution; test with 10K+ contacts |
| Tracking pixel blocked by email clients                  | Open tracking is inherently imprecise; document limitation; do not rely solely on open rate |
| GDPR compliance for unsolicited campaigns               | Enforce consent fields; auto-exclude contacts without consent; log consent dates |
| Webhook endpoint security (spoofed callbacks)            | Verify webhook signatures (Mailgun/Twilio provide signature headers); whitelist source IPs |
| TipTap editor rendering differently in sent email        | Use inline CSS conversion (e.g., `juice` library) before sending; test across email clients |
| SMS encoding issues with special characters              | Detect Unicode content, warn about segment count impact, validate character encoding before send |

---

## 8. Integration Configuration Checklist

| Integration          | Configuration Required                                             | Status |
|----------------------|--------------------------------------------------------------------|--------|
| Mailgun / SES        | Account created, domain verified, API key obtained                 | [ ]    |
| SPF record           | DNS TXT record for sending domain                                  | [ ]    |
| DKIM record          | DNS CNAME records for sending domain                               | [ ]    |
| DMARC record         | DNS TXT record: `v=DMARC1; p=quarantine`                          | [ ]    |
| Twilio               | Account created, phone number purchased, API SID + token obtained  | [ ]    |
| Webhook endpoints    | Public URLs configured in Mailgun/Twilio dashboards                | [ ]    |
| Tracking domain      | Subdomain (e.g., `t.koomky.com`) pointing to app for pixel/click tracking | [ ] |

---

*End of Phase 3 — Marketing and Communication Campaigns*
