# Phase 23 — Module Documentation intégrée (v2.9)

| Field               | Value                                               |
|---------------------|-----------------------------------------------------|
| **Phase**           | 23                                                  |
| **Name**            | Module Documentation intégrée                       |
| **Duration**        | Weeks 203–214 (6 sprints, 12 semaines)              |
| **Milestone**       | M23 — v2.9.0 Release                                |
| **PRD Sections**    | §4.40 FR-DOCS (nouveau)                             |
| **Prerequisite**    | Phase 22 fully completed and tagged `v2.8.0`        |
| **Status**          | merged                                              |

---

## 1. Phase Objectives

| ID        | Objectif                                                                                                                           |
|-----------|------------------------------------------------------------------------------------------------------------------------------------|
| P23-OBJ-1 | Créer un module de documentation intégré à l'application, accessible depuis la sidebar                                             |
| P23-OBJ-2 | Documenter exhaustivement les 20+ modules du CRM avec textes éducatifs, captures d'écran, diagrammes et infographies              |
| P23-OBJ-3 | Générer automatiquement diagrammes de flux et infographies via Gemini API (GEMINI_API_KEY)                                         |
| P23-OBJ-4 | Capturer les screenshots clés de l'application via un script Playwright automatisé                                                 |
| P23-OBJ-5 | Intégrer la recherche dans les docs depuis la CommandPalette existante                                                             |
| P23-OBJ-6 | Maintenir une couverture de tests >= 80% backend et frontend                                                                       |

---

## 2. Contexte & Analyse de l'existant

### 2.1 Modules à documenter

| # | Module                       | Slug docs               | Complexité |
|---|------------------------------|-------------------------|------------|
| 1 | Démarrage rapide             | `getting-started`       | Faible     |
| 2 | Tableau de bord              | `dashboard`             | Faible     |
| 3 | Clients                      | `clients`               | Moyenne    |
| 4 | Prospects & Leads            | `leads`                 | Moyenne    |
| 5 | Factures                     | `invoices`              | Haute      |
| 6 | Devis                        | `quotes`                | Moyenne    |
| 7 | Avoirs                       | `credit-notes`          | Faible     |
| 8 | Dépenses                     | `expenses`              | Moyenne    |
| 9 | Projets                      | `projects`              | Moyenne    |
| 10| Calendrier                   | `calendar`              | Moyenne    |
| 11| Campagnes email              | `campaigns`             | Haute      |
| 12| Drip campaigns               | `drip`                  | Haute      |
| 13| Workflows automatisés        | `workflows`             | Haute      |
| 14| Liste de suppression         | `suppression`           | Faible     |
| 15| Documents (GED)              | `documents`             | Moyenne    |
| 16| Tickets support              | `tickets`               | Moyenne    |
| 17| Intelligence documentaire    | `rag`                   | Haute      |
| 18| Portail client               | `portal`                | Moyenne    |
| 19| Relances automatiques        | `reminders`             | Moyenne    |
| 20| Warm-up IP                   | `warmup`                | Faible     |
| 21| Scoring leads                | `scoring`               | Moyenne    |
| 22| Paramètres                   | `settings`              | Moyenne    |

### 2.2 Briques existantes réutilisables

| Brique                   | Localisation                                 | Phase |
|--------------------------|----------------------------------------------|-------|
| `GeminiService::generate()` | `backend/app/Services/GeminiService.php`   | 10    |
| Config Gemini            | `config/services.php` + `GEMINI_API_KEY` env | 10    |
| CommandPalette           | `components/search/command-palette.tsx`      | 1     |
| Sidebar principale       | `components/layout/sidebar.tsx`              | 1     |
| `app/(dashboard)/layout.tsx` | Shell Next.js dashboard                  | 1     |
| Playwright               | `frontend/e2e/`, `@playwright/test`          | 7     |

### 2.3 Gaps techniques à combler

