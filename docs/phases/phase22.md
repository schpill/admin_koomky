# Phase 22 — UX Polish & Connexion "Rester connecté" (v2.8)

| Field               | Value                                          |
|---------------------|------------------------------------------------|
| **Phase**           | 22                                             |
| **Name**            | UX Polish & Connexion "Rester connecté"        |
| **Duration**        | Weeks 199–202 (4 weeks)                        |
| **Milestone**       | M22 — v2.8.0 Release                           |
| **PRD Sections**    | §4.1 FR-AUTH (remember_me), §4.39 FR-PROFILE   |
| **Prerequisite**    | Phase 21 fully completed and tagged `v2.7.0`   |
| **Status**          | todo                                           |

---

## 1. Phase Objectives

| ID        | Objective                                                                                                          |
|-----------|--------------------------------------------------------------------------------------------------------------------|
| P22-OBJ-1 | Corriger les bugs UX hérités de la phase 21 (double layout profil, cursor pointer, textes anglais)                 |
| P22-OBJ-2 | Internationaliser complètement le composant `NotificationBell`                                                     |
| P22-OBJ-3 | Implémenter la fonctionnalité "Rester connecté.e" sur la page de connexion (backend + frontend)                    |
| P22-OBJ-4 | Améliorer le wording de la page de connexion (titre, description)                                                  |
| P22-OBJ-5 | Maintenir une couverture de tests >= 80% backend et frontend                                                       |

---

## 2. Contexte & Analyse de l'existant

### 2.1 Bugs identifiés

| ID       | Composant                                    | Bug                                                                                          |
|----------|----------------------------------------------|----------------------------------------------------------------------------------------------|
| BUG-001  | `components/layout/header.tsx`               | Les boutons d'icônes (clavier, thème, timer, cloche, avatar) n'ont pas `cursor-pointer`      |
| BUG-002  | `app/(dashboard)/profile/page.tsx`           | La page s'enveloppe dans `<DashboardLayout>` alors qu'elle est déjà dans le layout parent    |
| BUG-003  | `components/layout/notification-bell.tsx`    | Textes entièrement hardcodés en anglais : "Notifications", "Mark all read", "No notifications yet.", "Unread" |

### 2.2 Analyse du gap

| Besoin                        | État actuel                                                         | Solution Phase 22                                   |
|-------------------------------|---------------------------------------------------------------------|-----------------------------------------------------|
| Cursor pointer sur icônes     | Absent — les boutons ghost conservent le curseur par défaut         | Ajouter `cursor-pointer` sur chaque bouton icône    |
| Page profil sans double layout| Wrappée dans `<DashboardLayout>` en plus du layout Next.js parent  | Retirer le wrapper `<DashboardLayout>` de la page   |
| NotificationBell traduite     | Textes anglais hardcodés                                            | Clés i18n `notifications.*` en FR + EN              |
| "Rester connecté.e"           | Absent — le token d'actualisation expire toujours en 7 jours        | Checkbox pré-cochée ; backend émet un refresh token de 30 j si coché, 24 h si décoché |
| Titre page de connexion       | "Bon retour" — expression non usitée sur une page de login française | Remplacer par "Connexion" (FR) / "Sign in" (EN)     |

---

## 3. Choix techniques

### 3.1 BUG-001 — Cursor pointer

Ajouter `cursor-pointer` à la `className` des `<Button>` ghost/icon dans `header.tsx` :
- Bouton raccourcis clavier (Keyboard icon)
- Bouton thème (Sun/Moon icon)
- Bouton de déclenchement du DropdownMenu user (avatar/initiales)

Le composant `Button` de shadcn/ui applique déjà `cursor-pointer` par défaut sur les variants non-ghost. Vérifier si le problème vient du variant `ghost` + `size="icon"` et corriger au niveau du composant `Button` ou en surcharge locale.

### 3.2 BUG-002 — Double layout profil

`app/(dashboard)/profile/page.tsx` appelle `<DashboardLayout>` directement, ce qui crée un double rendu (header + sidebar imbriqués). La correction consiste à retirer ce wrapper : le layout parent `app/(dashboard)/layout.tsx` gère déjà le shell.

### 3.3 BUG-003 — NotificationBell i18n

Ajouter dans `messages.ts` (locales `fr` et `en`) la section `notifications` :

