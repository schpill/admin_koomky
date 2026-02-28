# Phase 13 — Task Tracking

> **Status**: done
> **Prerequisite**: Phase 12 fully merged and tagged `v1.8.0`
> **Spec**: [docs/phases/phase13.md](../phases/phase13.md)
> **Completed on**: February 28, 2026
> **Delivery**: PR #25 merged into `main`

---

## Completion Summary

La phase 13 est livree et mergee sur `main`.

- Sprint 40: backend timer live livre avec tests et webhook.
- Sprint 41: backend templates livre avec tests unitaires et feature.
- Sprint 42: frontend timer + templates livre avec integrations, tests unitaires, composants, E2E et CI verte.

Les tableaux ci-dessous restent la checklist de suivi originale de la phase.

---

## Sprint 40 — Backend Timer Live (Weeks 102–105)

### Infrastructure & Database

| ID              | Task                                                                                                                                                                    | Status | Owner |
|-----------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P13-BE-INF-01   | Migration `add_timer_fields_to_time_entries_table` — colonnes `started_at TIMESTAMP nullable`, `is_running BOOLEAN DEFAULT false NOT NULL`. Index `(user_id, is_running)`. | todo | — |

### Modèle & Service Timer

| ID          | Task                                                                                                                                                                           | Status | Owner |
|-------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P13-BE-001  | Extend `TimeEntry` model — Ajout `started_at`, `is_running` dans fillable + casts (started_at→datetime, is_running→boolean). Scope `running()`. Méthode `computeDurationMinutes(): int`. | todo | — |
| P13-BE-002  | Create `LiveTimerService` — méthodes : `start(User, Task, ?string): TimeEntry`, `stop(User): TimeEntry`, `cancel(User): void`, `active(User): ?TimeEntry`. Guard 1 timer actif par user. | todo | — |
| P13-BE-003  | Create `LiveTimerController` — méthodes : `active()` (200/204), `start(Request)` (201), `stop()` (200 + webhook), `cancel()` (204). | todo | — |
| P13-BE-004  | Create `StoreLiveTimerRequest` — règles : `task_id` required + appartient au projet de l'user, `description` nullable max 500. | todo | — |
| P13-BE-005  | Register routes `GET /timer/active`, `POST /timer/start`, `POST /timer/stop`, `DELETE /timer/cancel`. | todo | — |
| P13-BE-006  | Extend `WebhookDispatchService` — événement `time.timer_stopped` avec payload {task_id, project_id, duration_minutes, date, started_at, stopped_at}. | todo | — |
| P13-BE-007  | Extend `DashboardService` — méthode `timeTrackedTodayWidget(User): array` → {minutes_today, entries_count} (TimeEntry du jour, is_running=false). | todo | — |

### Backend Tests (TDD)

| ID          | Test File                                                              | Status | Owner |
|-------------|------------------------------------------------------------------------|--------|-------|
| P13-BT-001  | `tests/Unit/Services/LiveTimerServiceTest.php`                         | todo | — |
| P13-BT-002  | `tests/Feature/Timer/LiveTimerControllerTest.php`                      | todo | — |

---

## Sprint 41 — Backend Templates de Projets (Weeks 106–107)

### Infrastructure & Database

| ID              | Task                                                                                                                                                                    | Status | Owner |
|-----------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P13-BE-INF-02   | Migration `create_project_templates_table` — UUID PK, user_id FK CASCADE, name VARCHAR 255, description TEXT nullable, billing_type ENUM(hourly/fixed) nullable, default_hourly_rate DECIMAL 10,2 nullable, default_currency VARCHAR 3 nullable, estimated_hours DECIMAL 8,2 nullable, timestamps. | todo | — |
| P13-BE-INF-03   | Migration `create_project_template_tasks_table` — UUID PK, template_id FK CASCADE, title VARCHAR 255, description TEXT nullable, estimated_hours DECIMAL 8,2 nullable, priority ENUM(low/medium/high/urgent) DEFAULT medium, sort_order INT DEFAULT 0, timestamps. Index `(template_id, sort_order)`. | todo | — |

### Modèles, Services & Controllers