| Gap                             | Solution Phase 23                                                      |
|---------------------------------|------------------------------------------------------------------------|
| Pas de moteur MDX               | Installer `@next/mdx` + `@mdx-js/react` + plugins remark/rehype       |
| Pas de rendu Mermaid côté client| Installer `mermaid` (client component `<DocDiagram>`)                  |
| Pas de génération diagrammes    | Script Node.js `scripts/docs/generate-diagrams.mts` → appelle Gemini  |
| Pas de génération infographies  | Même script, prompt SVG → `public/docs/infographics/`                  |
| Pas de captures d'écran auto    | Script Playwright `scripts/docs/capture-screenshots.ts`                |
| Pas de recherche dans les docs  | Index JSON statique généré au build + extension CommandPalette         |

---

## 3. Choix techniques

### 3.1 Rendu MDX

**Stack** : `@next/mdx` + `@mdx-js/react` + `remark-gfm` + `rehype-highlight`

```
frontend/
  content/
    docs/
      getting-started/
        index.mdx
      dashboard/
        index.mdx
      invoices/
        index.mdx
        lifecycle.mdx      ← sous-pages possibles
      ...
  public/
    docs/
      screenshots/
        dashboard/
          overview.png
        invoices/
          list.png
          create.png
      diagrams/            ← Mermaid générés par Gemini
        invoices-lifecycle.mmd
        campaigns-workflow.mmd
      infographics/        ← SVG générés par Gemini
        invoices-overview.svg
        campaigns-funnel.svg
```

`next.config.ts` : activer `withMDX` + définir les composants MDX custom dans `mdx-components.tsx`.

### 3.2 Composants MDX custom

Tous créés dans `components/docs/` :

| Composant              | Rôle                                                                      |
|------------------------|---------------------------------------------------------------------------|
| `DocLayout`            | Layout spécifique docs : sidebar docs + fil d'ariane + table des matières |
| `DocSidebar`           | Navigation arborescente par module (collapsible sections)                 |
| `DocToc`               | Table des matières auto-générée depuis les titres h2/h3 de la page        |
| `DocCallout`           | Bloc tip/warning/info/danger stylisé (variante `type`)                    |
| `DocScreenshot`        | Image avec ombre, caption, lien vers zoom fullscreen                      |
| `DocDiagram`           | Rendu Mermaid client-side via `mermaid.js` depuis fichier `.mmd` public   |
| `DocInfographic`       | `<img>` vers SVG dans `public/docs/infographics/` avec caption            |
| `DocSteps`             | Numérotation pas-à-pas stylisée                                           |
| `DocBadge`             | Badge coloré inline (statuts, priorités)                                  |

### 3.3 Routing

```
app/(dashboard)/docs/
  layout.tsx               ← DocLayout (sidebar docs + TOC)
  page.tsx                 ← Page d'accueil docs (index des modules)
  [[...slug]]/
    page.tsx               ← Rendu dynamique du fichier MDX correspondant au slug
```

Le `[[...slug]]` mappe :
- `/docs` → `content/docs/index.mdx`
- `/docs/invoices` → `content/docs/invoices/index.mdx`
- `/docs/invoices/lifecycle` → `content/docs/invoices/lifecycle.mdx`

### 3.4 Génération diagrammes & infographies via Gemini

**Script** : `frontend/scripts/docs/generate-diagrams.mts`

**Entrée** : `frontend/content/docs/diagrams.config.ts` — tableau de définitions :

```ts
export const diagramDefinitions = [
  {
    id: "invoices-lifecycle",
    type: "mermaid",
    outputFile: "public/docs/diagrams/invoices-lifecycle.mmd",
    prompt: `Génère un diagramme Mermaid stateDiagram-v2 représentant le cycle de vie
complet d'une facture dans un CRM freelance. États : Brouillon, Envoyée, Partiellement payée,
Payée, En retard, Annulée. Inclus les transitions et les événements déclencheurs.
Réponds UNIQUEMENT avec le code Mermaid, sans balises markdown.`,
  },
  {
    id: "campaigns-funnel",
    type: "svg",
    outputFile: "public/docs/infographics/campaigns-funnel.svg",
    prompt: `Génère un SVG d'infographie représentant un entonnoir de campagne email :
Envoyés → Livrés → Ouverts → Cliqués → Convertis.
Utilise des couleurs dégradées du bleu au vert. Dimensions 800x500px.
Inclus des pourcentages typiques (100%, 95%, 35%, 15%, 5%).
Réponds UNIQUEMENT avec le code SVG complet, sans balises markdown.`,
  },
  // ... une définition par diagramme/infographie
]
```

