# Phase 17 — Task Tracking

> **Status**: done
> **Prerequisite**: Phase 16 fully merged and tagged `v2.2.0`
> **Spec**: [docs/phases/phase17.md](../phases/phase17.md)

---

## Sprint 55 — Backend Lead Scoring (Weeks 147–149)

### Infrastructure & Database

| ID              | Task                                                                                                                                                                        | Status | Owner |
|-----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P17-BE-INF-01   | Migration `add_email_score_to_contacts_table` — `email_score` SMALLINT DEFAULT 0, `email_score_updated_at` TIMESTAMP nullable. Index adapté à la structure actuelle `(client_id, email_score)`.              | done   | Codex     |
| P17-BE-INF-02   | Migration `create_scoring_rules_table` — UUID PK, user_id FK, event ENUM, points SMALLINT, expiry_days SMALLINT nullable, is_active BOOLEAN. Index unique `(user_id, event)`. | done | Codex |
| P17-BE-INF-03   | Migration `create_contact_score_events_table` — UUID PK, user_id FK, contact_id FK CASCADE, event VARCHAR(50), points SMALLINT, source_campaign_id FK nullable, expires_at TIMESTAMP nullable. Index `(contact_id, expires_at)`. | done | Codex |

### Backend Tasks

| ID          | Task                                                                                                                                                                                                            | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P17-BE-001  | Create `ScoringRule` model — `HasUuids`, `HasFactory`. Fillable, casts. Relations `user()`. Scope `activeForUser(User $user)`.                                                                                  | done   | Codex     |
| P17-BE-002  | Create `ContactScoreEvent` model — `HasUuids`. Fillable, casts. Relations `contact()`, `sourceCampaign()`.                                                                                                      | done   | Codex     |
| P17-BE-003  | Create `ContactScoreService` — `recordEvent(Contact, string event, ?Campaign)`, `recalculate(Contact): int`, `getHistory(Contact): Collection`. Insère règles par défaut si aucune règle active pour l'user.   | done   | Codex     |
| P17-BE-004  | Create `ScoringRuleController` — CRUD routes `GET/POST /scoring-rules`, `PUT/DELETE /scoring-rules/{rule}`.                                                                                                     | done   | Codex     |
| P17-BE-005  | Extend `EmailTrackingController::open()` — `ContactScoreService::recordEvent($contact, 'email_opened', $campaign)`.                                                                                             | done   | Codex     |
| P17-BE-006  | Extend `EmailTrackingController::click()` — `recordEvent(…, 'email_clicked', …)`.                                                                                                                              | done   | Codex     |
| P17-BE-007  | Extend `EmailTrackingController::unsubscribe()` / `UnsubscribeController` — `recordEvent(…, 'email_unsubscribed', …)`.                                                                                           | done   | Codex     |
| P17-BE-008  | Extend `CampaignRecipientObserver` — Sur hard bounce : `recordEvent(…, 'email_bounced', …)`.                                                                                                                    | done   | Codex     |
| P17-BE-009  | Extend `SegmentFilterEngine` — Critère `email_score` : opérateurs gte/lte/gt/lt/eq sur `contacts.email_score`.                                                                                                  | done   | Codex     |
| P17-BE-010  | Create `RecalculateExpiredScoresJob` — Schedulé quotidiennement. Recalcule `email_score` des contacts avec events expirés.                                                                                       | done   | Codex     |
| P17-BE-011  | PHPStan level 8 + Pint.                                                                                                                                                                                         | done   | Codex     |

### Backend Tests (TDD)

| ID          | Test File                                                                                    | Status | Owner |
|-------------|----------------------------------------------------------------------------------------------|--------|-------|
| P17-BT-001  | `tests/Unit/Services/ContactScoreServiceTest.php`                                            | done   | Codex     |
| P17-BT-002  | `tests/Unit/Jobs/RecalculateExpiredScoresJobTest.php`                                        | done   | Codex     |
| P17-BT-003  | `tests/Feature/Scoring/ScoringRuleCrudTest.php`                                              | done   | Codex     |
| P17-BT-004  | `tests/Feature/Scoring/ContactScoreIntegrationTest.php`                                      | done   | Codex     |