| ID          | Task                                                                                                                                                                           | Status | Owner |
|-------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P13-BE-008  | Create `ProjectTemplate` model — HasUuids, HasFactory. Fillable complet. Casts (default_hourly_rate decimal:2, estimated_hours decimal:2). Relations : user(), templateTasks() HasMany orderedBy sort_order. | todo | — |
| P13-BE-009  | Create `ProjectTemplateTask` model — HasUuids, HasFactory. Fillable, casts (estimated_hours decimal:2). Relation : template(). Scope : ordered() (orderBy sort_order). | todo | — |
| P13-BE-010  | Create `ProjectTemplatePolicy` — ownership standard (user_id match). Méthodes : viewAny, view, create, update, delete. Enregistrement dans AuthServiceProvider. | todo | — |
| P13-BE-011  | Create `ProjectTemplateService` — méthodes : `createFromProject(Project, string, ?string): ProjectTemplate` (copie fields + tâches du projet) ; `instantiate(ProjectTemplate, array, User): Project` (crée Project + Tasks depuis template). | todo | — |
| P13-BE-012  | Create `ProjectTemplateController` — méthodes : index (paginé 15/page), store, show, update, destroy, duplicate (POST — clone template + tasks). Utilise ProjectTemplatePolicy. | todo | — |
| P13-BE-013  | Create `ProjectTemplateSaveController` — méthode `store(Project)` : POST `/projects/{project}/save-as-template`. Ownership projet. Accepte name + description. Appelle ProjectTemplateService::createFromProject(). 201. | todo | — |
| P13-BE-014  | Create `ProjectTemplateInstantiateController` — méthode `store(ProjectTemplate)` : POST `/project-templates/{template}/instantiate`. Valide name, client_id, start_date, deadline. Appelle ProjectTemplateService::instantiate(). 201 avec projet + tâches. | todo | — |
| P13-BE-015  | Create `StoreProjectTemplateRequest` — règles : name requis max 255, description nullable max 2000, billing_type nullable in [hourly, fixed], default_hourly_rate nullable numeric min 0, default_currency nullable size 3, estimated_hours nullable numeric min 0, tasks array nullable avec validation imbriquée (title, description, estimated_hours, priority, sort_order). | todo | — |
| P13-BE-016  | Create `ProjectTemplateFactory` + `ProjectTemplateTaskFactory` — faker. État withTasks(int $count = 3) sur le template factory. | todo | — |
| P13-BE-017  | Register routes : GET/POST /project-templates, GET/PATCH/DELETE /project-templates/{template}, POST /project-templates/{template}/duplicate, POST /project-templates/{template}/instantiate, POST /projects/{project}/save-as-template. | todo | — |

### Backend Tests (TDD)

| ID          | Test File                                                                         | Status | Owner |
|-------------|-----------------------------------------------------------------------------------|--------|-------|
| P13-BT-003  | `tests/Unit/Services/ProjectTemplateServiceTest.php`                              | todo | — |
| P13-BT-004  | `tests/Feature/Templates/ProjectTemplateCrudTest.php`                             | todo | — |
| P13-BT-005  | `tests/Feature/Templates/ProjectTemplateSaveTest.php`                             | todo | — |
| P13-BT-006  | `tests/Feature/Templates/ProjectTemplateInstantiateTest.php`                      | todo | — |

---

## Sprint 42 — Frontend (Weeks 108–110)

### Frontend Tasks — Timer Live

| ID          | Task                                                                                                                                                                           | Status | Owner |
|-------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P13-FE-001  | Create `lib/stores/timer.ts` Zustand store — State : activeEntry, elapsedSeconds, isRunning, taskId, projectId. Actions : fetchActive, startTimer, stopTimer, cancelTimer, tick. Polling 30s sur fetchActive. | todo | — |
| P13-FE-002  | Create `components/timer/timer-badge.tsx` — badge NavBar si isRunning. Icône rouge pulsante + hh:mm:ss. Clic → TimerDropdown. | todo | — |
| P13-FE-003  | Create `components/timer/timer-dropdown.tsx` — dropdown : nom tâche + projet, timer, bouton Arrêter, bouton Annuler. | todo | — |
| P13-FE-004  | Create `components/timer/task-timer-button.tsx` — bouton Start/Stop par tâche. Disabled si timer actif sur autre tâche (tooltip). Props : taskId, projectId, taskName. | todo | — |
| P13-FE-005  | Extend `components/projects/task-kanban-board.tsx` — ajouter TaskTimerButton sur chaque carte. | todo | — |
| P13-FE-006  | Extend `components/projects/task-list-view.tsx` — ajouter TaskTimerButton dans chaque ligne (colonne "Timer"). | todo | — |
| P13-FE-007  | Extend layout NavBar — intégrer TimerBadge (conditionnel). Initialiser timer store au montage. | todo | — |
| P13-FE-008  | Extend `app/(dashboard)/page.tsx` — widget "Temps suivi aujourd'hui" : heures/minutes + nb d'entrées. Masqué si 0. | todo | — |

### Frontend Tasks — Templates de Projets

