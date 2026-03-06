# Phase 18 — Click Tracking par URL, Webhooks Email & IP Warm-up (v2.4)

| Field               | Value                                                        |
|---------------------|--------------------------------------------------------------|
| **Phase**           | 18                                                           |
| **Name**            | Click Tracking par URL, Webhooks Email & IP Warm-up          |
| **Duration**        | Weeks 159–170 (12 weeks)                                     |
| **Milestone**       | M18 — v2.4.0 Release                                        |
| **PRD Sections**    | §4.32 FR-CLICK (nouveau), §4.33 FR-WHOOK (nouveau), §4.34 FR-WARMUP (nouveau) |
| **Prerequisite**    | Phase 17 fully completed and tagged `v2.3.0`                 |
| **Status**          | todo                                                         |

---

## 1. Phase Objectives

| ID        | Objective                                                                                                                                                    |
|-----------|--------------------------------------------------------------------------------------------------------------------------------------------------------------|
| P18-OBJ-1 | Tracker les **clics par URL** dans les emails : savoir quelle URL a été cliquée, combien de fois, et par quel contact                                        |
| P18-OBJ-2 | Exposer des **analytics par lien** dans les rapports de campagne (tableau des URLs + taux de clic individuel)                                                |
| P18-OBJ-3 | Émettre des **webhooks sortants** sur tous les événements email : `email.opened`, `email.clicked`, `email.bounced`, `email.unsubscribed`                     |
| P18-OBJ-4 | Implémenter un **plan de warm-up IP/domaine** : planification automatique d'une montée en charge progressive des volumes d'envoi SES                        |
| P18-OBJ-5 | Protéger la délivrabilité en limitant les envois quotidiens au volume autorisé par le plan de warm-up actif                                                  |
| P18-OBJ-6 | Maintenir une couverture de tests >= 80% backend et frontend                                                                                                 |

---

## 2. Contexte & Analyse de l'existant

### 2.1 Ce qui est déjà en place

| Besoin | Brique existante | Phase d'origine |
|--------|-----------------|-----------------|
| `CampaignRecipient` avec `clicked_at` (premier clic global) | Phase 3 | Phase 3 |
| `EmailTrackingController::click()` — redirect après tracking | Phase 3 | Phase 3 |
| `WebhookDispatchService` + `WebhookDispatchJob` | Phase 7 | Phase 7 |
| Table `webhooks` avec event types | Phase 7 | Phase 7 |
| `CampaignAnalyticsService` avec taux de clic global | Phase 3 | Phase 3 |
| `ContactScoreService::recordEvent('email_clicked')` | Phase 17 | Phase 17 |

### 2.2 Analyse du gap

| Besoin | Gap actuel | Solution Phase 18 |
|--------|-----------|-------------------|
| Clic par URL | `clicked_at` est une date unique, sans URL | Table `campaign_link_clicks` + rewrite des URLs sortantes |
| Analytics par lien | Aucun rapport par URL | Extension `CampaignAnalyticsService` + UI tableau des liens |
| Webhooks email events | Les webhooks (phase 7) couvrent les entités CRM (lead, invoice…), pas les events email | Ajout event types `email.*` dans `WebhookDispatchService` |
| IP warm-up | Aucun — envoi full volume immédiat | Table `email_warmup_plans` + `WarmupGuardService` + rate limiting dans `SendEmailCampaignJob` |

---

## 3. Choix techniques

### 3.1 Modèle de données

**Migrations :**

