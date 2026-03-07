# Phase 20 Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build the full phase 20 feature set across backend, frontend, tests, verification, and PR creation.

**Architecture:** Extend the existing Laravel campaign, portal, analytics, and GDPR services in place. Keep new functionality isolated in small services where the phase introduces new decision logic, then expose those extensions through the existing Next.js campaign and portal surfaces.

**Tech Stack:** Laravel 12, Pest, Next.js app router, Zustand, Vitest, Playwright, dompdf.

---

### Task 1: Save planning artifacts and establish branch context

**Files:**
- Create: `docs/plans/2026-03-06-phase20-design.md`
- Create: `docs/plans/2026-03-06-phase20-implementation.md`

**Step 1: Write the planning artifacts**

Create the design and implementation plan files with the approved approach and exact task sequencing.

**Step 2: Create the feature branch**

Run: `git checkout -b apex/phase20`
Expected: branch created successfully.

**Step 3: Commit**

```bash
git add docs/plans/2026-03-06-phase20-design.md docs/plans/2026-03-06-phase20-implementation.md
git commit -m "docs: add phase 20 design and implementation plan"
```

### Task 2: Preference center backend data model

**Files:**
- Create: `backend/database/migrations/2026_03_06_160000_create_communication_preferences_table.php`
- Create: `backend/database/migrations/2026_03_06_160100_add_timezone_to_contacts_table.php`
- Create: `backend/database/migrations/2026_03_06_160200_add_email_category_to_campaigns_table.php`
- Create: `backend/app/Models/CommunicationPreference.php`
- Create: `backend/database/factories/CommunicationPreferenceFactory.php`
- Modify: `backend/app/Models/Contact.php`
- Modify: `backend/app/Models/Campaign.php`

**Step 1: Write the failing tests**

Use:
- `backend/tests/Unit/Services/PreferenceCenterServiceTest.php`
- `backend/tests/Feature/Campaign/CampaignPreferenceFilterTest.php`

Cover:
- missing preferences are auto-created
- `isAllowed()` respects stored preference
- transactional category is always allowed
- campaign records hold `email_category`

**Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Unit/Services/PreferenceCenterServiceTest.php tests/Feature/Campaign/CampaignPreferenceFilterTest.php`
Expected: FAIL because tables/model/service do not exist yet.

**Step 3: Write minimal implementation**

Add migrations, model, factory, and model fields/casts/relations to support preference and timezone/category storage.

**Step 4: Run tests to verify they pass**

Run: `php artisan test tests/Unit/Services/PreferenceCenterServiceTest.php tests/Feature/Campaign/CampaignPreferenceFilterTest.php`
Expected: PASS.

### Task 3: Preference center service and campaign/drip/workflow filtering

**Files:**
- Create: `backend/app/Services/PreferenceCenterService.php`
- Modify: `backend/app/Jobs/SendEmailCampaignJob.php`
- Modify: `backend/app/Jobs/SendDripStepEmailJob.php`
- Modify: `backend/app/Jobs/SendWorkflowEmailJob.php`

**Step 1: Write the failing tests**

Expand:
- `backend/tests/Unit/Services/PreferenceCenterServiceTest.php`
- `backend/tests/Feature/Campaign/CampaignPreferenceFilterTest.php`
- `backend/tests/Feature/Drip/DripSequenceSendTest.php`
- `backend/tests/Feature/Workflow/WorkflowExecutionTest.php`

Cover:
- promotional/newsletter messages are skipped when opted out
- transactional messages still send
- no recipient row is created for blocked contacts

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Services/PreferenceCenterServiceTest.php tests/Feature/Campaign/CampaignPreferenceFilterTest.php tests/Feature/Drip/DripSequenceSendTest.php tests/Feature/Workflow/WorkflowExecutionTest.php`

**Step 3: Write minimal implementation**

Implement the service and inject it at send-time decision points.

**Step 4: Run test to verify it passes**

Run the same command and confirm PASS.

### Task 4: Portal preference center routes and personalization variable

**Files:**
- Create: `backend/app/Http/Controllers/PreferenceCenterController.php`
- Modify: `backend/routes/web.php`
- Modify: `backend/routes/api.php`
- Modify: `backend/app/Services/PersonalizationService.php`
- Modify: `backend/app/Services/PortalActivityLogger.php`
- Test: `backend/tests/Feature/Portal/PreferenceCenterPortalTest.php`