```ts
notifications: {
  title: "Notifications",            // même mot en FR et EN
  markAllRead: "Tout marquer lu",    // EN: "Mark all read"
  noNotifications: "Aucune notification pour le moment.",  // EN: "No notifications yet."
  unread: "Non lu",                  // EN: "Unread"
}
```

Remplacer dans `notification-bell.tsx` les chaînes hardcodées par `t("notifications.title")`, etc.

### 3.4 "Rester connecté.e" — Remember Me

#### Backend

`LoginRequest` accepte un champ optionnel `remember_me` (boolean, défaut `false`).

`AuthController::issueTokens()` reçoit un paramètre `$rememberMe: bool` :
- `$rememberMe = true` → refresh token expire dans **30 jours** (actuel : 7 jours)
- `$rememberMe = false` → refresh token expire dans **24 heures**

L'access token reste à 15 minutes dans les deux cas.

#### Frontend

- Ajouter un `<Checkbox>` pré-coché avec le label `t("auth.login.rememberMe")` dans le formulaire de connexion.
- Passer `remember_me: rememberMe` dans le corps de la requête `POST /auth/login`.
- Si `remember_me = true` : définir le cookie `koomky-refresh-token` avec `max-age=2592000` (30 jours).
- Si `remember_me = false` : définir le cookie sans `max-age` (cookie de session, effacé à la fermeture du navigateur).

### 3.5 Wording page de connexion

| Clé i18n                | Valeur FR actuelle    | Valeur FR corrigée                          |
|-------------------------|-----------------------|---------------------------------------------|
| `auth.login.title`      | "Bon retour"          | "Connexion"                                 |
| `auth.login.description`| "Entrez vos identifiants pour accéder à votre compte" | _(inchangée)_                |

Aucun changement côté EN (valeur actuelle "Welcome back" est acceptable pour EN ; à remplacer par "Sign in" pour homogénéité).

---

## 4. Entry Criteria

- Phase 21 exit criteria 100% satisfaits.
- CI verts sur `main`.
- `v2.7.0` tagué.

---

## 5. Scope — Requirement Traceability

| Feature                                       | Priority | Included |
|-----------------------------------------------|----------|----------|
| Cursor pointer sur icônes header              | High     | Yes      |
| Fix double layout page profil                 | High     | Yes      |
| Traduction complète NotificationBell          | High     | Yes      |
| Checkbox "Rester connecté.e" (pré-cochée)     | High     | Yes      |
| Logique remember_me backend (durée du token)  | High     | Yes      |
| Logique remember_me frontend (cookie session) | High     | Yes      |
| Titre connexion "Bon retour" → "Connexion"    | Medium   | Yes      |
| 2FA — remember me pour 30 j                   | Low      | No       |

---

## 6. Detailed Sprint Breakdown

### 6.1 Sprint 73 — Backend + i18n (Weeks 199–200)

#### 6.1.1 Backend Tasks

| ID          | Task                                                                                                                               |
|-------------|------------------------------------------------------------------------------------------------------------------------------------|
| P22-BE-001  | Modifier `LoginRequest` — ajouter le champ `remember_me` : `nullable()`, `boolean()`.                                             |
| P22-BE-002  | Modifier `AuthController::issueTokens()` — accepter `bool $rememberMe = false` ; refresh token 30 j si vrai, 24 h sinon.          |
| P22-BE-003  | Modifier `AuthController::login()` — passer `$request->boolean('remember_me')` à `issueTokens()`.                                 |
| P22-BE-004  | PHPStan level 8 + Pint — 0 erreur.                                                                                                 |

#### 6.1.2 Backend Tests (TDD)

| ID          | Test File                                                                                                          |
|-------------|--------------------------------------------------------------------------------------------------------------------|
| P22-BT-001  | `tests/Feature/Auth/LoginRememberMeTest.php` — avec `remember_me=true` : refresh token valide 30 j ; avec `remember_me=false` : refresh token valide 24 h. |

#### 6.1.3 i18n Tasks

