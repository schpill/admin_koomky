# Koomky — Project Guidelines

## Project Overview

Koomky is a self-hosted Freelance CRM built as a monorepo:
- **Backend**: Laravel 12 (PHP 8.3+) — `backend/`
- **Frontend**: Next.js 15 / React 19 / shadcn/ui — `frontend/`
- **Database**: PostgreSQL 16
- **Cache/Queue**: Redis 7
- **Search**: Meilisearch
- **Infra**: Docker Compose (core + monitoring services)

## Current Implementation Snapshot

- **Phase 19 closed & merged to `main`** — Branch `apex/phase19-workflow-automation` (merged 2026-03-06) delivers workflow automation multi-étapes (v2.5.0): modèles `Workflow`, `WorkflowStep`, `WorkflowEnrollment` (3 migrations), `WorkflowStepExecutor` (8 types de nœuds : send_email, wait, condition, update_score, add_tag, remove_tag, enroll_drip, update_field, end), `WorkflowTriggerService` (5 triggers : email_opened, email_clicked, score_threshold, contact_created, segment_entered), `AdvanceWorkflowEnrollmentsJob` (scheduler 5 min, isolation erreurs par enrollment), `CheckSegmentMembershipJob` quotidien, `PruneWorkflowEnrollmentsCommand` hebdomadaire. Frontend : éditeur visuel `@xyflow/react` v12 (`ReactFlow` + `ReactFlowProvider`), `WorkflowNodeConfig`, `WorkflowEnrollmentsTable`, store Zustand, widget dashboard. GDPR export inclus.
- **Phase 18 closed & merged to `main`** — Branch `apex/phase18-full` (merged 2026-03-06) delivers click tracking par URL, webhooks email & IP warm-up (v2.4.0): réécriture des URLs sortantes dans `PersonalizationService` (mailto/tel préservés), table `campaign_link_clicks`, `getLinkStats()` dans `CampaignAnalyticsService` (unique_clicks, click_rate), export CSV des liens. Webhooks `email.opened/clicked/bounced/unsubscribed/campaign_sent` via `WebhookEvent` enum enrichi. IP warm-up : `EmailWarmupPlan`, `WarmupGuardService` (canSend/increment/reset), quota quotidien respecté dans `SendEmailCampaignJob` (replanification si quota atteint), `ResetWarmupCountersJob` quotidien, UI settings + widget dashboard.
- **Phase 17 closed & merged to `main`** — Branch `apex/phase17-full` (merged 2026-03-06) delivers lead scoring, STO & dynamic content (v2.3.0): `ContactScoreService` (recordEvent, recalculate, règles par défaut), tables `scoring_rules` + `contact_score_events`, intégration open/click/bounce/unsubscribe, filtre `email_score` dans `SegmentFilterEngine`, `RecalculateExpiredScoresJob` quotidien. STO : `ContactSendTimeService` (heure modale PostgreSQL-compatible, délai UTC), toggle `use_sto` + `sto_window_hours` sur campagnes. Contenu dynamique : parser `{{#if condition}}…{{else}}…{{/if}}` dans `PersonalizationService` (nesting max 2), `DynamicContentValidatorService`, validation 422 dans `CampaignController`, `DynamicContentEditor` composant React.
- **Phase 16 closed & merged to `main`** — Branch `apex/phase16-full` (merged 2026-03-06) delivers drip campaigns, suppression list & bounce management (v2.2.0): séquences drip multi-étapes avec conditions comportementales (if_opened/if_clicked/if_not_opened), `AdvanceDripEnrollmentsJob` schedulé toutes les 5 min, suppression list centralisée par user (hard bounce auto-blacklist, désabonnement, manuel, import/export CSV), retry soft bounce (max 3, backoff), `CampaignRecipientObserver`, analytics étendues (hard/soft bounce counts, suppressed), GDPR export drip+suppression, commande `drip-enrollments:prune`, UI complète (pages drip, suppression, widget dashboard).
- **Phase 15 closed & merged to `main`** — Branch `apex-omega/15-phase15-full` (merged 2026-03-06) delivers email campaign enhancements (v2.1.0): test multi-destinataires (1–5 emails), personnalisation avec contact fictif (`renderPreview()`), déduplication destinataires par email (`firstOrCreate` + contrainte unique DB), A/B Testing complet (variants A/B, split aléatoire, sélection auto/manuelle du gagnant, analytics par variante), GDPR export variants, widget dashboard "A/B Tests actifs".
- **Phase 12 closed & merged to `main`** — PR #24 (`feat/phase12-reminders`, merged 2026-02-27) delivers the full automatic reminder sequences scope (v1.8.0): configurable sequences, Artisan scheduler, ReminderMail with variable interpolation, per-invoice lifecycle (pause/resume/skip/cancel), GDPR export, webhook `invoice.reminder_sent`, dashboard widget.
- **Phase 11 closed & merged to `main`** — PR #23 (`feature/phase-11-product-catalog-ai-campaigns`, merged 2026-02-27) delivers the full product catalog + AI campaign generation scope (v1.7.0), including backend/frontend test completion and CI hardening fixes.
- **Phase 10 closed & merged to `main`** — PR #22 (`feature/phase-10-rag-mcp`, merged 2026-02-25) delivers the full RAG pipeline + MCP server (v1.6.0).
- **Phase 10 scope delivered**:
  - Backend RAG pipeline: `GeminiService` (embed + generate via Gemini API), `DocumentTextExtractorService` (PDF/DOCX/TXT), `DocumentChunkService` (overlap chunking), `DocumentEmbeddingService`, `VectorSearchService` (pgvector cosine), `RagService` (top-5 + prompt + answer/sources/tokens/latency)
  - Infrastructure: pgvector extension, `document_chunks` table (HNSW index, vector(768)), `embedding_status` column on documents, `rag_usage_logs` table, queue `embeddings`, `ProcessDocumentEmbeddingJob`
  - API: `RagController` (ask, search, status, reindex), `PortalRagController`, `McpTokenController` (PAT scope `mcp:read`), `McpScopeGuard` middleware
  - MCP server TypeScript (`mcp/`): 4 tools (search_documents, ask_question, list_topics, get_document_context), stdio + SSE transport, `docs/mcp/claude-desktop.md`
  - Frontend: Zustand `rag` store, `chat-widget.tsx` (portal chatbot), `embedding-status-badge.tsx`, settings RAG admin page, document detail badge + reindex button, sidebar entry
  - 10 backend tests (Unit + Feature) + 5 MCP TypeScript tests + 5 frontend unit/component tests + 2 E2E scenarios
