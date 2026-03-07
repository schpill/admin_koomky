# Phase 20 — Task Tracking

> **Status**: done
> **Prerequisite**: Phase 19 fully merged and tagged `v2.5.0`
> **Spec**: [docs/phases/phase20.md](../phases/phase20.md)

---

## Sprint 67 — Backend Preference Center & Timezone STO (Weeks 183–185)

### Infrastructure & Database

| ID              | Task                                                                                                                                                                        | Status | Owner |
|-----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P20-BE-INF-01   | Migration `create_communication_preferences_table` — UUID PK, user_id FK, contact_id FK CASCADE, category ENUM(newsletter/promotional/transactional), subscribed BOOLEAN DEFAULT true, updated_at, timestamps. Index unique `(contact_id, category)`. | done   | Codex |
| P20-BE-INF-02   | Migration `add_timezone_to_contacts_table` — `timezone` VARCHAR(64) nullable.                                                                                               | done   | Codex |
| P20-BE-INF-03   | Migration `add_category_to_campaigns_table` — `email_category` ENUM(newsletter/promotional/transactional) DEFAULT promotional.                                              | done   | Codex |

### Backend Tasks

| ID          | Task                                                                                                                                                                                                            | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P20-BE-001  | Create `CommunicationPreference` model — `HasUuids`, `HasFactory`. Fillable, casts subscribed bool. Relations `contact()`.                                                                                      | done   | Codex |
| P20-BE-002  | Create `PreferenceCenterService` — `getPreferences(Contact $contact): Collection`, `updatePreference(Contact $contact, string $category, bool $subscribed): void`, `isAllowed(Contact $contact, string $category): bool`. Crée les 3 préférences par défaut si absentes. | done   | Codex |
| P20-BE-003  | Extend `Campaign` model — `email_category` dans `$fillable` + cast.                                                                                                                                             | done   | Codex |
| P20-BE-004  | Extend `StoreCampaignRequest` — `email_category` ENUM nullable (défaut promotional).                                                                                                                            | done   | Codex |
| P20-BE-005  | Extend `SendEmailCampaignJob` — Avant dispatch : `PreferenceCenterService::isAllowed($contact, $campaign->email_category)`. Skip si false (sauf transactionnel).                                                | done   | Codex |
| P20-BE-006  | Extend `SendDripStepEmailJob` et `SendWorkflowEmailJob` — Même logique de vérification préférence.                                                                                                              | done   | Codex |
| P20-BE-007  | Create `PreferenceCenterController` (portail) — `GET /portal/preferences/{contact}` (URL signée), `POST /portal/preferences/{contact}`.                                                                         | done   | Codex |
| P20-BE-008  | Extend `PersonalizationService` — Ajouter variable `{{preferences_url}}` : génère l'URL signée portail/preferences pour le contact courant.                                                                     | done   | Codex |
| P20-BE-009  | Extend `ContactSendTimeService::getOptimalHour()` — Prendre en compte `contact->timezone`. Requête SQL avec `AT TIME ZONE`. Fallback UTC si null.                                                               | done   | Codex |
| P20-BE-010  | Extend `ContactSendTimeService::getNextSendDelay()` — Calculer la prochaine occurrence de optimal_hour en heure locale du contact, convertir en UTC.                                                            | done   | Codex |
| P20-BE-011  | Extend `Contact` model — `timezone` dans `$fillable`. Extend `EmailTrackingController::click()` — Détecter timezone via IP si `contact->timezone` null.                                                         | done   | Codex |
| P20-BE-012  | PHPStan level 8 + Pint.                                                                                                                                                                                         | done   | Codex |

### Backend Tests (TDD)

| ID          | Test File                                                                                    | Status | Owner |
|-------------|----------------------------------------------------------------------------------------------|--------|-------|
| P20-BT-001  | `tests/Unit/Services/PreferenceCenterServiceTest.php`                                        | done   | Codex |
| P20-BT-002  | `tests/Feature/Portal/PreferenceCenterPortalTest.php`                                        | done   | Codex |
| P20-BT-003  | `tests/Feature/Campaign/CampaignPreferenceFilterTest.php`                                    | done   | Codex |
| P20-BT-004  | `tests/Unit/Services/ContactSendTimeServiceTimezoneTest.php`                                 | done   | Codex |

---

## Sprint 68 — Backend Rapports & Score Délivrabilité (Weeks 186–188)

### Backend Tasks

| ID          | Task                                                                                                                                                                                                            | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P20-BE-013  | Create `CampaignReportService` — `getFullReport(Campaign $campaign): array` (summary + links + timeline). `exportCsv(): StreamedResponse`. `exportPdf(): StreamedResponse` (barryvdh/laravel-dompdf).          | done   | Codex |
| P20-BE-014  | Create `resources/views/reports/campaign-report.blade.php` — Template HTML rapport PDF : summary, tableau liens, graphique timeline SVG inline.                                                                 | done   | Codex |
| P20-BE-015  | Extend `CampaignController` — `GET /campaigns/{campaign}/report` (JSON), `GET /campaigns/{campaign}/report/pdf` (stream), `GET /campaigns/{campaign}/report/csv` (stream). Policy ownership.                   | done   | Codex |
| P20-BE-016  | Create `DeliverabilityScoreService` — `analyze(string $subject, string $htmlContent): array{score:int, issues:array}`. Règles : mots spam, ratio texte/images, lien désabonnement, longueur sujet, majuscules, alt images, HTML invalide. | done   | Codex |
| P20-BE-017  | Extend `CampaignController::store()`/`update()` — Appeler `DeliverabilityScoreService::analyze()`. Retourner `deliverability` dans la réponse (warnings non bloquants).                                        | done   | Codex |
| P20-BE-018  | PHPStan level 8 + Pint.                                                                                                                                                                                         | done   | Codex |

