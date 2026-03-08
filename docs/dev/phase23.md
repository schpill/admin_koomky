# Phase 23 — Task Tracking

> **Status**: todo
> **Prerequisite**: Phase 22 fully merged and tagged `v2.8.0`
> **Spec**: [docs/phases/phase23.md](../phases/phase23.md)

---

## Sprint 75 — Infrastructure technique (Weeks 203–204)

### Packages

| ID          | Task                                                                                                                    | Status | Owner |
|-------------|-------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P23-INF-000 | `pnpm add @next/mdx @mdx-js/react remark-gfm rehype-highlight mermaid && pnpm add -D @types/mdx tsx`                   | todo   |       |

### Infrastructure Tasks

| ID          | Task                                                                                                                                                                  | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P23-INF-001 | `next.config.ts` — `withMDX` + plugins remark/rehype + `pageExtensions: ['ts','tsx','mdx']`.                                                                         | todo   |       |
| P23-INF-002 | `frontend/mdx-components.tsx` — `useMDXComponents()` avec tous les composants docs custom.                                                                           | todo   |       |
| P23-INF-003 | `components/docs/doc-layout.tsx` — 3 colonnes : DocSidebar + contenu MDX + DocToc.                                                                                   | todo   |       |
| P23-INF-004 | `components/docs/doc-sidebar.tsx` — navigation arborescente 22 modules, config `DOC_NAV`, lien actif.                                                                | todo   |       |
| P23-INF-005 | `components/docs/doc-toc.tsx` — extraction h2/h3, liens sticky, scroll-spy.                                                                                          | todo   |       |
| P23-INF-006 | `components/docs/doc-callout.tsx` — variantes tip/warning/danger/info + icônes Lucide.                                                                               | todo   |       |
| P23-INF-007 | `components/docs/doc-screenshot.tsx` — figure + figcaption + Dialog fullscreen shadcn/ui.                                                                            | todo   |       |
| P23-INF-008 | `components/docs/doc-diagram.tsx` — `'use client'`, import dynamique `mermaid`, fetch `.mmd`, `mermaid.render()`, affichage SVG.                                    | todo   |       |
| P23-INF-009 | `components/docs/doc-infographic.tsx` — figure + img SVG + caption.                                                                                                  | todo   |       |
| P23-INF-010 | `components/docs/doc-steps.tsx` — ol stylisé avec cercles numérotés Tailwind.                                                                                        | todo   |       |
| P23-INF-011 | `components/docs/doc-badge.tsx` — badge inline, variantes de couleur.                                                                                                | todo   |       |
| P23-INF-012 | `app/(dashboard)/docs/layout.tsx` — utilise `DocLayout`, hérite du shell parent.                                                                                     | todo   |       |
| P23-INF-013 | `app/(dashboard)/docs/page.tsx` — page d'accueil : grille de 22 cards modules (icône + titre + description + lien).                                                  | todo   |       |
| P23-INF-014 | `app/(dashboard)/docs/[[...slug]]/page.tsx` — résolution slug → MDX, import dynamique, rendu, 404 si inexistant.                                                     | todo   |       |
| P23-INF-015 | `components/layout/sidebar.tsx` — ajouter entrée "Documentation" (icône `BookOpen`, lien `/docs`) avant "Paramètres".                                                | todo   |       |
| P23-INF-016 | Créer `content/docs/` — arborescence complète des 22 répertoires modules + fichiers `index.mdx` placeholders.                                                        | todo   |       |

### Recherche docs

| ID          | Task                                                                                                                        | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P23-INF-017 | `scripts/docs/build-search-index.mts` — lire MDX, extraire frontmatter + texte brut, écrire `public/docs/search-index.json`. | todo   |       |
| P23-INF-018 | `package.json` — ajouter `"prebuild": "tsx scripts/docs/build-search-index.mts"`.                                          | todo   |       |
| P23-INF-019 | `components/search/command-palette.tsx` — groupe "Documentation" depuis `search-index.json`, icône `BookOpen`.              | todo   |       |

---

## Sprint 76 — Script Gemini + Script Screenshots (Weeks 205–206)

### Script Gemini

