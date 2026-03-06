# Phase 19 — Task Tracking

> **Status**: todo
> **Prerequisite**: Phase 18 fully merged and tagged `v2.4.0`
> **Spec**: [docs/phases/phase19.md](../phases/phase19.md)

---

## Sprint 63 — Backend Modèles & Enrollment (Weeks 171–173)

### Infrastructure & Database

| ID              | Task                                                                                                                                                                        | Status | Owner |
|-----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P19-BE-INF-01   | Migration `create_workflows_table` — UUID PK, user_id FK, name, description nullable, trigger_type ENUM, trigger_config JSON nullable, status ENUM(draft/active/paused/archived), entry_step_id UUID nullable, timestamps. | todo   |       |
| P19-BE-INF-02   | Migration `create_workflow_steps_table` — UUID PK, workflow_id FK CASCADE, type ENUM(send_email/wait/condition/update_score/add_tag/remove_tag/enroll_drip/update_field/end), config JSON, next_step_id UUID nullable self-ref, else_step_id UUID nullable self-ref, position_x FLOAT, position_y FLOAT, timestamps. | todo   |       |
| P19-BE-INF-03   | Migration `create_workflow_enrollments_table` — UUID PK, workflow_id FK CASCADE, contact_id FK CASCADE, current_step_id UUID nullable FK, status ENUM, enrolled_at, last_processed_at nullable, completed_at nullable, error_message TEXT nullable. Index unique `(workflow_id, contact_id)` partiel actif. Index `(status, last_processed_at)`. | todo   |       |

### Backend Tasks

| ID          | Task                                                                                                                                                                                                            | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P19-BE-001  | Create `Workflow` model — `HasUuids`, `HasFactory`. Fillable, casts trigger_config array. Relations `user()`, `steps()`, `enrollments()`. Scopes `active()`, `forUser()`, `withTrigger(string $type)`.          | todo   |       |
| P19-BE-002  | Create `WorkflowStep` model — `HasUuids`, `HasFactory`. Fillable, casts config array. Relations `workflow()`, `nextStep()`, `elseStep()`. Méthode `isEnd(): bool`.                                              | todo   |       |
| P19-BE-003  | Create `WorkflowEnrollment` model — `HasUuids`, `HasFactory`. Fillable, casts. Relations `workflow()`, `contact()`, `currentStep()`. Scopes `active()`, `dueForProcessing()`.                                   | todo   |       |
| P19-BE-004  | Create `WorkflowEnrollmentService` — `enroll(Contact $contact, Workflow $workflow): ?WorkflowEnrollment`, `enrollSegment(Segment $segment, Workflow $workflow): int`. Vérifie double enrollment et suppression list. | todo   |       |
| P19-BE-005  | Create `WorkflowPolicy` — ownership + actions enroll/pause/cancel.                                                                                                                                              | todo   |       |
| P19-BE-006  | Create `WorkflowController` — CRUD workflows + actions activate/pause. CRUD steps via `POST /workflows/{workflow}/steps`, `PUT /workflow-steps/{step}`, `DELETE /workflow-steps/{step}`.                        | todo   |       |
| P19-BE-007  | Create `WorkflowEnrollmentController` — `GET /workflows/{workflow}/enrollments`, `PATCH /{enrollment}/pause`, `PATCH /{enrollment}/resume`, `PATCH /{enrollment}/cancel`.                                       | todo   |       |
| P19-BE-008  | PHPStan level 8 + Pint.                                                                                                                                                                                         | todo   |       |

### Backend Tests (TDD)

| ID          | Test File                                                                                    | Status | Owner |
|-------------|----------------------------------------------------------------------------------------------|--------|-------|
| P19-BT-001  | `tests/Unit/Services/WorkflowEnrollmentServiceTest.php`                                      | todo   |       |
| P19-BT-002  | `tests/Feature/Workflow/WorkflowCrudTest.php`                                                | todo   |       |

---

## Sprint 64 — Backend Executor & Triggers (Weeks 174–176)

### Backend Tasks

| ID          | Task                                                                                                                                                                                                            | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P19-BE-009  | Create `WorkflowStepExecutor` — `execute(WorkflowStep $step, WorkflowEnrollment $enrollment): ?string`. Dispatch par type : send_email, wait, condition, update_score, add_tag, remove_tag, enroll_drip, update_field, end. | todo   |       |
| P19-BE-010  | Create `SendWorkflowEmailJob` — Vérifie suppression list. Envoie email depuis config du step. Crée `CampaignRecipient` lié au workflow.                                                                          | todo   |       |
| P19-BE-011  | Create `AdvanceWorkflowEnrollmentsJob` — Schedulé toutes les 5 min. Charge enrollments `dueForProcessing()`. Exécute step, avance `current_step_id`. Gère les erreurs par enrollment.                           | todo   |       |
| P19-BE-012  | Create `WorkflowTriggerService` — `evaluateTriggers(string $event, Contact $contact, array $context): void`. Pour chaque workflow actif avec le trigger : vérifie `trigger_config` puis enrole.                  | todo   |       |
| P19-BE-013  | Extend `EmailTrackingController::open()` et `click()` — Appeler `WorkflowTriggerService::evaluateTriggers('email_opened'/'email_clicked', …)`.                                                                  | todo   |       |
| P19-BE-014  | Extend `ContactObserver::created()` — Appeler `WorkflowTriggerService::evaluateTriggers('contact_created', …)`.                                                                                                 | todo   |       |
| P19-BE-015  | Extend `ContactScoreService::recalculate()` — Après mise à jour score, appeler `WorkflowTriggerService::evaluateTriggers('score_threshold', …)`.                                                                | todo   |       |
| P19-BE-016  | Create `CheckSegmentMembershipJob` — Schedulé quotidiennement. Pour chaque workflow avec trigger `segment_entered` : évaluer segment → enroller nouveaux contacts.                                              | todo   |       |
| P19-BE-017  | PHPStan level 8 + Pint.                                                                                                                                                                                         | todo   |       |