**Step 1: Write the failing test**

Cover:
- signed GET returns 3 categories
- signed POST updates preferences
- invalid signature returns 403
- `{{preferences_url}}` renders

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Portal/PreferenceCenterPortalTest.php tests/Unit/Services/PersonalizationServiceTest.php`

**Step 3: Write minimal implementation**

Add the controller, signed routes, API bridge for the frontend page, and personalization variable support.

**Step 4: Run test to verify it passes**

Run the same command and confirm PASS.

### Task 5: Timezone-aware STO

**Files:**
- Modify: `backend/app/Services/ContactSendTimeService.php`
- Modify: `backend/app/Http/Controllers/Api/V1/EmailTrackingController.php`
- Modify: `backend/app/Models/Contact.php`
- Test: `backend/tests/Unit/Services/ContactSendTimeServiceTimezoneTest.php`
- Test: `backend/tests/Feature/Campaign/EmailTrackingTest.php`

**Step 1: Write the failing tests**

Cover:
- local-hour aggregation for optimal hour
- delay conversion from local hour back to UTC
- first click populates missing timezone

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Services/ContactSendTimeServiceTimezoneTest.php tests/Feature/Campaign/EmailTrackingTest.php`

**Step 3: Write minimal implementation**

Add timezone persistence and local-time STO calculations with safe fallbacks.

**Step 4: Run test to verify it passes**

Run the same command and confirm PASS.

### Task 6: Campaign report service and export endpoints

**Files:**
- Create: `backend/app/Services/CampaignReportService.php`
- Create: `backend/resources/views/reports/campaign-report.blade.php`
- Modify: `backend/app/Http/Controllers/Api/V1/CampaignController.php`
- Modify: `backend/routes/api.php`
- Test: `backend/tests/Unit/Services/CampaignReportServiceTest.php`
- Test: `backend/tests/Feature/Campaign/CampaignReportExportTest.php`

**Step 1: Write the failing tests**

Cover:
- summary, links, and timeline generation
- CSV response headers/content
- PDF response headers/content type
- route authorization

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Services/CampaignReportServiceTest.php tests/Feature/Campaign/CampaignReportExportTest.php`

**Step 3: Write minimal implementation**

Implement report assembly, Blade rendering, CSV streaming, and API endpoints.

**Step 4: Run test to verify it passes**

Run the same command and confirm PASS.

### Task 7: Deliverability scoring backend

**Files:**
- Create: `backend/app/Services/DeliverabilityScoreService.php`
- Modify: `backend/app/Http/Controllers/Api/V1/CampaignController.php`
- Test: `backend/tests/Unit/Services/DeliverabilityScoreServiceTest.php`
- Test: `backend/tests/Feature/Campaign/CampaignCrudTest.php`

**Step 1: Write the failing tests**

Cover:
- spam subject deductions
- missing unsubscribe link deduction
- alt/image/text warnings
- response payload includes `deliverability` on create/update

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Services/DeliverabilityScoreServiceTest.php tests/Feature/Campaign/CampaignCrudTest.php`

**Step 3: Write minimal implementation**

Implement the heuristic service and append its output to campaign responses.

**Step 4: Run test to verify it passes**

Run the same command and confirm PASS.

### Task 8: GDPR export extension

**Files:**
- Modify: `backend/app/Services/DataExportService.php`
- Test: `backend/tests/Feature/Portal/PreferenceCenterGdprTest.php`
- Test: `backend/tests/Unit/Services/DataExportServiceTest.php`

**Step 1: Write the failing tests**