| ID          | Task                                                                                                                                                                           | Status | Owner |
|-------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P13-FE-009  | Create `lib/stores/project-templates.ts` Zustand store — State : templates[], selectedTemplate, isLoading, error. Actions : fetchTemplates, createTemplate, updateTemplate, deleteTemplate, duplicateTemplate, saveProjectAsTemplate, instantiateTemplate. | todo | — |
| P13-FE-010  | Create `app/(dashboard)/settings/project-templates/page.tsx` — liste des templates avec cards, bouton "+ Nouveau template", message onboarding si vide. | todo | — |
| P13-FE-011  | Create `app/(dashboard)/settings/project-templates/new/page.tsx` — formulaire création avec ProjectTemplateTaskBuilder. Submit → redirect /settings/project-templates/{id}. | todo | — |
| P13-FE-012  | Create `app/(dashboard)/settings/project-templates/[id]/page.tsx` — détail template : champs, liste tâches, boutons Modifier/Dupliquer/Supprimer. | todo | — |
| P13-FE-013  | Create `components/project-templates/project-template-card.tsx` — card : nom, badge billing_type, nb tâches, bouton "Utiliser", kebab (Modifier, Dupliquer, Supprimer). | todo | — |
| P13-FE-014  | Create `components/project-templates/project-template-form.tsx` — formulaire réutilisable (new + edit). Champs + ProjectTemplateTaskBuilder. Zod schema exporté. | todo | — |
| P13-FE-015  | Create `components/project-templates/project-template-task-builder.tsx` — liste ordonnée @dnd-kit/sortable. Chaque tâche : title, description, estimated_hours, priority. Bouton "+ Ajouter tâche". Corbeille par tâche. sort_order auto. | todo | — |
| P13-FE-016  | Create `components/project-templates/instantiate-template-dialog.tsx` — dialog : client (Combobox), nom projet (Input), start_date, deadline (DatePicker), preview liste tâches. Submit → redirect /projects/{id}. | todo | — |
| P13-FE-017  | Create `components/project-templates/save-as-template-dialog.tsx` — dialog contextuelle depuis page projet : nom + description. Submit → saveProjectAsTemplate → toast. | todo | — |
| P13-FE-018  | Extend formulaire "Nouveau Projet" — section "Utiliser un template" (Select optionnel). Si sélectionné → pré-remplir champs + InstantiateTemplateDialog. | todo | — |
| P13-FE-019  | Extend page projet `app/(dashboard)/projects/[id]/page.tsx` — bouton "Sauvegarder comme template" dans kebab/header. Déclenche SaveAsTemplateDialog. | todo | — |
| P13-FE-020  | Extend sidebar — entrée "Templates de projets" avec icône LayoutTemplate (lucide-react) dans sous-menu Paramètres. | todo | — |

### Frontend Tests

| ID          | Test File                                                                                           | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------|--------|-------|
| P13-FT-001  | `tests/unit/stores/timer.test.ts`                                                                   | todo | — |
| P13-FT-002  | `tests/components/timer/timer-badge.test.tsx`                                                       | todo | — |
| P13-FT-003  | `tests/components/timer/timer-dropdown.test.tsx`                                                    | todo | — |
| P13-FT-004  | `tests/components/timer/task-timer-button.test.tsx`                                                 | todo | — |
| P13-FT-005  | `tests/unit/stores/project-templates.test.ts`                                                       | todo | — |
| P13-FT-006  | `tests/components/project-templates/project-template-card.test.tsx`                                 | todo | — |
| P13-FT-007  | `tests/components/project-templates/project-template-form.test.tsx`                                 | todo | — |
| P13-FT-008  | `tests/components/project-templates/project-template-task-builder.test.tsx`                         | todo | — |
| P13-FT-009  | `tests/components/project-templates/instantiate-template-dialog.test.tsx`                           | todo | — |
| P13-FT-010  | `tests/e2e/timer/live-timer-flow.spec.ts`                                                           | todo | — |
| P13-FT-011  | `tests/e2e/templates/project-template-crud.spec.ts`                                                 | todo | — |
| P13-FT-012  | `tests/e2e/templates/project-template-instantiate.spec.ts`                                          | todo | — |

---

## Récapitulatif

| Sprint    | Semaines | Livrable principal                                     | Tasks                    |
|-----------|----------|--------------------------------------------------------|--------------------------|
| Sprint 40 | 102–105  | Backend timer live — migration, model, service, API    | 1 INF + 7 BE + 2 tests   |
| Sprint 41 | 106–107  | Backend templates — migrations, models, service, API   | 2 INF + 10 BE + 4 tests  |
| Sprint 42 | 108–110  | Frontend — timer, templates, wizard, E2E               | 20 FE + 12 tests         |
| **Total** | **9 sem** | **v1.9.0**                                            | **~42 tâches + 18 tests** |
