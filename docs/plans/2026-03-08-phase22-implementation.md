# Phase 22 Implementation Plan

**Goal:** Implémenter la phase 22 en autonomie complète avec une boucle TDD couvrant backend, frontend, i18n, documentation et validation.

**Architecture:** Garder le backend minimal autour de `AuthController`/`LoginRequest`, déplacer la responsabilité `remember_me` du frontend dans le store auth partagé, puis appliquer les corrections UX et i18n sans refactor hors périmètre.

**Tech Stack:** Laravel 12, Sanctum, Pest, Next.js app router, Zustand, shadcn/radix, Vitest.

## Task 1: Save artifacts and branch context

**Files:**
- Create: `docs/plans/2026-03-08-phase22-design.md`
- Create: `docs/plans/2026-03-08-phase22-implementation.md`

**Steps:**
1. Capturer le design et l’ordre d’exécution.
2. Travailler sur la branche `apex/phase22-ux-remember-me`.

## Task 2: Backend remember_me by TDD

**Files:**
- Create: `backend/tests/Feature/Api/V1/LoginRememberMeTest.php`
- Modify: `backend/app/Http/Requests/Api/V1/Auth/LoginRequest.php`
- Modify: `backend/app/Http/Controllers/Api/V1/AuthController.php`

**Steps:**
1. Écrire le test rouge sur les expirations 30 jours / 24 heures.
2. Exécuter le test pour confirmer l’échec.
3. Implémenter `remember_me` côté requête + émission des tokens.
4. Rejouer le test pour confirmer le vert.

## Task 3: Frontend UX, i18n and auth persistence by TDD

**Files:**
- Create: `frontend/tests/components/layout/header-cursor.test.tsx`
- Create: `frontend/tests/components/layout/notification-bell.test.tsx`
- Create: `frontend/tests/components/auth/login-remember-me.test.tsx`
- Modify: `frontend/tests/unit/auth-store.test.ts`
- Modify: `frontend/components/ui/button.tsx`
- Modify: `frontend/components/layout/notification-bell.tsx`
- Modify: `frontend/app/auth/login/page.tsx`
- Modify: `frontend/lib/stores/auth.ts`
- Modify: `frontend/lib/i18n/messages.ts`
- Modify: `frontend/app/(dashboard)/profile/page.tsx`

**Steps:**
1. Poser les tests rouges sur les labels, le payload login et les cookies auth.
2. Implémenter le namespace `notifications`, le wording login et la checkbox `remember_me`.
3. Déplacer la logique de persistance/cookies dans `useAuthStore`.
4. Corriger le double layout profil et l’affordance `cursor-pointer`.
5. Rejouer les tests ciblés jusqu’au vert.

## Task 4: Phase bookkeeping and validation

**Files:**
- Modify: `docs/dev/phase22.md`

**Steps:**
1. Marquer les tâches phase 22 en `done`.
2. Exécuter les validations backend et frontend pertinentes.
3. Réaliser la revue adversariale et corriger les derniers écarts si besoin.