| Migration | Description |
|-----------|-------------|
| `create_campaign_link_clicks_table` | UUID PK, `user_id` FK, `campaign_id` FK, `recipient_id` FK, `contact_id` FK, `url` VARCHAR(2048) NOT NULL, `clicked_at` TIMESTAMP NOT NULL, `ip_address` VARCHAR(45) nullable, `user_agent` TEXT nullable. Index `(recipient_id)`, index `(campaign_id, url)`. |
| `create_email_warmup_plans_table` | UUID PK, `user_id` FK, `name` VARCHAR(255), `status` ENUM(`active`, `paused`, `completed`), `daily_volume_start` INT NOT NULL (volume J1), `daily_volume_max` INT NOT NULL (plafond cible), `increment_percent` TINYINT DEFAULT 30 (hausse quotidienne en %), `current_day` SMALLINT DEFAULT 0, `current_daily_limit` INT NOT NULL, `started_at` TIMESTAMP nullable, timestamps. Index unique `(user_id, status)` partiel sur `active`. |
| `add_warmup_sent_today_to_users_table` | `warmup_sent_today` INT DEFAULT 0, `warmup_last_reset_at` DATE nullable. Compteur remis à zéro chaque jour. |

### 3.2 Logique Click Tracking

```
Réécriture des URLs sortantes :
1. PersonalizationService::render() réécrit chaque <a href="..."> dans le contenu HTML :
   href="https://app/t/click/{token}?url={urlEncodée}"
   (le token identifie le recipient, l'url est l'URL cible)

EmailTrackingController::click() modifié :
1. Décoder le recipient via token
2. Enregistrer un CampaignLinkClick{url, clicked_at, ip_address, user_agent}
3. Mettre à jour CampaignRecipient.clicked_at si premier clic
4. Appeler ContactScoreService::recordEvent('email_clicked') si premier clic sur cette URL
5. Dispatch webhook email.clicked avec payload {campaign_id, contact_id, url, clicked_at}
6. Redirect vers l'URL cible
```

### 3.3 Logique Webhooks Email Events

```
Events à dispatcher via WebhookDispatchService :
- email.opened  : {campaign_id, contact_id, opened_at}
- email.clicked : {campaign_id, contact_id, url, clicked_at}
- email.bounced : {campaign_id, contact_id, bounce_type, bounced_at}
- email.unsubscribed : {campaign_id, contact_id, unsubscribed_at}

Intégration :
- EmailTrackingController::open() → dispatch email.opened
- EmailTrackingController::click() → dispatch email.clicked (avec url)
- CampaignRecipientObserver::updated() (bounce) → dispatch email.bounced
- UnsubscribeController / EmailTrackingController::unsubscribe() → dispatch email.unsubscribed

Filtrage côté webhook endpoint :
- L'utilisateur peut s'abonner uniquement à certains event types (déjà géré par la table webhooks)
```

### 3.4 Logique IP Warm-up

```
WarmupGuardService :
- canSend(User $user): bool → vérifie si warmup_sent_today < current_daily_limit
- incrementSentCount(User $user): void → incrémente warmup_sent_today
- resetDailyCountIfNeeded(User $user): void → remet à 0 si warmup_last_reset_at < today
- advancePlan(EmailWarmupPlan $plan): void → incrémente current_day, recalcule current_daily_limit
  (current_daily_limit = min(daily_volume_max, daily_volume_start * (1 + increment_percent/100)^current_day))

ResetWarmupCountersJob (schedulé quotidiennement, 00:01) :
- Pour chaque user avec plan actif : reset warmup_sent_today, advance plan

SendEmailCampaignJob modifié :
- Si user a un plan de warm-up actif :
  a. WarmupGuardService::resetDailyCountIfNeeded()
  b. Pour chaque email à envoyer : vérifier canSend() avant dispatch
  c. Si quota atteint : stocker les recipients restants dans une table de file d'attente warm-up
  d. Replanifier l'envoi des restants au lendemain (J+1, 06:00)
```

---

## 4. Entry Criteria

- Phase 17 exit criteria 100% satisfaits.
- CI verts sur `main`.
- `v2.3.0` tagué et déployé.
- `EmailTrackingController`, `WebhookDispatchService`, `PersonalizationService` stables.

---

## 5. Scope — Requirement Traceability

### 5.1 Click Tracking par URL (FR-CLICK)