- **Phase 9 closed & merged to `main`** — PR #21 (`feature/phase-9-tickets`, merged 2026-02-23) delivers the full Support Ticket System.
- **Phase 9 scope delivered**:
  - Ticket model (UUID PK, Scout Searchable, `byStatus`/`byPriority`/`byClient`/`byAssignee`/`overdue` scopes), TicketMessage model, factories, migrations (tickets, ticket_messages, ticket_documents pivot)
  - `TicketPolicy` (owner: toutes actions ; assigné : message + changement de statut)
  - `TicketController` (index avec Scout search, store, show, update, destroy, changeStatus, assign, stats, overdue)
  - `TicketMessageController` + `TicketDocumentController` (upload/attach/detach GED)
  - `TicketNotificationService` (notifyAssigned, notifyOwnerResolved, notifyOwnerClosed, notifyParticipantsNewMessage — toutes en queue)
  - `TicketObserver` (assigned_to par défaut, webhooks ticket.opened/assigned/resolved/closed/deleted, set resolved_at/closed_at via updateQuietly)
  - `TicketMessageObserver` (set first_response_at sur premier message public de l'assigné)
  - Meilisearch Scout index (searchable: title, description; filterable: user_id, assigned_to, client_id, project_id, status, priority, category, tags)
  - GDPR export inclusion (tickets + ticket_messages publics)
  - Frontend: 2 stores Zustand (tickets, ticketDetail), page liste, page détail, 9 composants (form-dialog, status-badge, priority-badge, message-thread, message-composer, attachments-panel, stats-card, status-change-dialog), entrée sidebar, widget dashboard "Tickets urgents"
  - 82 tests backend (689 total, 1966 assertions) + 57 tests frontend unitaires + 5 scénarios E2E
- **Phase 8 closed & merged to `main`** — PR #20 (`feature/phase-8-ged`, merged 2026-02-21) delivers the full GED (Document Management System).
- **Phase 8 scope delivered**:
  - Document model (UUID PK, Scout Searchable, `byType`/`byClient`/`byTag` scopes), migration, factory, policy, observer
  - `DocumentTypeDetectorService` (finfo MIME detection, dangerous MIME rejection, script language detection)
  - `DocumentStorageService` (store, overwrite, delete, streamDownload, quota enforcement)
  - `DocumentMailService` + `DocumentAttachmentMail` (queued, attachment from storage, `last_sent_at`/`last_sent_to`)
  - `DocumentController` (index with Scout search fix, store, show, update, destroy, reupload, download, sendEmail, bulkDestroy, stats)
  - Meilisearch Scout index (searchable: title, original_filename, tags; filterable: user_id, client_id, document_type; sortable: created_at, title, file_size)
  - GDPR export inclusion and webhook events (document.uploaded, document.updated, document.deleted, document.sent)
  - Frontend: Zustand store, documents library page (grid/list, search, filters, bulk select, stats bar), document detail page, 7 components (upload dialog, reupload dialog, preview, card, type badge, send email dialog, filters), sidebar entry, dashboard widget
  - 45 backend tests (130 assertions) + 244 frontend tests (97.4% coverage) + 5 E2E scenarios
- **Phase 7 closed & released as `v1.3.0`** — PR #18 delivered the features; PR #19 (`fix/phase7-code-review`, merged 2026-02-20) applied the full code-review hardening (SSRF fix, LIKE escape, DI, LeadPolicy, webhook retry, service deduplication, 62 new frontend tests). Tag `v1.3.0` pushed to GitHub.
- **Phase 7 delivered artifacts**:
  - Backend: webhook models/controllers, `WebhookDispatchService`/`WebhookDispatchJob`, PAT scope-guard middleware, `StoreLeadRequest`, webhook delivery logging + OpenAPI UI (`dedoc/scramble` @ `/api/docs`), lead-related observers, and comprehensive unit tests (including WebhookDispatchService/Job and LeadConversion).
  - Frontend: API tokens/webhook forms, lead Kanban/activities/convert dialog + zustand store, pipeline analytics page, dashboard pipeline widget, and their corresponding Playwright/Vitest specs.
  - Infrastructure: Meilisearch lead index, GDPR export inclusion, and coverage above the 80% gates for both backend and frontend.
- **Phase 6 is fully implemented and merged to `main`** (Client Portal & Expense Tracking roadmap scope).
- **Phase 6 scope delivered**:
  - Client portal (magic link auth, dashboard, invoice/quote viewing, quote accept/reject flows)
  - Online Stripe payments from portal (payment intents, webhook sync, notifications)
  - Expense tracking (categories, CRUD, receipt upload/download, reporting, CSV export, import/export integration)
  - Financial integration (profit/loss, project profitability, dashboard widgets, billable expense invoicing)
- **Post-merge hardening from PR #15 (`fix/phase6-code-review`) is now on `main`**:
  - Stripe webhook and payment service typing/null-safety fixes (PHPStan-compatible)
  - Deterministic backend tests for portal payments and payment notifications
  - Stripe service test fixtures aligned with `create`/`update` payment intent code paths
- **Phase 5 is implemented, merged to `main`, and released as `v1.1.0`** (tag + GitHub release).
- **Phase 5 scope delivered**:
  - Recurring invoices (profiles, generator jobs, scheduling, notifications, UI, tests)
  - Multi-currency support (currencies/rates services, conversion in documents/reports/dashboard, UI, tests)
  - Calendar integration (connections/events, sync drivers/jobs, auto-events, UI, tests)
  - Prometheus + Grafana monitoring stack (metrics endpoint/middleware/service, exporters, dashboards, docs)
- **Coverage gate policy**: backend and frontend thresholds remain **>= 80%**.
- **Phase 5 validation automation is available** via:
  - `scripts/validate-phase5.sh` (backend coverage, frontend coverage, CI status check, tag check)
- **No dedicated Phase 6–10 validation scripts exist yet**:
  - Use phase-specific suites documented in `docs/dev/phase6.md` through `docs/dev/phase10.md`.
- **Public signup is disabled**:
  - Backend route `POST /api/v1/auth/register` is removed.
  - Frontend `/auth/register` page and middleware exposure are removed.
- **Email campaigns support Amazon SES in API mode**:
  - Per-user runtime credentials are supported via settings (`api_key`, `api_secret`, `api_region`).
  - Fallback to global `services.ses` config remains available if per-user credentials are absent.
- **User provisioning is now admin-only via CLI command**:
  - `php artisan users:create`
  - Asks for email (if not provided as argument), creates user, prints generated password in clear text.
  - Password policy enforced by generator: at least 8 chars, with lowercase, uppercase, number, and special char.
- **CI gates are green on current merged work** with backend and frontend checks.
- **Local pre-push checks are enforced via Husky**:
  - Frontend: `pnpm --dir frontend format:check` (Prettier)
  - Backend: `./vendor/bin/pint --test` and `./vendor/bin/phpstan analyse --memory-limit=1G` (executed in the `api` container)

## Task Tracking

Task tracking files live in `docs/dev/phase{1,2,3,4,5,6,7,8,9,10,11,12}.md`. These are the **source of truth** for task progress across all contributors (humans and AI agents).

### Status values

| Status   | Meaning |
|----------|---------|
| `todo`   | Not started |
| `wip`    | Work in progress |
| `done`   | Code complete locally |
| `pr`     | Pull request open |
| `merged` | Merged to main |

### Rules

1. **Before starting a task**: set its status to `wip` and add your name in the Owner column.
2. **After completing a task**: set its status to `done`.
3. **Never skip a sprint**: complete Sprint N before starting Sprint N+1.
4. **Phase gating**: Phase N must be fully `merged` before starting Phase N+1.
5. **Keep it concise**: only update status and owner, do not modify task descriptions.

### Format

Each task line follows this pattern:
```
| ID | Task description | Status | Owner |
```

## Development Workflow

- **Branch naming**: `feat/{short-description}` or `fix/{short-description}`
- **Commits**: Conventional Commits (`feat:`, `fix:`, `test:`, `chore:`, `refactor:`, `docs:`)
- **TDD**: Write tests first, then implementation (Red-Green-Refactor)
- **Coverage gate**: >= 80% on both backend and frontend
- **CI must pass** before any merge to `main`

## Auth and User Provisioning

- Koomky is a private CRM instance. Do not re-enable self-registration without explicit product decision.
- Create accounts with:
  ```bash
  cd backend
  php artisan users:create
  # or
  php artisan users:create owner@example.com
  ```
- The command stores a hashed password in DB and prints the generated plain password once in console output.

## Key Commands

```bash
make up          # Start all Docker services
make down        # Stop services
make test        # Run all tests (backend + frontend)
make test-be     # Run backend tests only
make test-fe     # Run frontend tests only
make lint        # Run all linters (Pint, PHPStan, ESLint)
cd frontend && pnpm prettier --write .   # Auto-fix frontend formatting
make fresh       # Reset database
make seed        # Seed database
cd backend && php artisan users:create   # Create a private CRM user account
```

## ⚠️ Règle critique — Ne jamais lancer `pnpm build` sur le container frontend en cours d'exécution

**`docker compose run --rm frontend pnpm build` est INTERDIT** quand le container
`frontend` est déjà en cours d'exécution en mode dev (`pnpm dev`).

**Pourquoi** : le dossier `frontend/` est monté en volume. Un `pnpm build` dans un
container éphémère écrase `.next/` sur le filesystem hôte avec des artefacts de
production. Le dev server qui tourne voit alors un `.next/` corrompu et sert les
chunks JS/CSS en 404 — les styles disparaissent de l'application.

**Pour vérifier les types TypeScript**, utiliser exclusivement :
```bash
make lint        # inclut ESLint, ne touche pas .next/
# ou laisser la CI GitHub valider le build TypeScript
```

**Si les styles ont disparu** (diagnostic : 404 sur `/_next/static/css/` dans les logs nginx) :
```bash
docker compose restart frontend   # régénère .next/ proprement en mode dev
```

## Architecture Decisions

- **API format**: JSON:API-inspired with `data`, `meta`, `links` structure
- **Auth**: Laravel Sanctum (JWT-style tokens)
- **State management**: Zustand (frontend)
- **Forms**: react-hook-form + Zod
- **UI components**: shadcn/ui (Radix UI + Tailwind CSS)
- **Testing backend**: Pest
- **Testing frontend**: Vitest + React Testing Library
- **E2E**: Playwright
- **Static analysis**: PHPStan level 8 (Larastan)
- **Code style**: Laravel Pint (backend), ESLint (frontend)

## CI Notes

- Backend CI runs against PostgreSQL database name `koomky` in GitHub Actions.
- Frontend CI enforces:
  - `pnpm lint`
  - `pnpm format:check`
  - `pnpm vitest run --coverage`
- Global coverage thresholds (Vitest): lines/functions/branches/statements >= 80%.

## Reference Documents

- `PRD.md` — Full product requirements (v1.1.0 baseline + v1.2/v1.3/v1.6 roadmap)
- `docs/phases/phase{1,2,3,4,5,6,7,8,9,10,11,12}.md` — Detailed specs per phase
- `docs/dev/phase{1,2,3,4,5,6,7,8,9,10,11,12}.md` — Task tracking per phase
- `docs/mcp/claude-desktop.md` — MCP server config for Claude Desktop
- `scripts/validate-phase5.sh` — Automated local validation for Phase 5 gates
