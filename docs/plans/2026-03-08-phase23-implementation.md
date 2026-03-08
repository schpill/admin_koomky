# Phase 23 Implementation Plan

**Goal:** Livrer toute la phase 23 en autonomie complète avec TDD pragmatique, validation build/lint/tests et préparation PR.

## Task 1: Infrastructure and saved artifacts

**Files:**
- Create: `docs/plans/2026-03-08-phase23-design.md`
- Create: `docs/plans/2026-03-08-phase23-implementation.md`
- Modify: `frontend/package.json`
- Modify: `frontend/next.config.ts`

**Steps:**
1. Installer la stack MDX/outillage docs.
2. Activer MDX dans Next et préparer les scripts de génération.
3. Sauvegarder le design et le plan sur disque.

## Task 2: Test-first docs contracts

**Files:**
- Create: `frontend/tests/components/docs/doc-callout.test.tsx`
- Create: `frontend/tests/components/docs/doc-screenshot.test.tsx`
- Create: `frontend/tests/components/docs/doc-diagram.test.tsx`
- Create: `frontend/tests/components/docs/doc-sidebar.test.tsx`
- Create: `frontend/tests/pages/docs/docs-home.test.tsx`
- Create: `frontend/tests/unit/docs/build-search-index.test.ts`

**Steps:**
1. Poser les tests rouges sur les primitives docs et l'indexeur.
2. Vérifier les échecs ciblés avant implémentation.

## Task 3: Implement docs platform

**Files:**
- Create: `frontend/lib/docs/*`
- Create: `frontend/components/docs/*`
- Create: `frontend/mdx-components.tsx`
- Create: `frontend/app/(dashboard)/docs/**/*`
- Modify: `frontend/components/layout/sidebar.tsx`
- Modify: `frontend/components/search/command-palette.tsx`
- Modify: `frontend/lib/i18n/messages.ts`

**Steps:**
1. Créer le registre central docs.
2. Implémenter les composants docs et le layout dédié.
3. Brancher la navigation globale et la palette.
4. Créer le contenu MDX et les assets placeholders.

## Task 4: Scripts and bookkeeping

**Files:**
- Create: `frontend/scripts/docs/*`
- Create: `frontend/content/docs/**/*`
- Modify: `Makefile`
- Modify: `docs/dev/phase23.md`

**Steps:**
1. Générer l'index de recherche.
2. Ajouter les scripts Gemini et screenshots.
3. Mettre à jour le suivi de phase.

## Task 5: Validate and finish

**Steps:**
1. Exécuter tests ciblés puis vérifications frontend plus larges.
2. Réaliser une revue adversariale.
3. Corriger les écarts restants.
4. Préparer commit et PR si l'état vérifié le permet.
