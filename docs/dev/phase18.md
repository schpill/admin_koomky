# Phase 18 — Task Tracking

> **Status**: todo
> **Prerequisite**: Phase 17 fully merged and tagged `v2.3.0`
> **Spec**: [docs/phases/phase18.md](../phases/phase18.md)

---

## Sprint 59 — Backend Click Tracking (Weeks 159–161)

### Infrastructure & Database

| ID              | Task                                                                                                                                                                        | Status | Owner |
|-----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P18-BE-INF-01   | Migration `create_campaign_link_clicks_table` — UUID PK, user_id FK, campaign_id FK, recipient_id FK, contact_id FK, url VARCHAR(2048), clicked_at TIMESTAMP, ip_address VARCHAR(45) nullable, user_agent TEXT nullable. Index `(recipient_id)`, `(campaign_id, url)`. | todo   |       |

### Backend Tasks

| ID          | Task                                                                                                                                                                                                            | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P18-BE-001  | Create `CampaignLinkClick` model — `HasUuids`, `HasFactory`. Fillable, casts clicked_at datetime. Relations `campaign()`, `recipient()`, `contact()`.                                                           | todo   |       |
| P18-BE-002  | Extend `PersonalizationService::render()` — Réécrire les `<a href="...">` en `/t/click/{token}?url={urlEncodée}`. Ne pas réécrire mailto:, tel:, ni les URLs déjà trackées.                                     | todo   |       |
| P18-BE-003  | Extend `EmailTrackingController::click()` — Créer `CampaignLinkClick`. Mettre à jour `CampaignRecipient.clicked_at` si null. Appeler `ContactScoreService::recordEvent('email_clicked')` si premier clic sur cette URL. | todo   |       |
| P18-BE-004  | Extend `CampaignAnalyticsService` — Ajouter `getLinkStats(Campaign $campaign): Collection` : pour chaque URL unique `{url, total_clicks, unique_clicks, click_rate}`.                                           | todo   |       |
| P18-BE-005  | Extend `CampaignController` — Route `GET /campaigns/{campaign}/links` + `GET /campaigns/{campaign}/links/export` (stream CSV).                                                                                  | todo   |       |
| P18-BE-006  | PHPStan level 8 + Pint.                                                                                                                                                                                         | todo   |       |

### Backend Tests (TDD)

| ID          | Test File                                                                                    | Status | Owner |
|-------------|----------------------------------------------------------------------------------------------|--------|-------|
| P18-BT-001  | `tests/Unit/Services/PersonalizationServiceLinkRewriteTest.php`                              | todo   |       |
| P18-BT-002  | `tests/Feature/Campaign/CampaignLinkClickTest.php`                                           | todo   |       |
| P18-BT-003  | `tests/Feature/Campaign/CampaignLinkAnalyticsTest.php`                                       | todo   |       |

---

## Sprint 60 — Backend Webhooks Email & IP Warm-up (Weeks 162–164)

### Infrastructure & Database

| ID              | Task                                                                                                                                                                        | Status | Owner |
|-----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P18-BE-INF-02   | Migration `create_email_warmup_plans_table` — UUID PK, user_id FK, name, status ENUM, daily_volume_start INT, daily_volume_max INT, increment_percent TINYINT, current_day SMALLINT, current_daily_limit INT, started_at nullable, timestamps. | todo   |       |
| P18-BE-INF-03   | Migration `add_warmup_fields_to_users_table` — `warmup_sent_today` INT DEFAULT 0, `warmup_last_reset_at` DATE nullable.                                                     | todo   |       |

### Backend Tasks

| ID          | Task                                                                                                                                                                                                            | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P18-BE-007  | Extend `WebhookDispatchService` — Ajouter event types `email.opened`, `email.clicked`, `email.bounced`, `email.unsubscribed`, `email.campaign_sent`. Dispatcher depuis les controllers/observers appropriés.    | todo   |       |
| P18-BE-008  | Create `EmailWarmupPlan` model — `HasUuids`, `HasFactory`. Fillable, casts. Relations `user()`. Scope `activeForUser(User $user)`. Méthode `advancePlan(): void`.                                               | todo   |       |
| P18-BE-009  | Create `WarmupGuardService` — `canSend(User $user): bool`, `incrementSentCount(User $user): void`, `resetDailyCountIfNeeded(User $user): void`.                                                                 | todo   |       |
| P18-BE-010  | Create `EmailWarmupPlanController` — CRUD + `PATCH /{plan}/pause` + `PATCH /{plan}/resume`.                                                                                                                     | todo   |       |
| P18-BE-011  | Extend `SendEmailCampaignJob` — Si plan warm-up actif : vérifier `canSend()` avant dispatch, stopper et replanifier si quota atteint.                                                                           | todo   |       |
| P18-BE-012  | Create `ResetWarmupCountersJob` — Schedulé quotidiennement 00:01. Reset compteurs + advance plan + passe `completed` si plafond atteint.                                                                        | todo   |       |
| P18-BE-013  | PHPStan level 8 + Pint.                                                                                                                                                                                         | todo   |       |

