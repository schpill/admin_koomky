# Phase 16 — Task Tracking

> **Status**: done
> **Prerequisite**: Phase 15 fully merged and tagged `v2.1.0`
> **Spec**: [docs/phases/phase16.md](../phases/phase16.md)

---

## Sprint 51 — Backend Suppression List & Bounce Management (Weeks 135–137)

### Infrastructure & Database

| ID              | Task                                                                                                                                                                        | Status | Owner |
|-----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P16-BE-INF-01   | Migration `create_suppressed_emails_table` — UUID PK, user_id FK, email VARCHAR(255), reason ENUM(unsubscribed/hard_bounce/manual), source_campaign_id FK nullable, suppressed_at TIMESTAMP. Index unique `(user_id, email)`. | done  | Codex |
| P16-BE-INF-02   | Migration `add_bounce_fields_to_campaign_recipients_table` — `bounce_count` TINYINT DEFAULT 0, `bounce_type` ENUM(hard/soft) nullable.                                      | done  | Codex |

### Backend Tasks

| ID          | Task                                                                                                                                                                                                            | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P16-BE-001  | Create `SuppressedEmail` model — `HasUuids`, `HasFactory`. Fillable, casts datetime. Relations `user()`, `sourceCampaign()`. Scope `forUser(User $user)`.                                                        | done   | Codex ||
| P16-BE-002  | Create `CampaignRecipientObserver` — `updated()` : si statut passe à `bounced` ET `bounce_type=hard` → `SuppressedEmail::firstOrCreate(…, ['reason' => 'hard_bounce'])`. Enregistrer dans `AppServiceProvider`. | done   | Codex ||
| P16-BE-003  | Create `SuppressionService` — `suppress()`, `isSuppressed()`, `getSuppressedEmails()`, `importCsv()`, `exportCsv()`.                                                                                            | done   | Codex ||
| P16-BE-004  | Create `SuppressionListController` — index (paginé + recherche), store, destroy, import (CSV), export (CSV stream). Routes `GET/POST /suppression-list`, `DELETE /{entry}`, `POST /import`, `GET /export`.      | done   | Codex ||
| P16-BE-005  | Extend `SendEmailCampaignJob` — Charger la suppression list en mémoire avant le cursor. Skip les contacts dont l'email est supprimé.                                                                            | done   | Codex ||
| P16-BE-006  | Extend `EmailTrackingController::unsubscribe()` — Ajouter `SuppressionService::suppress(…, 'unsubscribed')` après update du recipient.                                                                          | done   | Codex ||
| P16-BE-007  | Extend `CampaignWebhookController` — Parser bounces SES entrants : hard → `bounce_type=hard`, soft → `bounce_type=soft`, incrémenter `bounce_count`.                                                            | done   | Codex ||
| P16-BE-008  | Create `RetryBouncedEmailJob` — Si `bounce_count < 3` ET soft : re-dispatch `SendCampaignEmailJob`, incrémente `bounce_count`. Sinon : `bounce_type=hard` → observer déclenche blacklist.                       | done   | Codex ||
| P16-BE-009  | Extend `CampaignAnalyticsService` — Ajouter `hard_bounce_count`, `soft_bounce_count`, `suppressed_count` dans les stats.                                                                                        | done   | Codex ||
| P16-BE-010  | PHPStan level 8 + Pint sur tous les fichiers du sprint.                                                                                                                                                         | done   | Codex ||

### Backend Tests (TDD)

| ID          | Test File                                                                                    | Status | Owner |
|-------------|----------------------------------------------------------------------------------------------|--------|-------|
| P16-BT-001  | `tests/Unit/Services/SuppressionServiceTest.php`                                             | done   | Codex ||
| P16-BT-002  | `tests/Feature/Campaigns/SuppressionListCrudTest.php`                                        | done   | Codex ||
| P16-BT-003  | `tests/Feature/Campaigns/BounceManagementTest.php`                                           | done   | Codex ||
| P16-BT-004  | `tests/Feature/Campaigns/CampaignAnalyticsBounceTest.php`                                    | done   | Codex ||

---

## Sprint 52 — Backend Drip Sequences (Weeks 138–140)

### Infrastructure & Database