**Fonctionnement du script** :
1. Lire `diagrams.config.ts`
2. Pour chaque définition, appeler `POST https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=${GEMINI_API_KEY}`
3. Écrire le résultat dans `outputFile`
4. Loguer le statut (✓ / ✗)

**Makefile target** :
```makefile
docs-diagrams:
	cd frontend && GEMINI_API_KEY=$(GEMINI_API_KEY) pnpm docs:diagrams

docs-screenshots:
	$(DOCKER_COMPOSE) exec frontend pnpm docs:screenshots
```

**Variables d'environnement** : `GEMINI_API_KEY` — déjà utilisé par le backend, à exposer aussi dans le `.env` racine pour le script frontend.

### 3.5 Captures d'écran via Playwright

**Script** : `frontend/scripts/docs/capture-screenshots.ts`

Stratégie :
1. Se connecte à l'app (login avec `DOCS_SCREENSHOT_EMAIL` + `DOCS_SCREENSHOT_PASSWORD`)
2. Navigue sur chaque page clé
3. Prend un screenshot PNG (`page.screenshot({ path, fullPage: false })`)
4. Crop optionnel sur un sélecteur CSS
5. Stocke dans `public/docs/screenshots/[module]/[slug].png`

Pages à capturer : au minimum 3–5 captures par module (liste, détail, formulaire, état vide, diagramme/chart).

Ce script **n'est pas dans la CI** — il s'exécute en local avec `make docs-screenshots`.

### 3.6 Recherche dans les docs

À la génération du build Next.js, un script `prebuild` génère `public/docs/search-index.json` :
```json
[
  { "slug": "invoices", "title": "Factures", "description": "...", "content": "..." },
  ...
]
```

La `CommandPalette` existante est étendue pour chercher dans cet index via un groupe `Documentation` (résultats avec icône `BookOpen`, lien vers `/docs/[slug]`).

### 3.7 Template type d'article MDX

Chaque article MDX suit cette structure standard :

```mdx
---
title: "Titre du module"
description: "Résumé en une phrase"
module: "slug"
---

## Vue d'ensemble

[Paragraphe introductif : qu'est-ce que ce module, à quoi il sert, quand l'utiliser]

## Concepts clés

[Tableau ou liste à puces des termes importants avec définitions]

## Fonctionnalités principales

[Liste des fonctionnalités avec description courte de chacune]

## Guide pas à pas

<DocSteps>
1. **Étape 1 : ...**
   [Description + screenshot]
   <DocScreenshot src="/docs/screenshots/[module]/step1.png" alt="..." caption="..." />

2. **Étape 2 : ...**
   ...
</DocSteps>

## Diagrammes

<DocDiagram src="/docs/diagrams/[module]-workflow.mmd" title="Flux [module]" />

## Infographie

<DocInfographic src="/docs/infographics/[module]-overview.svg" title="Vue d'ensemble [module]" />

## Cas d'utilisation courants

[2–4 scénarios concrets type "En tant que freelance, je veux..."]

## Astuces & bonnes pratiques

<DocCallout type="tip">
[Conseil pratique]
</DocCallout>

## Limitations & points d'attention

<DocCallout type="warning">
[Ce que le module ne fait pas / pièges courants]
</DocCallout>

## Questions fréquentes

**Q : ...**
R : ...
```

---

## 4. Entry Criteria

- Phase 22 exit criteria 100% satisfaits.
- CI verts sur `main`.
- `v2.8.0` tagué.

---

## 5. Scope — Requirement Traceability

