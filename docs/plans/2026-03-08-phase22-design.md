# Phase 22 Design

**Date:** 2026-03-08
**Phase:** 22
**Source Specs:** `docs/phases/phase22.md`, `docs/dev/phase22.md`

## Goal

Livrer la phase 22 de bout en bout en corrigeant les régressions UX de la phase 21, en internationalisant complètement `NotificationBell`, et en ajoutant un flux de connexion avec `remember_me` cohérent entre API, cookies et persistance frontend.

## Existing Context

- Le backend Laravel 12 utilise Sanctum avec access token 15 minutes et refresh token stocké en base, déjà rotatif via `/auth/refresh`.
- Le frontend Next.js utilise Zustand persistant pour l’authentification, une couche i18n maison, et des composants shadcn/radix.
- La phase 21 a introduit la page `/profile`, le menu utilisateur et les notifications de layout, ce qui explique les deux régressions UX ciblées ici.

## Design Decisions

### 1. Remember Me côté backend

- Étendre `LoginRequest` avec `remember_me?: boolean`.
- Laisser l’access token inchangé à 15 minutes.
- Faire varier l’expiration du refresh token à l’émission:
  - `remember_me=true` → 30 jours
  - `remember_me=false` → 24 heures

### 2. Remember Me côté frontend

- Ajouter une case à cocher pré-cochée sur la page de login.
- Envoyer `remember_me` dans `POST /auth/login`.
- Faire porter la logique cookie/persistance au store auth, pas à la page:
  - cookie refresh persistant (`max-age=2592000`) si `rememberMe=true`
  - cookie de session sans `max-age` sinon
  - persistance Zustand en `localStorage` si `rememberMe=true`, sinon en `sessionStorage`

### 3. Correctifs UX ciblés

- Ajouter `cursor-pointer` au composant `Button` pour corriger proprement l’ensemble des boutons icon ghost concernés sans multiplier les surcharges locales.
- Retirer le wrapper `DashboardLayout` de `/profile` pour éviter le double shell.

### 4. Internationalisation

- Ajouter un namespace top-level `notifications.*` dans `messages.ts`.
- Corriger le wording login:
  - FR: `Connexion`
  - EN: `Sign in`
- Ajouter `auth.login.rememberMe` dans les deux locales.

## Testing Strategy

- Backend TDD avec un test Pest dédié sur les deux expirations `remember_me`.
- Frontend TDD avec couverture ciblée sur:
  - affordance `cursor-pointer`
  - libellés de `NotificationBell`
  - checkbox login + payload `remember_me`
  - cookies auth `rememberMe`

## Risks

- Le store auth persisté en `localStorage` contredisait le besoin “déconnexion à la fermeture du navigateur” si `remember_me=false`; la correction devait couvrir cookies et storage applicatif.
- Les composants Radix nécessitent quelques ajustements de harness en test pour rester focalisés sur le comportement métier plutôt que sur les détails d’implémentation du portail/menu.
