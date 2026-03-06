# Phase 17 — Lead Scoring, Send Time Optimization & Dynamic Content (v2.3)

| Field               | Value                                                        |
|---------------------|--------------------------------------------------------------|
| **Phase**           | 17                                                           |
| **Name**            | Lead Scoring, Send Time Optimization & Dynamic Content       |
| **Duration**        | Weeks 147–158 (12 weeks)                                     |
| **Milestone**       | M17 — v2.3.0 Release                                        |
| **PRD Sections**    | §4.29 FR-SCORE (nouveau), §4.30 FR-STO (nouveau), §4.31 FR-DYN (nouveau) |
| **Prerequisite**    | Phase 16 fully completed and tagged `v2.2.0`                 |
| **Status**          | done                                                         |

---

## 1. Phase Objectives

| ID        | Objective                                                                                                                                                  |
|-----------|------------------------------------------------------------------------------------------------------------------------------------------------------------|
| P17-OBJ-1 | Implémenter un **système de scoring de contacts** : chaque interaction email (ouverture, clic, désabonnement, bounce) modifie un score numérique par contact |
| P17-OBJ-2 | Permettre la définition de **règles de scoring personnalisées** par l'utilisateur (points attribués par action, expiration des points)                      |
| P17-OBJ-3 | Exposer le score dans les segments (filtre `score >= N`) et dans la vue contact                                                                            |
| P17-OBJ-4 | Implémenter le **Send Time Optimization (STO)** : calculer la meilleure heure d'envoi par contact en se basant sur l'historique d'ouvertures               |
| P17-OBJ-5 | Permettre l'activation du STO sur une campagne (envoi étalé sur 24h selon les heures optimales par contact)                                                |
| P17-OBJ-6 | Implémenter le **contenu dynamique** dans les emails : blocs conditionnels `{{#if condition}}…{{/if}}` affichant du contenu selon les attributs du contact  |
| P17-OBJ-7 | Exposer un **éditeur de blocs dynamiques** dans l'interface de création de campagne                                                                        |
| P17-OBJ-8 | Maintenir une couverture de tests >= 80% backend et frontend                                                                                               |

---

## 2. Contexte & Analyse de l'existant

### 2.1 Ce qui est déjà en place

| Besoin | Brique existante | Phase d'origine |
|--------|-----------------|-----------------|
| `CampaignRecipient` avec opened_at, clicked_at, bounced_at, unsubscribed | Phase 3 | Phase 3 |
| `PersonalizationService` avec resolver `contact.*` et `client.*` | Phase 3 + 15 | Phase 3/15 |
| `SegmentFilterEngine` avec critères multi-opérateurs | Phase 7/14 | Phase 7/14 |
| `Contact` model avec `$fillable` étendu | Phase 2 | Phase 2 |
| `SendEmailCampaignJob` avec scheduled_at | Phase 3 | Phase 3 |

### 2.2 Analyse du gap

| Besoin | Gap actuel | Solution Phase 17 |
|--------|-----------|-------------------|
| Score par contact | Aucun champ score sur `Contact` | Colonne `email_score` INT + `ContactScoreService` |
| Règles de scoring | Inexistantes | Table `scoring_rules` + service d'évaluation |
| Filtre segment par score | `SegmentFilterEngine` ne connaît pas le score | Extension du moteur avec critère `email_score` |
| STO | `scheduled_at` fixe pour tous les recipients | `ContactSendTimeService` + envoi différé par recipient |
| Contenu dynamique | Variables simples `{{var}}` uniquement | Parser Handlebars-like `{{#if}}/{{else}}/{{/if}}` dans `PersonalizationService` |
| Éditeur blocs dynamiques | Aucun | `DynamicContentEditor` composant React |

---

## 3. Choix techniques

### 3.1 Modèle de données

**Migrations :**

