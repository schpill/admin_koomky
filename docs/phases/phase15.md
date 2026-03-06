# Phase 15 — Email Campaign Enhancements : Test Multi-Destinataires, Personnalisation & A/B Testing (v2.1)

| Field               | Value                                                        |
|---------------------|--------------------------------------------------------------|
| **Phase**           | 15                                                           |
| **Name**            | Email Campaign Enhancements — Test, Personnalisation, Déduplication & A/B |
| **Duration**        | Weeks 123–134 (12 weeks)                                     |
| **Milestone**       | M15 — v2.1.0 Release                                        |
| **PRD Sections**    | §4.22 FR-TEST (nouveau), §4.23 FR-VARS (nouveau), §4.24 FR-DEDUP (nouveau), §4.25 FR-AB (nouveau) |
| **Prerequisite**    | Phase 14 fully completed and tagged `v2.0.0`                 |
| **Status**          | todo                                                         |

---

## 1. Phase Objectives

| ID        | Objective                                                                                                                               |
|-----------|-----------------------------------------------------------------------------------------------------------------------------------------|
| P15-OBJ-1 | Permettre l'envoi d'un email de test à **plusieurs destinataires simultanément** (liste de 1 à 5 adresses)                              |
| P15-OBJ-2 | Résoudre les **variables de personnalisation** (`{{first_name}}` etc.) lors des envois de test, avec un contact fictif de prévisualisation |
| P15-OBJ-3 | Enrichir le `PersonalizationService` avec les variables manquantes : `industry`, `department`, `address`, `zip_code`                   |
| P15-OBJ-4 | Ajouter une **interface d'insertion de variables** dans l'éditeur de campagne (liste cliquable des balises disponibles)                  |
| P15-OBJ-5 | Implémenter le **A/B Testing** de campagnes email : deux variantes (sujet et/ou contenu), split configurable, sélection automatique ou manuelle du gagnant |
| P15-OBJ-6 | Exposer des **analytics A/B** comparant open rate, click rate et conversion par variante                                               |
| P15-OBJ-7 | Garantir qu'un contact ne reçoit **qu'un seul exemplaire** d'une campagne, même s'il correspond à plusieurs critères du segment (ex. tagué "coiffeur" ET "barbier") |
| P15-OBJ-8 | Maintenir une couverture de tests >= 80% backend et frontend                                                                           |

---

## 2. Contexte & Analyse de l'existant

### 2.1 Ce qui est déjà en place (aucune réimplémentation)

| Besoin | Brique existante | Phase d'origine |
|--------|-----------------|-----------------|
| Envoi de campagne email | `SendEmailCampaignJob`, `SendCampaignEmailJob` | Phase 3 |
| Test d'envoi unitaire (1 email) | `CampaignController::testSend()`, `CampaignTestMail` | Phase 3 |
| Personnalisation de base | `PersonalizationService` (5 variables fixes + resolver générique) | Phase 3 |
| Tracking ouverture / clic | `EmailTrackingController` | Phase 3 |
| Modèle `Campaign` + `CampaignRecipient` | Phase 3 | Phase 3 |
| Éditeur HTML campagne | `frontend/components/campaigns/` | Phase 3 |
| Variables `industry`, `department` sur `Client` | Migration Phase 14 | Phase 14 |
| Analytics campagnes | `CampaignAnalyticsController`, `CampaignAnalyticsService` | Phase 3 |
| Comparaison multi-campagnes | `GET /campaigns/compare` | Phase 3 |

### 2.2 Analyse du gap

| Besoin | Gap actuel | Solution Phase 15 |
|--------|-----------|-------------------|
| Test multi-destinataires | `testSend` accepte 1 email max | Validation `emails[]` + boucle d'envoi |
| Résolution variables dans le test | `CampaignTestMail` envoie le HTML brut | Contact fictif de prévisualisation + `PersonalizationService::renderPreview()` |
| Variables `industry`, `department` | Non whitelistées dans `PersonalizationService` | Ajout dans le resolver générique `client.*` |
| Variables `address`, `zip_code` | Absentes du resolver | Ajout dans le resolver générique `client.*` |
| Bouton d'insertion de variables | Aucune UI | Panel "Variables disponibles" dans l'éditeur |
| Doublons de destinataires au sein d'une campagne | Un contact matchant plusieurs critères du segment (ex. tags "coiffeur" ET "barbier") peut recevoir l'email N fois — aucune contrainte unique sur `campaign_recipients` | Contrainte unique `(campaign_id, contact_id)` en DB + `->distinct()` + `firstOrCreate` dans `SendEmailCampaignJob` |
| A/B Testing | Non implémenté | `CampaignVariant` model + split job + sélection gagnant |
| Analytics A/B | Inexistantes | Extension `CampaignAnalyticsController` |

