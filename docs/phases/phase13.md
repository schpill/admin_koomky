# Phase 13 — Timer Live & Templates de Projets (v1.9)

| Field               | Value                                                        |
|---------------------|--------------------------------------------------------------|
| **Phase**           | 13                                                           |
| **Name**            | Timer Live & Templates de Projets                            |
| **Duration**        | Weeks 102–110 (9 weeks)                                      |
| **Milestone**       | M13 — v1.9.0 Release                                        |
| **PRD Sections**    | §4.18 FR-TMR (nouveau), §4.19 FR-TPL (nouveau)              |
| **Prerequisite**    | Phase 12 fully completed and tagged `v1.8.0`                 |
| **Status**          | Planned                                                      |

---

## 1. Phase Objectives

| ID        | Objective                                                                                                                                           |
|-----------|-----------------------------------------------------------------------------------------------------------------------------------------------------|
| P13-OBJ-1 | Permettre le démarrage/arrêt d'un timer live sur une tâche, converti automatiquement en `TimeEntry` à l'arrêt                                      |
| P13-OBJ-2 | Garantir qu'un seul timer actif par utilisateur est possible à tout instant                                                                          |
| P13-OBJ-3 | Afficher un indicateur de timer actif persistant dans la barre de navigation pour une visibilité globale                                             |
| P13-OBJ-4 | Permettre la création de templates de projets à partir d'un projet existant ou depuis zéro                                                           |
| P13-OBJ-5 | Permettre l'instanciation d'un nouveau projet depuis un template (tâches, jalons, description, type de facturation pré-remplis)                     |
| P13-OBJ-6 | Maintenir une couverture de tests >= 80% backend et frontend                                                                                        |

---

## 2. Choix techniques

### 2.1 Infrastructure existante réutilisée

Aucune nouvelle infrastructure infra requise :

| Besoin | Brique existante | Phase d'origine |
|--------|-----------------|-----------------|
| Stockage des saisies de temps | `TimeEntry` (model + migration + factory + controller) | Phase 3 |
| Association tâche → time entry | `Task::timeEntries()` HasMany | Phase 3 |
| Association projet → time entries | `Project::timeEntries()` HasManyThrough | Phase 3 |
| Calcul temps total projet | `Project::getTotalTimeSpentAttribute()` | Phase 3 |
| Auth + Policy | Sanctum + `ProjectPolicy` (ownership) | Phase 1 |
| Queue / Jobs | Laravel Horizon (queues déjà configurées) | Phase 5 |
| Webhooks | `WebhookDispatchService` | Phase 7 |

### 2.2 Modèle de données — Timer Live

**Une migration** nécessaire pour le timer live :