| Feature                                                | Priority | Included |
|--------------------------------------------------------|----------|----------|
| Infrastructure MDX + routing `/docs`                   | High     | Yes      |
| Composants docs (DocLayout, DocSidebar, DocDiagram…)  | High     | Yes      |
| Script génération diagrammes Mermaid via Gemini        | High     | Yes      |
| Script génération infographies SVG via Gemini          | High     | Yes      |
| Script Playwright screenshots                          | High     | Yes      |
| Entrée "Documentation" dans la sidebar principale      | High     | Yes      |
| Recherche docs dans CommandPalette                     | Medium   | Yes      |
| Article : Démarrage rapide                             | High     | Yes      |
| Article : Tableau de bord                             | High     | Yes      |
| Article : Clients                                      | High     | Yes      |
| Article : Prospects & Leads                            | High     | Yes      |
| Article : Factures                                     | High     | Yes      |
| Article : Devis                                        | High     | Yes      |
| Article : Avoirs                                       | Medium   | Yes      |
| Article : Dépenses                                     | High     | Yes      |
| Article : Projets                                      | High     | Yes      |
| Article : Calendrier                                   | Medium   | Yes      |
| Article : Campagnes email                              | High     | Yes      |
| Article : Drip campaigns                               | High     | Yes      |
| Article : Workflows automatisés                        | High     | Yes      |
| Article : Liste de suppression                         | Medium   | Yes      |
| Article : Documents (GED)                              | High     | Yes      |
| Article : Tickets support                              | High     | Yes      |
| Article : Intelligence documentaire (RAG)              | High     | Yes      |
| Article : Portail client                               | High     | Yes      |
| Article : Relances automatiques                        | Medium   | Yes      |
| Article : Warm-up IP                                   | Medium   | Yes      |
| Article : Scoring leads                                | Medium   | Yes      |
| Article : Paramètres                                   | Medium   | Yes      |
| Versioning des docs                                    | Low      | No       |
| Internationalisation des articles (EN)                 | Low      | No       |
| Commentaires / feedback sur les articles               | Low      | No       |

---

## 6. Detailed Sprint Breakdown

### 6.1 Sprint 75 — Infrastructure technique (Weeks 203–204)

#### 6.1.1 Packages à installer

```bash
# MDX
pnpm add @next/mdx @mdx-js/react remark-gfm rehype-highlight

# Mermaid (client-side)
pnpm add mermaid

# Types
pnpm add -D @types/mdx
```

#### 6.1.2 Frontend Infrastructure Tasks

| ID          | Task                                                                                                                                                           |
|-------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------|
| P23-INF-001 | `next.config.ts` — envelopper avec `withMDX({ options: { remarkPlugins: [remarkGfm], rehypePlugins: [rehypeHighlight] } })`. Ajouter `pageExtensions: ['ts','tsx','mdx']`. |
| P23-INF-002 | Créer `frontend/mdx-components.tsx` — exporter `useMDXComponents()` avec les composants custom (`DocCallout`, `DocScreenshot`, `DocDiagram`, `DocInfographic`, `DocSteps`, `DocBadge`). |
| P23-INF-003 | Créer `components/docs/doc-layout.tsx` — layout 3 colonnes : `DocSidebar` (gauche, collapsible), contenu MDX (centre, `max-w-3xl`), `DocToc` (droite, `sticky`). |
| P23-INF-004 | Créer `components/docs/doc-sidebar.tsx` — navigation arborescente des 22 modules. Config statique `DOC_NAV` (array of `{ title, slug, children? }`). Mise en surbrillance du lien actif. |
| P23-INF-005 | Créer `components/docs/doc-toc.tsx` — extraire les headings h2/h3 du DOM via `useEffect`, afficher une liste de liens sticky, avec scroll-spy. |
| P23-INF-006 | Créer `components/docs/doc-callout.tsx` — variantes `tip` (bleu), `warning` (orange), `danger` (rouge), `info` (gris). Icônes Lucide correspondantes. |
| P23-INF-007 | Créer `components/docs/doc-screenshot.tsx` — `<figure>` avec `<img>`, ombre, `<figcaption>`, clic → dialog fullscreen (shadcn/ui `Dialog`). |
| P23-INF-008 | Créer `components/docs/doc-diagram.tsx` — composant `'use client'` qui importe `mermaid` dynamiquement, lit le fichier `.mmd` via fetch, initialise `mermaid.render()`, affiche le SVG résultant. |
| P23-INF-009 | Créer `components/docs/doc-infographic.tsx` — `<figure>` avec `<img src={src}>` (SVG), titre, caption, ombre. |
| P23-INF-010 | Créer `components/docs/doc-steps.tsx` — enumère ses enfants en les enveloppant dans un `<ol>` stylisé avec des cercles numérotés Tailwind. |
| P23-INF-011 | Créer `components/docs/doc-badge.tsx` — badge inline avec variantes de couleur. |
| P23-INF-012 | Créer `app/(dashboard)/docs/layout.tsx` — utilise `DocLayout`. Pas de double shell (hérite de `(dashboard)/layout.tsx`). |
| P23-INF-013 | Créer `app/(dashboard)/docs/page.tsx` — page d'accueil docs : titre "Documentation Koomky", grille de cards par module (icône + titre + description + lien). |
| P23-INF-014 | Créer `app/(dashboard)/docs/[[...slug]]/page.tsx` — résoudre `params.slug` → chemin MDX dans `content/docs/`, importer dynamiquement, rendre via `next/mdx`. Gérer 404 si fichier inexistant. |
| P23-INF-015 | `components/layout/sidebar.tsx` — ajouter l'entrée "Documentation" avec icône `BookOpen` avant "Paramètres". Lien vers `/docs`. |
| P23-INF-016 | Créer `content/docs/` — structure de répertoires vides pour les 22 modules + fichier `index.mdx` placeholder par module. |