---

## 3. Choix techniques

### 3.1 Modèle de données

**Trois migrations nécessaires :**

| Migration | Description |
|-----------|-------------|
| `create_campaign_variants_table` | UUID PK, `campaign_id` FK → campaigns CASCADE, `label` VARCHAR(2) NOT NULL (`A`\|`B`), `subject` VARCHAR(255) nullable, `content` TEXT nullable, `send_percent` TINYINT NOT NULL DEFAULT 50, `sent_count` INT DEFAULT 0, `open_count` INT DEFAULT 0, `click_count` INT DEFAULT 0, timestamps. Index unique `(campaign_id, label)`. |
| `add_ab_testing_fields_to_campaigns_table` | `is_ab_test` BOOLEAN DEFAULT false, `ab_winner_variant_id` UUID FK nullable → campaign_variants SET NULL, `ab_winner_selected_at` TIMESTAMP nullable, `ab_winner_criteria` VARCHAR(20) nullable (`open_rate`\|`click_rate`\|`manual`), `ab_auto_select_after_hours` TINYINT nullable (1–72). |
| `add_variant_id_to_campaign_recipients_table` | `variant_id` UUID FK nullable → campaign_variants SET NULL. Index `(campaign_id, variant_id)`. |

### 3.2 Logique A/B Testing

```
Création d'une campagne A/B :
1. Campaign.is_ab_test = true
2. Deux CampaignVariant créés (label A et B)
   - Variant A : subject_a, content_a, send_percent (ex. 50)
   - Variant B : subject_b, content_b, send_percent = 100 - send_percent_A
3. send_percent A + send_percent B = 100 (validation)

Envoi :
1. SendEmailCampaignJob détecte is_ab_test = true
2. Le segment est splité aléatoirement selon send_percent
   - ex. 50% des contacts → variante A, 50% → variante B
3. CampaignRecipient.variant_id = variant utilisé
4. SendCampaignEmailJob utilise subject/content du variant si présent

Sélection du gagnant :
- Automatique : SelectAbWinnerJob dispatché après ab_auto_select_after_hours heures
  → calcule open_rate (open_count/sent_count) ou click_rate par variante
  → marque ab_winner_variant_id + ab_winner_selected_at = now()
- Manuelle : POST /campaigns/{id}/ab/select-winner { variant_id }
```

### 3.3 Contact fictif de prévisualisation

`PersonalizationService::renderPreview(string $content): string` — résout toutes les variables avec des valeurs fictives statiques :

| Variable | Valeur fictive |
|----------|----------------|
| `{{first_name}}` | "Marie" |
| `{{last_name}}` | "Dupont" |
| `{{company}}` | "Acme Corp" |
| `{{email}}` | "marie.dupont@example.com" |
| `{{phone}}` | "+33 6 12 34 56 78" |
| `{{contact.position}}` | "Directrice" |
| `{{client.city}}` | "Paris" |
| `{{client.country}}` | "France" |
| `{{client.address}}` | "12 rue de la Paix" |
| `{{client.zip_code}}` | "75001" |
| `{{client.industry}}` | "Wedding Planner" |
| `{{client.department}}` | "75" |
| `{{client.reference}}` | "REF-001" |

### 3.4 Nouvelles variables dans `PersonalizationService`

La whitelist du resolver générique `client.*` passe de :
`['name', 'email', 'phone', 'city', 'country', 'reference']`
à :
`['name', 'email', 'phone', 'city', 'country', 'address', 'zip_code', 'industry', 'department', 'reference']`

### 3.5 Déduplication des destinataires (FR-DEDUP)

Un contact peut correspondre à plusieurs critères d'un même segment (ex. tagué "coiffeur" ET "barbier", ou avoir `industry = "Coiffeur"` ET appartenir à un groupe géographique). Sans protection, le job d'envoi créerait plusieurs `CampaignRecipient` pour le même contact dans la même campagne, entraînant N envois.