| ID              | Task                                                                                                                                                                        | Status | Owner |
|-----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P16-BE-INF-03   | Migration `create_drip_sequences_table` — UUID PK, user_id FK, name, trigger_event ENUM, trigger_campaign_id FK nullable, status ENUM(active/paused/archived), settings JSON, timestamps. | done  | Codex |
| P16-BE-INF-04   | Migration `create_drip_steps_table` — UUID PK, sequence_id FK CASCADE, position TINYINT, delay_hours SMALLINT, condition ENUM(none/if_opened/if_clicked/if_not_opened), subject, content, template_id FK nullable. Index unique `(sequence_id, position)`. | done  | Codex |
| P16-BE-INF-05   | Migration `create_drip_enrollments_table` — UUID PK, sequence_id FK CASCADE, contact_id FK CASCADE, current_step_position TINYINT, status ENUM(active/completed/paused/cancelled/failed), enrolled_at, last_processed_at, completed_at. Index unique `(sequence_id, contact_id)`. Index `(status, last_processed_at)`. | done  | Codex |

### Backend Tasks

| ID          | Task                                                                                                                                                                                                            | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P16-BE-011  | Create `DripSequence` model — `HasUuids`, `HasFactory`. Fillable, casts settings array. Relations `user()`, `steps()` ordered by position, `enrollments()`. Scopes `active()`, `forUser()`.                   | done   | Codex ||
| P16-BE-012  | Create `DripStep` model — `HasUuids`, `HasFactory`. Fillable, casts. Relations `sequence()`, `template()`. Méthode `evaluateCondition(DripEnrollment $enrollment): bool`.                                      | done   | Codex ||
| P16-BE-013  | Create `DripEnrollment` model — `HasUuids`, `HasFactory`. Fillable, casts. Relations `sequence()`, `contact()`. Scopes `active()`, `dueForProcessing()`.                                                       | done   | Codex ||
| P16-BE-014  | Create `DripEnrollmentService` — `enroll(Contact, DripSequence)`, `enrollSegment(Segment, DripSequence): int`. Vérifie suppression list + double enrollment.                                                   | done   | Codex ||
| P16-BE-015  | Create `SendDripStepEmailJob` — Vérifie suppression list. Envoie email. Crée `CampaignRecipient`. Met à jour enrollment (position++, last_processed_at, completed si fin).                                       | done   | Codex ||
| P16-BE-016  | Create `AdvanceDripEnrollmentsJob` — Schedulé toutes les 5 min. Charge enrollments `dueForProcessing()`. Évalue condition, dispatch `SendDripStepEmailJob` si OK.                                               | done   | Codex ||
| P16-BE-017  | Create `DripSequenceController` — CRUD + `enroll` (POST), `enrollSegment` (POST), `pause`/`resume`/`cancel` (PATCH enrollment). Routes API v1.                                                                 | done   | Codex ||
| P16-BE-018  | Create `DripSequencePolicy` — ownership + actions enroll/pause/resume/cancel.                                                                                                                                  | done   | Codex ||
| P16-BE-019  | PHPStan level 8 + Pint.                                                                                                                                                                                         | done   | Codex ||

### Backend Tests (TDD)

| ID          | Test File                                                                                    | Status | Owner |
|-------------|----------------------------------------------------------------------------------------------|--------|-------|
| P16-BT-005  | `tests/Unit/Services/DripEnrollmentServiceTest.php`                                          | done   | Codex ||
| P16-BT-006  | `tests/Unit/Jobs/AdvanceDripEnrollmentsJobTest.php`                                          | done   | Codex ||
| P16-BT-007  | `tests/Feature/Drip/DripSequenceCrudTest.php`                                               | done   | Codex ||
| P16-BT-008  | `tests/Feature/Drip/DripSequenceSendTest.php`                                               | done   | Codex ||

---

## Sprint 53 — Frontend Drip & Suppression UI (Weeks 141–143)

### Frontend Tasks

