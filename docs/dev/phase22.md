# Phase 22 — Task Tracking

> **Status**: todo
> **Prerequisite**: Phase 21 fully merged and tagged `v2.7.0`
> **Spec**: [docs/phases/phase22.md](../phases/phase22.md)

---

## Sprint 73 — Backend Remember Me + i18n (Weeks 199–200)

### Backend Tasks

| ID          | Task                                                                                                                              | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P22-BE-001  | Modifier `LoginRequest` — ajouter le champ `remember_me` : `nullable()`, `boolean()`.                                            | todo   |       |
| P22-BE-002  | Modifier `AuthController::issueTokens()` — accepter `bool $rememberMe = false` ; refresh token 30 j si vrai, 24 h sinon.         | todo   |       |
| P22-BE-003  | Modifier `AuthController::login()` — passer `$request->boolean('remember_me')` à `issueTokens()`.                                | todo   |       |
| P22-BE-004  | PHPStan level 8 + Pint — 0 erreur.                                                                                                | todo   |       |

### Backend Tests (TDD)

| ID          | Test File                                                                                                                          | Status | Owner |
|-------------|------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P22-BT-001  | `tests/Feature/Auth/LoginRememberMeTest.php` — with `remember_me=true`: refresh token valid 30 d ; with `remember_me=false`: refresh token valid 24 h. | todo   |       |

### i18n Tasks

| ID           | Task                                                                                                                   | Status | Owner |
|--------------|------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P22-I18N-001 | Ajouter clé `notifications.*` dans `messages.ts` (FR + EN) : `title`, `markAllRead`, `noNotifications`, `unread`.     | todo   |       |
| P22-I18N-002 | Modifier `auth.login.title` FR : "Bon retour" → "Connexion".                                                          | todo   |       |
| P22-I18N-003 | Modifier `auth.login.title` EN : "Welcome back" → "Sign in".                                                          | todo   |       |
| P22-I18N-004 | Ajouter `auth.login.rememberMe` (FR : "Rester connecté.e", EN : "Stay signed in") dans `messages.ts`.                 | todo   |       |

---

## Sprint 74 — Frontend UI Fixes & Remember Me (Weeks 201–202)

### Frontend Tasks

| ID          | Task                                                                                                                                                                          | Status | Owner |
|-------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P22-FE-001  | `components/layout/header.tsx` — ajouter `cursor-pointer` sur les boutons icônes (raccourcis, thème, avatar/user). Vérifier si correction dans `Button` ou surcharge locale. | todo   |       |
| P22-FE-002  | `app/(dashboard)/profile/page.tsx` — retirer le wrapper `<DashboardLayout>` et l'import associé.                                                                             | todo   |       |
| P22-FE-003  | `components/layout/notification-bell.tsx` — remplacer tous les textes hardcodés anglais par `t("notifications.*")`. Importer `useI18n`.                                       | todo   |       |
| P22-FE-004  | `app/auth/login/page.tsx` — ajouter `<Checkbox>` pré-coché (`rememberMe=true`), label `t("auth.login.rememberMe")`, passer `remember_me` dans la requête, ajuster `max-age` cookie selon valeur. | todo   |       |
| P22-FE-005  | ESLint + Prettier — 0 erreur.                                                                                                                                                 | todo   |       |

### Frontend Tests

| ID          | Test File                                                                                                                         | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P22-FT-001  | `tests/components/layout/header-cursor.test.tsx` — boutons icônes ont `cursor-pointer`.                                          | todo   |       |
| P22-FT-002  | `tests/components/layout/notification-bell.test.tsx` — libellés traduits FR/EN, état vide, badge "non lu".                       | todo   |       |
| P22-FT-003  | `tests/components/auth/login-remember-me.test.tsx` — checkbox présente et pré-cochée ; dé-cocher = `remember_me=false` dans la requête. | todo   |       |

---

## Récapitulatif

| Sprint    | Semaines | Livrable principal                                    | Tasks                            |
|-----------|----------|-------------------------------------------------------|----------------------------------|
| Sprint 73 | 199–200  | Backend remember_me + i18n (notifications, login)     | 4 BE + 1 test + 4 i18n           |
| Sprint 74 | 201–202  | Frontend UI fixes + remember me + tests               | 5 FE + 3 tests                   |
| **Total** | **4 sem**| **v2.8.0**                                            | **~4 BE + 9 FE/i18n + 4 tests**  |