Deux couches de protection complémentaires :

| Couche | Mécanisme | Avantage |
|--------|-----------|----------|
| Base de données | Contrainte unique `(campaign_id, contact_id)` sur `campaign_recipients` | Filet de sécurité absolu, empêche tout doublon même en cas de race condition |
| Application | `->distinct()` sur la query contacts dans `SendEmailCampaignJob` + `firstOrCreate(['campaign_id', 'contact_id'])` au lieu de `create()` | Évite les exceptions DB, silencieux sur les tentatives de doublon |

**Périmètre** : s'applique à toutes les campagnes email (A/B et non-A/B). Ne concerne pas les doublons *inter-campagnes* — si un prospect est ciblé par deux campagnes distinctes sur des sujets différents, il reçoit les deux emails normalement.

### 3.6 Test multi-destinataires

Backend : `testSend` accepte `emails[]` (array de 1 à 5 adresses, chacune validée `email|max:255`). Le body et le subject sont résolus une fois via `PersonalizationService::renderPreview()`, puis envoyés à chaque adresse.

Frontend : champ de saisie multi-valeurs (tags input) dans `TestSendModal`, avec validation format email à la saisie côté client.

---

## 4. Entry Criteria

- Phase 14 exit criteria 100% satisfaits.
- Tous les checks CI Phase 14 verts sur `main`.
- `v2.0.0` tagué et déployé en production.
- `PersonalizationService` stable et couvert par les tests existants.
- `CampaignController::testSend()` + `SendEmailCampaignJob` stables.

---

## 5. Scope — Requirement Traceability

### 5.1 Module Test d'envoi & Personnalisation (FR-TEST + FR-VARS)

| Feature | Priority | Included |
|---------|----------|----------|
| Test sur plusieurs emails (1–5 adresses simultanées) | High | Yes |
| Résolution des variables dans l'email de test (contact fictif) | High | Yes |
| Variables `{{client.address}}`, `{{client.zip_code}}` | High | Yes |
| Variables `{{client.industry}}`, `{{client.department}}` | High | Yes |
| Méthode `PersonalizationService::renderPreview()` | High | Yes |
| Panel "Variables disponibles" dans l'éditeur (insertion one-click) | Medium | Yes |
| Test d'envoi SMS multi-numéros (1–3) | Low | Yes |

### 5.2 Module Déduplication des destinataires (FR-DEDUP)

| Feature | Priority | Included |
|---------|----------|----------|
| Contrainte unique `(campaign_id, contact_id)` sur `campaign_recipients` | High | Yes |
| `->distinct()` sur la query contacts dans `SendEmailCampaignJob` | High | Yes |
| `firstOrCreate` au lieu de `create()` pour `CampaignRecipient` dans le job | High | Yes |
| Déduplication également appliquée aux campagnes A/B | High | Yes |
| Déduplication inter-campagnes (un prospect ne reçoit jamais deux campagnes distinctes) | Low | No |

### 5.3 Module A/B Testing (FR-AB)

| Feature | Priority | Included |
|---------|----------|----------|
| Modèle `CampaignVariant` (sujet + contenu par variante) | High | Yes |
| Configuration split (send_percent A + B = 100) | High | Yes |
| Envoi splité aléatoirement par variante | High | Yes |
| `CampaignRecipient.variant_id` tracé | High | Yes |
| Critère de victoire : open_rate ou click_rate | High | Yes |
| Sélection automatique du gagnant après N heures (`SelectAbWinnerJob`) | High | Yes |
| Sélection manuelle du gagnant | High | Yes |
| Analytics A/B par variante (sent/open/click rates) | High | Yes |
| UI création des variantes A et B dans le formulaire campagne | High | Yes |
| UI résultats A/B avec badge gagnant | High | Yes |
| Widget "A/B Tests actifs" sur le dashboard | Medium | Yes |
| A/B testing sur campagnes SMS | Low | No |
| Plus de 2 variantes (A/B/C) | Low | No |
| Envoi du gagnant au reste du segment (3-way split) | Low | No |

---

## 6. Detailed Sprint Breakdown

### 6.1 Sprint 47 — Backend Test Multi-Email, Personnalisation & Déduplication (Weeks 123–125)

