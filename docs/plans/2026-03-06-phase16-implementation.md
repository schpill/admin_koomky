# Phase 16 Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Deliver phase 16 end-to-end: suppression list, bounce management, drip sequences, frontend management UI, analytics extensions, GDPR export, and pruning.

**Architecture:** Implement backend domain primitives first in Laravel with test-first iterations, then expose stable API endpoints to dedicated Next.js/Zustand stores and UI screens, then finish with GDPR/pruning hardening and full validation. Reuse existing campaign sending, analytics, dashboard, and policy patterns to minimize regression risk.

**Tech Stack:** Laravel 12, PHPUnit, queues/scheduler, Next.js App Router, Zustand, Vitest, Playwright, Tailwind, TypeScript.

---

### Task 1: Save analysis and branch the work

**Files:**
- Create: `docs/plans/2026-03-06-phase16-implementation.md`
- Modify: none
- Test: none

**Step 1: Verify current branch and worktree state**

Run: `git branch --show-current`
Expected: `main`

**Step 2: Create feature branch**

Run: `git checkout -b apex/phase16-full`
Expected: branch created successfully

**Step 3: Verify clean baseline**

Run: `git status --short`
Expected: no unrelated changes

**Step 4: Commit**

```bash
git add docs/plans/2026-03-06-phase16-implementation.md
git commit -m "docs: add phase 16 implementation plan"
```

### Task 2: Add failing backend tests for suppression list service and CRUD

**Files:**
- Create: `backend/tests/Unit/Services/SuppressionServiceTest.php`
- Create: `backend/tests/Feature/Campaigns/SuppressionListCrudTest.php`
- Create: `backend/tests/Feature/Campaigns/BounceManagementTest.php`
- Create: `backend/tests/Feature/Campaigns/CampaignAnalyticsBounceTest.php`
- Test: same files

**Step 1: Write the failing tests**

Cover:
- manual suppression is idempotent
- suppression CSV import/export
- suppression CRUD ownership and pagination/search
- hard bounce creates suppression entry
- soft bounce retries and escalates after third failure
- campaign sending skips suppressed emails
- analytics expose hard/soft/suppressed counts

**Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Unit/Services/SuppressionServiceTest.php tests/Feature/Campaigns/SuppressionListCrudTest.php tests/Feature/Campaigns/BounceManagementTest.php tests/Feature/Campaigns/CampaignAnalyticsBounceTest.php`
Expected: FAIL because migrations/models/services/controllers do not exist yet

**Step 3: Write minimal implementation**

Implement:
- suppression migrations
- `SuppressedEmail` model
- `SuppressionService`
- `CampaignRecipientObserver`
- suppression CRUD/import/export controller + routes
- send/unsubscribe/webhook/analytics extensions
- retry job registration

**Step 4: Run tests to verify they pass**

Run: same `php artisan test ...` command
Expected: PASS

**Step 5: Commit**

```bash
git add backend
git commit -m "feat: add suppression list and bounce management"
```

### Task 3: Add failing backend tests for drip sequences

**Files:**
- Create: `backend/tests/Unit/Services/DripEnrollmentServiceTest.php`
- Create: `backend/tests/Unit/Jobs/AdvanceDripEnrollmentsJobTest.php`
- Create: `backend/tests/Feature/Drip/DripSequenceCrudTest.php`
- Create: `backend/tests/Feature/Drip/DripSequenceSendTest.php`
- Test: same files

**Step 1: Write the failing tests**

Cover:
- enrollment idempotence
- suppression check on enrollment
- segment enrollment count
- scheduler advancement timing
- `if_opened`, `if_clicked`, `if_not_opened`
- completion of final step
- CRUD ownership and enrollment actions
- drip send creates trackable `CampaignRecipient`

**Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Unit/Services/DripEnrollmentServiceTest.php tests/Unit/Jobs/AdvanceDripEnrollmentsJobTest.php tests/Feature/Drip/DripSequenceCrudTest.php tests/Feature/Drip/DripSequenceSendTest.php`
Expected: FAIL because drip schema and code do not exist yet

**Step 3: Write minimal implementation**

