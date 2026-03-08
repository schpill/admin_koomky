# Phase 23 Design

**Goal:** Ajouter un module de documentation intégré au dashboard, extensible et indexable, sans dépendre d'un backend dédié.

**Decision:** Centraliser la taxonomie documentaire dans une configuration TypeScript partagée, rendre les contenus via MDX sous `frontend/content/docs`, et générer un index statique JSON consommé par la Command Palette.

## Architecture

- `frontend/lib/docs/config.ts`
  - source unique pour les 22 modules, la navigation docs, les cards home et les métadonnées de recherche.
- `frontend/content/docs/**`
  - contenu MDX versionné, frontmatter strict (`title`, `description`, `module`).
- `frontend/app/(dashboard)/docs/**`
  - home docs, layout docs dédié, route catch-all pour résoudre les slugs MDX.
- `frontend/components/docs/**`
  - primitives d'affichage (`DocLayout`, `DocSidebar`, `DocToc`, `DocCallout`, `DocScreenshot`, `DocDiagram`, `DocInfographic`, `DocSteps`, `DocBadge`).
- `frontend/scripts/docs/**`
  - build de l'index de recherche, génération Gemini des diagrammes/infographies, capture Playwright des screenshots.

## Key Choices

- Recherche docs purement frontend via `public/docs/search-index.json` chargé une seule fois puis filtré en mémoire.
- Diagrammes Mermaid rendus uniquement côté client pour isoler le coût de `mermaid`.
- Assets docs présents dans le repo avec placeholders valides pour garantir une build autonome même sans lancer Gemini/Playwright.
- Contenu des 22 modules rédigé selon un template uniforme pour tenir l'exigence de complétude et la maintenabilité.

## Risks

- Le volume de contenu est important: la cohérence dépend d'un registre central et d'un template commun.
- L'import dynamique MDX doit rester compatible avec Next App Router et le build statique.
- La palette de commande actuelle est peu typée: l'extension docs doit rester additive et ne pas casser la recherche existante.