#### 6.1.0 Infrastructure & Database

| Migration | Description |
|-----------|-------------|
| `add_unique_contact_constraint_to_campaign_recipients_table` | Contrainte unique `(campaign_id, contact_id)` sur `campaign_recipients`. Protège au niveau DB contre tout doublon de destinataire au sein d'une même campagne, quelle qu'en soit l'origine (race condition, bug applicatif, segment mal construit). |

#### 6.1.1 Backend — Services & Controller

| ID          | Task | PRD Ref |
|-------------|------|---------|
| P15-BE-001  | Extend `PersonalizationService` — Ajouter `renderPreview(string $content): string` résolvant toutes les variables avec le tableau de valeurs fictives défini en §3.3. Étendre la whitelist du resolver générique `client.*` avec `address`, `zip_code`, `industry`, `department` (voir §3.4). | FR-VARS |
| P15-BE-002  | Extend `CampaignController::testSend()` — Remplacer la validation `email` string unique par `emails` array (required, min 1, max 5 items, chaque item `email|max:255`). Résoudre subject et body via `PersonalizationService::renderPreview()` avant envoi. Boucler sur chaque adresse. | FR-TEST |
| P15-BE-003  | Extend `CampaignTestMail` — Accepter un `string $renderedBody` et `string $renderedSubject` pré-résolus. Modifier `build()` pour les utiliser. | FR-TEST |
| P15-BE-004  | Create `StoreCampaignTestRequest` — Règles email : `emails` required array, min:1, max:5, `emails.*` rule `email|max:255`. Règles SMS : `phones` array, min:1, max:3, `phones.*` string max:20. | FR-TEST |
| P15-BE-004b | Extend `SendEmailCampaignJob` — Déduplication des destinataires : (1) ajouter `->distinct()` sur la query contacts avant le cursor ; (2) remplacer `CampaignRecipient::create()` par `CampaignRecipient::firstOrCreate(['campaign_id' => …, 'contact_id' => …], […autres champs…])` — un contact matchant N critères du segment n'est créé qu'une fois et ne reçoit qu'un seul email par campagne. | FR-DEDUP |

#### 6.1.2 Backend Tests — Personnalisation & Test (TDD)

