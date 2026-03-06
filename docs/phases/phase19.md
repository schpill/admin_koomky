# Phase 19 — Workflow Automation Multi-étapes (v2.5)

| Field               | Value                                                        |
|---------------------|--------------------------------------------------------------|
| **Phase**           | 19                                                           |
| **Name**            | Workflow Automation Multi-étapes                             |
| **Duration**        | Weeks 171–182 (12 weeks)                                     |
| **Milestone**       | M19 — v2.5.0 Release                                        |
| **PRD Sections**    | §4.35 FR-WF (nouveau)                                        |
| **Prerequisite**    | Phase 18 fully completed and tagged `v2.4.0`                 |
| **Status**          | todo                                                         |

---

## 1. Phase Objectives

| ID        | Objective                                                                                                                                                           |
|-----------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| P19-OBJ-1 | Permettre la création de **workflows d'automation** multi-étapes : séquence de nœuds (actions + conditions + attentes) déclenchée par un événement                  |
| P19-OBJ-2 | Supporter des **triggers variés** : email ouvert/cliqué, score franchi, contact créé/mis à jour, entrée dans un segment, déclenchement manuel                       |
| P19-OBJ-3 | Supporter des **actions** : envoyer email, attendre N jours/heures, branchement conditionnel (if/else), mettre à jour le score, ajouter/retirer tag, inscrire en drip |
| P19-OBJ-4 | Fournir un **éditeur visuel** (flow diagram) pour construire les workflows sans coder                                                                                |
| P19-OBJ-5 | Gérer les **enrollments de contacts** dans les workflows : un contact ne peut être inscrit qu'une seule fois simultanément par workflow                               |
| P19-OBJ-6 | Maintenir une couverture de tests >= 80% backend et frontend                                                                                                        |

---

## 2. Contexte & Analyse de l'existant

### 2.1 Ce qui est déjà en place

| Besoin | Brique existante | Phase d'origine |
|--------|-----------------|-----------------|
| `DripSequence` + `DripEnrollment` + `AdvanceDripEnrollmentsJob` | Phase 16 | Phase 16 |
| `ContactScoreService::recordEvent()` | Phase 17 | Phase 17 |
| `SuppressionService` + check systématique | Phase 16 | Phase 16 |
| `WebhookDispatchService` pour les event types email | Phase 18 | Phase 18 |
| `SegmentFilterEngine` multi-critères | Phase 7/14 | Phase 7/14 |
| Queue Redis pour jobs | Phase 3 | Phase 3 |

### 2.2 Analyse du gap

| Besoin | Gap actuel | Solution Phase 19 |
|--------|-----------|-------------------|
| Workflows multi-étapes | Les drip séquences sont linéaires (délai fixe, pas de branches) | Modèle `Workflow` + `WorkflowStep` avec `next_step_id` / `else_step_id` |
| Branchements conditionnels | Conditions limitées dans drip (if_opened/if_clicked) | Nœud `condition` avec expression évaluée contre attributs contact + score |
| Actions métier dans un flow | Drip ne fait qu'envoyer des emails | Nœuds : `update_score`, `add_tag`, `enroll_drip`, `update_field`, `end` |
| Triggers variés | Drip est déclenché manuellement ou par campaign_sent uniquement | `WorkflowTriggerService` observant score, segment, events email, CRUD contacts |
| Éditeur visuel | Aucun éditeur de flow dans l'interface | Composant React basé sur `reactflow` |

---

## 3. Choix techniques

### 3.1 Modèle de données

**Migrations :**