#### 6.1.3 Recherche dans les docs

| ID          | Task                                                                                                                                                             |
|-------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| P23-INF-017 | Créer `scripts/docs/build-search-index.mts` — lire tous les `.mdx` de `content/docs/`, extraire `frontmatter` (title, description, module) + texte brut (strip MDX), écrire `public/docs/search-index.json`. |
| P23-INF-018 | `package.json` — ajouter `"prebuild": "tsx scripts/docs/build-search-index.mts"` pour générer l'index avant chaque build. |
| P23-INF-019 | `components/search/command-palette.tsx` — charger `search-index.json` au montage, ajouter un groupe "Documentation" avec résultats filtrés. Icône `BookOpen`. |

---

### 6.2 Sprint 76 — Script Gemini + Script Screenshots (Weeks 205–206)

#### 6.2.1 Script génération diagrammes & infographies

| ID          | Task                                                                                                                                                                             |
|-------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| P23-GEM-001 | Créer `frontend/content/docs/diagrams.config.ts` — tableau exhaustif de 30+ définitions (une entrée par diagramme/infographie souhaité pour tous les modules complexes). Voir §3.4. |
| P23-GEM-002 | Créer `frontend/scripts/docs/generate-diagrams.mts` — script Node.js/TypeScript : lire `diagrams.config.ts`, appeler Gemini API (`fetch` direct), écrire les fichiers résultants, afficher un rapport. Gestion d'erreurs robuste (retry x2, délai 2s). |
| P23-GEM-003 | `package.json` — ajouter script `"docs:diagrams": "tsx scripts/docs/generate-diagrams.mts"`. |
| P23-GEM-004 | `Makefile` — ajouter targets `docs-diagrams` et `docs-screenshots` (voir §3.4). |
| P23-GEM-005 | Générer l'ensemble des diagrammes et infographies pour les 22 modules (exécuter `make docs-diagrams`). Vérifier qualité visuelle de chaque output. |

**Diagrammes Mermaid planifiés** (type `stateDiagram-v2`, `flowchart`, `sequenceDiagram`) :

| Fichier                              | Module       | Type                    |
|--------------------------------------|--------------|-------------------------|
| `invoices-lifecycle.mmd`             | Factures     | Cycle de vie (states)   |
| `quotes-acceptance-flow.mmd`         | Devis        | Flux acceptation portail|
| `campaigns-sending-flow.mmd`         | Campagnes    | Flux envoi              |
| `drip-enrollment-flow.mmd`           | Drip         | Séquence enrollment     |
| `workflow-node-types.mmd`            | Workflows    | Types de nœuds          |
| `rag-pipeline.mmd`                   | RAG          | Pipeline embedding→chat |
| `leads-conversion-flow.mmd`          | Leads        | Kanban → Client         |
| `portal-payment-flow.mmd`            | Portail      | Flux paiement Stripe    |
| `reminders-lifecycle.mmd`            | Relances     | Séquences lifecycle     |
| `warmup-plan-flow.mmd`               | Warm-up IP   | Progression quota       |

**Infographies SVG planifiées** :

