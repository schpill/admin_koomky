# Phase 15 — Task Tracking

> **Status**: todo
> **Prerequisite**: Phase 14 fully merged and tagged `v2.0.0`
> **Spec**: [docs/phases/phase15.md](../phases/phase15.md)

---

## Sprint 47 — Backend Test Multi-Email, Personnalisation & Déduplication (Weeks 123–125)

### Infrastructure & Database

| ID              | Task                                                                                                                                                                          | Status | Owner |
|-----------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P15-BE-INF-00   | Migration `add_unique_contact_constraint_to_campaign_recipients_table` — Contrainte unique `(campaign_id, contact_id)`. Filet de sécurité DB-level contre les doublons.        | todo   | —     |

### Backend Tasks

| ID          | Task                                                                                                                                                                                                            | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P15-BE-001  | Extend `PersonalizationService` — `renderPreview()` avec contact fictif. Whitelist `client.*` étendue : `address`, `zip_code`, `industry`, `department`.                                                        | todo   | —     |
| P15-BE-002  | Extend `CampaignController::testSend()` — Validation `emails[]` (1–5 items). Résolution via `renderPreview()`. Boucle d'envoi multi-adresses.                                                                   | todo   | —     |
| P15-BE-003  | Extend `CampaignTestMail` — Accepter `renderedBody` et `renderedSubject` pré-résolus.                                                                                                                           | todo   | —     |
| P15-BE-004  | Create `StoreCampaignTestRequest` — `emails[]` (1–5, email max 255), `phones[]` (1–3, string max 20).                                                                                                           | todo   | —     |
| P15-BE-004b | Extend `SendEmailCampaignJob` — Déduplication : `->distinct()` sur query contacts + `CampaignRecipient::firstOrCreate(['campaign_id', 'contact_id'])`. Un contact matchant N critères du segment → 1 seul email. | todo   | —     |

### Backend Tests (TDD)

| ID          | Test File                                                                         | Status | Owner |
|-------------|-----------------------------------------------------------------------------------|--------|-------|
| P15-BT-001  | `tests/Unit/Services/PersonalizationServiceTest.php` (extension)                  | todo   | —     |
| P15-BT-002  | `tests/Feature/Campaigns/CampaignTestSendTest.php`                                | todo   | —     |
| P15-BT-002b | `tests/Feature/Campaigns/CampaignDeduplicateRecipientsTest.php`                   | todo   | —     |

---

## Sprint 48 — Backend A/B Testing (Weeks 126–128)

### Infrastructure & Database

| ID              | Task                                                                                                                                                                        | Status | Owner |
|-----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P15-BE-INF-01   | Migration `create_campaign_variants_table` — UUID PK, campaign_id FK, label VARCHAR(2), subject/content nullable, send_percent TINYINT, sent/open/click counts, timestamps. Index unique `(campaign_id, label)`. | todo | — |
| P15-BE-INF-02   | Migration `add_ab_testing_fields_to_campaigns_table` — `is_ab_test` boolean, `ab_winner_variant_id` FK nullable, `ab_winner_selected_at`, `ab_winner_criteria`, `ab_auto_select_after_hours`. | todo | — |
| P15-BE-INF-03   | Migration `add_variant_id_to_campaign_recipients_table` — `variant_id` UUID FK nullable → campaign_variants SET NULL. Index `(campaign_id, variant_id)`.                    | todo   | —     |

### Backend Tasks

