# Phase 16 — Drip Campaigns, Suppression List & Bounce Management (v2.2)

| Field               | Value                                                        |
|---------------------|--------------------------------------------------------------|
| **Phase**           | 16                                                           |
| **Name**            | Drip Campaigns, Suppression List & Bounce Management         |
| **Duration**        | Weeks 135–146 (12 weeks)                                     |
| **Milestone**       | M16 — v2.2.0 Release                                        |
| **PRD Sections**    | §4.26 FR-DRIP (nouveau), §4.27 FR-SUPPRESS (nouveau), §4.28 FR-BOUNCE (nouveau) |
| **Prerequisite**    | Phase 15 fully completed and tagged `v2.1.0`                 |
| **Status**          | todo                                                         |

---

## 1. Phase Objectives

| ID        | Objective                                                                                                                                           |
|-----------|-----------------------------------------------------------------------------------------------------------------------------------------------------|
| P16-OBJ-1 | Permettre la création de **séquences drip** : série d'emails automatisés envoyés à intervalles définis (J+1, J+3, J+7…) après un événement déclencheur |
| P16-OBJ-2 | Supporter les **déclencheurs comportementaux** : envoyer l'étape suivante si l'email précédent a été ouvert / cliqué, ou si non-ouvert après N heures |
| P16-OBJ-3 | Implémenter une **liste de suppression centralisée** (email supprimé = blacklisté pour toutes les campagnes futures de l'utilisateur)                |
| P16-OBJ-4 | Gérer automatiquement les **hard bounces** : adresse blacklistée immédiatement et exclue des futures campagnes                                       |
| P16-OBJ-5 | Gérer les **soft bounces** : réessai configurable (max 3) avec backoff, puis passage en hard bounce si dépassé                                       |
| P16-OBJ-6 | Exposer les statistiques de délivrabilité : taux de bounce (hard/soft), taux de désabonnement, liste de suppression consultable                     |
| P16-OBJ-7 | Maintenir une couverture de tests >= 80% backend et frontend                                                                                         |

---

## 2. Contexte & Analyse de l'existant

### 2.1 Ce qui est déjà en place

| Besoin | Brique existante | Phase d'origine |
|--------|-----------------|-----------------|
| Modèle `Campaign` + `CampaignRecipient` | Phase 3 | Phase 3 |
| Statuts recipient : sent/delivered/opened/clicked/bounced/failed/unsubscribed | Phase 3 | Phase 3 |
| `SendEmailCampaignJob`, `SendCampaignEmailJob` | Phase 3 | Phase 3 |
| Tracking ouverture / clic (`EmailTrackingController`) | Phase 3 | Phase 3 |
| Séquences de relance pour factures (`ReminderSequence`) | Phase 12 | Phase 12 |
| Analytics bounce rate | Phase 3 | Phase 3 |

### 2.2 Analyse du gap

| Besoin | Gap actuel | Solution Phase 16 |
|--------|-----------|-------------------|
| Séquences multi-étapes | Les campagnes sont one-shot | `DripSequence` + `DripStep` models + orchestration job |
| Déclencheurs comportementaux | Aucun suivi post-envoi pour re-cibler | Conditions sur `opened_at`/`clicked_at` dans le job d'avancement |
| Suppression list centralisée | Un désabonné dans campagne A peut recevoir campagne B | Table `suppressed_emails` + check systématique dans tous les jobs |
| Hard bounce management | `bounced_at` tracé mais aucune action | Observer `CampaignRecipient` → blacklist automatique sur bounce |
| Soft bounce retry | Aucun mécanisme de retry | Compteur `bounce_count` + job de retry avec backoff exponentiel |

---

## 3. Choix techniques

### 3.1 Modèle de données

**Migrations :**

| Migration | Description |
|-----------|-------------|
| `create_drip_sequences_table` | UUID PK, `user_id` FK, `name` VARCHAR(255), `trigger_event` ENUM(`campaign_sent`, `contact_created`, `manual`), `trigger_campaign_id` UUID nullable FK, `status` ENUM(`active`, `paused`, `archived`), `settings` JSON nullable, timestamps. |
| `create_drip_steps_table` | UUID PK, `sequence_id` FK CASCADE, `position` TINYINT NOT NULL (ordre d'exécution, unique par séquence), `delay_hours` SMALLINT NOT NULL (délai depuis l'étape précédente ou le déclencheur), `condition` ENUM(`none`, `if_opened`, `if_clicked`, `if_not_opened`) DEFAULT `none`, `subject` VARCHAR(255) NOT NULL, `content` TEXT NOT NULL, `template_id` UUID FK nullable, timestamps. Index unique `(sequence_id, position)`. |
| `create_drip_enrollments_table` | UUID PK, `sequence_id` FK CASCADE, `contact_id` FK CASCADE, `current_step_position` TINYINT DEFAULT 0, `status` ENUM(`active`, `completed`, `paused`, `cancelled`, `failed`), `enrolled_at` TIMESTAMP, `last_processed_at` TIMESTAMP nullable, `completed_at` TIMESTAMP nullable, timestamps. Index unique `(sequence_id, contact_id)`. Index `(status, last_processed_at)` pour le scheduler. |
| `create_suppressed_emails_table` | UUID PK, `user_id` FK, `email` VARCHAR(255) NOT NULL, `reason` ENUM(`unsubscribed`, `hard_bounce`, `manual`), `source_campaign_id` UUID nullable FK, `suppressed_at` TIMESTAMP, timestamps. Index unique `(user_id, email)`. |
| `add_bounce_fields_to_campaign_recipients_table` | `bounce_count` TINYINT DEFAULT 0, `bounce_type` ENUM(`hard`, `soft`) nullable. |

### 3.2 Logique Drip Sequence

```
Enrollment :
1. Déclencheur → DripEnrollmentService::enroll(Contact $contact, DripSequence $sequence)
   - Vérifie que contact n'est pas déjà enrolled (statut active)
   - Vérifie que contact n'est pas dans la suppression list
   - Crée DripEnrollment{status=active, current_step_position=0}

Avancement (AdvanceDripEnrollmentsJob — schedulé toutes les 5 min) :
1. Charge les enrollments {status=active}
   où now() >= last_processed_at + current_step.delay_hours
2. Pour chaque enrollment :
   a. Charge l'étape courante (current_step_position)
   b. Évalue la condition :
      - if_opened : récupère le CampaignRecipient du step précédent → opened_at non null
      - if_clicked : clicked_at non null
      - if_not_opened : opened_at null ET delay_hours dépassé
      - none : toujours vrai
   c. Si condition non remplie → skip (ne pas avancer, attendre)
   d. Si condition remplie → SendDripStepEmailJob::dispatch(enrollment, step)
   e. current_step_position++ ; last_processed_at = now()
   f. Si plus d'étapes → status = completed, completed_at = now()

SendDripStepEmailJob :
1. Vérifie suppression list
2. Envoie via PersonalizationService + Mailable
3. Crée CampaignRecipient (pour tracking ouverture/clic)
```

### 3.3 Logique Suppression List

Deux couches :
| Couche | Mécanisme |
|--------|-----------|
| **Ajout automatique** | `CampaignRecipientObserver::updated()` : si `status` passe à `bounced` ET `bounce_type = hard` → `SuppressedEmail::firstOrCreate(['user_id', 'email'], ['reason' => 'hard_bounce'])` |
| **Ajout sur désabonnement** | `EmailTrackingController::unsubscribe()` existant → ajouter `SuppressedEmail::firstOrCreate(…, ['reason' => 'unsubscribed'])` |
| **Check à l'envoi** | `SendEmailCampaignJob` + `SendDripStepEmailJob` : charger en mémoire la suppression list de l'user (`Set<string>`) au début du job, skip les contacts dont l'email est dans le set |

### 3.4 Logique Bounce Management

```
Hard bounce (ex. adresse inexistante, domaine invalide) :
→ CampaignRecipient.status = 'bounced', bounce_type = 'hard', bounce_count = 1
→ Observer → SuppressedEmail créé

Soft bounce (ex. boîte pleine, serveur temporairement indisponible) :
→ CampaignRecipient.bounce_count++
→ Si bounce_count < 3 : RetryBounceEmailJob::dispatch()->delay(backoff)
   Backoffs : 1h, 6h, 24h (selon bounce_count)
→ Si bounce_count >= 3 : status = 'bounced', bounce_type = 'hard' → blacklist

Webhook SES/provider :
→ CampaignWebhookController étendu pour interpréter les types de bounce
```

---

## 4. Entry Criteria

- Phase 15 exit criteria 100% satisfaits.
- CI verts sur `main`.
- `v2.1.0` tagué et déployé.
- `CampaignRecipient`, `SendEmailCampaignJob`, `EmailTrackingController` stables.

---

## 5. Scope — Requirement Traceability

### 5.1 Drip Campaigns (FR-DRIP)

| Feature | Priority | Included |
|---------|----------|----------|
| Modèle `DripSequence` + `DripStep` + `DripEnrollment` | High | Yes |
| Enrollment d'un contact dans une séquence | High | Yes |
| Enrollment en masse depuis un segment | High | Yes |
| Job `AdvanceDripEnrollmentsJob` (scheduler 5 min) | High | Yes |
| Conditions comportementales (if_opened, if_clicked, if_not_opened) | High | Yes |
| Pause/reprise/annulation d'un enrollment | High | Yes |
| Tracking des emails envoyés par drip (via CampaignRecipient) | High | Yes |
| UI : liste des séquences, formulaire création, éditeur d'étapes | High | Yes |
| UI : vue enrollments avec statut par contact | Medium | Yes |
| UI : widget dashboard "Séquences actives" | Medium | Yes |
| Envoi immédiat de la première étape (delay_hours = 0) | High | Yes |
| Déclencheur automatique à la création d'un contact | Low | No |
| Déclencheur sur événement externe (webhook entrant) | Low | No |
| Plus de 10 étapes par séquence | Low | Yes |

### 5.2 Suppression List (FR-SUPPRESS)

| Feature | Priority | Included |
|---------|----------|----------|
| Table `suppressed_emails` centralisée par user | High | Yes |
| Ajout automatique sur hard bounce | High | Yes |
| Ajout automatique sur désabonnement | High | Yes |
| Ajout manuel par l'utilisateur | High | Yes |
| Import CSV d'emails à supprimer | Medium | Yes |
| Export CSV de la liste de suppression | Medium | Yes |
| Check systématique dans `SendEmailCampaignJob` | High | Yes |
| Check systématique dans `SendDripStepEmailJob` | High | Yes |
| UI : page de gestion de la liste (recherche, ajout, suppression) | High | Yes |
| Suppression inter-utilisateurs | Low | No |

### 5.3 Bounce Management (FR-BOUNCE)

| Feature | Priority | Included |
|---------|----------|----------|
| Distinction hard/soft bounce sur `CampaignRecipient` | High | Yes |
| Compteur `bounce_count` par recipient | High | Yes |
| Retry automatique soft bounce (max 3, backoff exponentiel) | High | Yes |
| Blacklist automatique après 3 soft bounces | High | Yes |
| Blacklist immédiate sur hard bounce | High | Yes |
| Webhook SES bounce parsing | Medium | Yes |
| Statistiques hard/soft bounce dans les analytics | Medium | Yes |

---

## 6. Detailed Sprint Breakdown

### 6.1 Sprint 51 — Backend Suppression List & Bounce Management (Weeks 135–137)

#### 6.1.1 Infrastructure & Database

| ID              | Migration | Description |
|-----------------|-----------|-------------|
| P16-BE-INF-01   | `create_suppressed_emails_table` | Voir §3.1 |
| P16-BE-INF-02   | `add_bounce_fields_to_campaign_recipients_table` | `bounce_count`, `bounce_type` |

#### 6.1.2 Backend — Modèles, Services & Controller

| ID          | Task |
|-------------|------|
| P16-BE-001  | Create `SuppressedEmail` model — `HasUuids`, `HasFactory`. Fillable : user_id, email, reason, source_campaign_id, suppressed_at. Casts datetime. Relations `user()`, `sourceCampaign()`. Scope `forUser(User $user)`. |
| P16-BE-002  | Create `CampaignRecipientObserver` — `updated()` : si statut passe à `bounced` ET bounce_type `hard` → `SuppressedEmail::firstOrCreate(['user_id' => campaign->user_id, 'email' => email], ['reason' => 'hard_bounce', 'source_campaign_id' => campaign_id, 'suppressed_at' => now()])`. Enregistrer dans `AppServiceProvider`. |
| P16-BE-003  | Create `SuppressionService` — `suppress(User $user, string $email, string $reason, ?string $sourceCampaignId = null): void`. `isSuppressed(User $user, string $email): bool`. `getSuppressedEmails(User $user): Collection`. `importCsv(User $user, string $path): array{imported:int, skipped:int}`. `exportCsv(User $user): StreamedResponse`. |
| P16-BE-004  | Create `SuppressionListController` — `index` (paginé, recherche par email), `store` (ajout manuel), `destroy` (suppression), `import` (upload CSV), `export` (CSV stream). Routes : `GET/POST /suppression-list`, `DELETE /suppression-list/{entry}`, `POST /suppression-list/import`, `GET /suppression-list/export`. |
| P16-BE-005  | Extend `SendEmailCampaignJob` — Charger en mémoire la liste des emails supprimés de l'user (`Set<string>`) avant le cursor. Skip les contacts dont l'email est dans le set. Log le skip dans `failure_reason`. |
| P16-BE-006  | Extend `EmailTrackingController::unsubscribe()` — Ajouter `SuppressionService::suppress(campaign->user, email, 'unsubscribed')` après l'update du recipient. |
| P16-BE-007  | Extend `CampaignWebhookController` — Parser les bounces entrants SES (SNS notification type `Bounce`) : hard bounce → `bounce_type=hard`, soft → `bounce_type=soft`, incrémenter `bounce_count`. |
| P16-BE-008  | Create `RetryBouncedEmailJob` — Queue `campaigns`. Charge le `CampaignRecipient`. Si `bounce_count < 3` et `bounce_type = soft` : re-dispatch `SendCampaignEmailJob`, incrémente `bounce_count`. Sinon : `bounce_type = hard` → observer déclenche blacklist. |
| P16-BE-009  | Extend `CampaignAnalyticsService` — Ajouter `hard_bounce_count`, `soft_bounce_count`, `suppressed_count` dans les stats retournées. |
| P16-BE-010  | PHPStan level 8 + Pint sur tous les fichiers du sprint. |

#### 6.1.3 Backend Tests (TDD)

| ID          | Test File |
|-------------|-----------|
| P16-BT-001  | `tests/Unit/Services/SuppressionServiceTest.php` — suppress/isSuppressed, import CSV, export CSV, idempotence sur double suppress |
| P16-BT-002  | `tests/Feature/Campaigns/SuppressionListCrudTest.php` — CRUD routes, ownership 403, recherche paginée |
| P16-BT-003  | `tests/Feature/Campaigns/BounceManagementTest.php` — hard bounce → blacklist, soft bounce → retry, 3 soft bounces → hard → blacklist, check suppression dans SendEmailCampaignJob |
| P16-BT-004  | `tests/Feature/Campaigns/CampaignAnalyticsBounceTest.php` — hard/soft bounce counts dans analytics |

---

### 6.2 Sprint 52 — Backend Drip Sequences (Weeks 138–140)

#### 6.2.1 Infrastructure & Database

| ID              | Migration |
|-----------------|-----------|
| P16-BE-INF-03   | `create_drip_sequences_table` — Voir §3.1 |
| P16-BE-INF-04   | `create_drip_steps_table` — Voir §3.1 |
| P16-BE-INF-05   | `create_drip_enrollments_table` — Voir §3.1 |

#### 6.2.2 Backend — Modèles, Services & Controller

| ID          | Task |
|-------------|------|
| P16-BE-011  | Create `DripSequence` model — `HasUuids`, `HasFactory`. Fillable, casts settings array. Relations `user()`, `steps()` HasMany ordered by position, `enrollments()` HasMany. Scopes `active()`, `forUser()`. |
| P16-BE-012  | Create `DripStep` model — `HasUuids`, `HasFactory`. Fillable, casts. Relations `sequence()`, `template()`. Méthode `evaluateCondition(DripEnrollment $enrollment): bool` — vérifie la condition comportementale sur le `CampaignRecipient` du step précédent. |
| P16-BE-013  | Create `DripEnrollment` model — `HasUuids`, `HasFactory`. Fillable, casts. Relations `sequence()`, `contact()`. Scopes `active()`, `dueForProcessing()` (filtre sur now() >= last_processed_at + step.delay_hours). |
| P16-BE-014  | Create `DripEnrollmentService` — `enroll(Contact $contact, DripSequence $sequence): DripEnrollment`. `enrollSegment(Segment $segment, DripSequence $sequence): int`. Vérifie suppression list avant enrollment. Évite double enrollment (idempotent). |
| P16-BE-015  | Create `SendDripStepEmailJob` — Queue `campaigns`. Charge enrollment + step. Vérifie suppression list. Envoie via `PersonalizationService` + `CampaignRecipientMail`. Crée `CampaignRecipient` pour tracking. Met à jour enrollment (position++, last_processed_at, status si terminé). |
| P16-BE-016  | Create `AdvanceDripEnrollmentsJob` — Queue `default`, schedulé toutes les 5 minutes dans `Console/Kernel.php`. Charge enrollments `dueForProcessing()`. Pour chaque : évalue condition du step courant, dispatch `SendDripStepEmailJob` si condition OK ou skip si non remplie. |
| P16-BE-017  | Create `DripSequenceController` — CRUD complet + `enroll` (POST enroller un contact), `enrollSegment` (POST enroller un segment), `pause`/`resume`/`cancel` (PATCH sur enrollment). Routes API v1. |
| P16-BE-018  | Create `DripSequencePolicy` — ownership standard + actions enroll/pause/resume/cancel. |
| P16-BE-019  | PHPStan level 8 + Pint. |

#### 6.2.3 Backend Tests (TDD)

| ID          | Test File |
|-------------|-----------|
| P16-BT-005  | `tests/Unit/Services/DripEnrollmentServiceTest.php` — enroll/double enrollment idempotent, suppression check, enrollSegment count |
| P16-BT-006  | `tests/Unit/Jobs/AdvanceDripEnrollmentsJobTest.php` — avancement, condition if_opened OK/KO, condition if_not_opened, complétion séquence |
| P16-BT-007  | `tests/Feature/Drip/DripSequenceCrudTest.php` — CRUD + enroll + enrollSegment + pause/resume/cancel |
| P16-BT-008  | `tests/Feature/Drip/DripSequenceSendTest.php` — email envoyé pour chaque step, CampaignRecipient créé, email supprimé skippé |

---

### 6.3 Sprint 53 — Frontend Drip & Suppression UI (Weeks 141–143)

#### 6.3.1 Frontend Tasks

| ID          | Task |
|-------------|------|
| P16-FE-001  | Create `lib/stores/drip-sequences.ts` Zustand store — state sequences[], currentSequence, enrollments[]. Actions : fetchSequences, createSequence, updateSequence, deleteSequence, fetchSteps, addStep, updateStep, deleteStep, enrollContact, enrollSegment, pauseEnrollment, cancelEnrollment. |
| P16-FE-002  | Create `lib/stores/suppression-list.ts` Zustand store — state entries[], total, page, search. Actions : fetchEntries, addEntry, removeEntry, importCsv, exportCsv. |
| P16-FE-003  | Create `app/(dashboard)/campaigns/drip/page.tsx` — Liste des séquences drip : nom, trigger, nb steps, nb enrollments actifs, statut. Bouton "Nouvelle séquence". |
| P16-FE-004  | Create `app/(dashboard)/campaigns/drip/create/page.tsx` + `[id]/page.tsx` — Formulaire séquence : nom, déclencheur, campagne source (si trigger=campaign_sent). Section "Étapes" : liste réorderable des steps avec délai, condition, sujet, contenu. Bouton "Ajouter une étape". |
| P16-FE-005  | Create `components/drip/drip-step-form.tsx` — Formulaire d'une étape : délai (heures), condition (Select), sujet, éditeur contenu avec `PersonalizationVariablesPanel`. |
| P16-FE-006  | Create `components/drip/drip-enrollments-table.tsx` — Tableau des contacts enrollés : nom, email, étape courante, statut, enrolled_at, dernière activité. Actions pause/cancel par ligne. |
| P16-FE-007  | Create `app/(dashboard)/campaigns/suppression/page.tsx` — Liste suppression : email, raison, date, campagne source. Barre de recherche. Bouton "Ajouter", import CSV, export CSV. |
| P16-FE-008  | Create `components/campaigns/suppression-list-table.tsx` — Tableau paginé avec badge raison (unsubscribed/hard_bounce/manual). Bouton de suppression par ligne. |
| P16-FE-009  | Extend sidebar — Entrée "Drip" sous Campagnes (icône GitBranch). Entrée "Liste de suppression" sous Campagnes (icône Ban). |
| P16-FE-010  | Extend `app/(dashboard)/page.tsx` — Widget "Séquences drip actives" : count séquences status=active + nb enrollments actifs totaux. Masqué si 0. |
| P16-FE-011  | Extend `components/campaigns/analytics.tsx` — Afficher hard_bounce_count, soft_bounce_count, suppressed_count dans la section stats. |

#### 6.3.2 Frontend Tests

| ID          | Test File |
|-------------|-----------|
| P16-FT-001  | `tests/unit/stores/drip-sequences.test.ts` |
| P16-FT-002  | `tests/unit/stores/suppression-list.test.ts` |
| P16-FT-003  | `tests/components/drip/drip-step-form.test.tsx` |
| P16-FT-004  | `tests/components/drip/drip-enrollments-table.test.tsx` |
| P16-FT-005  | `tests/components/campaigns/suppression-list-table.test.tsx` |
| P16-FT-006  | `tests/e2e/campaigns/drip-sequence-flow.spec.ts` |
| P16-FT-007  | `tests/e2e/campaigns/suppression-list-flow.spec.ts` |

#### 6.3.3 Scénarios E2E (Playwright)

| Scénario | Description |
|----------|-------------|
| `drip-sequence-flow.spec.ts` | Créer séquence 3 étapes (J+0, J+1 if_opened, J+3 none) → enroller un contact → vérifier email étape 1 envoyé → simuler opened_at → avancer → vérifier email étape 2 envoyé → vérifier complétion |
| `suppression-list-flow.spec.ts` | Ajouter email manuellement → lancer campagne → vérifier que l'email n'apparaît pas dans les recipients envoyés → import CSV 3 emails → vérifier 3 entrées dans la liste |

---

### 6.4 Sprint 54 — Hardening & CI (Weeks 144–146)

#### 6.4.1 Backend Tasks

| ID          | Task |
|-------------|------|
| P16-BE-020  | Extend `DataExportService` — Inclure `DripEnrollment` (séquences, étapes, statuts) + `SuppressedEmail` (raison, date) dans l'export GDPR. |
| P16-BE-021  | Add command `drip-enrollments:prune` — Supprime enrollments `completed`/`cancelled` de plus de 90 jours. Planifiée hebdomadairement. |
| P16-BE-022  | PHPStan level 8 + Pint sur tous les fichiers du sprint. |

#### 6.4.2 Frontend Tasks

| ID          | Task |
|-------------|------|
| P16-FE-012  | ESLint + Prettier — 0 erreur sur tous les fichiers nouveaux/modifiés. |

#### 6.4.3 Backend Tests

| ID          | Test File |
|-------------|-----------|
| P16-BT-009  | `tests/Feature/Drip/DripSequenceGdprTest.php` — export GDPR inclut enrollments + emails supprimés de l'user |
| P16-BT-010  | `tests/Feature/Drip/DripEnrollmentPruneTest.php` — prune supprime les bons enrollments, garde les actifs |

---

## 7. Exit Criteria

| Critère | Vérification |
|---------|-------------|
| Toutes les tâches `P16-BE-*` et `P16-FE-*` en statut `done` | `docs/dev/phase16.md` |
| Backend coverage >= 80% (`make test-be`) | CI green |
| Frontend coverage >= 80% (`pnpm vitest run --coverage`) | CI green |
| PHPStan level 8 | 0 erreur |
| Pint | 0 erreur |
| ESLint + Prettier | 0 erreur |
| 2 scénarios E2E Playwright verts | `make test-e2e` |
| Séquence drip 3 étapes envoyée avec conditions comportementales | Test manuel |
| Hard bounce → contact blacklisté + exclu des campagnes suivantes | Test manuel |
| Import CSV 10 emails → exclus du prochain envoi | Test manuel |
| Tag `v2.2.0` poussé sur GitHub | `git tag v2.2.0` |

---

## 8. Récapitulatif

| Sprint    | Semaines | Livrable principal                                              | Tasks                        |
|-----------|----------|-----------------------------------------------------------------|------------------------------|
| Sprint 51 | 135–137  | Suppression list + bounce management backend                    | 2 INF + 10 BE + 4 tests      |
| Sprint 52 | 138–140  | Drip sequences backend (models, jobs, scheduler)               | 3 INF + 9 BE + 4 tests       |
| Sprint 53 | 141–143  | Frontend drip UI + suppression UI + analytics bounce           | 11 FE + 7 tests              |
| Sprint 54 | 144–146  | Hardening GDPR, prune, ESLint, CI                              | 3 BE/FE + 2 tests            |
| **Total** | **12 sem** | **v2.2.0**                                                   | **~42 tâches + 17 tests**    |