| ID          | Task                                                                                                                                                   | Status | Owner |
|-------------|--------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P23-GEM-001 | `content/docs/diagrams.config.ts` — 30+ définitions de diagrammes/infographies (module, type mermaid/svg, outputFile, prompt Gemini).                  | todo   |       |
| P23-GEM-002 | `scripts/docs/generate-diagrams.mts` — script Node.js/TS : lit config, appelle Gemini API (fetch direct + GEMINI_API_KEY), écrit les outputs, retry x2, rapport. | todo   |       |
| P23-GEM-003 | `package.json` — script `"docs:diagrams": "tsx scripts/docs/generate-diagrams.mts"`.                                                                   | todo   |       |
| P23-GEM-004 | `Makefile` — targets `docs-diagrams` et `docs-screenshots`.                                                                                            | todo   |       |
| P23-GEM-005 | Exécuter `make docs-diagrams` — générer l'ensemble des 30+ fichiers, valider qualité visuelle.                                                         | todo   |       |

### Script Screenshots Playwright

| ID          | Task                                                                                                                                          | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P23-SCR-001 | `scripts/docs/capture-screenshots.ts` — Playwright : login, navigation, screenshot par page clé, stockage `public/docs/screenshots/[module]/`. | todo   |       |
| P23-SCR-002 | `.env.example` — ajouter `DOCS_SCREENSHOT_EMAIL` + `DOCS_SCREENSHOT_PASSWORD`.                                                               | todo   |       |
| P23-SCR-003 | `package.json` — script `"docs:screenshots": "playwright --config=playwright.docs.config.ts scripts/docs/capture-screenshots.ts"`.            | todo   |       |
| P23-SCR-004 | Exécuter le script — valider les captures pour les 22 modules (≥ 3 screenshots par module).                                                   | todo   |       |

---

## Sprint 77 — Contenu modules core (Weeks 207–208)

| ID          | Article                             | Fichier MDX                                 | Diagramme                      | Infographie                  | Status | Owner |
|-------------|-------------------------------------|---------------------------------------------|--------------------------------|------------------------------|--------|-------|
| P23-DOC-001 | Démarrage rapide                    | `content/docs/getting-started/index.mdx`    | Flowchart "premiers pas"       | —                            | todo   |       |
| P23-DOC-002 | Tableau de bord                     | `content/docs/dashboard/index.mdx`          | —                              | `dashboard-widgets-map.svg`  | todo   |       |
| P23-DOC-003 | Clients                             | `content/docs/clients/index.mdx`            | —                              | —                            | todo   |       |
| P23-DOC-004 | Prospects & Leads                   | `content/docs/leads/index.mdx`              | `leads-conversion-flow.mmd`    | —                            | todo   |       |
| P23-DOC-005 | Factures (+ sous-page Cycle de vie) | `content/docs/invoices/index.mdx` + `lifecycle.mdx` | `invoices-lifecycle.mmd` | `invoices-overview.svg`  | todo   |       |
| P23-DOC-006 | Devis                               | `content/docs/quotes/index.mdx`             | `quotes-acceptance-flow.mmd`   | —                            | todo   |       |
| P23-DOC-007 | Avoirs                              | `content/docs/credit-notes/index.mdx`       | —                              | —                            | todo   |       |
| P23-DOC-008 | Dépenses                            | `content/docs/expenses/index.mdx`           | —                              | —                            | todo   |       |
| P23-DOC-009 | Projets                             | `content/docs/projects/index.mdx`           | —                              | —                            | todo   |       |
| P23-DOC-010 | Calendrier                          | `content/docs/calendar/index.mdx`           | —                              | —                            | todo   |       |

---

## Sprint 78 — Contenu modules email & scoring (Weeks 209–210)

| ID          | Article                             | Fichier MDX                                              | Diagramme                       | Infographie                      | Status | Owner |
|-------------|-------------------------------------|----------------------------------------------------------|---------------------------------|----------------------------------|--------|-------|
| P23-DOC-011 | Campagnes email (+ A/B Testing)     | `content/docs/campaigns/index.mdx` + `ab-testing.mdx`   | `campaigns-sending-flow.mmd`    | `campaigns-funnel.svg`           | todo   |       |
| P23-DOC-012 | Drip campaigns                      | `content/docs/drip/index.mdx`                            | `drip-enrollment-flow.mmd`      | `drip-sequence-visual.svg`       | todo   |       |
| P23-DOC-013 | Workflows automatisés               | `content/docs/workflows/index.mdx`                       | `workflow-node-types.mmd`       | `workflow-graph-example.svg`     | todo   |       |
| P23-DOC-014 | Liste de suppression                | `content/docs/suppression/index.mdx`                     | —                               | —                                | todo   |       |
| P23-DOC-015 | Warm-up IP                          | `content/docs/warmup/index.mdx`                          | `warmup-plan-flow.mmd`          | —                                | todo   |       |
| P23-DOC-016 | Scoring leads                       | `content/docs/scoring/index.mdx`                         | —                               | `scoring-rules-overview.svg`     | todo   |       |