| Fichier                              | Module       | Contenu                          |
|--------------------------------------|--------------|----------------------------------|
| `dashboard-widgets-map.svg`          | Dashboard    | Carte des widgets                |
| `invoices-overview.svg`              | Factures     | Anatomie d'une facture           |
| `campaigns-funnel.svg`               | Campagnes    | Entonnoir email                  |
| `drip-sequence-visual.svg`           | Drip         | Visualisation séquence multi-step|
| `workflow-graph-example.svg`         | Workflows    | Exemple graphe visuel            |
| `scoring-rules-overview.svg`         | Scoring      | Grille de scoring                |
| `rag-architecture.svg`               | RAG          | Architecture complète RAG        |
| `portal-access-flow.svg`             | Portail      | Flux magic link → portal         |

#### 6.2.2 Script captures d'écran Playwright

| ID          | Task                                                                                                                                                                      |
|-------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| P23-SCR-001 | Créer `frontend/scripts/docs/capture-screenshots.ts` — script Playwright : login, navigation page par page, screenshot via `page.screenshot()`, stockage dans `public/docs/screenshots/[module]/[slug].png`. |
| P23-SCR-002 | Définir `DOCS_SCREENSHOT_EMAIL` + `DOCS_SCREENSHOT_PASSWORD` dans `.env` local (jamais commité). Ajouter ces variables à `.env.example`. |
| P23-SCR-003 | `package.json` — ajouter `"docs:screenshots": "playwright --config=playwright.docs.config.ts scripts/docs/capture-screenshots.ts"`. |
| P23-SCR-004 | Exécuter le script, vérifier et valider les captures pour les 22 modules (au moins 3 captures par module : liste, détail, formulaire). |

---

### 6.3 Sprint 77 — Contenu modules core (Weeks 207–208)

Chaque article suit le template §3.7 : vue d'ensemble, concepts clés, fonctionnalités, guide pas à pas (avec `<DocSteps>` + `<DocScreenshot>`), diagrammes, infographie, cas d'utilisation, FAQ.

| ID          | Article MDX                                           | Fichier                                    | Diagramme                         | Infographie                          |
|-------------|-------------------------------------------------------|--------------------------------------------|-----------------------------------|--------------------------------------|
| P23-DOC-001 | **Démarrage rapide** — Installation, premier login, navigation générale, créer son premier client+facture | `content/docs/getting-started/index.mdx` | Flowchart "premiers pas"          | —                                    |
| P23-DOC-002 | **Tableau de bord** — Widgets (MRR, factures impayées, tickets, campagnes), personnalisation, lecture des métriques | `content/docs/dashboard/index.mdx` | —                                 | `dashboard-widgets-map.svg`          |
| P23-DOC-003 | **Clients** — CRUD, fiche client complète, contacts associés, projets/factures liés, export | `content/docs/clients/index.mdx` | —                                 | —                                    |
| P23-DOC-004 | **Prospects & Leads** — Import XLSX, Kanban par stade, activités, scoring, conversion en client, webhook | `content/docs/leads/index.mdx` | `leads-conversion-flow.mmd`       | —                                    |
| P23-DOC-005 | **Factures** — Création, lignes de produit, multi-devise, récurrentes, envoi, paiement Stripe, PDF, relances, cycle de vie | `content/docs/invoices/index.mdx` + `lifecycle.mdx` | `invoices-lifecycle.mmd` | `invoices-overview.svg` |
| P23-DOC-006 | **Devis** — Création depuis facture ou scratch, personnalisation, envoi, acceptation/rejet portail, conversion facture | `content/docs/quotes/index.mdx` | `quotes-acceptance-flow.mmd`      | —                                    |
| P23-DOC-007 | **Avoirs** — Création depuis facture, types de remboursement, comptabilité | `content/docs/credit-notes/index.mdx` | —                                 | —                                    |
| P23-DOC-008 | **Dépenses** — Catégories, CRUD, reçus, import CSV, export, rentabilité projet, facturation | `content/docs/expenses/index.mdx` | —                                 | —                                    |
| P23-DOC-009 | **Projets** — Création, jalons, association client/factures, rentabilité, temps passé | `content/docs/projects/index.mdx` | —                                 | —                                    |
| P23-DOC-010 | **Calendrier** — Connexion Google/Outlook/Apple, synchronisation bidirectionnelle, événements auto (factures, projets) | `content/docs/calendar/index.mdx` | —                                 | —                                    |