| Migration | Description |
|-----------|-------------|
| `add_email_score_to_contacts_table` | `email_score` SMALLINT DEFAULT 0 NOT NULL. `email_score_updated_at` TIMESTAMP nullable. Index `(user_id, email_score)` pour les filtres de segment. |
| `create_scoring_rules_table` | UUID PK, `user_id` FK, `event` ENUM(`email_opened`, `email_clicked`, `email_bounced`, `email_unsubscribed`, `campaign_sent`), `points` SMALLINT NOT NULL (peut être négatif), `expiry_days` SMALLINT nullable (null = jamais). `is_active` BOOLEAN DEFAULT true. Timestamps. Index unique `(user_id, event)`. |
| `create_contact_score_events_table` | UUID PK, `user_id` FK, `contact_id` FK CASCADE, `event` VARCHAR(50), `points` SMALLINT, `source_campaign_id` UUID nullable FK, `expires_at` TIMESTAMP nullable, `created_at` TIMESTAMP. Index `(contact_id, expires_at)` pour recalcul. |
| `add_sto_fields_to_campaigns_table` | `use_sto` BOOLEAN DEFAULT false. `sto_window_hours` TINYINT DEFAULT 24 (fenêtre d'envoi max en heures). |

### 3.2 Logique Lead Scoring

```
Scoring Rules (configurées par l'user) :
- email_opened  : +10 pts, expiry 90 jours
- email_clicked : +20 pts, expiry 90 jours
- email_bounced : -5 pts, pas d'expiry
- email_unsubscribed : -50 pts, pas d'expiry
- campaign_sent : +1 pt, expiry 180 jours (pour mesurer l'engagement global)

ContactScoreService::recordEvent(Contact $contact, string $event, ?Campaign $campaign):
1. Charge la règle active pour l'event de l'user
2. Si règle inexistante → rien
3. Crée ContactScoreEvent{event, points, expires_at = now()+expiry_days}
4. Recalcule email_score = SUM(points WHERE expires_at IS NULL OR expires_at > now())
5. Met à jour contact.email_score + email_score_updated_at

Recalcul quotidien (RecalculateExpiredScoresJob) :
- Pour chaque contact ayant des événements expirés depuis la dernière exécution
- Recalcule email_score en ne comptant que les événements non-expirés
```

### 3.3 Logique Send Time Optimization (STO)

```
ContactSendTimeService::getOptimalHour(Contact $contact): ?int
1. Récupère tous les CampaignRecipient de l'user pour ce contact où opened_at IS NOT NULL
2. Groupe par heure de la journée (0–23)
3. Retourne l'heure avec le plus d'ouvertures
4. Si < 3 ouvertures historiques → retourne null (pas assez de données → envoi à scheduled_at normal)

Envoi avec STO (SendEmailCampaignJob modifié) :
1. Si campaign.use_sto = true :
   a. Pour chaque contact, calcule optimal_hour via ContactSendTimeService
   b. Calcule le délai jusqu'à la prochaine occurrence de optimal_hour dans la fenêtre sto_window_hours
   c. Dispatch SendCampaignEmailJob->delay(delai_calculé)
   d. Les contacts sans données STO (null) sont envoyés immédiatement
2. Si use_sto = false : comportement actuel inchangé
```

### 3.4 Logique Dynamic Content

```
Syntaxe supportée dans subject et content :
  {{#if contact.industry == "Wedding Planner"}}Contenu A{{/if}}
  {{#if client.department == "75"}}Paris{{else}}Province{{/if}}
  {{#if email_score >= 50}}Email VIP{{else}}Email standard{{/if}}

PersonalizationService::render() étendu :
1. Avant la résolution des variables simples, parser les blocs {{#if}}
2. Évaluer la condition (comparaisons ==, !=, >=, <=, >, <) contre les attributs du contact/client
3. Remplacer le bloc par la branche correcte
4. Puis résoudre les variables simples dans la branche retenue

renderPreview() :
- Évaluer les conditions avec les valeurs fictives (§3.3 Phase 15)
- Condition email_score : valeur fictive = 75 (affiche toujours la branche "score élevé" en preview)

Validation à la création de campagne :
- Backend : `DynamicContentValidatorService::validate(string $content): array{valid:bool, errors:[]}
  - Vérifie la syntaxe des blocs (ouverture/fermeture correctes, pas de nesting > 2 niveaux)
  - Vérifie que les variables dans les conditions existent dans la whitelist
```

### 3.5 Règles de scoring par défaut

À la création d'un compte (ou si aucune règle), des règles par défaut sont insérées :

| Événement | Points | Expiry |
|-----------|--------|--------|
| email_opened | +10 | 90 jours |
| email_clicked | +20 | 90 jours |
| email_bounced | -5 | jamais |
| email_unsubscribed | -50 | jamais |
| campaign_sent | +1 | 180 jours |

---

## 4. Entry Criteria

- Phase 16 exit criteria 100% satisfaits.
- CI verts sur `main`.
- `v2.2.0` tagué et déployé.
- `PersonalizationService`, `SegmentFilterEngine`, `SendEmailCampaignJob` stables.

---

## 5. Scope — Requirement Traceability

### 5.1 Lead Scoring (FR-SCORE)

| Feature | Priority | Included |
|---------|----------|----------|
| Colonne `email_score` sur `Contact` | High | Yes |
| Table `scoring_rules` configurables par user | High | Yes |
| Table `contact_score_events` (audit log des points) | High | Yes |
| `ContactScoreService` (record + recalcul) | High | Yes |
| Intégration dans `EmailTrackingController` (open/click/unsubscribe) | High | Yes |
| Intégration dans `CampaignRecipientObserver` (bounce) | High | Yes |
| Filtre `email_score` dans `SegmentFilterEngine` | High | Yes |
| Recalcul quotidien des scores expirés | Medium | Yes |
| UI : configuration des règles de scoring | High | Yes |
| UI : score affiché dans la vue contact | High | Yes |
| UI : filtre score dans le segment builder | High | Yes |
| Règles de scoring par défaut à la création | Medium | Yes |
| Scoring basé sur événements non-email (visite page, ouverture ticket) | Low | No |
| Score composite multi-canaux | Low | No |

### 5.2 Send Time Optimization (FR-STO)

| Feature | Priority | Included |
|---------|----------|----------|
| `ContactSendTimeService` (calcul heure optimale) | High | Yes |
| Toggle `use_sto` sur les campagnes | High | Yes |
| `sto_window_hours` configurable (défaut 24h) | Medium | Yes |
| Envoi différé par recipient selon heure optimale | High | Yes |
| Fallback immédiat si < 3 ouvertures historiques | High | Yes |
| UI : toggle STO dans le formulaire de campagne | High | Yes |
| UI : indicateur "X contacts avec heure optimale connue" | Medium | Yes |
| STO sur campagnes drip (phase 16) | Low | No |
| Apprentissage ML (modèle prédictif) | Low | No |

### 5.3 Dynamic Content (FR-DYN)

| Feature | Priority | Included |
|---------|----------|----------|
| Parser `{{#if condition}}…{{/if}}` dans `PersonalizationService` | High | Yes |
| Opérateurs de comparaison : ==, !=, >=, <=, >, < | High | Yes |
| Attributs supportés dans les conditions : `contact.*`, `client.*`, `email_score` | High | Yes |
| Branche `{{else}}` optionnelle | High | Yes |
| Nesting `{{#if}}` dans `{{#if}}` (max 2 niveaux) | Medium | Yes |
| Résolution dans `renderPreview()` (valeurs fictives) | High | Yes |
| Validation syntaxe backend (`DynamicContentValidatorService`) | High | Yes |
| UI : éditeur de blocs dynamiques avec assistant visuel | High | Yes |
| UI : prévisualisation du contenu résolu | Medium | Yes |
| Contenu dynamique dans les SMS | Low | No |
| Contenu dynamique dans les étapes drip | Low | Yes |

---

## 6. Detailed Sprint Breakdown

### 6.1 Sprint 55 — Backend Lead Scoring (Weeks 147–149)

#### 6.1.1 Infrastructure & Database

| ID              | Migration |
|-----------------|-----------|
| P17-BE-INF-01   | `add_email_score_to_contacts_table` — Voir §3.1 |
| P17-BE-INF-02   | `create_scoring_rules_table` — Voir §3.1 |
| P17-BE-INF-03   | `create_contact_score_events_table` — Voir §3.1 |

#### 6.1.2 Backend — Modèles, Services & Controller

| ID          | Task |
|-------------|------|
| P17-BE-001  | Create `ScoringRule` model — `HasUuids`, `HasFactory`. Fillable : user_id, event, points, expiry_days, is_active. Casts is_active bool, points/expiry_days integer. Relations `user()`. Scope `activeForUser(User $user)`. |
| P17-BE-002  | Create `ContactScoreEvent` model — `HasUuids`. Fillable : user_id, contact_id, event, points, source_campaign_id, expires_at. Casts expires_at datetime, points integer. Relations `contact()`, `sourceCampaign()`. |
| P17-BE-003  | Create `ContactScoreService` — `recordEvent(Contact $contact, string $event, ?Campaign $campaign = null): void`. `recalculate(Contact $contact): int` (SUM points des events non-expirés). `getHistory(Contact $contact): Collection`. Insère règles par défaut si aucune règle active pour l'user. |
| P17-BE-004  | Create `ScoringRuleController` — CRUD routes `GET/POST /scoring-rules`, `PUT/DELETE /scoring-rules/{rule}`. Retourne la liste triée par event. |
| P17-BE-005  | Extend `EmailTrackingController::open()` — Appeler `ContactScoreService::recordEvent($contact, 'email_opened', $campaign)` après update du recipient. |
| P17-BE-006  | Extend `EmailTrackingController::click()` — Appeler `recordEvent(…, 'email_clicked', …)`. |
| P17-BE-007  | Extend `EmailTrackingController::unsubscribe()` — Appeler `recordEvent(…, 'email_unsubscribed', …)`. |
| P17-BE-008  | Extend `CampaignRecipientObserver` — Sur hard bounce : appeler `recordEvent(…, 'email_bounced', …)`. |
| P17-BE-009  | Extend `SegmentFilterEngine` — Critère `email_score` : opérateurs `gte`, `lte`, `gt`, `lt`, `eq`. Join sur `contacts.email_score`. |
| P17-BE-010  | Create `RecalculateExpiredScoresJob` — Queue `default`, schedulé quotidiennement. Charge les contacts ayant des events expirés depuis hier. Pour chacun : `recalculate()` + mise à jour `email_score`. |
| P17-BE-011  | PHPStan level 8 + Pint. |

#### 6.1.3 Backend Tests (TDD)

| ID          | Test File |
|-------------|-----------|
| P17-BT-001  | `tests/Unit/Services/ContactScoreServiceTest.php` — recordEvent ajoute points, recalcul exclut expirés, règles par défaut insérées si absentes |
| P17-BT-002  | `tests/Unit/Jobs/RecalculateExpiredScoresJobTest.php` — score recalculé, contacts sans expiry non impactés |
| P17-BT-003  | `tests/Feature/Scoring/ScoringRuleCrudTest.php` — CRUD ownership + validation |
| P17-BT-004  | `tests/Feature/Scoring/ContactScoreIntegrationTest.php` — open tracking → score +10, click → +20, bounce → -5, SegmentFilterEngine filtre par score |

---

### 6.2 Sprint 56 — Backend STO & Dynamic Content (Weeks 150–152)

#### 6.2.1 Infrastructure & Database

| ID              | Migration |
|-----------------|-----------|
| P17-BE-INF-04   | `add_sto_fields_to_campaigns_table` — Voir §3.1 |

#### 6.2.2 Backend — Services & Controller

| ID          | Task |
|-------------|------|
| P17-BE-012  | Create `ContactSendTimeService` — `getOptimalHour(Contact $contact, User $user): ?int`. Agrège les `opened_at` (groupe par heure), retourne l'heure modale. Retourne null si < 3 ouvertures. `getNextSendDelay(int $optimalHour, int $windowHours): int` (secondes jusqu'à la prochaine occurrence dans la fenêtre). |
| P17-BE-013  | Extend `Campaign` model — `use_sto` et `sto_window_hours` dans `$fillable` + casts. |
| P17-BE-014  | Extend `StoreCampaignRequest` — `use_sto` boolean nullable, `sto_window_hours` integer 1–48 nullable. |
| P17-BE-015  | Extend `SendEmailCampaignJob` — Si `campaign->use_sto` : pour chaque contact, calculer `getOptimalHour()` + `getNextSendDelay()`, dispatch `SendCampaignEmailJob->delay($delay)`. Si null → dispatch immédiat. |
| P17-BE-016  | Create `DynamicContentValidatorService` — `validate(string $content): array{valid:bool, errors:string[]}`. Parse les blocs `{{#if}}…{{/if}}` et `{{#if}}…{{else}}…{{/if}}`. Vérifie : ouverture/fermeture correcte, nesting ≤ 2, attribut dans la whitelist autorisée, opérateur valide. |
| P17-BE-017  | Extend `PersonalizationService::render()` — Avant la résolution des variables : parser et évaluer les blocs `{{#if condition}}…{{else}}…{{/if}}`. Condition supportée : `attribute operator value` (==, !=, >=, <=, >, <). Attributs supportés : `contact.*`, `client.*`, `email_score`. Résolution récursive (nesting max 2). |
| P17-BE-018  | Extend `PersonalizationService::renderPreview()` — Évaluer les blocs `{{#if}}` avec les valeurs fictives définies en §3.3 Phase 15. `email_score` fictif = 75. |
| P17-BE-019  | Extend `CampaignController::store()`/`update()` — Appeler `DynamicContentValidatorService::validate()` sur `content` et les `variants.*.content`. Retourner 422 si invalide avec les erreurs de syntaxe. |
| P17-BE-020  | PHPStan level 8 + Pint. |

#### 6.2.3 Backend Tests (TDD)

| ID          | Test File |
|-------------|-----------|
| P17-BT-005  | `tests/Unit/Services/ContactSendTimeServiceTest.php` — getOptimalHour retourne heure modale, null si < 3 ouvertures, getNextSendDelay calcule le délai correct |
| P17-BT-006  | `tests/Unit/Services/DynamicContentValidatorServiceTest.php` — syntaxe valide, fermeture manquante → erreur, nesting > 2 → erreur, attribut non autorisé → erreur |
| P17-BT-007  | `tests/Unit/Services/PersonalizationServiceDynamicTest.php` — `{{#if contact.industry == "X"}}A{{/if}}` résolu, `{{else}}` résolu, nesting 2 niveaux, email_score >= condition, renderPreview avec valeurs fictives |
| P17-BT-008  | `tests/Feature/Campaigns/CampaignStoTest.php` — campagne use_sto → recipients ont des délais différents, contact sans historique → envoi immédiat |
| P17-BT-009  | `tests/Feature/Campaigns/CampaignDynamicContentTest.php` — POST /campaigns avec contenu invalide → 422, contenu valide → 201, envoi résout les blocs correctement par contact |

---

### 6.3 Sprint 57 — Frontend Scoring, STO & Dynamic Content UI (Weeks 153–155)

#### 6.3.1 Frontend Tasks

| ID          | Task |
|-------------|------|
| P17-FE-001  | Create `lib/stores/scoring-rules.ts` Zustand store — state rules[], actions : fetchRules, createRule, updateRule, deleteRule. |
| P17-FE-002  | Create `app/(dashboard)/settings/scoring/page.tsx` — Configuration des règles de scoring : tableau editable (event → points → expiry_days → is_active). Bouton reset aux valeurs par défaut. |
| P17-FE-003  | Extend `components/contacts/contact-detail.tsx` — Section "Score email" : valeur numérique avec badge couleur (rouge <0, orange 0–30, vert >30), graphique sparkline de l'historique des points. |
| P17-FE-004  | Extend `components/segments/segment-builder.tsx` — Critère `email_score` : Select opérateur (≥, ≤, =, >, <) + Input numérique. |
| P17-FE-005  | Create `components/campaigns/sto-config.tsx` — Section STO dans le formulaire campagne : toggle "Optimiser l'heure d'envoi", input "Fenêtre d'envoi (heures)", indicateur "X contacts avec heure optimale connue" (appel API). |
| P17-FE-006  | Extend pages `campaigns/create` + `campaigns/[id]` — Intégrer `StoConfig`. Ajouter `use_sto` et `sto_window_hours` dans le payload de création/mise à jour. |
| P17-FE-007  | Create `components/campaigns/dynamic-content-editor.tsx` — Panneau "Contenu dynamique" à côté de l'éditeur de contenu. Interface visuelle pour insérer des blocs `{{#if}}` : Select attribut, Select opérateur, Input valeur, textarea "Contenu si vrai", textarea "Contenu si faux" (optionnel). Génère le snippet et l'insère dans l'éditeur. Bouton "Prévisualiser" (appelle renderPreview backend). |
| P17-FE-008  | Extend `app/(dashboard)/campaigns/create/page.tsx` — Intégrer `DynamicContentEditor`. Afficher les erreurs de validation `dynamic_content_errors` retournées par le backend dans le formulaire. |
| P17-FE-009  | Extend sidebar Settings — Entrée "Scoring" dans les paramètres. |
| P17-FE-010  | Extend `app/(dashboard)/page.tsx` — Widget "Contacts chauds" : count contacts avec `email_score >= 50` + lien vers liste filtrée. Masqué si 0. |

#### 6.3.2 Frontend Tests

| ID          | Test File |
|-------------|-----------|
| P17-FT-001  | `tests/unit/stores/scoring-rules.test.ts` |
| P17-FT-002  | `tests/components/campaigns/sto-config.test.tsx` |
| P17-FT-003  | `tests/components/campaigns/dynamic-content-editor.test.tsx` |
| P17-FT-004  | `tests/components/segments/segment-builder-score.test.tsx` |
| P17-FT-005  | `tests/e2e/campaigns/dynamic-content-flow.spec.ts` |
| P17-FT-006  | `tests/e2e/campaigns/sto-flow.spec.ts` |

#### 6.3.3 Scénarios E2E (Playwright)

| Scénario | Description |
|----------|-------------|
| `dynamic-content-flow.spec.ts` | Créer campagne → insérer bloc `{{#if client.industry == "Wedding Planner"}}Bonjour mariés !{{else}}Bonjour !{{/if}}` → prévisualisation affiche "Bonjour mariés !" (valeur fictive) → sauvegarder → vérifier 201 → envoyer à un contact `industry=Wedding Planner` → email reçu contient "Bonjour mariés !" |
| `sto-flow.spec.ts` | Créer campagne avec use_sto=true → envoyer → vérifier que les recipients ont des `sent_at` différents selon leur historique d'ouverture |

---

### 6.4 Sprint 58 — Hardening & CI (Weeks 156–158)

#### 6.4.1 Backend Tasks

| ID          | Task |
|-------------|------|
| P17-BE-021  | Extend `DataExportService` — Inclure `ContactScoreEvent` dans l'export GDPR (historique des points par contact, sans données tiers). Inclure `ScoringRule` dans le profil user. |
| P17-BE-022  | PHPStan level 8 + Pint — 0 erreur sur tous les fichiers nouveaux/modifiés de la phase. |

#### 6.4.2 Frontend Tasks

| ID          | Task |
|-------------|------|
| P17-FE-011  | ESLint + Prettier — 0 erreur sur tous les fichiers nouveaux/modifiés. |

#### 6.4.3 Backend Tests

| ID          | Test File |
|-------------|-----------|
| P17-BT-010  | `tests/Feature/Scoring/ContactScoreGdprTest.php` — export GDPR inclut events de score de l'user, n'inclut pas ceux d'un autre user |

---

## 7. Exit Criteria

| Critère | Vérification |
|---------|-------------|
| Toutes les tâches `P17-BE-*` et `P17-FE-*` en statut `done` | `docs/dev/phase17.md` |
| Backend coverage >= 80% (`make test-be`) | CI green |
| Frontend coverage >= 80% (`pnpm vitest run --coverage`) | CI green |
| PHPStan level 8 | 0 erreur |
| Pint | 0 erreur |
| ESLint + Prettier | 0 erreur |
| 2 scénarios E2E Playwright verts | `make test-e2e` |
| Score augmente après ouverture d'email (test manuel) | Test manuel |
| Campagne STO : différents contacts envoyés à des heures différentes | Test manuel |
| Bloc `{{#if}}` résolu correctement par contact | Test manuel |
| Segment filtré par `email_score >= 50` retourne les bons contacts | Test manuel |
| Tag `v2.3.0` poussé sur GitHub | `git tag v2.3.0` |

---

## 8. Récapitulatif

| Sprint    | Semaines | Livrable principal                                              | Tasks                        |
|-----------|----------|-----------------------------------------------------------------|------------------------------|
| Sprint 55 | 147–149  | Lead scoring backend (rules, events, service, segment filter)  | 3 INF + 11 BE + 4 tests      |
| Sprint 56 | 150–152  | STO + dynamic content backend (parser, validator, job étendu)  | 1 INF + 9 BE + 5 tests       |
| Sprint 57 | 153–155  | Frontend scoring UI + STO config + dynamic content editor      | 10 FE + 6 tests              |
| Sprint 58 | 156–158  | Hardening GDPR, PHPStan, ESLint, CI                            | 2 BE/FE + 1 test             |
| **Total** | **12 sem** | **v2.3.0**                                                   | **~37 tâches + 16 tests**    |