| Migration | Description |
|-----------|-------------|
| `create_workflows_table` | UUID PK, `user_id` FK, `name` VARCHAR(255), `description` TEXT nullable, `trigger_type` ENUM(`email_opened`, `email_clicked`, `score_threshold`, `contact_created`, `contact_updated`, `segment_entered`, `manual`), `trigger_config` JSON nullable (ex: `{campaign_id: "...", threshold: 50}`), `status` ENUM(`draft`, `active`, `paused`, `archived`), `entry_step_id` UUID nullable (FK vers workflow_steps, défini après création des steps), timestamps. |
| `create_workflow_steps_table` | UUID PK, `workflow_id` FK CASCADE, `type` ENUM(`send_email`, `wait`, `condition`, `update_score`, `add_tag`, `remove_tag`, `enroll_drip`, `update_field`, `end`), `config` JSON NOT NULL (paramètres spécifiques au type), `next_step_id` UUID nullable FK self-reference, `else_step_id` UUID nullable FK self-reference (branche false pour type=condition), `position_x` FLOAT DEFAULT 0, `position_y` FLOAT DEFAULT 0 (coordonnées UI), timestamps. |
| `create_workflow_enrollments_table` | UUID PK, `workflow_id` FK CASCADE, `contact_id` FK CASCADE, `current_step_id` UUID nullable FK, `status` ENUM(`active`, `completed`, `paused`, `cancelled`, `failed`), `enrolled_at` TIMESTAMP, `last_processed_at` TIMESTAMP nullable, `completed_at` TIMESTAMP nullable, `error_message` TEXT nullable. Index unique `(workflow_id, contact_id)` partiel sur `active`. Index `(status, last_processed_at)` pour le scheduler. |

### 3.2 Types de nœuds et configuration

```
send_email   : config = {subject: string, content: string, template_id?: uuid}
wait         : config = {duration: int, unit: "hours"|"days"}
condition    : config = {attribute: string, operator: string, value: mixed}
               → next_step_id = branche true
               → else_step_id = branche false (null = fin si faux)
update_score : config = {delta: int}  (positif ou négatif)
add_tag      : config = {tag: string}
remove_tag   : config = {tag: string}
enroll_drip  : config = {sequence_id: uuid}
update_field : config = {field: string, value: mixed}  (champs contact whitelist)
end          : config = {}
```

### 3.3 Logique d'exécution

```
Enrollment (WorkflowEnrollmentService::enroll(Contact, Workflow)) :
1. Vérifier que le contact n'est pas déjà enrolled (status=active) dans ce workflow
2. Vérifier suppression list
3. Créer WorkflowEnrollment{current_step_id = workflow.entry_step_id, status=active}

Scheduler (AdvanceWorkflowEnrollmentsJob — schedulé toutes les 5 min) :
1. Charger enrollments {status=active, last_processed_at <= now() ou null}
2. Pour chaque enrollment :
   a. Charger current_step
   b. Si type=wait : vérifier si le délai est écoulé (last_processed_at + duration <= now())
      → si non : skip
   c. Exécuter l'action du step (WorkflowStepExecutor::execute(step, contact))
   d. Mettre à jour current_step_id = next_step_id (ou else_step_id si condition=false)
   e. Si next_step_id null ou type=end : enrollment.status = completed

WorkflowStepExecutor::execute(WorkflowStep, Contact) :
- send_email   → dispatch SendWorkflowEmailJob
- wait         → ne rien faire (le scheduler gère le délai)
- condition    → évaluer la condition, retourner true/false pour choisir la branche
- update_score → ContactScoreService::recordEvent ou ajustement direct
- add_tag      → ajouter tag au client associé
- remove_tag   → retirer tag
- enroll_drip  → DripEnrollmentService::enroll()
- update_field → Contact::update([field => value])
- end          → enrollment.status = completed

WorkflowTriggerService (observateurs) :
- score_threshold   → ContactScoreService::recalculate() → si score franchit le seuil → enroll
- email_opened      → EmailTrackingController::open() → enroll dans workflows matchant
- email_clicked     → EmailTrackingController::click() → enroll
- contact_created   → ContactObserver::created() → enroll dans workflows actifs avec ce trigger
- segment_entered   → SegmentMembershipJob (vérification périodique) → enroll
```

---

## 4. Entry Criteria

- Phase 18 exit criteria 100% satisfaits.
- CI verts sur `main`.
- `v2.4.0` tagué et déployé.
- `DripEnrollmentService`, `ContactScoreService`, `SuppressionService` stables.

---

## 5. Scope — Requirement Traceability

### 5.1 Workflow Automation (FR-WF)

