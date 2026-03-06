# Phase 17 Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Deliver phase 17 lead scoring, send time optimization, dynamic content, related UI, and GDPR/export support across backend and frontend.

**Architecture:** The implementation extends the existing Laravel campaign pipeline and Next.js dashboard rather than adding parallel subsystems. Score changes are centralized in services and persisted both as an aggregate on contacts and as event history, while STO and dynamic content are layered onto the current campaign send/personalization flow.

**Tech Stack:** Laravel 12, PHPUnit/Pest, PHPStan/Pint, Next.js, React, Zustand, Vitest, Playwright.

---

### Task 1: Backend scoring schema

**Files:**
- Create: `backend/database/migrations/*_add_email_score_to_contacts_table.php`
- Create: `backend/database/migrations/*_create_scoring_rules_table.php`
- Create: `backend/database/migrations/*_create_contact_score_events_table.php`
- Test: `backend/tests/Unit/Services/ContactScoreServiceTest.php`

**Steps:**
1. Write the failing scoring service test expecting `email_score` persistence and score-event storage.
2. Run the test to verify it fails for missing schema/service behavior.
3. Add the migrations with indexes and constraints from the phase spec.
4. Re-run the focused test to keep the failure targeted to missing service/model logic.

### Task 2: Backend scoring domain

**Files:**
- Create: `backend/app/Models/ScoringRule.php`
- Create: `backend/app/Models/ContactScoreEvent.php`
- Create: `backend/app/Services/ContactScoreService.php`
- Modify: `backend/app/Models/Contact.php`
- Test: `backend/tests/Unit/Services/ContactScoreServiceTest.php`

**Steps:**
1. Expand the scoring service test for default rules, expired events, and history retrieval.
2. Run the focused test and confirm failure.
3. Implement the models and minimal service logic to satisfy the test.
4. Re-run the focused test and keep it green.

### Task 3: Scoring API and integrations

**Files:**
- Create: `backend/app/Http/Controllers/Api/V1/ScoringRuleController.php`
- Modify: `backend/routes/api.php`
- Modify: `backend/app/Http/Controllers/Api/V1/EmailTrackingController.php`
- Modify: `backend/app/Observers/CampaignRecipientObserver.php`
- Modify: `backend/app/Jobs/SendEmailCampaignJob.php`
- Modify: `backend/app/Services/SegmentFilterEngine.php`
- Test: `backend/tests/Feature/Scoring/ScoringRuleCrudTest.php`
- Test: `backend/tests/Feature/Scoring/ContactScoreIntegrationTest.php`

**Steps:**
1. Write failing CRUD and integration tests for rule ownership, tracking events, bounce scoring, send scoring, and segment filtering by `email_score`.
2. Run the focused tests and verify the expected failures.
3. Implement the API/controller/routes and wire the scoring service into the tracking, send, bounce, and segment flows.
4. Re-run the focused tests until green.

### Task 4: Expiry recalculation job

**Files:**
- Create: `backend/app/Jobs/RecalculateExpiredScoresJob.php`
- Modify: `backend/routes/console.php`
- Test: `backend/tests/Unit/Jobs/RecalculateExpiredScoresJobTest.php`

**Steps:**
1. Write the failing job test around expired score events and unaffected contacts.
2. Run the focused test to verify failure.
3. Implement the job and schedule it daily.
4. Re-run the focused test and confirm pass.

### Task 5: STO backend

**Files:**
- Create: `backend/database/migrations/*_add_sto_fields_to_campaigns_table.php`
- Create: `backend/app/Services/ContactSendTimeService.php`
- Modify: `backend/app/Models/Campaign.php`
- Modify: `backend/app/Http/Requests/Api/V1/Campaigns/StoreCampaignRequest.php`
- Modify: `backend/app/Jobs/SendEmailCampaignJob.php`
- Test: `backend/tests/Unit/Services/ContactSendTimeServiceTest.php`
- Test: `backend/tests/Feature/Campaign/CampaignStoTest.php`

**Steps:**
1. Write failing STO unit and feature tests for optimal hour calculation, fallback behavior, and delayed recipient dispatch.
2. Run the tests and confirm the red state.
3. Implement migration, service, model/request updates, and job delay logic.
4. Re-run the focused tests until green.

### Task 6: Dynamic content backend

**Files:**
- Create: `backend/app/Services/DynamicContentValidatorService.php`
- Modify: `backend/app/Services/PersonalizationService.php`
- Modify: `backend/app/Http/Controllers/Api/V1/CampaignController.php`
- Test: `backend/tests/Unit/Services/DynamicContentValidatorServiceTest.php`
- Test: `backend/tests/Unit/Services/PersonalizationServiceDynamicTest.php`
- Test: `backend/tests/Feature/Campaign/CampaignDynamicContentTest.php`

**Steps:**
1. Write failing tests for syntax validation, conditional rendering, preview behavior, nesting limit, and campaign validation errors.
2. Run the focused tests to verify failure.
3. Implement validator + parser/evaluator and connect validation to campaign create/update.
4. Re-run the focused tests until green.

### Task 7: Frontend scoring UI

**Files:**
- Create: `frontend/lib/stores/scoring-rules.ts`
- Create: `frontend/app/(dashboard)/settings/scoring/page.tsx`
- Modify: `frontend/components/layout/sidebar.tsx`
- Modify: `frontend/components/contacts/contact-detail.tsx`
- Modify: `frontend/components/segments/segment-builder.tsx`
- Modify: `frontend/app/(dashboard)/page.tsx`
- Test: `frontend/tests/unit/stores/scoring-rules.test.ts`
- Test: `frontend/tests/components/segments/segment-builder-score.test.tsx`

**Steps:**
1. Write failing frontend tests for the store and segment score criterion first.
2. Run the focused tests to verify red.
3. Implement the store and UI changes, then add contact detail/dashboard coverage.
4. Re-run the focused tests until green.

### Task 8: Frontend STO and dynamic content UI

**Files:**
- Create: `frontend/components/campaigns/sto-config.tsx`
- Create: `frontend/components/campaigns/dynamic-content-editor.tsx`
- Modify: `frontend/app/(dashboard)/campaigns/create/page.tsx`
- Modify: `frontend/app/(dashboard)/campaigns/[id]/page.tsx`
- Test: `frontend/tests/components/campaigns/sto-config.test.tsx`
- Test: `frontend/tests/components/campaigns/dynamic-content-editor.test.tsx`
- Test: `frontend/tests/e2e/campaigns/sto-flow.spec.ts`
- Test: `frontend/tests/e2e/campaigns/dynamic-content-flow.spec.ts`

**Steps:**
1. Write failing component tests for STO controls and dynamic block insertion.
2. Run them and verify failure.
3. Implement the UI wiring into create/edit pages and preserve existing campaign payload behavior.
4. Add or complete E2E coverage, then re-run the relevant suites.

### Task 9: GDPR/export and hardening

**Files:**
- Modify: `backend/app/Services/DataExportService.php`
- Test: `backend/tests/Feature/Scoring/ContactScoreGdprTest.php`

**Steps:**
1. Write the failing GDPR/export test for scoring entities.
2. Run it to confirm failure.
3. Extend the export payload with scoring rules and score events.
4. Re-run the focused test until green.

### Task 10: Verification and release prep

**Files:**
- Modify: `docs/dev/phase17.md`
- Modify: `docs/phases/phase17.md`

**Steps:**
1. Run the full relevant backend and frontend verification commands.
2. Fix any failures found during validation or adversarial review.
3. Mark the phase tracking docs with the actual completion status.
4. Prepare commit(s) and PR summary only after fresh verification evidence.