### Backend Tests (TDD)

| ID          | Test File                                                                                    | Status | Owner |
|-------------|----------------------------------------------------------------------------------------------|--------|-------|
| P19-BT-003  | `tests/Unit/Services/WorkflowStepExecutorTest.php`                                           | todo   |       |
| P19-BT-004  | `tests/Unit/Jobs/AdvanceWorkflowEnrollmentsJobTest.php`                                      | todo   |       |
| P19-BT-005  | `tests/Feature/Workflow/WorkflowTriggerTest.php`                                             | todo   |       |
| P19-BT-006  | `tests/Feature/Workflow/WorkflowExecutionTest.php`                                           | todo   |       |

---

## Sprint 65 — Frontend Workflow Builder (Weeks 177–179)

### Frontend Tasks

| ID          | Task                                                                                                                                                                                                            | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P19-FE-001  | Create `lib/stores/workflows.ts` Zustand store — state workflows[], currentWorkflow, enrollments[]. Actions : fetch, create, update, delete, activate, pause, fetchEnrollments, cancelEnrollment.               | todo   |       |
| P19-FE-002  | Create `app/(dashboard)/campaigns/workflows/page.tsx` — Liste workflows : nom, trigger, nb enrollments actifs, taux completion, statut.                                                                         | todo   |       |
| P19-FE-003  | Create `app/(dashboard)/campaigns/workflows/create/page.tsx` + `[id]/page.tsx` — Config (nom, trigger) + éditeur de flow.                                                                                       | todo   |       |
| P19-FE-004  | Create `components/workflows/workflow-builder.tsx` — Éditeur visuel `reactflow` : palette nœuds (send_email, wait, condition, update_score, add_tag, end), connexions next/else, sauvegarde layout.             | todo   |       |
| P19-FE-005  | Create `components/workflows/workflow-node-config.tsx` — Panneau de configuration latéral par type de nœud.                                                                                                     | todo   |       |
| P19-FE-006  | Create `components/workflows/workflow-enrollments-table.tsx` — Tableau contacts enrollés : étape courante, statut, enrolled_at, actions pause/cancel.                                                           | todo   |       |
| P19-FE-007  | Extend sidebar — Entrée "Workflows" sous Campagnes (icône GitFork).                                                                                                                                             | todo   |       |
| P19-FE-008  | Extend `app/(dashboard)/page.tsx` — Widget "Workflows actifs" (count + enrollments actifs). Masqué si 0.                                                                                                        | todo   |       |

### Frontend Tests

| ID          | Test File                                                                                           | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------|--------|-------|
| P19-FT-001  | `tests/unit/stores/workflows.test.ts`                                                               | todo   |       |
| P19-FT-002  | `tests/components/workflows/workflow-node-config.test.tsx`                                          | todo   |       |
| P19-FT-003  | `tests/components/workflows/workflow-enrollments-table.test.tsx`                                    | todo   |       |
| P19-FT-004  | `tests/e2e/campaigns/workflow-builder-flow.spec.ts`                                                 | todo   |       |
| P19-FT-005  | `tests/e2e/campaigns/workflow-execution-flow.spec.ts`                                               | todo   |       |

---

## Sprint 66 — Hardening GDPR & CI (Weeks 180–182)

### Backend Tasks

| ID          | Task                                                                                           | Status | Owner |
|-------------|------------------------------------------------------------------------------------------------|--------|-------|
| P19-BE-018  | Extend `DataExportService` — Inclure `WorkflowEnrollment` dans l'export GDPR.                 | todo   |       |
| P19-BE-019  | Add command `workflow-enrollments:prune` — Supprime enrollments completed/cancelled > 90 jours. Planifiée hebdomadairement. | todo   |       |
| P19-BE-020  | PHPStan level 8 + Pint sur tous les fichiers de la phase.                                      | todo   |       |

### Frontend Tasks

| ID          | Task                                                                                           | Status | Owner |
|-------------|------------------------------------------------------------------------------------------------|--------|-------|
| P19-FE-009  | ESLint + Prettier — 0 erreur sur tous les fichiers nouveaux/modifiés.                          | todo   |       |

### Backend Tests

| ID          | Test File                                                                                    | Status | Owner |
|-------------|----------------------------------------------------------------------------------------------|--------|-------|
| P19-BT-007  | `tests/Feature/Workflow/WorkflowGdprTest.php`                                                | todo   |       |
| P19-BT-008  | `tests/Feature/Workflow/WorkflowEnrollmentPruneTest.php`                                     | todo   |       |

---

## Récapitulatif

| Sprint    | Semaines | Livrable principal                                              | Tasks                           |
|-----------|----------|-----------------------------------------------------------------|---------------------------------|
| Sprint 63 | 171–173  | Backend modèles, enrollment, CRUD API                          | 3 INF + 8 BE + 2 tests          |
| Sprint 64 | 174–176  | Backend executor, triggers, scheduler                          | 9 BE + 4 tests                  |
| Sprint 65 | 177–179  | Frontend workflow builder + enrollments UI                     | 8 FE + 5 tests                  |
| Sprint 66 | 180–182  | Hardening GDPR, prune, PHPStan, ESLint, CI                     | 3 BE/FE + 2 tests               |
| **Total** | **12 sem** | **v2.5.0**                                                   | **~3 INF + 28 BE/FE + 13 tests** |