| Feature | Priority | Included |
|---------|----------|----------|
| Modèles `Workflow`, `WorkflowStep`, `WorkflowEnrollment` | High | Yes |
| Nœuds : send_email, wait, condition, update_score, add_tag, enroll_drip, end | High | Yes |
| Nœuds : remove_tag, update_field | Medium | Yes |
| Triggers : email_opened, email_clicked, score_threshold, contact_created | High | Yes |
| Triggers : segment_entered, manual | Medium | Yes |
| Trigger : contact_updated (champ spécifique) | Low | No |
| `WorkflowEnrollmentService` (enroll, enrollSegment) | High | Yes |
| `AdvanceWorkflowEnrollmentsJob` (scheduler 5 min) | High | Yes |
| `WorkflowStepExecutor` (dispatch par type) | High | Yes |
| `WorkflowTriggerService` (observateurs d'événements) | High | Yes |
| CRUD API workflows + steps | High | Yes |
| API : pause/resume/cancel enrollment | High | Yes |
| Éditeur visuel frontend (reactflow) | High | Yes |
| Vue liste des enrollments actifs par workflow | High | Yes |
| Analytics workflow : taux de completion, taux de chute par étape | Medium | Yes |
| Versioning de workflow (ne pas modifier un workflow actif) | Medium | Yes |
| Workflows multi-canaux (SMS, notification push) | Low | No |
| Import/export de workflow en JSON | Low | No |

---

## 6. Detailed Sprint Breakdown

### 6.1 Sprint 63 — Backend Modèles & Enrollment (Weeks 171–173)

#### 6.1.1 Infrastructure & Database

| ID              | Migration |
|-----------------|-----------|
| P19-BE-INF-01   | `create_workflows_table` — Voir §3.1 |
| P19-BE-INF-02   | `create_workflow_steps_table` — Voir §3.1 |
| P19-BE-INF-03   | `create_workflow_enrollments_table` — Voir §3.1 |

#### 6.1.2 Backend Tasks

| ID          | Task |
|-------------|------|
| P19-BE-001  | Create `Workflow` model — `HasUuids`, `HasFactory`. Fillable, casts trigger_config array. Relations `user()`, `steps()`, `enrollments()`. Scopes `active()`, `forUser()`, `withTrigger(string $type)`. |
| P19-BE-002  | Create `WorkflowStep` model — `HasUuids`, `HasFactory`. Fillable, casts config array. Relations `workflow()`, `nextStep()`, `elseStep()`. Méthode `isEnd(): bool`. |
| P19-BE-003  | Create `WorkflowEnrollment` model — `HasUuids`, `HasFactory`. Fillable, casts. Relations `workflow()`, `contact()`, `currentStep()`. Scopes `active()`, `dueForProcessing()`. |
| P19-BE-004  | Create `WorkflowEnrollmentService` — `enroll(Contact $contact, Workflow $workflow): ?WorkflowEnrollment`, `enrollSegment(Segment $segment, Workflow $workflow): int`. Vérifie double enrollment et suppression list. |
| P19-BE-005  | Create `WorkflowPolicy` — ownership + actions enroll/pause/cancel. |
| P19-BE-006  | Create `WorkflowController` — CRUD : `GET/POST /workflows`, `PUT/DELETE /workflows/{workflow}`. Actions : `PATCH /workflows/{workflow}/activate`, `PATCH /workflows/{workflow}/pause`. Steps : `POST /workflows/{workflow}/steps`, `PUT /workflow-steps/{step}`, `DELETE /workflow-steps/{step}`. |
| P19-BE-007  | Create `WorkflowEnrollmentController` — `GET /workflows/{workflow}/enrollments` (paginé), `PATCH /workflow-enrollments/{enrollment}/pause`, `PATCH /workflow-enrollments/{enrollment}/resume`, `PATCH /workflow-enrollments/{enrollment}/cancel`. |
| P19-BE-008  | PHPStan level 8 + Pint. |

#### 6.1.3 Backend Tests (TDD)

| ID          | Test File |
|-------------|-----------|
| P19-BT-001  | `tests/Unit/Services/WorkflowEnrollmentServiceTest.php` — enroll crée enrollment, double enrollment rejeté, contact supprimé non enrollé |
| P19-BT-002  | `tests/Feature/Workflow/WorkflowCrudTest.php` — CRUD ownership, activation refuse si entry_step absent |

---

### 6.2 Sprint 64 — Backend Executor & Triggers (Weeks 174–176)

#### 6.2.1 Backend Tasks

| ID          | Task |
|-------------|------|
| P19-BE-009  | Create `WorkflowStepExecutor` — `execute(WorkflowStep $step, WorkflowEnrollment $enrollment): ?string` (retourne l'ID du prochain step). Dispatch par type : send_email, wait (vérification délai), condition (évaluation), update_score, add_tag, remove_tag, enroll_drip, update_field, end. |
| P19-BE-010  | Create `SendWorkflowEmailJob` — Vérifie suppression list. Construit email depuis config (subject, content, template_id). Envoie via SES. Crée `CampaignRecipient` lié au workflow. |
| P19-BE-011  | Create `AdvanceWorkflowEnrollmentsJob` — Schedulé toutes les 5 min. Charge enrollments `dueForProcessing()`. Pour chaque : `WorkflowStepExecutor::execute()` → avancer `current_step_id` → sauvegarder. Gérer les erreurs par enrollment sans stopper le batch. |
| P19-BE-012  | Create `WorkflowTriggerService` — `evaluateTriggers(string $event, Contact $contact, array $context): void`. Pour chaque workflow actif avec le trigger correspondant : vérifier `trigger_config` (seuil, campaign_id…) puis `enroll()`. |
| P19-BE-013  | Extend `EmailTrackingController::open()` et `click()` — Appeler `WorkflowTriggerService::evaluateTriggers('email_opened'/'email_clicked', $contact, ['campaign_id' => …])`. |
| P19-BE-014  | Extend `ContactObserver::created()` — Appeler `WorkflowTriggerService::evaluateTriggers('contact_created', $contact, [])`. |
| P19-BE-015  | Extend `ContactScoreService::recalculate()` — Après mise à jour du score, appeler `WorkflowTriggerService::evaluateTriggers('score_threshold', $contact, ['score' => $score])`. |
| P19-BE-016  | Create `CheckSegmentMembershipJob` — Schedulé quotidiennement. Pour chaque workflow actif avec trigger `segment_entered` : évaluer le segment → enroller les nouveaux contacts. |
| P19-BE-017  | PHPStan level 8 + Pint. |

#### 6.2.2 Backend Tests (TDD)

| ID          | Test File |
|-------------|-----------|
| P19-BT-003  | `tests/Unit/Services/WorkflowStepExecutorTest.php` — send_email dispatche le job, wait bloque si délai non écoulé, condition évalue correctement, update_score ajuste le score, add_tag ajoute le tag |
| P19-BT-004  | `tests/Unit/Jobs/AdvanceWorkflowEnrollmentsJobTest.php` — enrollment avance, enrollment complété sur end, erreur isolée par enrollment |
| P19-BT-005  | `tests/Feature/Workflow/WorkflowTriggerTest.php` — email_opened enrole dans workflow actif, score_threshold enrole si seuil franchi, contact_created enrole |
| P19-BT-006  | `tests/Feature/Workflow/WorkflowExecutionTest.php` — workflow complet (send → wait → condition → send → end) exécuté correctement, branche else suivie si condition false |

---

### 6.3 Sprint 65 — Frontend Workflow Builder (Weeks 177–179)

#### 6.3.1 Frontend Tasks

| ID          | Task |
|-------------|------|
| P19-FE-001  | Create `lib/stores/workflows.ts` Zustand store — state workflows[], currentWorkflow, enrollments[]. Actions : fetch, create, update, delete, activate, pause, fetchEnrollments, cancelEnrollment. |
| P19-FE-002  | Create `app/(dashboard)/campaigns/workflows/page.tsx` — Liste workflows : nom, trigger, nb enrollments actifs, taux completion, statut badge (draft/active/paused). |
| P19-FE-003  | Create `app/(dashboard)/campaigns/workflows/create/page.tsx` + `[id]/page.tsx` — Formulaire config (nom, trigger, trigger_config) + éditeur de flow. |
| P19-FE-004  | Create `components/workflows/workflow-builder.tsx` — Éditeur visuel basé sur `reactflow`. Palette de nœuds à glisser (send_email, wait, condition, update_score, add_tag, end). Connexions entre nœuds (next/else). Sauvegarde auto du layout (position_x, position_y). |
| P19-FE-005  | Create `components/workflows/workflow-node-config.tsx` — Panneau de configuration latéral s'ouvrant au clic sur un nœud. Formulaire spécifique par type (send_email : subject + editor, wait : durée + unité, condition : attribut + opérateur + valeur…). |
| P19-FE-006  | Create `components/workflows/workflow-enrollments-table.tsx` — Tableau des contacts enrollés : étape courante, statut, enrolled_at, actions pause/cancel. |
| P19-FE-007  | Extend sidebar — Entrée "Workflows" sous Campagnes (icône GitFork). |
| P19-FE-008  | Extend `app/(dashboard)/page.tsx` — Widget "Workflows actifs" : count + enrollments actifs totaux. Masqué si 0. |

#### 6.3.2 Frontend Tests

| ID          | Test File |
|-------------|-----------|
| P19-FT-001  | `tests/unit/stores/workflows.test.ts` |
| P19-FT-002  | `tests/components/workflows/workflow-node-config.test.tsx` — rendu du formulaire par type de nœud |
| P19-FT-003  | `tests/components/workflows/workflow-enrollments-table.test.tsx` |
| P19-FT-004  | `tests/e2e/campaigns/workflow-builder-flow.spec.ts` — créer workflow → ajouter nœuds → activer → vérifier enrollment sur trigger |
| P19-FT-005  | `tests/e2e/campaigns/workflow-execution-flow.spec.ts` — contact enrollé → avance au nœud wait → avance au send → email reçu |

---

### 6.4 Sprint 66 — Hardening GDPR & CI (Weeks 180–182)

#### 6.4.1 Backend Tasks

| ID          | Task |
|-------------|------|
| P19-BE-018  | Extend `DataExportService` — Inclure `WorkflowEnrollment` dans l'export GDPR (workflows rejoints, étape courante, statut). |
| P19-BE-019  | Add command `workflow-enrollments:prune` — Supprime enrollments completed/cancelled > 90 jours. Planifiée hebdomadairement. |
| P19-BE-020  | PHPStan level 8 + Pint — 0 erreur. |

#### 6.4.2 Frontend Tasks

| ID          | Task |
|-------------|------|
| P19-FE-009  | ESLint + Prettier — 0 erreur. |

#### 6.4.3 Backend Tests

| ID          | Test File |
|-------------|-----------|
| P19-BT-007  | `tests/Feature/Workflow/WorkflowGdprTest.php` — export GDPR contient enrollments de l'user, pas ceux d'un autre user |
| P19-BT-008  | `tests/Feature/Workflow/WorkflowEnrollmentPruneTest.php` — enrollments anciens supprimés, actifs préservés |

---

## 7. Exit Criteria

| Critère | Vérification |
|---------|-------------|
| Toutes les tâches `P19-BE-*` et `P19-FE-*` en statut `done` | `docs/dev/phase19.md` |
| Backend coverage >= 80% | CI green |
| Frontend coverage >= 80% | CI green |
| PHPStan level 8 — 0 erreur | CI green |
| Pint + ESLint + Prettier — 0 erreur | CI green |
| Workflow déclenché par email_opened enrole le bon contact | Test manuel |
| Nœud condition branche correctement selon score | Test manuel |
| Nœud wait bloque l'avancement jusqu'à l'heure prévue | Test manuel |
| Éditeur visuel sauvegarde et recharge le flow correctement | Test manuel |
| Tag `v2.5.0` poussé sur GitHub | `git tag v2.5.0` |

---

## 8. Récapitulatif

| Sprint    | Semaines | Livrable principal                                              | Tasks                           |
|-----------|----------|-----------------------------------------------------------------|---------------------------------|
| Sprint 63 | 171–173  | Backend modèles, enrollment, CRUD API                          | 3 INF + 8 BE + 2 tests          |
| Sprint 64 | 174–176  | Backend executor, triggers, scheduler                          | 9 BE + 4 tests                  |
| Sprint 65 | 177–179  | Frontend workflow builder + enrollments UI                     | 8 FE + 5 tests                  |
| Sprint 66 | 180–182  | Hardening GDPR, prune, PHPStan, ESLint, CI                     | 3 BE/FE + 2 tests               |
| **Total** | **12 sem** | **v2.5.0**                                                   | **~3 INF + 28 BE/FE + 13 tests** |