### Backend Tests (TDD)

| ID          | Test File                                                                                    | Status | Owner |
|-------------|----------------------------------------------------------------------------------------------|--------|-------|
| P18-BT-004  | `tests/Feature/Campaign/CampaignEmailWebhooksTest.php`                                       | todo   |       |
| P18-BT-005  | `tests/Unit/Services/WarmupGuardServiceTest.php`                                             | todo   |       |
| P18-BT-006  | `tests/Feature/Campaign/CampaignWarmupTest.php`                                              | todo   |       |
| P18-BT-007  | `tests/Unit/Jobs/ResetWarmupCountersJobTest.php`                                             | todo   |       |

---

## Sprint 61 — Frontend Click Analytics & Warm-up UI (Weeks 165–167)

### Frontend Tasks

| ID          | Task                                                                                                                                                                                                            | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P18-FE-001  | Create `lib/stores/warmup-plans.ts` Zustand store — state plans[], currentPlan. Actions : fetch, create, update, delete, pause, resume.                                                                         | todo   |       |
| P18-FE-002  | Extend `components/campaigns/analytics.tsx` — Onglet "Liens" : tableau URLs (clics totaux, clics uniques, taux), tri desc, bouton export CSV.                                                                   | todo   |       |
| P18-FE-003  | Create `app/(dashboard)/settings/warmup/page.tsx` — Formulaire création plan + vue progression (jour courant, limite du jour, sparkline).                                                                       | todo   |       |
| P18-FE-004  | Extend sidebar Settings — Entrée "Warm-up IP".                                                                                                                                                                  | todo   |       |
| P18-FE-005  | Extend `app/(dashboard)/page.tsx` — Widget "Warm-up en cours" (jour / limite / envoyé aujourd'hui). Masqué si aucun plan actif.                                                                                 | todo   |       |
| P18-FE-006  | Extend webhook settings UI — Ajouter les event types email dans la liste des événements disponibles.                                                                                                            | todo   |       |

### Frontend Tests

| ID          | Test File                                                                                           | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------|--------|-------|
| P18-FT-001  | `tests/unit/stores/warmup-plans.test.ts`                                                            | todo   |       |
| P18-FT-002  | `tests/components/campaigns/campaign-link-analytics.test.tsx`                                       | todo   |       |
| P18-FT-003  | `tests/components/settings/warmup-plan-form.test.tsx`                                               | todo   |       |
| P18-FT-004  | `tests/e2e/campaigns/click-tracking-flow.spec.ts`                                                   | todo   |       |
| P18-FT-005  | `tests/e2e/settings/warmup-plan-flow.spec.ts`                                                       | todo   |       |

---

## Sprint 62 — Hardening GDPR & CI (Weeks 168–170)

### Backend Tasks

| ID          | Task                                                                                           | Status | Owner |
|-------------|------------------------------------------------------------------------------------------------|--------|-------|
| P18-BE-014  | Extend `DataExportService` — Inclure `CampaignLinkClick` dans l'export GDPR.                  | todo   |       |
| P18-BE-015  | PHPStan level 8 + Pint sur tous les fichiers de la phase.                                      | todo   |       |

### Frontend Tasks

| ID          | Task                                                                                           | Status | Owner |
|-------------|------------------------------------------------------------------------------------------------|--------|-------|
| P18-FE-007  | ESLint + Prettier — 0 erreur sur tous les fichiers nouveaux/modifiés.                          | todo   |       |

### Backend Tests

| ID          | Test File                                                                                    | Status | Owner |
|-------------|----------------------------------------------------------------------------------------------|--------|-------|
| P18-BT-008  | `tests/Feature/Campaign/CampaignLinkClickGdprTest.php`                                       | todo   |       |

---

## Récapitulatif

| Sprint    | Semaines | Livrable principal                                              | Tasks                        |
|-----------|----------|-----------------------------------------------------------------|------------------------------|
| Sprint 59 | 159–161  | Backend click tracking par URL + analytics                     | 1 INF + 6 BE + 3 tests       |
| Sprint 60 | 162–164  | Backend webhooks email events + IP warm-up                     | 2 INF + 7 BE + 4 tests       |
| Sprint 61 | 165–167  | Frontend analytics par lien + warm-up UI + webhooks settings   | 6 FE + 5 tests               |
| Sprint 62 | 168–170  | Hardening GDPR, PHPStan, ESLint, CI                            | 2 BE/FE + 1 test             |
| **Total** | **12 sem** | **v2.4.0**                                                   | **~3 INF + 21 BE/FE + 13 tests** |
