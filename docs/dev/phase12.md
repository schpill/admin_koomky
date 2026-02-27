# Phase 12 — Task Tracking

> **Status**: done
> **Prerequisite**: Phase 11 fully merged and tagged `v1.7.0`
> **Spec**: [docs/phases/phase12.md](../phases/phase12.md)

---

## Sprint 37 — Backend Fondations (Weeks 90–93)

### Infrastructure & Database

| ID              | Task                                                                                                                                                                    | Status | Owner |
|-----------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P12-BE-INF-01   | Migration `create_reminder_sequences_table` — UUID PK, user_id FK CASCADE, name VARCHAR 255, description TEXT nullable, is_active BOOLEAN DEFAULT true, is_default BOOLEAN DEFAULT false, timestamps. Index `(user_id, is_default)`, `(user_id, is_active)`. | done | codex |
| P12-BE-INF-02   | Migration `create_reminder_steps_table` — UUID PK, sequence_id FK CASCADE, step_number INT, delay_days INT, subject VARCHAR 255, body TEXT, timestamps. Index `(sequence_id, step_number)`. UNIQUE `(sequence_id, step_number)`. | done | codex |
| P12-BE-INF-03   | Migration `create_invoice_reminder_schedules_table` — UUID PK, invoice_id FK CASCADE UNIQUE, sequence_id FK SET NULL nullable, user_id FK CASCADE, started_at TIMESTAMP, completed_at TIMESTAMP nullable, is_paused BOOLEAN DEFAULT false, next_reminder_step_id UUID nullable FK → reminder_steps SET NULL, timestamps. Index `(user_id, completed_at)`. | done | codex |
| P12-BE-INF-04   | Migration `create_reminder_deliveries_table` — UUID PK, invoice_id FK CASCADE, reminder_step_id FK CASCADE, user_id FK CASCADE, sent_at TIMESTAMP nullable, status ENUM(pending/sent/failed/skipped) DEFAULT pending, error_message TEXT nullable, timestamps. UNIQUE `(invoice_id, reminder_step_id)`. Index `invoice_id`. | done | codex |

### Modèles & Policies

| ID          | Task                                                                                                                                                                           | Status | Owner |
|-------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P12-BE-001  | Create `ReminderSequence` model — HasUuids, HasFactory. Fillable, casts (is_active/is_default booleans). Relations : user(), steps() HasMany orderedBy step_number, invoiceSchedules() HasMany. Scopes : active(), default(). | done | codex |
| P12-BE-002  | Create `ReminderStep` model — HasUuids, HasFactory. Fillable, casts (delay_days int). Relations : sequence(), deliveries(). Scope : ordered() (orderBy step_number). | done | codex |
| P12-BE-003  | Create `InvoiceReminderSchedule` model — HasUuids. Fillable, casts (started_at/completed_at datetime, is_paused boolean). Relations : invoice(), sequence(), user(), deliveries(), nextStep() BelongsTo→ReminderStep. Scopes : active() (completed_at IS NULL + is_paused false), pending(). | done | codex |
| P12-BE-004  | Create `ReminderDelivery` model — HasUuids. Fillable, casts (sent_at datetime). Relations : invoice(), step() BelongsTo→ReminderStep, user(). Scopes : sent(), failed(), skipped(). | done | codex |
| P12-BE-005  | Create `ReminderSequencePolicy` — ownership standard (user_id match). Méthodes : viewAny, view, create, update, delete. Enregistrement dans AuthServiceProvider. | done | codex |
| P12-BE-006  | Extend `InvoiceObserver::updated()` — (1) status→`overdue` : créer InvoiceReminderSchedule si séquence default active, started_at=invoice->due_date, next_reminder_step_id=premier step. Guard doublon. (2) status→`paid` : appeler ReminderDispatchService::completeSchedule(). (3) status→`cancelled` : idem. | done | codex |
| P12-BE-007  | Create `ReminderSequenceFactory` — faker pour tous les champs. États : active(), withSteps(int $count = 3). | done | codex |
| P12-BE-008  | Create `ReminderStepFactory` — faker avec delay_days croissants (3, 7, 14). | done | codex |

### Backend Tests (TDD)

| ID          | Test File                                                              | Status | Owner |
|-------------|------------------------------------------------------------------------|--------|-------|
| P12-BT-001  | `tests/Unit/Models/ReminderSequenceTest.php`                           | done | codex |
| P12-BT-002  | `tests/Feature/Reminders/ReminderObserverTest.php`                     | done | codex |

---

## Sprint 38 — Services & API (Weeks 94–97)

### Services & Commandes