| ID          | Task                                                                                                                                                                                                            | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P16-FE-001  | Create `lib/stores/drip-sequences.ts` Zustand store — state sequences[], currentSequence, enrollments[]. Actions : fetch, create, update, delete, addStep, updateStep, deleteStep, enroll, enrollSegment, pause, cancel. | done  | Codex |
| P16-FE-002  | Create `lib/stores/suppression-list.ts` Zustand store — state entries[], total, page, search. Actions : fetch, add, remove, importCsv, exportCsv.                                                               | done   | Codex ||
| P16-FE-003  | Create `app/(dashboard)/campaigns/drip/page.tsx` — Liste séquences : nom, trigger, nb steps, nb enrollments actifs, statut.                                                                                     | done   | Codex ||
| P16-FE-004  | Create `app/(dashboard)/campaigns/drip/create/page.tsx` + `[id]/page.tsx` — Formulaire séquence + liste réorderable des étapes.                                                                                 | done   | Codex ||
| P16-FE-005  | Create `components/drip/drip-step-form.tsx` — Délai (heures), condition Select, sujet, éditeur contenu avec PersonalizationVariablesPanel.                                                                      | done   | Codex ||
| P16-FE-006  | Create `components/drip/drip-enrollments-table.tsx` — Tableau contacts enrollés : étape courante, statut, actions pause/cancel.                                                                                 | done   | Codex ||
| P16-FE-007  | Create `app/(dashboard)/campaigns/suppression/page.tsx` — Liste paginée + recherche + add manuel + import/export CSV.                                                                                           | done   | Codex ||
| P16-FE-008  | Create `components/campaigns/suppression-list-table.tsx` — Tableau avec badge raison (unsubscribed/hard_bounce/manual). Bouton suppression par ligne.                                                           | done   | Codex ||
| P16-FE-009  | Extend sidebar — Entrée "Drip" sous Campagnes (icône GitBranch). Entrée "Suppression" sous Campagnes (icône Ban).                                                                                               | done   | Codex ||
| P16-FE-010  | Extend `app/(dashboard)/page.tsx` — Widget "Séquences drip actives" (count + enrollments actifs). Masqué si 0.                                                                                                  | done   | Codex ||
| P16-FE-011  | Extend `components/campaigns/analytics.tsx` — Afficher hard_bounce_count, soft_bounce_count, suppressed_count.                                                                                                  | done   | Codex ||

### Frontend Tests

| ID          | Test File                                                                                           | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------|--------|-------|
| P16-FT-001  | `tests/unit/stores/drip-sequences.test.ts`                                                          | done   | Codex ||
| P16-FT-002  | `tests/unit/stores/suppression-list.test.ts`                                                        | done   | Codex ||
| P16-FT-003  | `tests/components/drip/drip-step-form.test.tsx`                                                     | done   | Codex ||
| P16-FT-004  | `tests/components/drip/drip-enrollments-table.test.tsx`                                             | done   | Codex ||
| P16-FT-005  | `tests/components/campaigns/suppression-list-table.test.tsx`                                        | done   | Codex ||
| P16-FT-006  | `tests/e2e/campaigns/drip-sequence-flow.spec.ts`                                                    | done   | Codex ||
| P16-FT-007  | `tests/e2e/campaigns/suppression-list-flow.spec.ts`                                                 | done   | Codex ||

---

## Sprint 54 — Hardening GDPR & CI (Weeks 144–146)

### Backend Tasks

| ID          | Task                                                                                                                                                           | Status | Owner |
|-------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P16-BE-020  | Extend `DataExportService` — Inclure `DripEnrollment` + `SuppressedEmail` dans l'export GDPR.                                                                 | done   | Codex ||
| P16-BE-021  | Add command `drip-enrollments:prune` — Supprime enrollments completed/cancelled > 90 jours. Planifiée hebdomadairement.                                         | done   | Codex ||
| P16-BE-022  | PHPStan level 8 + Pint sur tous les fichiers du sprint.                                                                                                         | done   | Codex ||

### Frontend Tasks

| ID          | Task                                                                                                                                                           | Status | Owner |
|-------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P16-FE-012  | ESLint + Prettier — 0 erreur sur tous les fichiers nouveaux/modifiés.                                                                                          | done   | Codex ||

### Backend Tests

| ID          | Test File                                                                                    | Status | Owner |
|-------------|----------------------------------------------------------------------------------------------|--------|-------|
| P16-BT-009  | `tests/Feature/Drip/DripSequenceGdprTest.php`                                               | done   | Codex ||
| P16-BT-010  | `tests/Feature/Drip/DripEnrollmentPruneTest.php`                                            | done   | Codex ||

---

## Récapitulatif

| Sprint    | Semaines | Livrable principal                                              | Tasks                        |
|-----------|----------|-----------------------------------------------------------------|------------------------------|
| Sprint 51 | 135–137  | Suppression list + bounce management backend                    | 2 INF + 10 BE + 4 tests      |
| Sprint 52 | 138–140  | Drip sequences backend (models, jobs, scheduler)               | 3 INF + 9 BE + 4 tests       |
| Sprint 53 | 141–143  | Frontend drip UI + suppression UI + analytics bounce           | 11 FE + 7 tests              |
| Sprint 54 | 144–146  | Hardening GDPR, prune, ESLint, CI                              | 3 BE/FE + 2 tests            |
| **Total** | **12 sem** | **v2.2.0**                                                   | **~42 tâches + 17 tests**    |