Cover inclusion of `communication_preferences` in the full export payload.

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Portal/PreferenceCenterGdprTest.php tests/Unit/Services/DataExportServiceTest.php`

**Step 3: Write minimal implementation**

Append the new collection without disturbing the existing export shape.

**Step 4: Run test to verify it passes**

Run the same command and confirm PASS.

### Task 9: Frontend campaign store and deliverability badge

**Files:**
- Create: `frontend/components/campaigns/deliverability-badge.tsx`
- Modify: `frontend/lib/stores/campaigns.ts`
- Modify: `frontend/components/campaigns/*`
- Test: `frontend/tests/components/campaigns/deliverability-badge.test.tsx`
- Test: `frontend/tests/unit/stores/campaigns.test.ts`

**Step 1: Write the failing tests**

Cover:
- score color thresholds
- issue rendering
- store persistence of `email_category` and `deliverability`

**Step 2: Run test to verify it fails**

Run: `pnpm --dir frontend vitest run frontend/tests/components/campaigns/deliverability-badge.test.tsx frontend/tests/unit/stores/campaigns.test.ts`

**Step 3: Write minimal implementation**

Extend the store types/actions and add the badge component.

**Step 4: Run test to verify it passes**

Run the same command and confirm PASS.

### Task 10: Frontend campaign form, analytics exports, and portal preferences UI

**Files:**
- Create: `frontend/app/portal/preferences/[contact]/page.tsx`
- Modify: `frontend/app/(dashboard)/campaigns/create/page.tsx`
- Modify: `frontend/app/(dashboard)/campaigns/[id]/analytics/page.tsx`
- Modify: `frontend/components/campaigns/analytics-summary-cards.tsx`
- Modify: `frontend/components/campaigns/engagement-chart.tsx`
- Modify: `frontend/lib/portal.ts`
- Test: `frontend/tests/components/preferences/preference-center.test.tsx`
- Test: `frontend/tests/components/campaigns/campaign-analytics-export.test.tsx`

**Step 1: Write the failing tests**

Cover:
- preference toggles and submission state
- analytics export button behavior
- timeline rendering fallback

**Step 2: Run test to verify it fails**

Run: `pnpm --dir frontend vitest run frontend/tests/components/preferences/preference-center.test.tsx frontend/tests/components/campaigns/campaign-analytics-export.test.tsx`

**Step 3: Write minimal implementation**

Build the portal page and campaign analytics/form extensions.

**Step 4: Run test to verify it passes**

Run the same command and confirm PASS.

### Task 11: Contact timezone editing surface

**Files:**
- Modify: `frontend/components/clients/client-contact-list.tsx`
- Modify: `frontend/app/(dashboard)/clients/[id]/*`
- Test: relevant client/contact component tests

**Step 1: Write the failing tests**

Cover timezone field rendering and local time preview.

**Step 2: Run test to verify it fails**

Run the smallest targeted Vitest suite for the touched contact component.

**Step 3: Write minimal implementation**

Add timezone editing UI while preserving current contact flows.

**Step 4: Run test to verify it passes**

Run the same targeted suite and confirm PASS.

### Task 12: Playwright E2E coverage

**Files:**
- Create: `frontend/tests/e2e/portal/preference-center-flow.spec.ts`
- Create: `frontend/tests/e2e/campaigns/report-export-flow.spec.ts`

**Step 1: Write the failing tests**

Cover:
- portal preference center happy path
- campaign report CSV/PDF export flow

**Step 2: Run test to verify it fails**

Run: `pnpm --dir frontend exec playwright test frontend/tests/e2e/portal/preference-center-flow.spec.ts frontend/tests/e2e/campaigns/report-export-flow.spec.ts`

**Step 3: Write minimal implementation or fixtures until it passes**

Adjust mocks/routes/UI details only as needed.

**Step 4: Run test to verify it passes**

Run the same command and confirm PASS.

### Task 13: Full validation, review, and PR

**Files:**
- Modify: touched files as required by fixes

**Step 1: Run backend verification**

Run:
- `php artisan test`
- `./vendor/bin/pint --test`
- `./vendor/bin/phpstan analyse`

**Step 2: Run frontend verification**

Run:
- `pnpm --dir frontend vitest run`
- `pnpm --dir frontend exec playwright test`
- `pnpm --dir frontend lint`

**Step 3: Perform adversarial review**

Check:
- preference bypass for transactional only
- recipient creation skips for blocked contacts
- timezone fallback correctness
- export authorization and file streaming behavior
- deliverability false positives that would break saves

**Step 4: Commit and open PR**

```bash
git add <relevant files>
git commit -m "feat: implement phase 20 preference center and reporting"
git push -u origin apex/phase20
gh pr create --fill
```
