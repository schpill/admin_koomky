# Phase 21 — Task Tracking

> **Status**: todo
> **Prerequisite**: Phase 20 fully merged and tagged `v2.6.0`
> **Spec**: [docs/phases/phase21.md](../phases/phase21.md)

---

## Sprint 71 — Backend Profile (Weeks 195–196)

### Infrastructure & Database

| ID              | Task                                                                                         | Status | Owner |
|-----------------|----------------------------------------------------------------------------------------------|--------|-------|
| P21-BE-INF-01   | Migration `add_avatar_path_to_users_table` — `avatar_path` VARCHAR(500) nullable.           | done   | Codex |

### Backend Tasks

| ID          | Task                                                                                                                                                           | Status | Owner |
|-------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P21-BE-001  | Extend `User` model — `avatar_path` dans `$fillable`. Accessor `avatar_url` (URL publique ou null).                                                           | done   | Codex |
| P21-BE-002  | Create `ProfileController` — `GET /profile`, `PATCH /profile` (nom, email, avatar), `POST /profile/password` (vérification + update + révocation tokens).    | done   | Codex |
| P21-BE-003  | Create `UpdateProfileRequest` — name, email (unique ignore self), avatar (nullable image max 2048).                                                            | done   | Codex |
| P21-BE-004  | Create `UpdatePasswordRequest` — current_password required, password min:8 confirmed.                                                                          | done   | Codex |
| P21-BE-005  | Register routes dans `api.php` — `GET|PATCH /profile`, `POST /profile/password`. Middleware `auth:sanctum`.                                                    | done   | Codex |
| P21-BE-006  | PHPStan level 8 + Pint.                                                                                                                                         | done   | Codex |

### Backend Tests (TDD)

| ID          | Test File                                                                              | Status | Owner |
|-------------|----------------------------------------------------------------------------------------|--------|-------|
| P21-BT-001  | `tests/Feature/Profile/GetProfileTest.php`                                             | done   | Codex |
| P21-BT-002  | `tests/Feature/Profile/UpdateProfileTest.php`                                          | done   | Codex |
| P21-BT-003  | `tests/Feature/Profile/UpdatePasswordTest.php`                                         | done   | Codex |

---

## Sprint 72 — Frontend User Menu & Page Profil (Weeks 197–198)

### Frontend Tasks

| ID          | Task                                                                                                                                                                              | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P21-FE-001  | Extend `components/layout/header.tsx` — Remplacer `<Button>` User par `<DropdownMenu>`. Trigger : avatar ou initiales. Items : "Mon profil" (→ /profile), séparateur, "Déconnexion". | done   | Codex |
| P21-FE-002  | Create `lib/stores/profile.ts` (Zustand) — `user`, `fetchProfile()`, `updateProfile(data)`, `changePassword(data)`.                                                               | done   | Codex |
| P21-FE-003  | Create `app/(dashboard)/profile/page.tsx` — Section infos personnelles (nom, email, avatar). Section mot de passe. Toast succès/erreur.                                           | done   | Codex |
| P21-FE-004  | Create `components/profile/avatar-upload.tsx` — Zone drop/clic, preview, suppression.                                                                                            | done   | Codex |
| P21-FE-005  | ESLint + Prettier — 0 erreur.                                                                                                                                                      | done   | Codex |

### Frontend Tests

| ID          | Test File                                                              | Status | Owner |
|-------------|------------------------------------------------------------------------|--------|-------|
| P21-FT-001  | `tests/components/layout/header-user-menu.test.tsx`                   | done   | Codex |
| P21-FT-002  | `tests/components/profile/avatar-upload.test.tsx`                     | done   | Codex |
| P21-FT-003  | `tests/e2e/profile/profile-update-flow.spec.ts`                       | done   | Codex |
| P21-FT-004  | `tests/e2e/profile/password-change-flow.spec.ts`                      | done   | Codex |

---

## Récapitulatif

| Sprint    | Semaines | Livrable principal                        | Tasks                     |
|-----------|----------|-------------------------------------------|---------------------------|
| Sprint 71 | 195–196  | Backend profil (endpoints + tests)        | 1 INF + 5 BE + 3 tests    |
| Sprint 72 | 197–198  | Frontend user menu + page profil + tests  | 5 FE + 4 tests            |
| **Total** | **4 sem** | **v2.7.0**                               | **~1 INF + 10 BE/FE + 7 tests** |