Implement:
- drip migrations
- `DripSequence`, `DripStep`, `DripEnrollment`
- `DripEnrollmentService`
- `SendDripStepEmailJob`
- `AdvanceDripEnrollmentsJob`
- policy/controller/routes
- scheduler registration

**Step 4: Run tests to verify they pass**

Run: same `php artisan test ...` command
Expected: PASS

**Step 5: Commit**

```bash
git add backend
git commit -m "feat: add drip sequence backend workflows"
```

### Task 4: Add failing frontend tests for stores and components

**Files:**
- Create: `frontend/tests/unit/stores/drip-sequences.test.ts`
- Create: `frontend/tests/unit/stores/suppression-list.test.ts`
- Create: `frontend/tests/components/drip/drip-step-form.test.tsx`
- Create: `frontend/tests/components/drip/drip-enrollments-table.test.tsx`
- Create: `frontend/tests/components/campaigns/suppression-list-table.test.tsx`
- Create: `frontend/e2e/campaigns/drip-sequence-flow.spec.ts`
- Create: `frontend/e2e/campaigns/suppression-list-flow.spec.ts`

**Step 1: Write the failing tests**

Cover:
- Zustand fetch/create/update/delete flows
- CSV import/export triggers
- drip step condition editing
- enrollment pause/cancel actions
- suppression list rendering/removal
- e2e happy path for drip and suppression screens

**Step 2: Run tests to verify they fail**

Run: `pnpm --dir frontend vitest run frontend/tests/unit/stores/drip-sequences.test.ts frontend/tests/unit/stores/suppression-list.test.ts frontend/tests/components/drip/drip-step-form.test.tsx frontend/tests/components/drip/drip-enrollments-table.test.tsx frontend/tests/components/campaigns/suppression-list-table.test.tsx`
Expected: FAIL because stores/components/pages do not exist yet

**Step 3: Write minimal implementation**

Implement:
- `frontend/lib/stores/drip-sequences.ts`
- `frontend/lib/stores/suppression-list.ts`
- drip pages/components
- suppression page/component
- sidebar/dashboard/analytics updates
- i18n additions required by UI

**Step 4: Run tests to verify they pass**

Run: same `pnpm --dir frontend vitest run ...` command
Expected: PASS

**Step 5: Commit**

```bash
git add frontend
git commit -m "feat: add drip and suppression frontend"
```

### Task 5: Add failing backend tests for GDPR export and pruning

**Files:**
- Create: `backend/tests/Feature/Drip/DripSequenceGdprTest.php`
- Create: `backend/tests/Feature/Drip/DripEnrollmentPruneTest.php`
- Test: same files

**Step 1: Write the failing tests**

Cover:
- GDPR export includes `DripEnrollment` and `SuppressedEmail`
- prune command removes completed/cancelled enrollments older than 90 days and preserves recent or active ones

**Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/Drip/DripSequenceGdprTest.php tests/Feature/Drip/DripEnrollmentPruneTest.php`
Expected: FAIL because export/command changes do not exist yet

**Step 3: Write minimal implementation**

Implement:
- `DataExportService` extension
- `drip-enrollments:prune` command
- scheduler registration

**Step 4: Run tests to verify they pass**

Run: same `php artisan test ...` command
Expected: PASS

**Step 5: Commit**

```bash
git add backend
git commit -m "feat: harden drip data lifecycle"
```

### Task 6: Full validation, examine, and PR preparation

**Files:**
- Modify: backend/frontend files touched above
- Test: full targeted suites and project validation commands

**Step 1: Run backend validation**

Run:
- `php artisan test`
- `vendor/bin/phpstan analyse`
- `vendor/bin/pint --test`

Expected: PASS

**Step 2: Run frontend validation**

Run:
- `pnpm --dir frontend vitest run`
- `pnpm --dir frontend exec playwright test`
- `pnpm --dir frontend lint`

Expected: PASS

**Step 3: Perform adversarial review**

Review:
- suppression checks on every send path
- ownership/authorization on all new endpoints
- retry escalation and duplicate-send edge cases
- drip progression correctness and scheduler idempotence

**Step 4: Commit final fixes**

```bash
git add backend frontend
git commit -m "chore: finalize phase 16 validation"
```

**Step 5: Prepare PR**

Draft:
- title
- summary
- change list
- test plan
