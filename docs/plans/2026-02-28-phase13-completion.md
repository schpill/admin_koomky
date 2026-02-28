# Phase 13 Completion Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Finish Phase 13 end-to-end with TDD, including the missing backend template service unit tests, all required frontend integrations, the missing reusable template components, and the required frontend/backend verification for a green CI path.

**Architecture:** Reuse the Phase 13 backend foundations already present in the worktree, then finish the frontend by tightening the Zustand stores, introducing the missing reusable template form/task-builder/dialog components, and wiring timer/template features into the existing dashboard, layout, project, and settings flows. Execute in vertical slices so each feature is covered by failing tests before implementation and verified with the project’s real test runners.

**Tech Stack:** Laravel, PHPUnit/Pest via Docker `api` container PHP binary, Next.js App Router, Zustand, React Hook Form, Zod, Vitest, Playwright, shadcn/ui, dnd-kit.

---

### Task 1: Stabilize Existing Phase 13 Baseline

**Files:**
- Modify: `backend/tests/Unit/Services/ProjectTemplateServiceTest.php`
- Modify: `backend/app/Services/ProjectTemplateService.php`
- Verify: `backend/tests/Feature/Templates/ProjectTemplateCrudTest.php`
- Verify: `backend/tests/Feature/Templates/ProjectTemplateInstantiateTest.php`

**Step 1: Write or finish the failing backend unit tests**

Cover:
- `createFromProject()` copies project fields into a template
- `createFromProject()` preserves task ordering into template tasks
- `instantiate()` creates a project and tasks from ordered template tasks
- `instantiate()` applies overrides from request data

**Step 2: Run the backend unit test in Docker**

Run: `docker compose exec -T api php vendor/bin/phpunit tests/Unit/Services/ProjectTemplateServiceTest.php`
Expected: FAIL for the missing or incorrect behaviors

**Step 3: Implement the minimum backend fixes**

Fix service behavior only where required by the test failures.

**Step 4: Re-run backend unit and related feature tests**

Run:
- `docker compose exec -T api php vendor/bin/phpunit tests/Unit/Services/ProjectTemplateServiceTest.php`
- `docker compose exec -T api php vendor/bin/phpunit tests/Feature/Templates/ProjectTemplateCrudTest.php`
- `docker compose exec -T api php vendor/bin/phpunit tests/Feature/Templates/ProjectTemplateInstantiateTest.php`

**Step 5: Keep the baseline stable**

If feature tests expose adjacent backend defects, fix them now before moving into frontend work.

### Task 2: Finish Timer Frontend Behavior and Integrations

**Files:**
- Modify: `frontend/lib/stores/timer.ts`
- Modify: `frontend/components/timer/timer-badge.tsx`
- Modify: `frontend/components/timer/timer-dropdown.tsx`
- Modify: `frontend/components/timer/task-timer-button.tsx`
- Modify: `frontend/components/projects/task-kanban-board.tsx`
- Modify: `frontend/components/projects/task-list-view.tsx`
- Modify: `frontend/components/layout/header.tsx`
- Modify: `frontend/components/layout/dashboard-layout.tsx`
- Modify: `frontend/app/(dashboard)/page.tsx`
- Create/Modify tests:
  - `frontend/tests/unit/stores/timer.test.ts`
  - `frontend/tests/components/timer/timer-badge.test.tsx`
  - `frontend/tests/components/timer/timer-dropdown.test.tsx`
  - `frontend/tests/components/timer/task-timer-button.test.tsx`

**Step 1: Write failing timer store/component tests**

Cover:
- active timer fetch/start/stop/cancel state transitions
- 204/no-active handling
- badge formatting and visibility
- dropdown stop/cancel actions
- task button start/stop/disabled states
- kanban/list rendering with integrated timer actions

**Step 2: Run targeted frontend tests**

Run:
- `pnpm --dir frontend vitest run frontend/tests/unit/stores/timer.test.ts`
- `pnpm --dir frontend vitest run frontend/tests/components/timer/timer-badge.test.tsx frontend/tests/components/timer/timer-dropdown.test.tsx frontend/tests/components/timer/task-timer-button.test.tsx`

Expected: FAIL

**Step 3: Implement the minimum timer UI/store changes**

Keep integration low-impact and consistent with existing layout/header/project views.

**Step 4: Re-run the targeted tests**

Expected: PASS

### Task 3: Build Reusable Project Template UI Primitives

**Files:**
- Modify: `frontend/lib/stores/project-templates.ts`
- Modify: `frontend/components/project-templates/project-template-card.tsx`
- Modify: `frontend/components/project-templates/instantiate-template-dialog.tsx`
- Create: `frontend/components/project-templates/project-template-form.tsx`
- Create: `frontend/components/project-templates/project-template-task-builder.tsx`
- Create: `frontend/components/project-templates/save-as-template-dialog.tsx`
- Create/Modify tests:
  - `frontend/tests/unit/stores/project-templates.test.ts`
  - `frontend/tests/components/project-templates/project-template-card.test.tsx`
  - `frontend/tests/components/project-templates/project-template-form.test.tsx`
  - `frontend/tests/components/project-templates/project-template-task-builder.test.tsx`
  - `frontend/tests/components/project-templates/instantiate-template-dialog.test.tsx`