---

### 6.4 Sprint 78 — Contenu modules email & scoring (Weeks 209–210)

| ID          | Article MDX                                           | Fichier                                     | Diagramme                           | Infographie                             |
|-------------|-------------------------------------------------------|---------------------------------------------|-------------------------------------|-----------------------------------------|
| P23-DOC-011 | **Campagnes email** — Création, éditeur HTML, A/B testing, STO, contenu dynamique `{{#if}}`, analytics (open/click/bounce), export PDF/CSV | `content/docs/campaigns/index.mdx` + `ab-testing.mdx` | `campaigns-sending-flow.mmd` | `campaigns-funnel.svg`         |
| P23-DOC-012 | **Drip campaigns** — Séquences multi-étapes, conditions comportementales (if_opened/if_clicked), enrollment, avancement automatique, analytics | `content/docs/drip/index.mdx` | `drip-enrollment-flow.mmd`          | `drip-sequence-visual.svg`              |
| P23-DOC-013 | **Workflows automatisés** — Éditeur visuel (`@xyflow/react`), 8 types de nœuds, 5 triggers, enrollments, cas d'usage (nurturing, re-engagement) | `content/docs/workflows/index.mdx` | `workflow-node-types.mmd`           | `workflow-graph-example.svg`            |
| P23-DOC-014 | **Liste de suppression** — Qu'est-ce qu'une suppression, hard/soft bounce, désabonnement, import/export CSV, règles automatiques | `content/docs/suppression/index.mdx` | —                                   | —                                       |
| P23-DOC-015 | **Warm-up IP** — Pourquoi le warm-up, plans de progression, quotas quotidiens, activation, reset | `content/docs/warmup/index.mdx` | `warmup-plan-flow.mmd`              | —                                       |
| P23-DOC-016 | **Scoring leads** — Système de points, règles par défaut (open +5, click +10, bounce -20…), seuils, filtrage par score dans segments, recalcul | `content/docs/scoring/index.mdx` | —                                   | `scoring-rules-overview.svg`            |

---

### 6.5 Sprint 79 — Contenu modules support, RAG & portail (Weeks 211–212)

| ID          | Article MDX                                           | Fichier                                     | Diagramme                        | Infographie                    |
|-------------|-------------------------------------------------------|---------------------------------------------|----------------------------------|--------------------------------|
| P23-DOC-017 | **Documents (GED)** — Upload, types détectés, quota, prévisualisation, envoi par email, recherche Scout, gestion par tags | `content/docs/documents/index.mdx` | —                                | —                              |
| P23-DOC-018 | **Tickets support** — Création, assignation, priorités, statuts (open/pending/resolved/closed), SLA, messages, pièces jointes | `content/docs/tickets/index.mdx` | —                                | —                              |
| P23-DOC-019 | **Intelligence documentaire (RAG)** — Upload docs, embedding Gemini, recherche sémantique, chat contextuel, MCP server, quota tokens | `content/docs/rag/index.mdx` + `mcp.mdx` | `rag-pipeline.mmd`              | `rag-architecture.svg`         |
| P23-DOC-020 | **Portail client** — Accès magic link, vue factures/devis, acceptation devis, paiement Stripe, préférences RGPD | `content/docs/portal/index.mdx` | `portal-payment-flow.mmd`        | `portal-access-flow.svg`       |
| P23-DOC-021 | **Relances automatiques** — Configuration des séquences, délais, templates email variables, cycle de vie par facture (pause/reprise/skip/annuler) | `content/docs/reminders/index.mdx` | `reminders-lifecycle.mmd`        | —                              |
| P23-DOC-022 | **Paramètres** — Profil, entreprise, 2FA, tokens API, webhooks, campagnes email (SES), scoring config, warm-up config | `content/docs/settings/index.mdx` | —                                | —                              |

---

### 6.6 Sprint 80 — Tests, polish, tag v2.9.0 (Weeks 213–214)

#### 6.6.1 Polish & cohérence