---

## Sprint 79 — Contenu modules support, RAG & portail (Weeks 211–212)

| ID          | Article                                  | Fichier MDX                                         | Diagramme                       | Infographie                   | Status | Owner |
|-------------|------------------------------------------|-----------------------------------------------------|---------------------------------|-------------------------------|--------|-------|
| P23-DOC-017 | Documents (GED)                          | `content/docs/documents/index.mdx`                  | —                               | —                             | todo   |       |
| P23-DOC-018 | Tickets support                          | `content/docs/tickets/index.mdx`                    | —                               | —                             | todo   |       |
| P23-DOC-019 | Intelligence documentaire (+ MCP)        | `content/docs/rag/index.mdx` + `mcp.mdx`            | `rag-pipeline.mmd`              | `rag-architecture.svg`        | todo   |       |
| P23-DOC-020 | Portail client                           | `content/docs/portal/index.mdx`                     | `portal-payment-flow.mmd`       | `portal-access-flow.svg`      | todo   |       |
| P23-DOC-021 | Relances automatiques                    | `content/docs/reminders/index.mdx`                  | `reminders-lifecycle.mmd`       | —                             | todo   |       |
| P23-DOC-022 | Paramètres                               | `content/docs/settings/index.mdx`                   | —                               | —                             | todo   |       |

---

## Sprint 80 — Polish, tests, tag v2.9.0 (Weeks 213–214)

### Polish & cohérence

| ID          | Task                                                                                                                      | Status | Owner |
|-------------|---------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P23-POL-001 | Relecture complète 22 articles — vocabulaire, orthographe, complétude template standard.                                  | todo   |       |
| P23-POL-002 | Vérifier : ≥ 1 `<DocScreenshot>`, ≥ 1 `<DocCallout>`, ≥ 1 FAQ par article.                                              | todo   |       |
| P23-POL-003 | Page `/docs` home — grille finale avec descriptions soignées.                                                            | todo   |       |
| P23-POL-004 | `DocSidebar` — vérifier tous les slugs, navigation complète.                                                              | todo   |       |
| P23-POL-005 | ESLint + Prettier + PHPStan + Pint — 0 erreur.                                                                           | todo   |       |

### Frontend Tests

| ID          | Test File                                                                                                                 | Status | Owner |
|-------------|---------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P23-FT-001  | `tests/components/docs/doc-callout.test.tsx` — variantes tip/warning/danger/info.                                        | todo   |       |
| P23-FT-002  | `tests/components/docs/doc-screenshot.test.tsx` — rendu + dialog fullscreen.                                             | todo   |       |
| P23-FT-003  | `tests/components/docs/doc-diagram.test.tsx` — fetch mmd + rendu SVG (mock mermaid).                                    | todo   |       |
| P23-FT-004  | `tests/components/docs/doc-sidebar.test.tsx` — 22 liens présents, lien actif.                                            | todo   |       |
| P23-FT-005  | `tests/pages/docs/docs-home.test.tsx` — 22 cards modules rendues.                                                        | todo   |       |
| P23-FT-006  | `tests/unit/docs/build-search-index.test.ts` — index contient tous les modules avec title/slug/description.              | todo   |       |

---

## Récapitulatif

| Sprint    | Semaines  | Livrable principal                                              | Tasks                                       |
|-----------|-----------|-----------------------------------------------------------------|---------------------------------------------|
| Sprint 75 | 203–204   | Infrastructure MDX, routing, composants, sidebar               | 20 INF                                      |
| Sprint 76 | 205–206   | Script Gemini (30+ diagrammes/infographies) + screenshots       | 5 GEM + 4 SCR                               |
| Sprint 77 | 207–208   | Articles modules core (getting-started → calendrier)           | 10 DOC                                      |
| Sprint 78 | 209–210   | Articles modules email & scoring                               | 6 DOC                                       |
| Sprint 79 | 211–212   | Articles modules support, RAG, portail, paramètres             | 6 DOC                                       |
| Sprint 80 | 213–214   | Polish, tests, tag v2.9.0                                      | 5 POL + 6 tests                             |
| **Total** | **12 sem**| **v2.9.0 — 22 articles + infrastructure complète**             | **~62 tâches**                              |