| ID          | Task                                                                                                                                                                                                            | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P15-BE-005  | Create `CampaignVariant` model — `HasUuids`, `HasFactory`. Fillable, casts entiers. Relation `campaign()`. Méthodes `openRate()`, `clickRate()`.                                                                 | todo   | —     |
| P15-BE-006  | Extend `Campaign` model — Champs A/B dans `$fillable` + casts. Relations `variants()` HasMany, `winnerVariant()` BelongsTo. Méthode `isAbTest(): bool`.                                                         | todo   | —     |
| P15-BE-007  | Extend `CampaignRecipient` model — `variant_id` dans `$fillable`. Relation `variant()` BelongsTo `CampaignVariant`.                                                                                             | todo   | —     |
| P15-BE-008  | Extend `StoreCampaignRequest` — Validation A/B : `is_ab_test`, `variants[]`, `send_percent`, `ab_winner_criteria`, `ab_auto_select_after_hours`. Règle custom : somme send_percent = 100.                       | todo   | —     |
| P15-BE-009  | Extend `CampaignController` — `store()`/`update()` sync variants. `show()` charge variants. Méthode `selectWinner()` : `POST /campaigns/{id}/ab/select-winner`.                                                 | todo   | —     |
| P15-BE-010  | Extend `SendEmailCampaignJob` — Détection `isAbTest()`, split aléatoire selon send_percent, assignment `variant_id`, dispatch `SelectAbWinnerJob` si auto-select configuré.                                     | todo   | —     |
| P15-BE-011  | Extend `SendCampaignEmailJob` — Si `variant_id` présent : utiliser subject/content du variant. Incrémenter `variant->sent_count`.                                                                               | todo   | —     |
| P15-BE-012  | Create `SelectAbWinnerJob` — Queue `default`. Calcule open_rate ou click_rate par variante. Sélectionne le gagnant. Met à jour `ab_winner_variant_id` + `ab_winner_selected_at`.                                | todo   | —     |
| P15-BE-013  | Extend `EmailTrackingController::open()` + `click()` — Incrémenter `variant->open_count` / `variant->click_count` via `recipient->variant_id` si présent.                                                      | todo   | —     |
| P15-BE-014  | Extend `CampaignAnalyticsController::show()` — Inclure `ab_variants[]` si `is_ab_test` : label, sent/open/click counts, rates, is_winner.                                                                      | todo   | —     |
| P15-BE-015  | Register route `POST /campaigns/{campaign}/ab/select-winner` dans le groupe v1 authentifié.                                                                                                                     | todo   | —     |

### Backend Tests (TDD)

| ID          | Test File                                                                         | Status | Owner |
|-------------|-----------------------------------------------------------------------------------|--------|-------|
| P15-BT-003  | `tests/Unit/Jobs/SelectAbWinnerJobTest.php`                                       | todo   | —     |
| P15-BT-004  | `tests/Feature/Campaigns/CampaignAbTestCrudTest.php`                              | todo   | —     |
| P15-BT-005  | `tests/Feature/Campaigns/CampaignAbTestAnalyticsTest.php`                         | todo   | —     |
| P15-BT-006  | `tests/Feature/Campaigns/CampaignAbTestSendTest.php`                              | todo   | —     |

---

## Sprint 49 — Frontend A/B Testing & Variables UI (Weeks 129–131)

### Frontend Tasks

| ID          | Task                                                                                                                                                                                                            | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P15-FE-001  | Extend `lib/stores/campaigns.ts` — Types `CampaignVariant`, extension `CampaignAnalytics.ab_variants[]`. Action `selectAbWinner(campaignId, variantId)`.                                                        | todo   | —     |
| P15-FE-002  | Extend `components/campaigns/test-send-modal.tsx` — Tags-input multi-email (1–5). Validation format. Badge info variables fictives.                                                                              | todo   | —     |
| P15-FE-003  | Create `components/campaigns/personalization-variables-panel.tsx` — Liste toutes les variables avec description et bouton "Copier".                                                                              | todo   | —     |
| P15-FE-004  | Extend pages `campaigns/create` + `campaigns/[id]` — Intégrer `PersonalizationVariablesPanel` à côté de l'éditeur.                                                                                              | todo   | —     |
| P15-FE-005  | Create `components/campaigns/ab-test-config.tsx` — Toggle A/B, formulaires variantes A/B, slider split %, critère gagnant, heures auto-select.                                                                  | todo   | —     |
| P15-FE-006  | Extend `app/(dashboard)/campaigns/create/page.tsx` — Intégrer `AbTestConfig`. Si A/B activé : masquer subject/content racine, afficher variantes.                                                               | todo   | —     |
| P15-FE-007  | Create `components/campaigns/ab-test-results.tsx` — Tableau comparatif variantes avec badge gagnant. Bouton "Sélectionner comme gagnant" si manual + pas de gagnant.                                            | todo   | —     |
| P15-FE-008  | Extend `app/(dashboard)/campaigns/[id]/page.tsx` — Afficher `AbTestResults` si `campaign.is_ab_test`.                                                                                                           | todo   | —     |

