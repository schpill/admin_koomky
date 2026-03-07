# Phase 21 — User Menu & Profile Settings (v2.7)

| Field               | Value                                       |
|---------------------|---------------------------------------------|
| **Phase**           | 21                                          |
| **Name**            | User Menu & Profile Settings                |
| **Duration**        | Weeks 195–198 (4 weeks)                     |
| **Milestone**       | M21 — v2.7.0 Release                        |
| **PRD Sections**    | §4.39 FR-PROFILE (nouveau)                  |
| **Prerequisite**    | Phase 20 fully completed and tagged `v2.6.0` |
| **Status**          | todo                                        |

---

## 1. Phase Objectives

| ID        | Objective                                                                                                        |
|-----------|------------------------------------------------------------------------------------------------------------------|
| P21-OBJ-1 | Implémenter le menu utilisateur (icône `User` en haut à droite) : dropdown avec profil, paramètres, déconnexion  |
| P21-OBJ-2 | Créer une page de profil permettant de modifier nom, email et avatar                                             |
| P21-OBJ-3 | Permettre le changement de mot de passe depuis le profil (ancien mot de passe requis)                            |
| P21-OBJ-4 | Maintenir une couverture de tests >= 80% backend et frontend                                                     |

---

## 2. Contexte & Analyse de l'existant

### 2.1 Ce qui est déjà en place

| Besoin | Brique existante | Phase d'origine |
|--------|-----------------|-----------------|
| Auth Sanctum (login/logout) | `AuthController`, `POST /auth/logout` | Phase 1 |
| Modèle `User` avec `name`, `email`, `password` | Phase 1 | Phase 1 |
| Header avec bouton `User` (icône seulement, sans action) | `components/layout/header.tsx` ligne 77 | Phase 1 |
| Sidebar avec lien "Déconnexion" | Phase 1 | Phase 1 |

### 2.2 Analyse du gap

| Besoin | Gap actuel | Solution Phase 21 |
|--------|-----------|-------------------|
| Menu utilisateur dropdown | Bouton `User` sans `onClick` ni `DropdownMenu` | `DropdownMenu` shadcn/ui avec items |
| Page profil | Absente | `app/(dashboard)/profile/page.tsx` |
| Modifier nom/email | Aucun endpoint | `PATCH /api/v1/profile` |
| Changer mot de passe | Aucun endpoint | `POST /api/v1/profile/password` |
| Avatar utilisateur | Non implémenté | Upload + stockage local, affichage dans header |

---

## 3. Choix techniques

### 3.1 Backend

```
PATCH /api/v1/profile
  Body: { name?: string, email?: string, avatar?: file (multipart) }
  → Valider unicité email si changé
  → Stocker avatar dans storage/app/avatars/{user_id}.{ext}
  → Retourner l'utilisateur mis à jour

POST /api/v1/profile/password
  Body: { current_password: string, password: string, password_confirmation: string }
  → Vérifier Hash::check(current_password, user->password)
  → Mettre à jour le hash
  → Invalider tous les tokens Sanctum (logout des autres sessions)

GET /api/v1/profile
  → Retourner l'utilisateur authentifié avec avatar_url
```

**Modèle `User`** — ajouter `avatar_path` (nullable) dans `$fillable` + migration.

### 3.2 Frontend

```
Header — icône User :
  → Remplacer <Button> seul par <DropdownMenu> shadcn/ui
  → Items : "Mon profil" (→ /profile), separator, "Déconnexion"
  → Afficher avatar (initiales si absent) dans le trigger

Page /profile :
  → Formulaire : nom, email, upload avatar (preview)
  → Section séparée : formulaire changement de mot de passe
  → Notifications toast sur succès/erreur
  → Zustand store `profile` : { user, updateProfile, changePassword }
```

---

## 4. Entry Criteria

- Phase 20 exit criteria 100% satisfaits.
- CI verts sur `main`.
- `v2.6.0` tagué.

---

## 5. Scope — Requirement Traceability

| Feature | Priority | Included |
|---------|----------|----------|
| Dropdown menu `User` (profil + déconnexion) | High | Yes |
| Page profil `/profile` | High | Yes |
| Modifier nom et email | High | Yes |
| Upload avatar | Medium | Yes |
| Changer mot de passe | High | Yes |
| Invalider autres sessions au changement de mot de passe | Medium | Yes |
| 2FA / TOTP | Low | No |
| Historique des connexions | Low | No |

---

## 6. Detailed Sprint Breakdown

### 6.1 Sprint 71 — Backend Profile (Weeks 195–196)

#### 6.1.1 Infrastructure & Database

| ID              | Migration |
|-----------------|-----------|
| P21-BE-INF-01   | `add_avatar_path_to_users_table` — `avatar_path` VARCHAR(500) nullable. |

