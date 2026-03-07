# Phase 21 Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build the full phase 21 profile and user-menu feature set across backend, frontend, tests, verification, and PR preparation.

**Architecture:** Extend the existing Laravel auth/settings surface with a dedicated profile controller and requests, then wire a new Next.js profile store/page and a header dropdown that reuses the current auth store for session state. Keep implementation vertical and TDD-driven from backend API to frontend interactions.

**Tech Stack:** Laravel 12, Sanctum, Pest, Next.js app router, Zustand, shadcn/radix, Vitest, Playwright.

---

### Task 1: Save planning artifacts and establish branch context

**Files:**
- Create: `docs/plans/2026-03-07-phase21-design.md`
- Create: `docs/plans/2026-03-07-phase21-implementation.md`

**Step 1: Write the planning artifacts**

Capture the agreed phase 21 design and the exact execution order.

**Step 2: Create the feature branch**

Run: `git checkout -b apex/phase21-user-menu-profile-settings`
Expected: branch created successfully.

### Task 2: Backend profile API by TDD

**Files:**
- Create: `backend/database/migrations/2026_03_07_120000_add_avatar_path_to_users_table.php`
- Create: `backend/app/Http/Controllers/Api/V1/ProfileController.php`
- Create: `backend/app/Http/Requests/Api/V1/Profile/UpdateProfileRequest.php`
- Create: `backend/app/Http/Requests/Api/V1/Profile/UpdatePasswordRequest.php`
- Create: `backend/tests/Feature/Profile/GetProfileTest.php`
- Create: `backend/tests/Feature/Profile/UpdateProfileTest.php`
- Create: `backend/tests/Feature/Profile/UpdatePasswordTest.php`
- Modify: `backend/app/Models/User.php`
- Modify: `backend/routes/api.php`

**Step 1: Write the failing tests**

Cover:
- current authenticated user is returned with `avatar_url`
- profile update changes name/email
- duplicate email returns `422`
- avatar upload stores the file and returns a public URL
- password change requires the correct current password
- password change revokes other tokens only

**Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/Profile/GetProfileTest.php tests/Feature/Profile/UpdateProfileTest.php tests/Feature/Profile/UpdatePasswordTest.php`
Expected: FAIL because routes/controller/requests do not exist yet.

**Step 3: Write minimal implementation**

Add the migration, accessor, requests, controller logic, and routes.

**Step 4: Run tests to verify they pass**

Run the same command and confirm PASS.

### Task 3: Frontend profile store and avatar component by TDD

**Files:**
- Create: `frontend/lib/stores/profile.ts`
- Create: `frontend/components/profile/avatar-upload.tsx`
- Create: `frontend/tests/components/profile/avatar-upload.test.tsx`
- Create: `frontend/tests/unit/profile-store.test.ts`

**Step 1: Write the failing tests**

Cover:
- store fetch/update/password actions hit the correct endpoints and sync the auth user
- avatar upload shows a preview after file selection
- avatar removal restores the placeholder state

**Step 2: Run tests to verify they fail**

Run: `pnpm --dir frontend test frontend/tests/components/profile/avatar-upload.test.tsx frontend/tests/unit/profile-store.test.ts`
Expected: FAIL because store/component do not exist yet.

**Step 3: Write minimal implementation**

Create the store and avatar upload component with the smallest API surface required by the profile page.

**Step 4: Run tests to verify they pass**

Run the same command and confirm PASS.

### Task 4: Frontend page and header menu by TDD

**Files:**
- Create: `frontend/app/(dashboard)/profile/page.tsx`
- Create: `frontend/tests/components/layout/header-user-menu.test.tsx`
- Modify: `frontend/components/layout/header.tsx`
- Modify: `frontend/lib/stores/auth.ts`

**Step 1: Write the failing tests**

Cover:
- header dropdown opens on click
- profile link is present
- logout action calls the logout API and clears auth state
- profile page loads existing user data and submits both forms

**Step 2: Run tests to verify they fail**

Run: `pnpm --dir frontend test frontend/tests/components/layout/header-user-menu.test.tsx`
Expected: FAIL because the dropdown/page integration does not exist yet.

**Step 3: Write minimal implementation**

Add the dropdown trigger/avatar rendering, logout flow, and the `/profile` page backed by the new store.

**Step 4: Run tests to verify they pass**

Run the same command and confirm PASS.

### Task 5: End-to-end coverage and phase bookkeeping

**Files:**
- Create: `frontend/tests/e2e/profile/profile-update-flow.spec.ts`
- Create: `frontend/tests/e2e/profile/password-change-flow.spec.ts`
- Modify: `docs/dev/phase21.md`

**Step 1: Write the failing tests**

Cover:
- updating the profile changes the header display
- changing the password invalidates another session/token

**Step 2: Run tests to verify they fail**

Run the relevant Playwright specs once the UI is wired.

**Step 3: Write minimal implementation/supporting fixes**

Adjust selectors, API behavior, or session handling only as needed to satisfy the phase.

**Step 4: Run focused verification**

Run backend tests, frontend Vitest, lint/type/build checks that are relevant, and the Playwright specs if environment permits.