### Frontend Tests

| ID          | Test File                                                                                           | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------|--------|-------|
| P15-FT-001  | `tests/unit/stores/campaigns-ab.test.ts`                                                            | todo   | —     |
| P15-FT-002  | `tests/components/campaigns/test-send-modal.test.tsx` (extension)                                   | todo   | —     |
| P15-FT-003  | `tests/components/campaigns/personalization-variables-panel.test.tsx`                               | todo   | —     |
| P15-FT-004  | `tests/components/campaigns/ab-test-config.test.tsx`                                                | todo   | —     |
| P15-FT-005  | `tests/components/campaigns/ab-test-results.test.tsx`                                               | todo   | —     |
| P15-FT-006  | `tests/e2e/campaigns/ab-test-flow.spec.ts`                                                          | todo   | —     |
| P15-FT-007  | `tests/e2e/campaigns/test-send-multi-email.spec.ts`                                                 | todo   | —     |

---

## Sprint 50 — Hardening GDPR & CI (Weeks 132–134)

### Backend Tasks

| ID          | Task                                                                                                                                                           | Status | Owner |
|-------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P15-BE-016  | PHPStan level 8 — 0 erreur sur tous les fichiers nouveaux/modifiés.                                                                                            | todo   | —     |
| P15-BE-017  | Pint — 0 erreur sur tous les fichiers PHP nouveaux/modifiés.                                                                                                   | todo   | —     |
| P15-BE-018  | Extend `DataExportService` — Inclure `CampaignVariant` dans l'export GDPR (variantes par campagne, compteurs agrégés, sans données personnelles recipients).   | todo   | —     |

### Frontend Tasks

| ID          | Task                                                                                                                                                           | Status | Owner |
|-------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P15-FE-009  | ESLint + Prettier — 0 erreur sur tous les fichiers nouveaux/modifiés.                                                                                          | todo   | —     |
| P15-FE-010  | Extend `app/(dashboard)/page.tsx` — Widget "A/B Tests actifs" : count campagnes `is_ab_test + status=sending`. Masqué si 0.                                    | todo   | —     |

### Backend Tests

| ID          | Test File                                                                         | Status | Owner |
|-------------|-----------------------------------------------------------------------------------|--------|-------|
| P15-BT-007  | `tests/Feature/Campaigns/CampaignAbTestGdprTest.php`                              | todo   | —     |

---

## Récapitulatif

| Sprint    | Semaines | Livrable principal                                              | Tasks                        |
|-----------|----------|-----------------------------------------------------------------|------------------------------|
| Sprint 47 | 123–125  | Test multi-email + variables résolues + déduplication destinataires | 1 INF + 5 BE + 3 tests       |
| Sprint 48 | 126–128  | A/B Testing backend (model, split job, tracking, analytics)        | 3 INF + 11 BE + 4 tests      |
| Sprint 49 | 129–131  | Frontend A/B config + résultats + variables panel + test modal     | 8 FE + 7 tests               |
| Sprint 50 | 132–134  | Hardening GDPR, PHPStan, ESLint, dashboard widget                 | 3 BE/FE + 1 test             |
| **Total** | **12 sem** | **v2.1.0**                                                     | **~31 tâches + 15 tests**    |