| Feature | Priority | Included |
|---------|----------|----------|
| Réécriture des URLs dans PersonalizationService | High | Yes |
| Table `campaign_link_clicks` | High | Yes |
| Analytics par URL dans CampaignAnalyticsService | High | Yes |
| UI : tableau des liens dans la page analytics de campagne | High | Yes |
| Export CSV des clics par URL | Medium | Yes |
| Heatmap visuelle du contenu email (position des liens) | Low | No |
| Tracking des clics sur les campagnes SMS | Low | No |

### 5.2 Webhooks Email Events (FR-WHOOK)

| Feature | Priority | Included |
|---------|----------|----------|
| Webhook `email.opened` | High | Yes |
| Webhook `email.clicked` (avec URL) | High | Yes |
| Webhook `email.bounced` | High | Yes |
| Webhook `email.unsubscribed` | High | Yes |
| Webhook `email.campaign_sent` (fin d'envoi campagne) | Medium | Yes |
| Webhook `drip.step_sent` | Low | No |
| Interface UI pour tester un webhook email event | Medium | Yes |

### 5.3 IP Warm-up (FR-WARMUP)

| Feature | Priority | Included |
|---------|----------|----------|
| Table `email_warmup_plans` | High | Yes |
| `WarmupGuardService` (canSend, increment, advance) | High | Yes |
| Intégration dans `SendEmailCampaignJob` | High | Yes |
| `ResetWarmupCountersJob` quotidien | High | Yes |
| UI : création / suivi du plan de warm-up | High | Yes |
| UI : graphique de progression (volume envoyé / limite du jour) | Medium | Yes |
| Warm-up automatique basé sur la réputation SES (feedback loop) | Low | No |

---

## 6. Detailed Sprint Breakdown

### 6.1 Sprint 59 — Backend Click Tracking (Weeks 159–161)

#### 6.1.1 Infrastructure & Database

| ID              | Migration |
|-----------------|-----------|
| P18-BE-INF-01   | `create_campaign_link_clicks_table` — Voir §3.1 |

#### 6.1.2 Backend Tasks

| ID          | Task |
|-------------|------|
| P18-BE-001  | Create `CampaignLinkClick` model — `HasUuids`, `HasFactory`. Fillable : user_id, campaign_id, recipient_id, contact_id, url, clicked_at, ip_address, user_agent. Casts clicked_at datetime. Relations `campaign()`, `recipient()`, `contact()`. |
| P18-BE-002  | Extend `PersonalizationService::render()` — Avant le rendu final, réécrire toutes les `<a href="...">` du contenu HTML en `href="/t/click/{token}?url={urlEncodée}"`. Ne pas réécrire les liens `mailto:`, `tel:`, ni les URLs déjà trackées. |
| P18-BE-003  | Extend `EmailTrackingController::click()` — Créer `CampaignLinkClick{url, clicked_at, ip_address, user_agent}`. Mettre à jour `CampaignRecipient.clicked_at` si null. Appeler `ContactScoreService::recordEvent('email_clicked')` si premier clic sur cette URL pour ce recipient. |
| P18-BE-004  | Extend `CampaignAnalyticsService` — Ajouter `getLinkStats(Campaign $campaign): Collection` : pour chaque URL unique, retourne `{url, total_clicks, unique_clicks, click_rate}`. |
| P18-BE-005  | Extend `CampaignController` — Route `GET /campaigns/{campaign}/links` retourne `getLinkStats()`. Route `GET /campaigns/{campaign}/links/export` stream CSV. |
| P18-BE-006  | PHPStan level 8 + Pint. |

#### 6.1.3 Backend Tests (TDD)

| ID          | Test File |
|-------------|-----------|
| P18-BT-001  | `tests/Unit/Services/PersonalizationServiceLinkRewriteTest.php` — URLs réécrites, mailto/tel préservés, double réécriture évitée |
| P18-BT-002  | `tests/Feature/Campaign/CampaignLinkClickTest.php` — clic enregistré, premier clic met à jour recipient, score incrémenté, second clic sur même URL non comptabilisé deux fois dans score |
| P18-BT-003  | `tests/Feature/Campaign/CampaignLinkAnalyticsTest.php` — getLinkStats retourne URLs triées par clics, click_rate correct, export CSV stream |

---

### 6.2 Sprint 60 — Backend Webhooks Email & IP Warm-up (Weeks 162–164)

#### 6.2.1 Infrastructure & Database

| ID              | Migration |
|-----------------|-----------|
| P18-BE-INF-02   | `create_email_warmup_plans_table` — Voir §3.1 |
| P18-BE-INF-03   | `add_warmup_fields_to_users_table` — Voir §3.1 |

#### 6.2.2 Backend Tasks

| ID          | Task |
|-------------|------|
| P18-BE-007  | Extend `WebhookDispatchService` — Ajouter les event types `email.opened`, `email.clicked`, `email.bounced`, `email.unsubscribed`, `email.campaign_sent`. Dispatcher depuis `EmailTrackingController`, `CampaignRecipientObserver`, `UnsubscribeController`, `SendEmailCampaignJob` (fin d'envoi). |
| P18-BE-008  | Create `EmailWarmupPlan` model — `HasUuids`, `HasFactory`. Fillable, casts. Relations `user()`. Scope `activeForUser(User $user)`. Méthode `advancePlan(): void` (incrémente current_day, recalcule current_daily_limit). |
| P18-BE-009  | Create `WarmupGuardService` — `canSend(User $user): bool`, `incrementSentCount(User $user): void`, `resetDailyCountIfNeeded(User $user): void`. |
| P18-BE-010  | Create `EmailWarmupPlanController` — CRUD : `GET/POST /warmup-plans`, `PUT/DELETE /warmup-plans/{plan}`, `PATCH /warmup-plans/{plan}/pause`, `PATCH /warmup-plans/{plan}/resume`. |
| P18-BE-011  | Extend `SendEmailCampaignJob` — Si plan de warm-up actif pour l'user : vérifier `WarmupGuardService::canSend()` avant chaque dispatch. Stopper et replanifier les recipients restants si quota atteint. |
| P18-BE-012  | Create `ResetWarmupCountersJob` — Schedulé quotidiennement à 00:01. Pour chaque plan actif : `resetDailyCountIfNeeded()` + `advancePlan()`. Passe le plan en `completed` si `current_daily_limit >= daily_volume_max`. |
| P18-BE-013  | PHPStan level 8 + Pint. |

#### 6.2.3 Backend Tests (TDD)

| ID          | Test File |
|-------------|-----------|
| P18-BT-004  | `tests/Feature/Campaign/CampaignEmailWebhooksTest.php` — open/click/bounce/unsubscribe déclenchent les webhooks avec payload correct |
| P18-BT-005  | `tests/Unit/Services/WarmupGuardServiceTest.php` — canSend false si quota atteint, reset quotidien, compteur incrémenté |
| P18-BT-006  | `tests/Feature/Campaign/CampaignWarmupTest.php` — envoi bloqué au-delà du quota, recipients restants replanifiés |
| P18-BT-007  | `tests/Unit/Jobs/ResetWarmupCountersJobTest.php` — compteurs reset, plan avancé, plan complété si plafond atteint |

---

### 6.3 Sprint 61 — Frontend Click Analytics & Warm-up UI (Weeks 165–167)

#### 6.3.1 Frontend Tasks

| ID          | Task |
|-------------|------|
| P18-FE-001  | Create `lib/stores/warmup-plans.ts` Zustand store — state plans[], currentPlan. Actions : fetch, create, update, delete, pause, resume. |
| P18-FE-002  | Extend `components/campaigns/analytics.tsx` — Onglet "Liens" : tableau des URLs (URL tronquée, clics totaux, clics uniques, taux), tri par clics desc, bouton export CSV. |
| P18-FE-003  | Create `app/(dashboard)/settings/warmup/page.tsx` — Formulaire de création de plan : volume départ, volume cible, incrément %. Vue progression : jour courant / total estimé, volume limite du jour, graphique sparkline jours précédents. Boutons pause/reprendre. |
| P18-FE-004  | Extend sidebar Settings — Entrée "Warm-up IP" dans les paramètres. |
| P18-FE-005  | Extend `app/(dashboard)/page.tsx` — Widget "Warm-up en cours" : jour courant / limite du jour / volume envoyé aujourd'hui. Masqué si aucun plan actif. |
| P18-FE-006  | Extend webhook settings UI — Ajouter les event types email dans la liste des événements disponibles à la création d'un webhook. |

#### 6.3.2 Frontend Tests

| ID          | Test File |
|-------------|-----------|
| P18-FT-001  | `tests/unit/stores/warmup-plans.test.ts` |
| P18-FT-002  | `tests/components/campaigns/campaign-link-analytics.test.tsx` |
| P18-FT-003  | `tests/components/settings/warmup-plan-form.test.tsx` |
| P18-FT-004  | `tests/e2e/campaigns/click-tracking-flow.spec.ts` — créer campagne → envoyer → simuler clic → vérifier tableau liens |
| P18-FT-005  | `tests/e2e/settings/warmup-plan-flow.spec.ts` — créer plan → vérifier limites respectées lors de l'envoi |

---

### 6.4 Sprint 62 — Hardening GDPR & CI (Weeks 168–170)

#### 6.4.1 Backend Tasks

| ID          | Task |
|-------------|------|
| P18-BE-014  | Extend `DataExportService` — Inclure `CampaignLinkClick` dans l'export GDPR (URLs cliquées par le contact, sans données autres contacts). |
| P18-BE-015  | PHPStan level 8 + Pint — 0 erreur sur tous les fichiers nouveaux/modifiés. |

#### 6.4.2 Frontend Tasks

| ID          | Task |
|-------------|------|
| P18-FE-007  | ESLint + Prettier — 0 erreur sur tous les fichiers nouveaux/modifiés. |

#### 6.4.3 Backend Tests

| ID          | Test File |
|-------------|-----------|
| P18-BT-008  | `tests/Feature/Campaign/CampaignLinkClickGdprTest.php` — export GDPR inclut clics du contact, pas ceux d'autres contacts |

---

## 7. Exit Criteria

| Critère | Vérification |
|---------|-------------|
| Toutes les tâches `P18-BE-*` et `P18-FE-*` en statut `done` | `docs/dev/phase18.md` |
| Backend coverage >= 80% | CI green |
| Frontend coverage >= 80% | CI green |
| PHPStan level 8 — 0 erreur | CI green |
| Pint — 0 erreur | CI green |
| ESLint + Prettier — 0 erreur | CI green |
| Clic sur lien email crée une entrée `campaign_link_clicks` | Test manuel |
| Tableau des liens affiché dans analytics campagne | Test manuel |
| Webhook `email.clicked` reçu avec l'URL cliquée | Test manuel |
| Envoi bloqué quand quota warm-up atteint, repris le lendemain | Test manuel |
| Tag `v2.4.0` poussé sur GitHub | `git tag v2.4.0` |

---

## 8. Récapitulatif

| Sprint    | Semaines | Livrable principal                                              | Tasks                        |
|-----------|----------|-----------------------------------------------------------------|------------------------------|
| Sprint 59 | 159–161  | Backend click tracking par URL + analytics                     | 1 INF + 6 BE + 3 tests       |
| Sprint 60 | 162–164  | Backend webhooks email events + IP warm-up                     | 2 INF + 7 BE + 4 tests       |
| Sprint 61 | 165–167  | Frontend analytics par lien + warm-up UI + webhooks settings   | 6 FE + 5 tests               |
| Sprint 62 | 168–170  | Hardening GDPR, PHPStan, ESLint, CI                            | 2 BE/FE + 1 test             |
| **Total** | **12 sem** | **v2.4.0**                                                   | **~3 INF + 21 BE/FE + 13 tests** |