### Backend Tests (TDD)

| ID          | Test File                                                                                    | Status | Owner |
|-------------|----------------------------------------------------------------------------------------------|--------|-------|
| P20-BT-005  | `tests/Unit/Services/CampaignReportServiceTest.php`                                          | done   | Codex |
| P20-BT-006  | `tests/Feature/Campaign/CampaignReportExportTest.php`                                        | done   | Codex |
| P20-BT-007  | `tests/Unit/Services/DeliverabilityScoreServiceTest.php`                                     | done   | Codex |

---

## Sprint 69 — Frontend Preferences, Timezone & Rapports (Weeks 189–191)

### Frontend Tasks

| ID          | Task                                                                                                                                                                                                            | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P20-FE-001  | Create `app/(portal)/preferences/[contact]/page.tsx` — Page portail préférences : 3 toggles par catégorie, bouton "Se désabonner de tout", message confirmation.                                                | done   | Codex |
| P20-FE-002  | Extend `components/campaigns/campaign-form.tsx` — Select `email_category`. Badge délivrabilité (0–100) mis à jour en temps réel. Liste des problèmes détectés.                                                  | done   | Codex |
| P20-FE-003  | Extend fiche contact — Champ timezone : Select IANA filtrable (react-select). Affichage heure locale actuelle du contact.                                                                                       | done   | Codex |
| P20-FE-004  | Extend `components/campaigns/analytics.tsx` — Boutons "Exporter PDF" et "Exporter CSV". Graphique timeline opens/clicks (recharts LineChart).                                                                   | done   | Codex |
| P20-FE-005  | Create `components/campaigns/deliverability-badge.tsx` — Badge coloré (vert ≥ 80, orange 50–79, rouge < 50) + popover issues avec icône sévérité.                                                              | done   | Codex |
| P20-FE-006  | Extend `lib/stores/campaigns.ts` — `email_category` dans payload. Stocker `deliverability` retourné par l'API.                                                                                                  | done   | Codex |

### Frontend Tests

| ID          | Test File                                                                                           | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------|--------|-------|
| P20-FT-001  | `tests/components/preferences/preference-center.test.tsx`                                           | done   | Codex |
| P20-FT-002  | `tests/components/campaigns/deliverability-badge.test.tsx`                                          | done   | Codex |
| P20-FT-003  | `tests/components/campaigns/campaign-analytics-export.test.tsx`                                     | done   | Codex |
| P20-FT-004  | `tests/e2e/portal/preference-center-flow.spec.ts`                                                   | done   | Codex |
| P20-FT-005  | `tests/e2e/campaigns/report-export-flow.spec.ts`                                                    | done   | Codex |

---

## Sprint 70 — Hardening GDPR & CI (Weeks 192–194)

### Backend Tasks

| ID          | Task                                                                                           | Status | Owner |
|-------------|------------------------------------------------------------------------------------------------|--------|-------|
| P20-BE-019  | Extend `DataExportService` — Inclure `CommunicationPreference` dans l'export GDPR.            | done   | Codex |
| P20-BE-020  | PHPStan level 8 + Pint sur tous les fichiers de la phase.                                      | done   | Codex |

### Frontend Tasks

| ID          | Task                                                                                           | Status | Owner |
|-------------|------------------------------------------------------------------------------------------------|--------|-------|
| P20-FE-007  | ESLint + Prettier — 0 erreur sur tous les fichiers nouveaux/modifiés.                          | done   | Codex |

### Backend Tests

| ID          | Test File                                                                                    | Status | Owner |
|-------------|----------------------------------------------------------------------------------------------|--------|-------|
| P20-BT-008  | `tests/Feature/Portal/PreferenceCenterGdprTest.php`                                          | done   | Codex |

---

## Récapitulatif

| Sprint    | Semaines | Livrable principal                                              | Tasks                           |
|-----------|----------|-----------------------------------------------------------------|---------------------------------|
| Sprint 67 | 183–185  | Backend preference center + timezone STO                       | 3 INF + 12 BE + 4 tests         |
| Sprint 68 | 186–188  | Backend rapports exportables + score délivrabilité             | 6 BE + 3 tests                  |
| Sprint 69 | 189–191  | Frontend préférences portail + timezone + exports + badge      | 6 FE + 5 tests                  |
| Sprint 70 | 192–194  | Hardening GDPR, PHPStan, ESLint, CI                            | 2 BE/FE + 1 test                |
| **Total** | **12 sem** | **v2.6.0**                                                   | **~3 INF + 26 BE/FE + 13 tests** |