#### 6.1.2 Backend Tasks

| ID          | Task |
|-------------|------|
| P21-BE-001  | Extend `User` model — `avatar_path` dans `$fillable`. Accessor `avatar_url` (URL publique ou null). |
| P21-BE-002  | Create `ProfileController` — `GET /profile` (utilisateur courant + avatar_url), `PATCH /profile` (nom, email, avatar upload), `POST /profile/password` (vérification ancien mdp, update, revoke autres tokens). |
| P21-BE-003  | Create `UpdateProfileRequest` — name required string max:255, email required email unique:users ignore self, avatar nullable image max:2048. |
| P21-BE-004  | Create `UpdatePasswordRequest` — current_password required, password required min:8 confirmed. |
| P21-BE-005  | Register routes dans `api.php` — `GET|PATCH /profile`, `POST /profile/password`. Middleware `auth:sanctum`. |
| P21-BE-006  | PHPStan level 8 + Pint. |

#### 6.1.3 Backend Tests (TDD)

| ID          | Test File |
|-------------|-----------|
| P21-BT-001  | `tests/Feature/Profile/GetProfileTest.php` — retourne l'utilisateur courant, avatar_url null si absent |
| P21-BT-002  | `tests/Feature/Profile/UpdateProfileTest.php` — update nom, update email (unicité vérifiée), upload avatar stocké, 422 si email dupliqué |
| P21-BT-003  | `tests/Feature/Profile/UpdatePasswordTest.php` — succès avec bon mot de passe, 422 si current_password incorrect, autres tokens révoqués |

---

### 6.2 Sprint 72 — Frontend User Menu & Page Profil (Weeks 197–198)

#### 6.2.1 Frontend Tasks

| ID          | Task |
|-------------|------|
| P21-FE-001  | Extend `components/layout/header.tsx` — Remplacer le `<Button>` `User` par un `<DropdownMenu>`. Trigger : avatar (img ou initiales dans un cercle coloré). Items : "Mon profil" (lien `/profile`), séparateur, "Déconnexion" (appel API logout + redirect). |
| P21-FE-002  | Create `lib/stores/profile.ts` (Zustand) — `user`, `fetchProfile()`, `updateProfile(data)`, `changePassword(data)`. |
| P21-FE-003  | Create `app/(dashboard)/profile/page.tsx` — Section "Informations personnelles" : champs nom + email + upload avatar avec preview. Section "Mot de passe" : champs ancien mdp + nouveau + confirmation. Bouton de sauvegarde par section. Toast succès/erreur. |
| P21-FE-004  | Create `components/profile/avatar-upload.tsx` — Composant upload : zone de drop ou clic, preview de l'image sélectionnée, bouton supprimer. |
| P21-FE-005  | ESLint + Prettier — 0 erreur. |

#### 6.2.2 Frontend Tests

| ID          | Test File |
|-------------|-----------|
| P21-FT-001  | `tests/components/layout/header-user-menu.test.tsx` — dropdown s'ouvre au clic, lien "Mon profil" présent, "Déconnexion" appelle logout |
| P21-FT-002  | `tests/components/profile/avatar-upload.test.tsx` — prévisualisation après sélection, suppression remet le placeholder |
| P21-FT-003  | `tests/e2e/profile/profile-update-flow.spec.ts` — modifier nom → sauvegarder → vérifier changement dans le header |
| P21-FT-004  | `tests/e2e/profile/password-change-flow.spec.ts` — changer mot de passe → déconnexion automatique des autres sessions |

---

## 7. Exit Criteria

| Critère | Vérification |
|---------|-------------|
| Toutes les tâches `P21-BE-*` et `P21-FE-*` en statut `done` | `docs/dev/phase21.md` |
| Clic sur l'icône User ouvre un dropdown fonctionnel | Test manuel |
| Page `/profile` accessible et formulaires fonctionnels | Test manuel |
| Changement de mot de passe invalide les autres sessions | Test manuel |
| Backend coverage >= 80% | CI green |
| Frontend coverage >= 80% | CI green |
| PHPStan level 8 — 0 erreur | CI green |
| Pint + ESLint + Prettier — 0 erreur | CI green |
| Tag `v2.7.0` poussé sur GitHub | `git tag v2.7.0` |

---

## 8. Récapitulatif

| Sprint    | Semaines | Livrable principal                        | Tasks                     |
|-----------|----------|-------------------------------------------|---------------------------|
| Sprint 71 | 195–196  | Backend profil (endpoints + tests)        | 1 INF + 5 BE + 3 tests    |
| Sprint 72 | 197–198  | Frontend user menu + page profil + tests  | 5 FE + 4 tests            |
| **Total** | **4 sem** | **v2.7.0**                               | **~1 INF + 10 BE/FE + 7 tests** |