| ID          | Task                                                                                                                        |
|-------------|-----------------------------------------------------------------------------------------------------------------------------|
| P23-POL-001 | Relecture complète des 22 articles — vérifier cohérence du vocabulaire, orthographe, complétude du template standard.       |
| P23-POL-002 | Vérifier que chaque article a : au moins 1 `<DocScreenshot>`, au moins 1 `<DocCallout>`, au moins 1 section FAQ.           |
| P23-POL-003 | Page d'accueil `/docs` — finaliser la grille de modules avec descriptions soignées et icônes cohérentes.                   |
| P23-POL-004 | `DocSidebar` — vérifier que tous les slugs sont correctement liés, actifs, et que la navigation est complète.               |
| P23-POL-005 | ESLint + Prettier — 0 erreur. PHPStan + Pint — 0 erreur.                                                                   |

#### 6.6.2 Frontend Tests

| ID          | Test File                                                                                                                   |
|-------------|-----------------------------------------------------------------------------------------------------------------------------|
| P23-FT-001  | `tests/components/docs/doc-callout.test.tsx` — variantes tip/warning/danger/info, contenu rendu.                           |
| P23-FT-002  | `tests/components/docs/doc-screenshot.test.tsx` — rendu image + caption, ouverture dialog au clic.                         |
| P23-FT-003  | `tests/components/docs/doc-diagram.test.tsx` — fetch du fichier `.mmd`, rendu SVG (mock mermaid).                          |
| P23-FT-004  | `tests/components/docs/doc-sidebar.test.tsx` — tous les 22 liens présents, lien actif mis en surbrillance.                  |
| P23-FT-005  | `tests/pages/docs/docs-home.test.tsx` — page d'accueil rend les 22 cards de modules.                                       |
| P23-FT-006  | `tests/unit/docs/build-search-index.test.ts` — l'index généré contient tous les modules avec `title`, `slug`, `description`.|

---

## 7. Exit Criteria

| Critère                                                               | Vérification                  |
|-----------------------------------------------------------------------|-------------------------------|
| Toutes les tâches `P23-INF-*`, `P23-GEM-*`, `P23-SCR-*`, `P23-DOC-*`, `P23-POL-*` en statut `done` | `docs/dev/phase23.md` |
| Page `/docs` accessible et navigationnable depuis la sidebar          | Test manuel                   |
| 22 articles complets avec template standard respecté                  | Revue manuelle                |
| Chaque article contient au moins 1 screenshot, 1 callout, 1 FAQ      | Revue manuelle                |
| Diagrammes Mermaid rendus correctement dans le navigateur             | Test manuel                   |
| Infographies SVG affichées correctement                               | Test manuel                   |
| Recherche dans CommandPalette retourne des résultats docs             | Test manuel                   |
| Script `make docs-diagrams` fonctionne avec `GEMINI_API_KEY` valide  | Test manuel                   |
| Script `make docs-screenshots` génère les captures                    | Test manuel                   |
| Backend coverage >= 80%                                               | CI green                      |
| Frontend coverage >= 80%                                              | CI green                      |
| PHPStan level 8 — 0 erreur                                            | CI green                      |
| Pint + ESLint + Prettier — 0 erreur                                   | CI green                      |
| Tag `v2.9.0` poussé sur GitHub                                        | `git tag v2.9.0`              |

---

## 8. Récapitulatif

| Sprint    | Semaines | Livrable principal                                              | Tasks                                       |
|-----------|-----------|-----------------------------------------------------------------|---------------------------------------------|
| Sprint 75 | 203–204  | Infrastructure MDX, routing, composants docs, sidebar           | 19 INF                                      |
| Sprint 76 | 205–206  | Script Gemini (30+ diagrammes/infographies) + script screenshots | 5 GEM + 4 SCR                               |
| Sprint 77 | 207–208  | Articles modules core (getting-started → calendrier)            | 10 DOC                                      |
| Sprint 78 | 209–210  | Articles modules email & scoring (campagnes → scoring)          | 6 DOC                                       |
| Sprint 79 | 211–212  | Articles modules support, RAG, portail, paramètres             | 6 DOC                                       |
| Sprint 80 | 213–214  | Polish, cohérence, tests, tag v2.9.0                            | 5 POL + 6 tests                             |
| **Total** | **12 sem**| **v2.9.0 — 22 articles + infrastructure complète**             | **~62 tâches au total**                     |