**Step 1: Write failing store and component tests**

Cover CRUD state updates, dialog submission flows, reusable form validation, and task builder ordering/add/remove behavior.

**Step 2: Run targeted frontend tests**

Run:
- `pnpm --dir frontend vitest run frontend/tests/unit/stores/project-templates.test.ts`
- `pnpm --dir frontend vitest run frontend/tests/components/project-templates/project-template-card.test.tsx frontend/tests/components/project-templates/project-template-form.test.tsx frontend/tests/components/project-templates/project-template-task-builder.test.tsx frontend/tests/components/project-templates/instantiate-template-dialog.test.tsx`

Expected: FAIL

**Step 3: Implement the minimum reusable template UI**

Use a single form model shared by create/edit/save-as-template paths.

**Step 4: Re-run the targeted tests**

Expected: PASS

### Task 4: Integrate Templates Into Existing Pages and Navigation

**Files:**
- Modify: `frontend/app/(dashboard)/settings/project-templates/page.tsx`
- Modify: `frontend/app/(dashboard)/settings/project-templates/new/page.tsx`
- Modify: `frontend/app/(dashboard)/settings/project-templates/[id]/page.tsx`
- Modify: `frontend/app/(dashboard)/projects/create/page.tsx`
- Modify: `frontend/app/(dashboard)/projects/[id]/page.tsx`
- Modify: `frontend/components/layout/sidebar.tsx`
- Modify tests as needed in existing/new component/page specs

**Step 1: Write failing tests around the missing integrations**

Cover:
- templates listing and onboarding
- create/edit flows using reusable form
- template picker in project creation
- save-as-template action on project detail
- sidebar entry presence

**Step 2: Run targeted Vitest suites**

Use the narrowest file set that proves the red state for each added integration.

**Step 3: Implement the minimum page and navigation integration**

Avoid duplicating template form logic between pages.

**Step 4: Re-run targeted tests**

Expected: PASS

### Task 5: Add E2E Coverage for Critical Phase 13 Flows

**Files:**
- Create: `frontend/tests/e2e/timer/live-timer-flow.spec.ts`
- Create: `frontend/tests/e2e/templates/project-template-crud.spec.ts`
- Create: `frontend/tests/e2e/templates/project-template-instantiate.spec.ts`
- Modify helpers/fixtures only if strictly required

**Step 1: Write failing E2E specs**

Cover:
- start/stop timer flow from project tasks with visible badge/widget updates
- project template CRUD flow
- instantiate-from-template flow

**Step 2: Run the targeted Playwright specs**

Run:
- `pnpm --dir frontend exec playwright test frontend/tests/e2e/timer/live-timer-flow.spec.ts`
- `pnpm --dir frontend exec playwright test frontend/tests/e2e/templates/project-template-crud.spec.ts frontend/tests/e2e/templates/project-template-instantiate.spec.ts`

Expected: FAIL

**Step 3: Implement any remaining minimal fixes**

Only fix behavior exposed by the E2E failures.

**Step 4: Re-run the targeted E2E specs**

Expected: PASS

### Task 6: Final Verification for Phase 13

**Files:**
- Review: `docs/phases/phase13.md`
- Review: `docs/dev/phase13.md`

**Step 1: Run focused backend verification**

Run:
- `docker compose exec -T api php vendor/bin/phpunit tests/Unit/Services/ProjectTemplateServiceTest.php`
- `docker compose exec -T api php vendor/bin/phpunit tests/Feature/Timer/LiveTimerControllerTest.php`
- `docker compose exec -T api php vendor/bin/phpunit tests/Feature/Templates/ProjectTemplateCrudTest.php`
- `docker compose exec -T api php vendor/bin/phpunit tests/Feature/Templates/ProjectTemplateSaveTest.php`
- `docker compose exec -T api php vendor/bin/phpunit tests/Feature/Templates/ProjectTemplateInstantiateTest.php`

**Step 2: Run focused frontend verification**

Run:
- `pnpm --dir frontend vitest run frontend/tests/unit/stores/timer.test.ts frontend/tests/unit/stores/project-templates.test.ts`
- `pnpm --dir frontend vitest run frontend/tests/components/timer/timer-badge.test.tsx frontend/tests/components/timer/timer-dropdown.test.tsx frontend/tests/components/timer/task-timer-button.test.tsx frontend/tests/components/project-templates/project-template-card.test.tsx frontend/tests/components/project-templates/project-template-form.test.tsx frontend/tests/components/project-templates/project-template-task-builder.test.tsx frontend/tests/components/project-templates/instantiate-template-dialog.test.tsx`
- `pnpm --dir frontend exec playwright test frontend/tests/e2e/timer/live-timer-flow.spec.ts frontend/tests/e2e/templates/project-template-crud.spec.ts frontend/tests/e2e/templates/project-template-instantiate.spec.ts`

**Step 3: Run broader confidence checks if the targeted suites are green**

Run:
- `pnpm --dir frontend vitest run --coverage`
- `docker compose exec -T api php vendor/bin/phpunit`

**Step 4: Reconcile against the phase docs**

Check every missing item from the Sprint 40/41/42 review against code and verification evidence before reporting completion.
