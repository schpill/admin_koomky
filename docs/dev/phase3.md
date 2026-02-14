# Phase 3 — Task Tracking

> **Status**: Not Started
> **Prerequisite**: Phase 2 fully merged
> **Spec**: [docs/phases/phase3.md](../phases/phase3.md)

---

## Sprint 9 — Contact Segmentation Engine (Weeks 15-16)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P3-BE-001 | Create Segment model (UUID, filters as JSON) | todo | |
| P3-BE-002 | Create SegmentFactory | todo | |
| P3-BE-003 | Create SegmentPolicy | todo | |
| P3-BE-004 | Create SegmentController (CRUD + preview) | todo | |
| P3-BE-005 | Create StoreSegmentRequest | todo | |
| P3-BE-006 | Create SegmentFilterEngine service | todo | |
| P3-BE-007 | Implement filter criteria (tag, last_interaction, project_status, revenue, location, created_at, custom) | todo | |
| P3-BE-008 | Implement AND/OR logic combinator | todo | |
| P3-BE-009 | Implement preview endpoint (paginated matching contacts) | todo | |
| P3-BE-010 | Implement dynamic segment resolution | todo | |
| P3-BE-011 | Implement segment contact count caching (Redis) | todo | |
| P3-BE-012 | Add unsubscribe/opt-out scopes on Contact | todo | |
| P3-BE-013 | Create UnsubscribeController (public signed URL) | todo | |
| P3-BE-014 | Implement consent management fields on Contact | todo | |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P3-FE-001 | Create stores/segments.ts Zustand store | todo | |
| P3-FE-002 | Create app/campaigns/segments/page.tsx | todo | |
| P3-FE-003 | Create app/campaigns/segments/create/page.tsx | todo | |
| P3-FE-004 | Create components/segments/segment-builder.tsx (visual filter builder) | todo | |
| P3-FE-005 | Create components/segments/segment-preview-panel.tsx | todo | |
| P3-FE-006 | Create app/campaigns/segments/[id]/edit/page.tsx | todo | |

---

## Sprint 10 — Email Campaign Builder & Execution (Weeks 16-18)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P3-BE-020 | Create Campaign model (UUID, relationships, scopes, Searchable) | todo | |
| P3-BE-021 | Create CampaignRecipient model | todo | |
| P3-BE-022 | Create CampaignTemplate model | todo | |
| P3-BE-023 | Create factories (Campaign, CampaignRecipient, CampaignTemplate) | todo | |
| P3-BE-024 | Create CampaignPolicy | todo | |
| P3-BE-025 | Create CampaignController (CRUD, send, pause, duplicate, test) | todo | |
| P3-BE-026 | Create StoreCampaignRequest | todo | |
| P3-BE-027 | Create CampaignTemplateController | todo | |
| P3-BE-028 | Create PersonalizationService (variable replacement) | todo | |
| P3-BE-029 | Create SendEmailCampaignJob (orchestrator) | todo | |
| P3-BE-030 | Create SendCampaignEmailJob (single email: personalize, track, unsubscribe link) | todo | |
| P3-BE-031 | Create EmailTrackingController (open pixel + click redirect) | todo | |
| P3-BE-032 | Create CampaignWebhookController (bounce, complaint, delivery) | todo | |
| P3-BE-033 | Implement campaign scheduling (DispatchScheduledCampaignsCommand) | todo | |
| P3-BE-034 | Implement campaign pause | todo | |
| P3-BE-035 | Implement campaign duplication | todo | |
| P3-BE-036 | Implement test send | todo | |
| P3-BE-037 | Implement campaign attachment handling (max 5MB) | todo | |
| P3-BE-038 | Configure email settings (FR-SET-004) | todo | |
| P3-BE-039 | Create MailConfigService (dynamic mail driver from user settings) | todo | |
| P3-BE-040 | Configure Meilisearch index for Campaign | todo | |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P3-FE-010 | Create stores/campaigns.ts Zustand store | todo | |
| P3-FE-011 | Create app/campaigns/page.tsx (data table) | todo | |
| P3-FE-012 | Create app/campaigns/create/page.tsx (multi-step wizard) | todo | |
| P3-FE-013 | Create components/campaigns/email-editor.tsx (TipTap) | todo | |
| P3-FE-014 | Create components/campaigns/template-selector.tsx | todo | |
| P3-FE-015 | Create components/campaigns/campaign-preview.tsx | todo | |
| P3-FE-016 | Create components/campaigns/test-send-modal.tsx | todo | |
| P3-FE-017 | Create app/campaigns/[id]/page.tsx (detail + recipients) | todo | |
| P3-FE-018 | Create components/campaigns/recipient-status-table.tsx | todo | |
| P3-FE-019 | Create app/settings/email/page.tsx | todo | |

---

## Sprint 11 — SMS Campaigns (Weeks 18-19)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P3-BE-050 | Create SmsService interface | todo | |
| P3-BE-051 | Create TwilioSmsDriver | todo | |
| P3-BE-052 | Create VonageSmsDriver | todo | |
| P3-BE-053 | Create SmsProviderManager (factory) | todo | |
| P3-BE-054 | Create SendSmsCampaignJob (orchestrator) | todo | |
| P3-BE-055 | Create SendCampaignSmsJob (single SMS) | todo | |
| P3-BE-056 | Create SmsWebhookController (delivery, failure, opt-out) | todo | |
| P3-BE-057 | Implement phone number validation (E.164) | todo | |
| P3-BE-058 | Implement SMS character counter (segments) | todo | |
| P3-BE-059 | Implement test SMS send | todo | |
| P3-BE-060 | Configure SMS settings (FR-SET-005) | todo | |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P3-FE-030 | Create components/campaigns/sms-composer.tsx (char counter) | todo | |
| P3-FE-031 | Update campaign wizard for SMS type | todo | |
| P3-FE-032 | Create components/campaigns/sms-preview.tsx (phone mockup) | todo | |
| P3-FE-033 | Create app/settings/sms/page.tsx | todo | |
| P3-FE-034 | Update recipient-status-table for SMS statuses | todo | |

---

## Sprint 12 — Campaign Analytics & Dashboard Enhancement (Weeks 19-20)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P3-BE-070 | Create CampaignAnalyticsService (per-campaign metrics) | todo | |
| P3-BE-071 | Create CampaignAnalyticsController | todo | |
| P3-BE-072 | Implement time-series analytics (opens/clicks per hour) | todo | |
| P3-BE-073 | Implement campaign comparison endpoint | todo | |
| P3-BE-074 | Implement analytics CSV export | todo | |
| P3-BE-075 | Enhance DashboardController (campaign summary widget) | todo | |
| P3-BE-076 | Create notification preferences endpoint | todo | |
| P3-BE-077 | Create CampaignCompletedNotification | todo | |
| P3-BE-078 | Log campaign events to client timeline | todo | |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P3-FE-040 | Create app/campaigns/[id]/analytics/page.tsx | todo | |
| P3-FE-041 | Create components/campaigns/analytics-summary-cards.tsx | todo | |
| P3-FE-042 | Create components/campaigns/engagement-chart.tsx (Recharts) | todo | |
| P3-FE-043 | Create app/campaigns/compare/page.tsx | todo | |
| P3-FE-044 | Create components/dashboard/campaign-summary-widget.tsx | todo | |
| P3-FE-045 | Create app/settings/notifications/page.tsx | todo | |
| P3-FE-046 | Create in-app notification system (bell icon, dropdown, unread badge) | todo | |
| P3-FE-047 | Run full Playwright E2E suite for campaign flows | todo | |
| P3-FE-048 | Run Vitest coverage >= 80% | todo | |