| ID          | Task                                                                                                                                                                           | Status | Owner |
|-------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P12-BE-009  | Create `ReminderDispatchService` — méthodes : dispatchDue(): int (charge schedules actifs dont step est dû, dispatche SendReminderJob, retourne count) ; completeSchedule(Invoice): void ; advanceStep(InvoiceReminderSchedule): void (next_reminder_step_id → step suivant ou null + completed_at si dernier). | done | codex |
| P12-BE-010  | Create `SendReminderJob` — ShouldQueue, queue 'reminders', tries 3, backoff [60, 300, 900]. handle() : envoyer ReminderMail, créer ReminderDelivery(sent), advanceStep(), dispatcher webhook, logger activité. failed() : ReminderDelivery(failed, error_message). | done | codex |
| P12-BE-011  | Create `DispatchDueRemindersCommand` — signature `reminders:dispatch`. Appelle ReminderDispatchService::dispatchDue(). Output "X relances dispatchées." Enregistrer dans Console/Kernel.php : ->dailyAt('08:00'). | done | codex |
| P12-BE-012  | Create `ReminderMail` — ShouldQueue. Subject depuis ReminderStep. Body depuis ReminderStep avec interpolation variables (client_name, invoice_number, invoice_amount, due_date, days_overdue, pay_link). Template Blade `mail/reminder/invoice-reminder.blade.php`. | done | codex |
| P12-BE-013  | Create template Blade `resources/views/mail/reminder/invoice-reminder.blade.php` — HTML email professionnel, header, corps dynamique, bouton "Régler maintenant" conditionnel (si pay_link), footer légal. Inline styles. | done | codex |
| P12-BE-014  | Extend `DataExportService::exportAll()` — inclure ReminderDelivery dans export GDPR (fichier `reminder_deliveries.csv` : invoice_number, step_number, delay_days, sent_at, status). | done | codex |
| P12-BE-015  | Extend `WebhookDispatchService` — ajouter événement `invoice.reminder_sent` avec payload (invoice_id, invoice_number, client_id, step_number, delay_days, sent_at). | done | codex |
| P12-BE-016  | Extend `DashboardService` — ajouter méthode `overdueInvoicesWidget(User): array` → {count, total_amount, currency} des factures overdue avec schedule actif (non complété, non mis en pause). | done | codex |

### Controllers & Routes

| ID          | Task                                                                                                                                                                           | Status | Owner |
|-------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P12-BE-017  | Create `ReminderSequenceController` — méthodes : index, store, show, update, destroy, setDefault (POST — marque is_default=true, retire is_default des autres séquences du user). Utilise ReminderSequencePolicy. | done | codex |
| P12-BE-018  | Create `InvoiceReminderController` — méthodes : show (schedule + deliveries), attach (POST), pause (POST), resume (POST), skip (POST — avance step + ReminderDelivery skipped), cancel (DELETE — completed_at=now()). Guard ownership via invoice->user_id. | done | codex |
| P12-BE-019  | Create `StoreReminderSequenceRequest` — règles : name requis max 255, description max 1000 nullable, is_active boolean, is_default boolean, steps array requis min 1, steps.*.step_number int requis, steps.*.delay_days int requis min 1 max 365, steps.*.subject string requis max 255, steps.*.body string requis max 10000. Validation custom : step_numbers uniques dans le tableau. | done | codex |
| P12-BE-020  | Create `UpdateReminderSequenceRequest` — mêmes règles que StoreReminderSequenceRequest, tous les champs optionnels (sometimes). | done | codex |
| P12-BE-021  | Register routes dans `routes/api.php` : GET/POST /reminder-sequences, GET/PATCH/DELETE /reminder-sequences/{sequence}, POST /reminder-sequences/{sequence}/default, GET /invoices/{invoice}/reminder, POST /invoices/{invoice}/reminder/attach, POST /invoices/{invoice}/reminder/pause, POST /invoices/{invoice}/reminder/resume, POST /invoices/{invoice}/reminder/skip, DELETE /invoices/{invoice}/reminder. | done | codex |

### Backend Tests (TDD)

| ID          | Test File                                                                         | Status | Owner |
|-------------|-----------------------------------------------------------------------------------|--------|-------|
| P12-BT-003  | `tests/Unit/Services/ReminderDispatchServiceTest.php`                             | done | codex |
| P12-BT-004  | `tests/Unit/Jobs/SendReminderJobTest.php`                                         | done | codex |
| P12-BT-005  | `tests/Feature/Reminders/ReminderSequenceCrudTest.php`                            | done | codex |
| P12-BT-006  | `tests/Feature/Reminders/InvoiceReminderControllerTest.php`                       | done | codex |
| P12-BT-007  | `tests/Feature/Reminders/ReminderDispatchCommandTest.php`                         | done | codex |
| P12-BT-008  | `tests/Feature/Reminders/ReminderGdprTest.php`                                    | done | codex |

---

## Sprint 39 — Frontend (Weeks 98–101)

### Frontend Tasks