---

## Sprint 56 — Backend STO & Dynamic Content (Weeks 150–152)

### Infrastructure & Database

| ID              | Task                                                                                                                                                                        | Status | Owner |
|-----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P17-BE-INF-04   | Migration `add_sto_fields_to_campaigns_table` — `use_sto` BOOLEAN DEFAULT false, `sto_window_hours` TINYINT DEFAULT 24.                                                     | done   | Codex     |

### Backend Tasks

| ID          | Task                                                                                                                                                                                                            | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P17-BE-012  | Create `ContactSendTimeService` — `getOptimalHour(Contact, User): ?int` (heure modale des opened_at, null si < 3). `getNextSendDelay(int $optimalHour, int $windowHours): int` (secondes).                      | done   | Codex     |
| P17-BE-013  | Extend `Campaign` model — `use_sto`, `sto_window_hours` dans `$fillable` + casts.                                                                                                                               | done   | Codex     |
| P17-BE-014  | Extend `StoreCampaignRequest` — `use_sto` boolean nullable, `sto_window_hours` integer 1–48 nullable.                                                                                                           | done   | Codex     |
| P17-BE-015  | Extend `SendEmailCampaignJob` — Si `use_sto` : calculer délai optimal par contact, dispatch `SendCampaignEmailJob->delay($delay)`. Null → immédiat.                                                             | done   | Codex     |
| P17-BE-016  | Create `DynamicContentValidatorService` — `validate(string $content): array{valid:bool, errors:string[]}`. Parse blocs `{{#if}}`, vérifie syntaxe, nesting ≤ 2, attribut dans whitelist, opérateur valide.      | done   | Codex     |
| P17-BE-017  | Extend `PersonalizationService::render()` — Parser et évaluer les blocs `{{#if condition}}…{{else}}…{{/if}}` avant résolution des variables. Attributs : `contact.*`, `client.*`, `email_score`. Nesting max 2. | done   | Codex     |
| P17-BE-018  | Extend `PersonalizationService::renderPreview()` — Évaluer les blocs `{{#if}}` avec valeurs fictives (email_score fictif = 75).                                                                                  | done   | Codex     |
| P17-BE-019  | Extend `CampaignController::store()`/`update()` — Appeler `DynamicContentValidatorService::validate()` sur content et variants.*.content. Retourner 422 si invalide.                                            | done   | Codex     |
| P17-BE-020  | PHPStan level 8 + Pint.                                                                                                                                                                                         | done   | Codex     |

### Backend Tests (TDD)

| ID          | Test File                                                                                    | Status | Owner |
|-------------|----------------------------------------------------------------------------------------------|--------|-------|
| P17-BT-005  | `tests/Unit/Services/ContactSendTimeServiceTest.php`                                         | done   | Codex     |
| P17-BT-006  | `tests/Unit/Services/DynamicContentValidatorServiceTest.php`                                 | done   | Codex     |
| P17-BT-007  | `tests/Unit/Services/PersonalizationServiceDynamicTest.php`                                  | done   | Codex     |
| P17-BT-008  | `tests/Feature/Campaign/CampaignStoTest.php`                                                 | done   | Codex     |
| P17-BT-009  | `tests/Feature/Campaign/CampaignDynamicContentTest.php`                                      | done   | Codex     |

---

## Sprint 57 — Frontend Scoring, STO & Dynamic Content UI (Weeks 153–155)

### Frontend Tasks