| ID          | Task                                                                                                                    |
|-------------|-------------------------------------------------------------------------------------------------------------------------|
| P22-I18N-001| Ajouter clé `notifications.*` dans `messages.ts` (FR + EN) : `title`, `markAllRead`, `noNotifications`, `unread`.      |
| P22-I18N-002| Modifier `auth.login.title` FR : "Bon retour" → "Connexion".                                                           |
| P22-I18N-003| Modifier `auth.login.title` EN : "Welcome back" → "Sign in".                                                            |
| P22-I18N-004| Ajouter `auth.login.rememberMe` (FR : "Rester connecté.e", EN : "Stay signed in") dans `messages.ts`.                  |

---

### 6.2 Sprint 74 — Frontend UI Fixes & Remember Me (Weeks 201–202)

#### 6.2.1 Frontend Tasks

| ID          | Task                                                                                                                                                         |
|-------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------|
| P22-FE-001  | `components/layout/header.tsx` — ajouter `cursor-pointer` sur les boutons icônes (raccourcis, thème, avatar/user). Vérifier si la correction doit se faire dans le composant `Button` (variant `ghost` + `size="icon"`) ou en surcharge locale. |
| P22-FE-002  | `app/(dashboard)/profile/page.tsx` — retirer le wrapper `<DashboardLayout>` et l'import associé. La page renvoie directement le contenu (le layout parent gère le shell). |
| P22-FE-003  | `components/layout/notification-bell.tsx` — remplacer tous les textes hardcodés anglais par les clés i18n `t("notifications.*")`. Importer `useI18n`.         |
| P22-FE-004  | `app/auth/login/page.tsx` — ajouter un `<Checkbox>` pré-coché (état React `rememberMe=true`) avec label `t("auth.login.rememberMe")`. Passer `remember_me` dans le corps de la requête de connexion. Ajuster le `max-age` du cookie `koomky-refresh-token` selon la valeur de `rememberMe`. |
| P22-FE-005  | ESLint + Prettier — 0 erreur.                                                                                                                                |

#### 6.2.2 Frontend Tests

| ID          | Test File                                                                                                                          |
|-------------|------------------------------------------------------------------------------------------------------------------------------------|
| P22-FT-001  | `tests/components/layout/header-cursor.test.tsx` — vérifier que les boutons icônes ont la classe `cursor-pointer`.                |
| P22-FT-002  | `tests/components/layout/notification-bell.test.tsx` — vérifier les libellés traduits FR et EN ; vérifier l'état vide et le badge "non lu". |
| P22-FT-003  | `tests/components/auth/login-remember-me.test.tsx` — checkbox présente, pré-cochée par défaut ; dé-cocher = requête avec `remember_me=false`. |

---

## 7. Exit Criteria

| Critère                                                                 | Vérification               |
|-------------------------------------------------------------------------|----------------------------|
| Toutes les tâches `P22-BE-*`, `P22-I18N-*`, `P22-FE-*` en statut `done`| `docs/dev/phase22.md`      |
| Icônes du header avec `cursor-pointer` au survol                        | Test manuel                |
| Page `/profile` sans double header/sidebar                             | Test manuel                |
| NotificationBell affichée en FR et EN sans texte anglais hardcodé       | Test manuel                |
| Checkbox "Rester connecté.e" visible, pré-cochée, fonctionnelle         | Test manuel                |
| Sans "Rester connecté.e" → fermeture navigateur = déconnexion           | Test manuel (cookie session)|
| Titre de connexion affiché "Connexion" (FR) / "Sign in" (EN)            | Test manuel                |
| Backend coverage >= 80%                                                 | CI green                   |
| Frontend coverage >= 80%                                                | CI green                   |
| PHPStan level 8 — 0 erreur                                              | CI green                   |
| Pint + ESLint + Prettier — 0 erreur                                     | CI green                   |
| Tag `v2.8.0` poussé sur GitHub                                          | `git tag v2.8.0`           |

---

## 8. Récapitulatif

| Sprint    | Semaines | Livrable principal                                    | Tasks                            |
|-----------|----------|-------------------------------------------------------|----------------------------------|
| Sprint 73 | 199–200  | Backend remember_me + i18n (notifications, login)     | 4 BE + 1 test + 4 i18n           |
| Sprint 74 | 201–202  | Frontend UI fixes + remember me + tests               | 5 FE + 3 tests                   |
| **Total** | **4 sem**| **v2.8.0**                                            | **~4 BE + 9 FE/i18n + 4 tests**  |