| Test File | Test Cases |
|-----------|-----------|
| `tests/Unit/Services/PersonalizationServiceTest.php` (extension) | `renderPreview()` remplace `{{first_name}}` par "Marie", `{{client.industry}}` par "Wedding Planner", `{{client.department}}` par "75", `{{client.address}}` par "12 rue de la Paix", `{{client.zip_code}}` par "75001", variable inconnue → chaîne vide ; `render()` — `{{client.industry}}` résolu depuis client réel, `{{client.department}}` résolu, `{{client.address}}` résolu, `{{client.zip_code}}` résolu |
| `tests/Feature/Campaigns/CampaignTestSendTest.php` | `POST /campaigns/{id}/test` avec `emails[3]` → 200 (3 envois), 0 emails → 422, 6 emails → 422, adresse malformée → 422, subject/body reçus avec valeurs fictives (pas de `{{first_name}}` brut), ownership 403 |
| `tests/Feature/Campaigns/CampaignDeduplicateRecipientsTest.php` | Contact matchant 2 critères du segment → 1 seul `CampaignRecipient` créé + 1 seul email envoyé ; `CampaignRecipient::firstOrCreate()` appelé deux fois pour le même contact+campaign → 2ème appel silencieux (pas d'exception) ; contrainte unique DB rejette un `INSERT` direct en doublon |

---

### 6.2 Sprint 48 — Backend A/B Testing (Weeks 126–128)

#### 6.2.1 Infrastructure & Database

| Migration / Config | Description |
|--------------------|-------------|
| `create_campaign_variants_table` | Voir §3.1 |
| `add_ab_testing_fields_to_campaigns_table` | Voir §3.1 |
| `add_variant_id_to_campaign_recipients_table` | Voir §3.1 |

#### 6.2.2 Backend — Modèles, Services & Controller

| ID          | Task | PRD Ref |
|-------------|------|---------|
| P15-BE-005  | Create `CampaignVariant` model — `HasUuids`, `HasFactory`. Fillable : campaign_id, label, subject, content, send_percent, sent_count, open_count, click_count. Casts integer sur les compteurs. Relation `campaign()` BelongsTo. Méthodes `openRate(): float`, `clickRate(): float`. | FR-AB |
| P15-BE-006  | Extend `Campaign` model — Ajout `is_ab_test`, `ab_winner_variant_id`, `ab_winner_selected_at`, `ab_winner_criteria`, `ab_auto_select_after_hours` dans `$fillable` et casts (is_ab_test bool, ab_winner_selected_at datetime). Relations `variants()` HasMany `CampaignVariant`, `winnerVariant()` BelongsTo. Méthode `isAbTest(): bool`. | FR-AB |
| P15-BE-007  | Extend `CampaignRecipient` model — Ajout `variant_id` dans `$fillable`. Relation `variant()` BelongsTo `CampaignVariant`. | FR-AB |
| P15-BE-008  | Extend `StoreCampaignRequest` — Validation A/B : `is_ab_test` boolean, `variants` array nullable max 2, `variants.*.label` in A\|B, `variants.*.subject` nullable string max 255, `variants.*.content` nullable string, `variants.*.send_percent` integer 1–99, `ab_winner_criteria` in open_rate\|click_rate\|manual nullable, `ab_auto_select_after_hours` integer 1–72 nullable. Règle custom : si `is_ab_test`, somme des `send_percent` doit être exactement 100. | FR-AB |
| P15-BE-009  | Extend `CampaignController` — `store()` + `update()` : si `is_ab_test`, créer/sync les `CampaignVariant` dans la transaction. `show()` : charger `variants`. Nouvelle méthode `selectWinner(Request $request, Campaign $campaign)` : `POST /campaigns/{id}/ab/select-winner { variant_id }` → valide que `variant_id` appartient à la campagne, marque `ab_winner_variant_id` + `ab_winner_selected_at = now()`. | FR-AB |
| P15-BE-010  | Extend `SendEmailCampaignJob` — Si `campaign->isAbTest()` : charger les variantes, spliter le segment aléatoirement selon `send_percent` (Fisher-Yates ou chunk), assigner `variant_id` sur chaque `CampaignRecipient`. Si non A/B : comportement actuel inchangé. Dispatcher `SelectAbWinnerJob::dispatch($campaign->id)->delay(now()->addHours($campaign->ab_auto_select_after_hours))` si auto select configuré. | FR-AB |
| P15-BE-011  | Extend `SendCampaignEmailJob` — Si `recipient->variant_id` non null : utiliser `variant->subject` (si non null) et `variant->content` (si non null) à la place du subject/content de la campagne. Incrémenter `variant->sent_count` via `increment()` après envoi. | FR-AB |
| P15-BE-012  | Create `SelectAbWinnerJob` — Queue : `default`. Charge la campaign avec variants. Garde-fous : `is_ab_test` = true, `ab_winner_variant_id` null (pas déjà sélectionné). Selon `ab_winner_criteria` : calcule `openRate()` ou `clickRate()` pour chaque variante, sélectionne celle avec le meilleur score. Met à jour `ab_winner_variant_id` + `ab_winner_selected_at`. | FR-AB |
| P15-BE-013  | Extend `EmailTrackingController::open()` + `click()` — Via le recipient chargé, si `recipient->variant_id` non null : incrémenter `CampaignVariant::increment('open_count')` / `increment('click_count')`. | FR-AB |
| P15-BE-014  | Extend `CampaignAnalyticsController::show()` — Inclure `ab_variants` dans la réponse si `campaign->isAbTest()` : `[{ label, sent_count, open_count, click_count, open_rate, click_rate, is_winner }]`. | FR-AB |
| P15-BE-015  | Register route — `POST /campaigns/{campaign}/ab/select-winner` dans le groupe `v1` authentifié. | FR-AB |

#### 6.2.3 Backend Tests — A/B Testing (TDD)

| Test File | Test Cases |
|-----------|-----------|
| `tests/Unit/Jobs/SelectAbWinnerJobTest.php` | Sélectionne variante A si open_rate A > B, sélectionne B si click_rate B > A, ne ré-exécute pas si gagnant déjà sélectionné, ne fait rien si `is_ab_test = false` |
| `tests/Feature/Campaigns/CampaignAbTestCrudTest.php` | `POST /campaigns` avec `is_ab_test + variants[A,B]` → 201 + variantes créées, `GET /campaigns/{id}` → champ `variants` présent, sum send_percent ≠ 100 → 422, plus de 2 variantes → 422, `POST /campaigns/{id}/ab/select-winner { variant_id }` → 200 gagnant marqué, variant_id étranger → 422, ownership 403 |
| `tests/Feature/Campaigns/CampaignAbTestAnalyticsTest.php` | `GET /campaigns/{id}/analytics` inclut `ab_variants` si A/B, open_rate et click_rate calculés correctement, `is_winner` = true sur la variante gagnante |
| `tests/Feature/Campaigns/CampaignAbTestSendTest.php` | `POST /campaigns/{id}/send` sur campagne A/B → recipients ont variant_id non null, répartition conforme au send_percent, `SendCampaignEmailJob` utilise subject/content de la variante |

---

### 6.3 Sprint 49 — Frontend A/B Testing & Variables UI (Weeks 129–131)

#### 6.3.1 Frontend — Store & Composants

| ID          | Task | PRD Ref |
|-------------|------|---------|
| P15-FE-001  | Extend `lib/stores/campaigns.ts` — Ajout types `CampaignVariant`, extension de `CampaignAnalytics` avec `ab_variants[]`. Actions : `selectAbWinner(campaignId, variantId)`. | FR-AB |
| P15-FE-002  | Extend `components/campaigns/test-send-modal.tsx` — Remplacer le champ unique email par un tags-input multi-valeurs (1–5 emails). Validation format email à la saisie. Badge d'information "Les variables seront résolues avec des données fictives" visible dans la modale. | FR-TEST |
| P15-FE-003  | Create `components/campaigns/personalization-variables-panel.tsx` — Panneau ou popover listant toutes les variables disponibles avec description. Bouton "Copier" par variable. | FR-VARS |
| P15-FE-004  | Extend `app/(dashboard)/campaigns/create/page.tsx` + `[id]/page.tsx` — Intégrer `PersonalizationVariablesPanel` à côté de l'éditeur de contenu et du champ subject. | FR-VARS |
| P15-FE-005  | Create `components/campaigns/ab-test-config.tsx` — Section A/B Testing dans le formulaire : toggle "Activer le A/B test", formulaire Variante A (subject, content textarea), formulaire Variante B (subject, content textarea), slider ou deux inputs pour split % (A + B = 100 validé en temps réel), Select "Critère gagnant" (open_rate / click_rate / manual), input "Sélectionner automatiquement après N heures" (conditionnel si critère ≠ manual). | FR-AB |
| P15-FE-006  | Extend `app/(dashboard)/campaigns/create/page.tsx` — Intégrer `AbTestConfig` dans le formulaire. Si `is_ab_test` activé : masquer les champs subject/content racine et afficher les formulaires variante A/B. Soumission du formulaire inclut `is_ab_test`, `variants`, `ab_winner_criteria`, `ab_auto_select_after_hours`. | FR-AB |
| P15-FE-007  | Create `components/campaigns/ab-test-results.tsx` — Tableau comparatif variantes : colonnes (Variante, Envoyés, Ouverts, Taux d'ouverture, Clics, Taux de clic). Badge "Gagnant" sur la variante `is_winner`. Bouton "Sélectionner comme gagnant" si `ab_winner_criteria = manual` et `ab_winner_variant_id = null`. | FR-AB |
| P15-FE-008  | Extend `app/(dashboard)/campaigns/[id]/page.tsx` — Afficher `AbTestResults` si `campaign.is_ab_test` dans la section analytics. | FR-AB |

#### 6.3.2 Frontend Tests

| ID          | Test File | Status | Owner |
|-------------|-----------|--------|-------|
| P15-FT-001  | `tests/unit/stores/campaigns-ab.test.ts` | todo | — |
| P15-FT-002  | `tests/components/campaigns/test-send-modal.test.tsx` (extension) | todo | — |
| P15-FT-003  | `tests/components/campaigns/personalization-variables-panel.test.tsx` | todo | — |
| P15-FT-004  | `tests/components/campaigns/ab-test-config.test.tsx` | todo | — |
| P15-FT-005  | `tests/components/campaigns/ab-test-results.test.tsx` | todo | — |
| P15-FT-006  | `tests/e2e/campaigns/ab-test-flow.spec.ts` | todo | — |
| P15-FT-007  | `tests/e2e/campaigns/test-send-multi-email.spec.ts` | todo | — |

#### 6.3.3 Scénarios E2E (Playwright)

| Scénario | Description |
|----------|-------------|
| `ab-test-flow.spec.ts` | Créer campagne → activer A/B → remplir variante A (sujet A, body A) + variante B (sujet B, body B) → split 50/50 → critère open_rate → 24h auto → sauvegarder → envoyer → vérifier que les recipients ont un variant_id → aller sur analytics → vérifier tableau A/B présent avec colonnes open_rate/click_rate |
| `test-send-multi-email.spec.ts` | Créer campagne avec `{{first_name}}` dans le body → ouvrir TestSendModal → saisir 3 emails → envoyer → vérifier que les 3 emails ont été reçus (mailhog) avec "Marie" à la place de `{{first_name}}` |

---

### 6.4 Sprint 50 — Hardening & CI (Weeks 132–134)

#### 6.4.1 Backend — Hardening

| ID          | Task | PRD Ref |
|-------------|------|---------|
| P15-BE-016  | PHPStan level 8 — 0 erreur sur tous les fichiers nouveaux/modifiés de la phase. | NFR |
| P15-BE-017  | Pint — 0 erreur sur tous les fichiers PHP nouveaux/modifiés. | NFR |
| P15-BE-018  | Extend `DataExportService` — Inclure `CampaignVariant` dans l'export GDPR de l'user (liste des variantes par campagne avec compteurs agrégés, sans données personnelles des recipients). | FR-AB |

#### 6.4.2 Frontend — Hardening

| ID          | Task | PRD Ref |
|-------------|------|---------|
| P15-FE-009  | ESLint + Prettier — 0 erreur sur tous les fichiers nouveaux/modifiés. | NFR |
| P15-FE-010  | Extend `app/(dashboard)/page.tsx` — Widget "A/B Tests actifs" : nombre de campagnes avec `is_ab_test = true` et `status = sending` + lien vers la liste des campagnes. Masqué si 0. | FR-AB |

#### 6.4.3 Tests de hardening

| Test File | Test Cases |
|-----------|-----------|
| `tests/Feature/Campaigns/CampaignAbTestGdprTest.php` | Export GDPR inclut les variantes des campagnes de l'user, n'inclut pas celles d'un autre user |

---

## 7. Exit Criteria

| Critère | Vérification |
|---------|-------------|
| Toutes les tâches `P15-BE-*` et `P15-FE-*` en statut `done` | `docs/dev/phase15.md` |
| Backend coverage >= 80% (`make test-be`) | CI green |
| Frontend coverage >= 80% (`pnpm vitest run --coverage`) | CI green |
| PHPStan level 8 (`./vendor/bin/phpstan analyse`) | 0 erreur |
| Pint (`./vendor/bin/pint --test`) | 0 erreur |
| ESLint + Prettier (`pnpm lint && pnpm format:check`) | 0 erreur |
| `tsc --noEmit` sans erreur | CI uniquement |
| 2 scénarios E2E Playwright verts | `make test-e2e` |
| Envoi test sur 3 emails avec variables résolues (valeurs fictives) | Test manuel |
| Campagne A/B créée, envoyée, gagnant sélectionné automatiquement | Test manuel |
| Analytics A/B affichées avec open_rate et click_rate par variante | Test manuel |
| Tag v2.1.0 poussé sur GitHub | `git tag v2.1.0` |

---

## 8. Récapitulatif

| Sprint    | Semaines | Livrable principal                                              | Tasks                        |
|-----------|----------|-----------------------------------------------------------------|------------------------------|
| Sprint 47 | 123–125  | Test multi-email + variables résolues + déduplication destinataires | 1 INF + 5 BE + 3 tests       |
| Sprint 48 | 126–128  | A/B Testing backend (model, split job, tracking, analytics)        | 3 INF + 11 BE + 4 tests      |
| Sprint 49 | 129–131  | Frontend A/B config + résultats + variables panel + test modal     | 8 FE + 7 tests               |
| Sprint 50 | 132–134  | Hardening GDPR, PHPStan, ESLint, dashboard widget                 | 3 BE/FE + 1 test             |
| **Total** | **12 sem** | **v2.1.0**                                                     | **~31 tâches + 15 tests**    |