| ID          | Task                                                                                                                                                                                                            | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P17-FE-001  | Create `lib/stores/scoring-rules.ts` Zustand store — state rules[]. Actions : fetch, create, update, delete.                                                                                                    | done   | Codex     |
| P17-FE-002  | Create `app/(dashboard)/settings/scoring/page.tsx` — Tableau editable des règles de scoring. Bouton reset aux valeurs par défaut.                                                                               | done   | Codex     |
| P17-FE-003  | Extend contact UI (`components/clients/client-contact-list.tsx` dans cette codebase) — Section "Score email" : badge couleur sur la liste des contacts client.                                                 | done   | Codex     |
| P17-FE-004  | Extend `components/segments/segment-builder.tsx` — Critère `email_score` : opérateur + Input numérique.                                                                                                         | done   | Codex     |
| P17-FE-005  | Create `components/campaigns/sto-config.tsx` — Toggle STO, input fenêtre (heures), indicateur "X contacts avec heure optimale connue".                                                                          | done   | Codex     |
| P17-FE-006  | Extend pages `campaigns/create` + `campaigns/[id]` — Intégrer `StoConfig`, inclure `use_sto` + `sto_window_hours` dans le payload / détail.                                                                     | done   | Codex     |
| P17-FE-007  | Create `components/campaigns/dynamic-content-editor.tsx` — Interface visuelle pour insérer des blocs `{{#if}}` : Select attribut, Select opérateur, Input valeur, textarea vrai/faux. Bouton "Prévisualiser".   | done   | Codex     |
| P17-FE-008  | Extend `app/(dashboard)/campaigns/create/page.tsx` — Intégrer `DynamicContentEditor`. Afficher erreurs `dynamic_content_errors` du backend.                                                                     | done   | Codex     |
| P17-FE-009  | Extend sidebar Settings — Entrée "Scoring" dans les paramètres.                                                                                                                                                 | done   | Codex     |
| P17-FE-010  | Extend `app/(dashboard)/page.tsx` — Widget "Contacts chauds" (count `email_score >= 50`). Masqué si 0.                                                                                                          | done   | Codex     |

### Frontend Tests

| ID          | Test File                                                                                           | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------|--------|-------|
| P17-FT-001  | `tests/unit/stores/scoring-rules.test.ts`                                                           | done   | Codex     |
| P17-FT-002  | `tests/components/campaigns/sto-config.test.tsx`                                                    | done   | Codex     |
| P17-FT-003  | `tests/components/campaigns/dynamic-content-editor.test.tsx`                                        | done   | Codex     |
| P17-FT-004  | `tests/components/segments/segment-builder-score.test.tsx`                                          | done   | Codex     |
| P17-FT-005  | `tests/e2e/campaigns/dynamic-content-flow.spec.ts`                                                  | done   | Codex     |
| P17-FT-006  | `tests/e2e/campaigns/sto-flow.spec.ts`                                                              | done   | Codex     |

---

## Sprint 58 — Hardening GDPR & CI (Weeks 156–158)

### Backend Tasks

| ID          | Task                                                                                                                                                           | Status | Owner |
|-------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P17-BE-021  | Extend `DataExportService` — Inclure `ContactScoreEvent` + `ScoringRule` dans l'export GDPR.                                                                   | done   | Codex     |
| P17-BE-022  | PHPStan level 8 + Pint sur tous les fichiers de la phase.                                                                                                       | done   | Codex     |

### Frontend Tasks

| ID          | Task                                                                                                                                                           | Status | Owner |
|-------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P17-FE-011  | ESLint + Prettier — 0 erreur sur tous les fichiers nouveaux/modifiés.                                                                                          | done   | Codex     |

### Backend Tests

| ID          | Test File                                                                                    | Status | Owner |
|-------------|----------------------------------------------------------------------------------------------|--------|-------|
| P17-BT-010  | `tests/Feature/Scoring/ContactScoreGdprTest.php`                                             | done   | Codex     |

---

## Récapitulatif

| Sprint    | Semaines | Livrable principal                                              | Tasks                        |
|-----------|----------|-----------------------------------------------------------------|------------------------------|
| Sprint 55 | 147–149  | Lead scoring backend (rules, events, service, segment filter)  | 3 INF + 11 BE + 4 tests      |
| Sprint 56 | 150–152  | STO + dynamic content backend (parser, validator, job étendu)  | 1 INF + 9 BE + 5 tests       |
| Sprint 57 | 153–155  | Frontend scoring UI + STO config + dynamic content editor      | 10 FE + 6 tests              |
| Sprint 58 | 156–158  | Hardening GDPR, PHPStan, ESLint, CI                            | 2 BE/FE + 1 test             |
| **Total** | **12 sem** | **v2.3.0**                                                   | **~37 tâches + 16 tests**    |