| ID          | Task                                                                                                                                                                           | Status | Owner |
|-------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P12-FE-001  | Create `stores/reminders.ts` Zustand store — State : sequences[], selectedSequence, invoiceReminder, isLoading, error. Actions : fetchSequences, createSequence, updateSequence, deleteSequence, setDefaultSequence, fetchInvoiceReminder, attachSequence, pauseReminder, resumeReminder, skipStep, cancelReminder. | done | codex |
| P12-FE-002  | Create `app/(dashboard)/settings/reminders/page.tsx` — liste des séquences avec ReminderSequenceCard, badge "Par défaut", toggle actif, bouton "+ Nouvelle séquence", message onboarding si vide. | done | codex |
| P12-FE-003  | Create `app/(dashboard)/settings/reminders/new/page.tsx` — formulaire création avec step builder. Submit → POST → redirect /settings/reminders/{id}. | done | codex |
| P12-FE-004  | Create `app/(dashboard)/settings/reminders/[id]/page.tsx` — détail séquence : nom, statut, badge défaut, liste steps, bouton Modifier, aperçu email (EmailPreviewModal). | done | codex |
| P12-FE-005  | Create `components/reminders/reminder-sequence-form.tsx` — formulaire réutilisable (new + edit). Props : defaultValues?, onSubmit(data), isLoading. Inclut ReminderStepBuilder. Zod schema exporté. | done | codex |
| P12-FE-006  | Create `components/reminders/reminder-sequence-card.tsx` — card : nom, badge "Par défaut", Switch is_active, nb étapes, kebab menu (Modifier, Définir par défaut, Supprimer). Style inactif si is_active=false. | done | codex |
| P12-FE-007  | Create `components/reminders/reminder-step-builder.tsx` — liste ordonnée avec @dnd-kit/sortable. Chaque étape : delay_days NumberInput, subject Input, body Textarea. Bouton "+ Ajouter étape" (max 10). Bouton corbeille par étape. step_number auto-calculé. | done | codex |
| P12-FE-008  | Create `components/reminders/invoice-reminder-panel.tsx` — si pas de schedule : bouton "Attacher une séquence" + Select. Si schedule actif : séquence attachée, ReminderTimeline, boutons Pause/Reprendre/Sauter étape/Détacher. | done | codex |
| P12-FE-009  | Create `components/reminders/reminder-timeline.tsx` — timeline verticale. Nœuds : icône statut (pending=horloge/sent=check/failed=croix/skipped=skip), label "J+{delay_days}", subject, date si sent. Couleurs par statut. | done | codex |
| P12-FE-010  | Create `components/reminders/email-preview-modal.tsx` — modal avec rendu HTML interpolé (variables remplacées par exemples : "Jean Dupont", "FAC-2026-0042", etc.). Bouton "Fermer". | done | codex |
| P12-FE-011  | Extend `app/(dashboard)/invoices/[id]/page.tsx` — ajouter onglet "Relances" (Tabs shadcn) avec InvoiceReminderPanel. Chargement indépendant. | done | codex |
| P12-FE-012  | Extend sidebar navigation — ajouter entrée "Relances" avec icône Bell (lucide-react) dans le sous-menu Paramètres. | done | codex |
| P12-FE-013  | Extend `app/(dashboard)/page.tsx` — widget "Factures en retard" : count + montant total, lien /invoices?filter=overdue. Masqué si 0. Chargement indépendant. | done | codex |

### Frontend Tests

| ID          | Test File                                                                  | Status | Owner |
|-------------|----------------------------------------------------------------------------|--------|-------|
| P12-FT-001  | `tests/unit/stores/reminders.test.ts`                                      | done | codex |
| P12-FT-002  | `tests/components/reminders/reminder-sequence-card.test.tsx`               | done | codex |
| P12-FT-003  | `tests/components/reminders/reminder-sequence-form.test.tsx`               | done | codex |
| P12-FT-004  | `tests/components/reminders/reminder-step-builder.test.tsx`                | done | codex |
| P12-FT-005  | `tests/components/reminders/invoice-reminder-panel.test.tsx`               | done | codex |
| P12-FT-006  | `tests/components/reminders/reminder-timeline.test.tsx`                    | done | codex |
| P12-FT-007  | `tests/components/reminders/email-preview-modal.test.tsx`                  | done | codex |
| P12-FT-008  | `tests/e2e/reminders/reminder-sequence-crud.spec.ts`                       | done | codex |
| P12-FT-009  | `tests/e2e/reminders/invoice-reminder-flow.spec.ts`                        | done | codex |

---

## Récapitulatif

| Sprint    | Semaines | Livrable principal                                     | Tasks                    |
|-----------|----------|--------------------------------------------------------|--------------------------|
| Sprint 37 | 90–93    | Backend fondations — migrations, modèles, observers    | 4 INF + 8 BE + 2 tests   |
| Sprint 38 | 94–97    | Services & API — dispatch, job, mail, controller       | 13 BE + 6 tests          |
| Sprint 39 | 98–101   | Frontend — settings, onglet facture, wizard, E2E       | 13 FE + 9 tests          |
| **Total** | **12 sem** | **v1.8.0**                                           | **~38 tâches + 17 tests** |