| Migration | Description |
|-----------|-------------|
| `add_timer_fields_to_time_entries_table` | Ajout de `started_at TIMESTAMP nullable` et `is_running BOOLEAN DEFAULT false` sur `time_entries`. Index `(user_id, is_running)` (via une migration séparée si `user_id` n'est pas encore indexé). Contrainte : un seul `is_running = true` par `user_id` (enforced au niveau service, pas DB). |

**Logique timer** dans `TimeEntry` :
- `started_at` : timestamp du démarrage du timer
- `is_running` : flag booléen
- `duration_minutes` : calculé lors de l'arrêt = `ceil((now - started_at) / 60)`
- `date` : date de `started_at` (ou date courante si minuit passé)

### 2.3 Modèle de données — Templates de Projets

**Deux migrations** nécessaires :

| Migration | Description |
|-----------|-------------|
| `create_project_templates_table` | id (UUID PK), user_id (FK → users CASCADE), name (VARCHAR 255), description (TEXT nullable), billing_type (ENUM hourly/fixed nullable), default_hourly_rate (DECIMAL 10,2 nullable), default_currency (VARCHAR 3 nullable), estimated_hours (DECIMAL 8,2 nullable), is_public (BOOLEAN DEFAULT false — template partagé entre instances, non utilisé v1.9 mais prévu), timestamps. |
| `create_project_template_tasks_table` | id (UUID PK), template_id (FK → project_templates CASCADE), title (VARCHAR 255), description (TEXT nullable), estimated_hours (DECIMAL 8,2 nullable), priority (ENUM low/medium/high/urgent DEFAULT medium), sort_order (INT DEFAULT 0), timestamps. Index `(template_id, sort_order)`. |

**Instanciation** : le `ProjectTemplateService::instantiate()` crée un `Project` + des `Task` depuis le template. Les `client_id`, `start_date`, `deadline` sont passés par l'utilisateur lors de l'instanciation.

### 2.4 État global du timer (Frontend)

Le timer live doit être visible depuis n'importe quelle page. Architecture :

- **Store Zustand `timer.ts`** : état singleton (`activeEntry`, `elapsedSeconds`, `isRunning`, `taskId`, `projectId`)
- **Polling toutes les 30 secondes** : `GET /api/v1/timer/active` pour resynchroniser l'état après refresh de page
- **Indicateur NavBar** : badge `TimerBadge` affiché dans la barre de navigation si `isRunning`
- **Tick** : `setInterval` côté client pour incrémenter `elapsedSeconds` chaque seconde (pas de polling serveur pour le tick)

---

## 3. Entry Criteria

- Phase 12 exit criteria 100% satisfaits.
- Tous les checks CI Phase 12 verts sur `main`.
- v1.8.0 tagué et déployé en production.
- `TimeEntry` model et `TimeEntryController` stables et couverts par les tests existants.
- `ProjectPolicy` et ownership pattern stables.

---

## 4. Scope — Requirement Traceability

### 4.1 Module Timer Live (FR-TMR)

| Feature | Priority | Included |
|---------|----------|----------|
| Démarrage timer sur une tâche (bouton Start) | High | Yes |
| Arrêt du timer → création automatique `TimeEntry` | High | Yes |
| Un seul timer actif par utilisateur | High | Yes |
| Endpoint `GET /timer/active` pour récupérer le timer courant | High | Yes |
| Endpoint `POST /timer/start` (task_id, description optionnelle) | High | Yes |
| Endpoint `POST /timer/stop` → crée TimeEntry, retourne l'entrée créée | High | Yes |
| Endpoint `DELETE /timer/cancel` → annule sans créer TimeEntry | Medium | Yes |
| Indicateur timer persistant dans la NavBar | High | Yes |
| Affichage du temps écoulé en temps réel (hh:mm:ss) | High | Yes |
| Intégration bouton Start/Stop sur les cartes de tâche (kanban + list view) | High | Yes |
| Arrêt automatique si autre timer démarré (ou rejet 409) | High | Yes |
| Timer multi-onglet : sync via polling 30s | Medium | Yes |
| Description optionnelle saisie au démarrage | Low | Yes |
| Webhook `time.timer_stopped` avec payload | Low | Yes |
| Widget dashboard "Temps suivi aujourd'hui" | Medium | Yes |
| Timer SMS / notifications push | Low | No |
| Timer offline (PWA) | Low | No |

### 4.2 Module Templates de Projets (FR-TPL)

| Feature | Priority | Included |
|---------|----------|----------|
| CRUD templates de projets | High | Yes |
| Ajout/suppression de tâches template | High | Yes |
| Instanciation d'un projet depuis un template | High | Yes |
| Sauvegarde d'un projet existant comme template ("Save as template") | High | Yes |
| Prévisualisation du template avant instanciation | Medium | Yes |
| Sélection du template dans le formulaire "Nouveau projet" | High | Yes |
| Tri drag-and-drop des tâches template | Medium | Yes |
| Duplication d'un template | Low | Yes |
| Page `/settings/project-templates` — gestion des templates | High | Yes |
| Templates partagés entre utilisateurs (multi-tenant) | Low | No |
| Import/export templates JSON | Low | No |

---

## 5. Detailed Sprint Breakdown

### 5.1 Sprint 40 — Backend Timer Live (Weeks 102–105)

#### 5.1.1 Infrastructure & Database

| Migration / Config | Description |
|--------------------|-------------|
| `add_timer_fields_to_time_entries_table` | Colonnes `started_at TIMESTAMP nullable`, `is_running BOOLEAN DEFAULT false NOT NULL`. Index `(user_id, is_running)`. |

#### 5.1.2 Backend — Modèle & Service Timer

| ID          | Task | PRD Ref |
|-------------|------|---------|
| P13-BE-001  | Extend `TimeEntry` model — Ajout dans `$fillable` : `started_at`, `is_running`. Ajout dans `casts()` : `started_at` → datetime, `is_running` → boolean. Scope `running()` : `whereIsRunning(true)`. Méthode `computeDurationMinutes(): int` (ceil des secondes entre `started_at` et now). | FR-TMR |
| P13-BE-002  | Create `LiveTimerService` — méthodes : `start(User $user, Task $task, ?string $description): TimeEntry` (stoppe tout timer actif existant, crée `TimeEntry` avec `is_running=true`, `started_at=now()`), `stop(User $user): TimeEntry` (charge l'entrée `is_running=true`, calcule `duration_minutes`, met `is_running=false`, `date=started_at->toDateString()`), `cancel(User $user): void` (supprime l'entrée `is_running=true` sans créer de saisie), `active(User $user): ?TimeEntry` (retourne l'entrée `is_running=true` ou null). | FR-TMR |
| P13-BE-003  | Create `LiveTimerController` — méthodes : `active()` (200 ou 204 si aucun timer), `start(Request $request)` (validate task_id, description; Gate::authorize update du projet; appelle `LiveTimerService::start()`; 201), `stop()` (appelle `LiveTimerService::stop()`; dispatche webhook `time.timer_stopped`; 200 avec TimeEntry créée), `cancel()` (appelle `LiveTimerService::cancel()`; 204). | FR-TMR |
| P13-BE-004  | Create `StoreLiveTimerRequest` — règles : `task_id` required, existe dans `tasks` + appartient à un projet de l'utilisateur ; `description` nullable string max 500. | FR-TMR |
| P13-BE-005  | Register routes dans `routes/api.php` (groupe `v1` authentifié) : `GET /timer/active`, `POST /timer/start`, `POST /timer/stop`, `DELETE /timer/cancel`. | FR-TMR |
| P13-BE-006  | Extend `WebhookDispatchService` — ajouter événement `time.timer_stopped` avec payload `{task_id, project_id, duration_minutes, date, started_at, stopped_at}`. | FR-TMR |
| P13-BE-007  | Extend `DashboardService` — ajouter méthode `timeTrackedTodayWidget(User $user): array` → `{minutes_today, entries_count}` : somme des `duration_minutes` des `TimeEntry` de l'utilisateur dont `date = today()` (entrées terminées uniquement, `is_running = false`). | FR-TMR |

#### 5.1.3 Backend Tests — Timer (TDD)

| Test File | Test Cases |
|-----------|-----------|
| `tests/Unit/Services/LiveTimerServiceTest.php` | `start()` crée une TimeEntry is_running=true, `start()` stoppe l'entrée précédente si déjà active, `stop()` calcule duration_minutes correctement, `stop()` met is_running=false, `stop()` lève exception si aucun timer actif, `cancel()` supprime l'entrée sans créer de saisie, `active()` retourne l'entrée active ou null, isolation user (timer user A n'affecte pas user B) |
| `tests/Feature/Timer/LiveTimerControllerTest.php` | `GET /timer/active` 204 si aucun timer, `GET /timer/active` 200 avec TimeEntry si timer actif, `POST /timer/start` 201 crée timer, `POST /timer/start` 409 si tâche d'un autre user, `POST /timer/stop` 200 retourne TimeEntry créée, `POST /timer/stop` 422 si aucun timer actif, `DELETE /timer/cancel` 204, ownership 403 |

---

### 5.2 Sprint 41 — Backend Templates de Projets (Weeks 106–107)

#### 5.2.1 Infrastructure & Database

| Migration / Config | Description |
|--------------------|-------------|
| `create_project_templates_table` | id (UUID PK), user_id (FK → users CASCADE), name (VARCHAR 255), description (TEXT nullable), billing_type (ENUM hourly/fixed nullable), default_hourly_rate (DECIMAL 10,2 nullable), default_currency (VARCHAR 3 nullable), estimated_hours (DECIMAL 8,2 nullable), timestamps. |
| `create_project_template_tasks_table` | id (UUID PK), template_id (FK → project_templates CASCADE), title (VARCHAR 255), description (TEXT nullable), estimated_hours (DECIMAL 8,2 nullable), priority (ENUM low/medium/high/urgent DEFAULT medium), sort_order (INT DEFAULT 0), timestamps. Index `(template_id, sort_order)`. |

#### 5.2.2 Backend — Modèles, Services & Controllers

| ID          | Task | PRD Ref |
|-------------|------|---------|
| P13-BE-008  | Create `ProjectTemplate` model — Traits : `HasUuids`, `HasFactory`. Fillable complet. Casts : `default_hourly_rate` decimal:2, `estimated_hours` decimal:2. Relations : `user()` BelongsTo, `templateTasks()` HasMany → `ProjectTemplateTask` (orderedBy sort_order). | FR-TPL |
| P13-BE-009  | Create `ProjectTemplateTask` model — Traits : `HasUuids`, `HasFactory`. Fillable : title, description, estimated_hours, priority, sort_order. Casts : `estimated_hours` decimal:2. Relation : `template()` BelongsTo. Scope : `ordered()` (orderBy sort_order). | FR-TPL |
| P13-BE-010  | Create `ProjectTemplatePolicy` — ownership standard (`user_id` match). Méthodes : `viewAny`, `view`, `create`, `update`, `delete`. Enregistrement dans `AuthServiceProvider`. | FR-TPL |
| P13-BE-011  | Create `ProjectTemplateService` — méthodes : `createFromProject(Project $project, string $name, ?string $description): ProjectTemplate` (copie `billing_type`, `hourly_rate` → `default_hourly_rate`, `currency` → `default_currency`, `estimated_hours`, et crée un `ProjectTemplateTask` par `Task` du projet avec title, description, estimated_hours, priority, sort_order) ; `instantiate(ProjectTemplate $template, array $data, User $user): Project` (crée un `Project` avec les champs du template + les données passées : client_id, name, start_date, deadline, puis crée les `Task` correspondant aux `templateTasks`). | FR-TPL |
| P13-BE-012  | Create `ProjectTemplateController` — méthodes : `index` (liste paginée 15/page), `store` (création depuis données), `show`, `update`, `destroy`, `duplicate` (POST — clone template + tasks, suffixe "(Copie)" au nom). Utilise `ProjectTemplatePolicy`. | FR-TPL |
| P13-BE-013  | Create `ProjectTemplateSaveController` — méthode `store(Project $project)` : POST `/projects/{project}/save-as-template`. Valide ownership du projet. Accepte `name` (string requis max 255), `description` (nullable). Appelle `ProjectTemplateService::createFromProject()`. 201 avec template créé. | FR-TPL |
| P13-BE-014  | Create `ProjectTemplateInstantiateController` — méthode `store(ProjectTemplate $template)` : POST `/project-templates/{template}/instantiate`. Valide : `name` requis max 255, `client_id` (existe + appartient user), `start_date` nullable date, `deadline` nullable date after_or_equal start_date. Appelle `ProjectTemplateService::instantiate()`. 201 avec projet créé (incluant ses tâches). | FR-TPL |
| P13-BE-015  | Create `StoreProjectTemplateRequest` — règles : `name` requis max 255, `description` nullable max 2000, `billing_type` nullable in [hourly, fixed], `default_hourly_rate` nullable numeric min 0, `default_currency` nullable string size 3, `estimated_hours` nullable numeric min 0, `tasks` array nullable, `tasks.*.title` string requis si présent max 255, `tasks.*.description` nullable string max 2000, `tasks.*.estimated_hours` nullable numeric min 0, `tasks.*.priority` nullable in [low, medium, high, urgent], `tasks.*.sort_order` nullable int. | FR-TPL |
| P13-BE-016  | Create `ProjectTemplateFactory` — faker pour tous les champs. État : `withTasks(int $count = 3)`. Create `ProjectTemplateTaskFactory` — faker avec sort_order croissant. | FR-TPL |
| P13-BE-017  | Register routes dans `routes/api.php` : `GET /project-templates`, `POST /project-templates`, `GET /project-templates/{template}`, `PATCH /project-templates/{template}`, `DELETE /project-templates/{template}`, `POST /project-templates/{template}/duplicate`, `POST /project-templates/{template}/instantiate`, `POST /projects/{project}/save-as-template`. | FR-TPL |

#### 5.2.3 Backend Tests — Templates (TDD)

| Test File | Test Cases |
|-----------|-----------|
| `tests/Unit/Services/ProjectTemplateServiceTest.php` | `createFromProject()` copie les bons champs, `createFromProject()` crée les tasks template avec bon sort_order, `instantiate()` crée un Project + Tasks depuis template, `instantiate()` applique les données overrides (name, client_id, etc.) |
| `tests/Feature/Templates/ProjectTemplateCrudTest.php` | index 200 (liste user), store 201 (template + tasks créés), show 200, update 200 (tasks recréées), destroy 204, duplicate 201 (clone avec "(Copie)"), ownership 403 |
| `tests/Feature/Templates/ProjectTemplateSaveTest.php` | save-as-template 201 (depuis projet avec tasks), ownership 403, projet sans tâches → template sans tasks |
| `tests/Feature/Templates/ProjectTemplateInstantiateTest.php` | instantiate 201 (projet + tasks créés), client_id invalide 422, deadline avant start_date 422, ownership template 403 |

---

### 5.3 Sprint 42 — Frontend (Weeks 108–110)

#### 5.3.1 Frontend Tasks — Timer Live

| ID          | Task | PRD Ref |
|-------------|------|---------|
| P13-FE-001  | Create `lib/stores/timer.ts` Zustand store — State : `activeEntry` (TimeEntry ou null), `elapsedSeconds`, `isRunning`, `taskId`, `projectId`. Actions : `fetchActive()` (GET /timer/active), `startTimer(taskId, description?)` (POST /timer/start), `stopTimer()` (POST /timer/stop → retourne TimeEntry créée), `cancelTimer()` (DELETE /timer/cancel), `tick()` (incrément elapsedSeconds). Init : `fetchActive()` au montage global. Polling 30s avec `setInterval` sur `fetchActive()`. | FR-TMR |
| P13-FE-002  | Create `components/timer/timer-badge.tsx` — badge affiché dans la NavBar si `isRunning`. Affiche : icône enregistrement (rouge pulsant), temps écoulé formaté `hh:mm:ss` (mis à jour chaque seconde via store). Clic → ouvre `TimerDropdown`. | FR-TMR |
| P13-FE-003  | Create `components/timer/timer-dropdown.tsx` — dropdown depuis `TimerBadge` : nom de la tâche + projet, timer affiché, bouton "Arrêter" (appelle `stopTimer()`), bouton "Annuler" (appelle `cancelTimer()`). | FR-TMR |
| P13-FE-004  | Create `components/timer/task-timer-button.tsx` — bouton réutilisable à placer sur les cartes/lignes de tâche. Props : `taskId`, `projectId`, `taskName`. Si `isRunning && timer.taskId === taskId` : bouton Stop (rouge). Sinon : bouton Start (icône Play). Disabled si timer actif sur une autre tâche (avec tooltip "Un timer est déjà actif"). | FR-TMR |
| P13-FE-005  | Extend `components/projects/task-kanban-board.tsx` — ajouter `TaskTimerButton` sur chaque carte de tâche. | FR-TMR |
| P13-FE-006  | Extend `components/projects/task-list-view.tsx` — ajouter `TaskTimerButton` dans chaque ligne de tâche (colonne "Timer"). | FR-TMR |
| P13-FE-007  | Extend layout NavBar — intégrer `TimerBadge` (conditionnel si `isRunning`). Initialiser le timer store au montage du layout. | FR-TMR |
| P13-FE-008  | Extend `app/(dashboard)/page.tsx` — ajouter widget "Temps suivi aujourd'hui" : affiche minutes/heures du jour, nombre de saisies. Chargement indépendant. Masqué si 0 minutes. | FR-TMR |

#### 5.3.2 Frontend Tasks — Templates de Projets

| ID          | Task | PRD Ref |
|-------------|------|---------|
| P13-FE-009  | Create `lib/stores/project-templates.ts` Zustand store — State : `templates[]`, `selectedTemplate`, `isLoading`, `error`. Actions : `fetchTemplates()`, `createTemplate(data)`, `updateTemplate(id, data)`, `deleteTemplate(id)`, `duplicateTemplate(id)`, `saveProjectAsTemplate(projectId, name, description?)`, `instantiateTemplate(id, data)`. | FR-TPL |
| P13-FE-010  | Create `app/(dashboard)/settings/project-templates/page.tsx` — liste des templates : cards avec nom, nb de tâches, type de facturation. Bouton "+ Nouveau template". Message onboarding si vide. | FR-TPL |
| P13-FE-011  | Create `app/(dashboard)/settings/project-templates/new/page.tsx` — formulaire création template avec `ProjectTemplateTaskBuilder`. Submit → POST → redirect `/settings/project-templates/{id}`. | FR-TPL |
| P13-FE-012  | Create `app/(dashboard)/settings/project-templates/[id]/page.tsx` — détail template : nom, type facturation, taux horaire, liste des tâches, bouton Modifier, bouton Dupliquer, bouton Supprimer. | FR-TPL |
| P13-FE-013  | Create `components/project-templates/project-template-card.tsx` — card : nom, badge type facturation, nb de tâches, bouton "Utiliser ce template", kebab (Modifier, Dupliquer, Supprimer). | FR-TPL |
| P13-FE-014  | Create `components/project-templates/project-template-form.tsx` — formulaire réutilisable (new + edit). Champs : nom, description, billing_type (Select), default_hourly_rate (conditionnel), estimated_hours, currency. Inclut `ProjectTemplateTaskBuilder`. Zod schema exporté. | FR-TPL |
| P13-FE-015  | Create `components/project-templates/project-template-task-builder.tsx` — liste ordonnée avec drag-and-drop (`@dnd-kit/sortable`). Chaque tâche : titre (Input), description (Textarea optionnelle), estimated_hours (NumberInput optionnel), priority (Select). Bouton "+ Ajouter une tâche". Bouton corbeille par tâche. sort_order auto-calculé. | FR-TPL |
| P13-FE-016  | Create `components/project-templates/instantiate-template-dialog.tsx` — dialog d'instanciation : sélection client (Combobox), nom du projet (Input, pré-rempli avec le nom du template), start_date (DatePicker optionnel), deadline (DatePicker optionnel), prévisualisation de la liste des tâches. Submit → `instantiateTemplate()` → redirect `/projects/{newProjectId}`. | FR-TPL |
| P13-FE-017  | Create `components/project-templates/save-as-template-dialog.tsx` — dialog contextuelle accessible depuis la page projet (`/projects/[id]`). Champs : nom (Input, pré-rempli avec nom du projet), description (Textarea optionnelle). Submit → `saveProjectAsTemplate()` → toast de confirmation. | FR-TPL |
| P13-FE-018  | Extend formulaire "Nouveau Projet" (`app/(dashboard)/projects/create/page.tsx` ou composant dédié) — ajouter section "Utiliser un template" : Select des templates disponibles (optionnel). Si sélectionné, pré-remplir billing_type, hourly_rate, estimated_hours et ouvrir `InstantiateTemplateDialog`. | FR-TPL |
| P13-FE-019  | Extend page projet `app/(dashboard)/projects/[id]/page.tsx` — ajouter bouton "Sauvegarder comme template" dans le menu kebab / header du projet. Déclenche `SaveAsTemplateDialog`. | FR-TPL |
| P13-FE-020  | Extend sidebar navigation — ajouter entrée "Templates de projets" avec icône `LayoutTemplate` (lucide-react) dans le sous-menu Paramètres. | FR-TPL |

#### 5.3.3 Frontend Tests

| ID          | Test File | Status | Owner |
|-------------|-----------|--------|-------|
| P13-FT-001  | `tests/unit/stores/timer.test.ts` | todo | — |
| P13-FT-002  | `tests/components/timer/timer-badge.test.tsx` | todo | — |
| P13-FT-003  | `tests/components/timer/timer-dropdown.test.tsx` | todo | — |
| P13-FT-004  | `tests/components/timer/task-timer-button.test.tsx` | todo | — |
| P13-FT-005  | `tests/unit/stores/project-templates.test.ts` | todo | — |
| P13-FT-006  | `tests/components/project-templates/project-template-card.test.tsx` | todo | — |
| P13-FT-007  | `tests/components/project-templates/project-template-form.test.tsx` | todo | — |
| P13-FT-008  | `tests/components/project-templates/project-template-task-builder.test.tsx` | todo | — |
| P13-FT-009  | `tests/components/project-templates/instantiate-template-dialog.test.tsx` | todo | — |
| P13-FT-010  | `tests/e2e/timer/live-timer-flow.spec.ts` | todo | — |
| P13-FT-011  | `tests/e2e/templates/project-template-crud.spec.ts` | todo | — |
| P13-FT-012  | `tests/e2e/templates/project-template-instantiate.spec.ts` | todo | — |

#### 5.3.4 Scénarios E2E (Playwright)

| Scénario | Description |
|----------|-------------|
| `live-timer-flow.spec.ts` | Ouvrir un projet → ouvrir une tâche → démarrer le timer → vérifier le badge NavBar → naviguer vers le dashboard → arrêter le timer depuis le badge → vérifier que la TimeEntry apparaît dans la tâche |
| `project-template-crud.spec.ts` | Créer un template "Audit SEO" avec 3 tâches → modifier le délai d'une tâche → dupliquer le template → supprimer l'original |
| `project-template-instantiate.spec.ts` | Sélectionner le template depuis "Nouveau Projet" → renseigner client + dates → créer le projet → vérifier que les 3 tâches existent |

---

## 6. Exit Criteria

| Critère | Vérification |
|---------|-------------|
| Toutes les tâches `P13-BE-*` et `P13-FE-*` en statut `done` | `docs/dev/phase13.md` |
| Backend coverage >= 80% (`make test-be`) | CI green |
| Frontend coverage >= 80% (`pnpm vitest run --coverage`) | CI green |
| PHPStan level 8 (`./vendor/bin/phpstan analyse`) | 0 erreur |
| Pint (`./vendor/bin/pint --test`) | 0 erreur |
| ESLint + Prettier (`pnpm lint && pnpm format:check`) | 0 erreur |
| `tsc --noEmit` sans erreur | CI uniquement (pas de `pnpm build` local) |
| 3 scénarios E2E Playwright verts | `make test-e2e` |
| Timer live démarre/arrête correctement via NavBar | Test manuel |
| Un seul timer actif par utilisateur (test 409/auto-stop) | Test manuel |
| Template instantié crée un projet + tâches | Test manuel |
| "Save as template" depuis projet existant fonctionne | Test manuel |
| Widget "Temps suivi aujourd'hui" visible sur dashboard | Test manuel |
| Tag v1.9.0 poussé sur GitHub | `git tag v1.9.0` |

---

## 7. Récapitulatif

| Sprint    | Semaines | Livrable principal                                     | Tasks                    |
|-----------|----------|--------------------------------------------------------|--------------------------|
| Sprint 40 | 102–105  | Backend timer live — migration, model, service, API    | 1 INF + 7 BE + 2 tests   |
| Sprint 41 | 106–107  | Backend templates — migrations, models, service, API   | 2 INF + 10 BE + 4 tests  |
| Sprint 42 | 108–110  | Frontend — timer, templates, wizard, E2E               | 20 FE + 12 tests         |
| **Total** | **9 sem** | **v1.9.0**                                            | **~42 tâches + 18 tests** |
