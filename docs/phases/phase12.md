# Phase 12 — Séquences de Relance Automatiques (v1.8)

| Field               | Value                                                        |
|---------------------|--------------------------------------------------------------|
| **Phase**           | 12                                                           |
| **Name**            | Séquences de Relance Automatiques — Factures Impayées        |
| **Duration**        | Weeks 90–101 (12 weeks)                                      |
| **Milestone**       | M12 — v1.8.0 Release                                        |
| **PRD Sections**    | §4.17 FR-REM (nouveau)                                       |
| **Prerequisite**    | Phase 11 fully completed and tagged `v1.7.0`                 |
| **Status**          | Planned                                                      |

---

## 1. Phase Objectives

| ID        | Objective                                                                                                                                  |
|-----------|--------------------------------------------------------------------------------------------------------------------------------------------|
| P12-OBJ-1 | Permettre la création de séquences de relance configurables (délai en jours, sujet, corps) déclenchées automatiquement sur les factures impayées |
| P12-OBJ-2 | Déclencher automatiquement une séquence quand une facture passe en statut `overdue` (via InvoiceObserver existant)                         |
| P12-OBJ-3 | Annuler automatiquement les relances quand la facture passe en `paid` ou `cancelled`                                                       |
| P12-OBJ-4 | Permettre la gestion manuelle par facture : pause, reprise, saut d'une étape, détachement de la séquence                                  |
| P12-OBJ-5 | Logger chaque relance envoyée (statut, timestamp, erreur éventuelle) et dispatcher le webhook `invoice.reminder_sent`                      |
| P12-OBJ-6 | Exposer un widget dashboard "Factures en retard" (nb + montant total des factures overdue avec relances en attente)                        |
| P12-OBJ-7 | Maintenir une couverture de tests >= 80% backend et frontend                                                                               |

---

## 2. Choix techniques

### 2.1 Infrastructure existante réutilisée

Aucune nouvelle infrastructure requise :

| Besoin | Brique existante | Phase d'origine |
|--------|-----------------|--------------------|
| Détection facture overdue | `InvoiceObserver::updated()` | Phase 1 |
| Envoi email | Laravel Mail + Queue Horizon | Phase 1 |
| Webhooks | `WebhookDispatchService` | Phase 7 |
| Export GDPR | `DataExportService` | Phase 4 |
| Scheduling commande | `Console/Kernel.php` (Artisan scheduler) | Phase 5 |
| Portail client (lien paiement) | `PortalAccessToken` | Phase 6 |
| Activités client | `ActivityService` | Phase 1 |
| Auth + Policy | Sanctum + standard ownership | Phase 1 |

### 2.2 Modèle de données

Quatre migrations sont nécessaires :

| Migration | Description |
|-----------|-------------|
| `create_reminder_sequences_table` | Séquence utilisateur — UUID PK, user_id FK, name, description, is_active, is_default (un seul par user), timestamps |
| `create_reminder_steps_table` | Étapes d'une séquence — UUID PK, sequence_id FK, step_number INT (ordre), delay_days INT (jours après date d'échéance), subject VARCHAR 255, body TEXT, timestamps. Index `(sequence_id, step_number)`. UNIQUE `(sequence_id, step_number)`. |
| `create_invoice_reminder_schedules_table` | Association facture ↔ séquence active — UUID PK, invoice_id FK UNIQUE, sequence_id FK, user_id FK, started_at TIMESTAMP (= due_date de la facture), completed_at TIMESTAMP nullable, is_paused BOOLEAN DEFAULT false, next_reminder_step_id UUID nullable FK → reminder_steps SET NULL. Index `(user_id, completed_at)`. |
| `create_reminder_deliveries_table` | Log des envois — UUID PK, invoice_id FK, reminder_step_id FK, user_id FK, sent_at TIMESTAMP, status ENUM(pending/sent/failed/skipped), error_message TEXT nullable, timestamps. UNIQUE `(invoice_id, reminder_step_id)`. Index `(invoice_id)`. |

### 2.3 Logique de dispatch

Le `ReminderDispatchService` s'exécute via une commande Artisan planifiée quotidiennement à 08:00.

Pour chaque `InvoiceReminderSchedule` actif (non complété, non mis en pause) :

```
1. Charger l'étape courante (next_reminder_step_id)
2. Vérifier : started_at + step.delay_days <= today
3. Si oui :
   a. Envoyer ReminderMail (via queue 'reminders')
   b. Créer ReminderDelivery (status: sent)
   c. Avancer next_reminder_step_id → étape suivante (ou null si dernière)
   d. Si null : marquer schedule.completed_at = now()
   e. Dispatcher webhook 'invoice.reminder_sent'
   f. Logger activité client
4. Si envoi échoue (exception) :
   a. ReminderDelivery (status: failed, error_message)
   b. Pas d'incrément de l'étape (retry possible)
```

### 2.4 Déclencheurs automatiques

| Événement | Observer | Action |
|-----------|----------|--------|
| `Invoice` passe en `overdue` | `InvoiceObserver::updated()` | Créer `InvoiceReminderSchedule` si l'utilisateur a une séquence `is_default = true` et `is_active = true`. `started_at = invoice.due_date`. Guard : ne pas créer si un schedule existe déjà. |
| `Invoice` passe en `paid` | `InvoiceObserver::updated()` | Marquer l'`InvoiceReminderSchedule` lié comme complété (`completed_at = now()`). |
| `Invoice` passe en `cancelled` | `InvoiceObserver::updated()` | Idem `paid` — annuler le schedule. |

### 2.5 Modèle de séquence par défaut

Un utilisateur peut définir une séquence `is_default = true`. Cette séquence est automatiquement attachée à toute nouvelle facture passant en `overdue`. Une seule séquence peut être `is_default` par utilisateur (enforced par un unique constraint conditionnel ou via le service).

Exemple de séquence standard :

| Étape | Délai (jours après échéance) | Objet suggéré |
|-------|------------------------------|---------------|
| 1 | 3 | Rappel amical — Facture {invoice_number} |
| 2 | 7 | Relance — Facture {invoice_number} en attente |
| 3 | 14 | Dernier rappel — Facture {invoice_number} |

### 2.6 Variables de template

Les corps d'email supportent des variables interpolées côté `ReminderMail` :

| Variable | Valeur |
|----------|--------|
| `{{client_name}}` | Nom du client |
| `{{invoice_number}}` | Numéro de facture |
| `{{invoice_amount}}` | Montant TTC formaté |
| `{{due_date}}` | Date d'échéance |
| `{{days_overdue}}` | Nombre de jours de retard |
| `{{pay_link}}` | Lien paiement portail (si portail actif) |

---

## 3. Entry Criteria

- Phase 11 exit criteria 100% satisfaits.
- Tous les checks CI Phase 11 verts sur `main`.
- v1.7.0 tagué et déployé en production.
- `InvoiceObserver` existant stable et couvert par les tests.
- Laravel Horizon opérationnel (queue `reminders` à créer).
- Scheduler Artisan opérationnel en production (cron `* * * * * php artisan schedule:run`).

---

## 4. Scope — Requirement Traceability

| Feature                                                                                      | Priority | Included |
|----------------------------------------------------------------------------------------------|----------|----------|
| CRUD séquences de relance (create, read, update, delete)                                     | High     | Yes      |
| Étapes configurables par séquence (step_number, delay_days, subject, body)                  | High     | Yes      |
| Séquence par défaut (auto-attachée aux nouvelles factures overdue)                           | High     | Yes      |
| Déclenchement automatique sur `invoice.overdue` via InvoiceObserver                         | High     | Yes      |
| Annulation automatique sur `invoice.paid` et `invoice.cancelled`                             | High     | Yes      |
| Commande Artisan `reminders:dispatch` planifiée quotidiennement à 08:00                     | High     | Yes      |
| ReminderMail avec variables d'interpolation (client_name, invoice_number, pay_link, etc.)   | High     | Yes      |
| Log ReminderDelivery par étape (status sent/failed/skipped, error_message)                  | High     | Yes      |
| Queue dédiée `reminders` (isolation des envois)                                              | High     | Yes      |
| Pause / reprise d'une séquence sur une facture spécifique                                   | High     | Yes      |
| Saut d'une étape (skip)                                                                      | Medium   | Yes      |
| Détachement manuel d'une séquence depuis une facture                                         | Medium   | Yes      |
| Attachement manuel d'une séquence à une facture overdue (hors auto)                         | Medium   | Yes      |
| Webhook `invoice.reminder_sent` avec payload complet                                         | Medium   | Yes      |
| Inclusion des ReminderDelivery dans l'export GDPR                                            | High     | Yes      |
| Widget dashboard "Factures en retard" (count + montant total)                               | Medium   | Yes      |
| Page settings `/settings/reminders` — gestion des séquences                                 | High     | Yes      |
| Onglet "Relances" sur la page `/invoices/[id]` avec timeline visuelle                       | High     | Yes      |
| Preview du corps d'email avec variables interpolées (mode aperçu)                            | Medium   | Yes      |
| ActivityService log pour chaque relance envoyée sur le client lié                           | Low      | Yes      |
| SMS / WhatsApp reminders                                                                      | Low      | No       |
| A/B testing des templates de relance                                                          | Low      | No       |
| Relances pour devis expirés (Quotes)                                                          | Low      | No       |
| Portail client : suivi des relances reçues                                                    | Low      | No       |
| Intégration CRM externe (Pipedrive, HubSpot)                                                 | Low      | No       |

---

## 5. Detailed Sprint Breakdown

### 5.1 Sprint 37 — Backend Fondations (Weeks 90–93)

#### 5.1.1 Infrastructure & Database

| Migration / Config | Description |
|--------------------|-------------|
| `create_reminder_sequences_table` | id (UUID PK), user_id (FK → users CASCADE), name (VARCHAR 255), description (TEXT nullable), is_active (BOOLEAN DEFAULT true), is_default (BOOLEAN DEFAULT false), timestamps. Index : `(user_id, is_default)`, `(user_id, is_active)`. |
| `create_reminder_steps_table` | id (UUID PK), sequence_id (FK → reminder_sequences CASCADE), step_number (INT), delay_days (INT), subject (VARCHAR 255), body (TEXT), timestamps. Index : `(sequence_id, step_number)`. UNIQUE : `(sequence_id, step_number)`. |
| `create_invoice_reminder_schedules_table` | id (UUID PK), invoice_id (FK → invoices CASCADE UNIQUE), sequence_id (FK → reminder_sequences SET NULL nullable), user_id (FK → users CASCADE), started_at (TIMESTAMP), completed_at (TIMESTAMP nullable), is_paused (BOOLEAN DEFAULT false), next_reminder_step_id (UUID nullable FK → reminder_steps SET NULL), timestamps. Index : `(user_id, completed_at)`. |
| `create_reminder_deliveries_table` | id (UUID PK), invoice_id (FK → invoices CASCADE), reminder_step_id (FK → reminder_steps CASCADE), user_id (FK → users CASCADE), sent_at (TIMESTAMP nullable), status (ENUM pending/sent/failed/skipped DEFAULT pending), error_message (TEXT nullable), timestamps. UNIQUE : `(invoice_id, reminder_step_id)`. Index : `invoice_id`. |

#### 5.1.2 Backend — Modèles & Policies

| ID          | Task | PRD Ref |
|-------------|------|---------|
| P12-BE-001  | Create `ReminderSequence` model — Traits : `HasUuids`, `HasFactory`. Fillable complet. Casts : `is_active` → boolean, `is_default` → boolean. Relations : `user()`, `steps()` (HasMany, orderedBy step_number), `invoiceSchedules()` (HasMany). Scopes : `active()`, `default()`. | FR-REM-001 |
| P12-BE-002  | Create `ReminderStep` model — Traits : `HasUuids`, `HasFactory`. Fillable. Casts : delay_days → int. Relations : `sequence()`, `deliveries()`. Scope : `ordered()` (orderBy step_number). | FR-REM-001 |
| P12-BE-003  | Create `InvoiceReminderSchedule` model — Traits : `HasUuids`. Fillable. Casts : `started_at` datetime, `completed_at` datetime, `is_paused` boolean. Relations : `invoice()`, `sequence()` (withTrashed si nécessaire), `user()`, `deliveries()`, `nextStep()` (BelongsTo → ReminderStep). Scopes : `active()` (completed_at IS NULL, is_paused false), `pending()`. | FR-REM-002 |
| P12-BE-004  | Create `ReminderDelivery` model — Traits : `HasUuids`. Fillable. Casts : `sent_at` datetime, `status` string. Relations : `invoice()`, `step()` (BelongsTo → ReminderStep), `user()`. Scopes : `sent()`, `failed()`, `skipped()`. | FR-REM-002 |
| P12-BE-005  | Create `ReminderSequencePolicy` — ownership standard (`user_id` match). Méthodes : `viewAny`, `view`, `create`, `update`, `delete`. Enregistrement dans `AuthServiceProvider`. | FR-REM-001 |
| P12-BE-006  | Extend `InvoiceObserver::updated()` — trois cas : (1) status → `overdue` : créer `InvoiceReminderSchedule` si séquence default active, `started_at = invoice->due_date`, `next_reminder_step_id` = premier step de la séquence. Guard : skip si schedule existe déjà. (2) status → `paid` : appeler `ReminderDispatchService::completeSchedule($invoice)`. (3) status → `cancelled` : idem. | FR-REM-002 |
| P12-BE-007  | Create `ReminderSequenceFactory` — faker pour tous les champs. États : `active()`, `withSteps(int $count = 3)`. | FR-REM-001 |
| P12-BE-008  | Create `ReminderStepFactory` — faker. Génération de delay_days croissants (3, 7, 14). | FR-REM-001 |

#### 5.1.3 Backend Tests — Modèles & Observers (TDD)

| Test File | Test Cases |
|-----------|-----------|
| `tests/Unit/Models/ReminderSequenceTest.php` | Relations correctes, scopes active/default, fillable, casts |
| `tests/Feature/Reminders/ReminderObserverTest.php` | InvoiceObserver crée schedule sur `overdue` (séquence default active), pas de double schedule, InvoiceObserver complète schedule sur `paid`, InvoiceObserver complète schedule sur `cancelled`, pas d'action si pas de séquence default, ownership isolé |

---

### 5.2 Sprint 38 — Services & API (Weeks 94–97)

#### 5.2.1 Backend — Services & Commandes

| ID          | Task | PRD Ref |
|-------------|------|---------|
| P12-BE-009  | Create `ReminderDispatchService` — méthodes : `dispatchDue(): int` (charge tous les schedules actifs dont `started_at + next_step.delay_days <= today`, pour chacun dispatch `SendReminderJob` en queue `reminders`, retourne le nombre de jobs dispatchés) ; `completeSchedule(Invoice $invoice): void` (mark schedule completed_at = now()) ; `advanceStep(InvoiceReminderSchedule $schedule): void` (next_reminder_step_id → step suivant OU null + completed_at si dernier). | FR-REM-002 |
| P12-BE-010  | Create `SendReminderJob` — `implements ShouldQueue`, queue `reminders`, tries 3, backoff [60, 300, 900]. Inject `ReminderDispatchService`. Méthode `handle()` : charger schedule + step, envoyer `ReminderMail` via `Mail::to($invoice->client->email)->queue()`, créer `ReminderDelivery` (status `sent`), appeler `advanceStep()`, dispatcher webhook `invoice.reminder_sent`, logger activité. `failed()` : créer `ReminderDelivery` (status `failed`, error_message). | FR-REM-002 |
| P12-BE-011  | Create `DispatchDueRemindersCommand` — signature `reminders:dispatch`. Appelle `ReminderDispatchService::dispatchDue()`. Output : `"X relances dispatchées."`. Enregistrer dans `Console/Kernel.php` : `->dailyAt('08:00')`. | FR-REM-002 |
| P12-BE-012  | Create `ReminderMail` — `implements ShouldQueue`. Subject depuis `ReminderStep::subject`. Body depuis `ReminderStep::body`. Interpolation des variables `{{client_name}}`, `{{invoice_number}}`, `{{invoice_amount}}`, `{{due_date}}`, `{{days_overdue}}`, `{{pay_link}}` (via `str_replace` sur un tableau). Template Blade `resources/views/mail/reminder/invoice-reminder.blade.php`. | FR-REM-003 |
| P12-BE-013  | Create template Blade `resources/views/mail/reminder/invoice-reminder.blade.php` — HTML email professionnel avec header, corps (variable `{{{ $body }}}`), bouton "Régler maintenant" (conditionnel si `$payLink`), footer légal. Inline styles pour compatibilité email. | FR-REM-003 |
| P12-BE-014  | Extend `DataExportService::exportAll()` — inclure les `ReminderDelivery` dans le ZIP GDPR (fichier `reminder_deliveries.csv` : invoice_number, step_number, delay_days, sent_at, status). | FR-REM-004 |
| P12-BE-015  | Extend `WebhookDispatchService` — ajouter événement `invoice.reminder_sent` avec payload `{invoice_id, invoice_number, client_id, step_number, delay_days, sent_at}`. | FR-REM-004 |
| P12-BE-016  | Extend `DashboardService` — ajouter méthode `overdueInvoicesWidget(User $user): array` → `{count, total_amount, currency}` des factures overdue avec un schedule actif (non completed, non paused). | FR-REM-005 |

#### 5.2.2 Backend — Controllers & Routes

| ID          | Task | PRD Ref |
|-------------|------|---------|
| P12-BE-017  | Create `ReminderSequenceController` — méthodes : `index` (toutes les séquences de l'user), `store`, `show`, `update`, `destroy`, `setDefault` (POST — marque is_default=true, retire is_default des autres). Utilise `ReminderSequencePolicy`. | FR-REM-001 |
| P12-BE-018  | Create `InvoiceReminderController` — méthodes : `show` (schedule actuel + deliveries pour une facture), `attach` (POST — attacher manuellement une séquence : segment_id validé + owned), `pause` (POST — is_paused=true), `resume` (POST — is_paused=false), `skip` (POST — avance next_reminder_step_id sans envoi, crée ReminderDelivery status skipped), `cancel` (DELETE — completed_at=now()). Guard ownership via `invoice->user_id`. | FR-REM-002 |
| P12-BE-019  | Create `StoreReminderSequenceRequest` — règles : `name` requis max 255, `description` max 1000 nullable, `is_active` boolean, `is_default` boolean, `steps` array requis min 1 item, `steps.*.step_number` int required, `steps.*.delay_days` int required min 1 max 365, `steps.*.subject` string required max 255, `steps.*.body` string required max 10000. Validation : step_numbers uniques dans le tableau. | FR-REM-001 |
| P12-BE-020  | Create `UpdateReminderSequenceRequest` — mêmes règles, tous les champs optionnels (`sometimes`). | FR-REM-001 |
| P12-BE-021  | Register routes dans `routes/api.php` (groupe `v1` authentifié) :<br>`GET /reminder-sequences`, `POST /reminder-sequences`<br>`GET /reminder-sequences/{sequence}`, `PATCH /reminder-sequences/{sequence}`, `DELETE /reminder-sequences/{sequence}`<br>`POST /reminder-sequences/{sequence}/default`<br>`GET /invoices/{invoice}/reminder`, `POST /invoices/{invoice}/reminder/attach`<br>`POST /invoices/{invoice}/reminder/pause`, `POST /invoices/{invoice}/reminder/resume`<br>`POST /invoices/{invoice}/reminder/skip`, `DELETE /invoices/{invoice}/reminder` | FR-REM-001 |

#### 5.2.3 Backend Tests — Services & API (TDD)

| Test File | Test Cases |
|-----------|-----------|
| `tests/Unit/Services/ReminderDispatchServiceTest.php` | `dispatchDue()` dispatche les jobs pour les steps dus, ignore les schedules mis en pause, ignore les schedules complétés, ignore les steps non encore dus (delay_days non atteint), `completeSchedule()` marque completed_at, `advanceStep()` avance correctement, avance à null sur dernier step |
| `tests/Unit/Jobs/SendReminderJobTest.php` | `handle()` crée ReminderDelivery status sent, `handle()` envoie ReminderMail (mock), `handle()` appelle advanceStep, `failed()` crée ReminderDelivery status failed |
| `tests/Feature/Reminders/ReminderSequenceCrudTest.php` | index 200 (liste user), store 201 (sequence + steps créés), show 200, update 200 (steps recréés), destroy 204, setDefault 200 (retire is_default des autres), ownership 403 |
| `tests/Feature/Reminders/InvoiceReminderControllerTest.php` | show 200 (schedule + deliveries), attach 200 (séquence manually attachée), pause 200 (is_paused=true), resume 200 (is_paused=false), skip 200 (ReminderDelivery skipped + step avancé), cancel 204 (completed_at set), ownership 403 |
| `tests/Feature/Reminders/ReminderDispatchCommandTest.php` | Artisan `reminders:dispatch` dispatche les jobs, output correct, steps non dus ignorés |
| `tests/Feature/Reminders/ReminderGdprTest.php` | Export GDPR contient `reminder_deliveries.csv`, isolé par user, champs corrects |

---

### 5.3 Sprint 39 — Frontend (Weeks 98–101)

#### 5.3.1 Front-end Tasks

| ID          | Task | PRD Ref |
|-------------|------|---------|
| P12-FE-001  | Create `stores/reminders.ts` Zustand store :<br>— State : `sequences[]`, `selectedSequence`, `invoiceReminder` (schedule courant), `isLoading`, `error`<br>— Actions : `fetchSequences()`, `createSequence(data)`, `updateSequence(id, data)`, `deleteSequence(id)`, `setDefaultSequence(id)`, `fetchInvoiceReminder(invoiceId)`, `attachSequence(invoiceId, sequenceId)`, `pauseReminder(invoiceId)`, `resumeReminder(invoiceId)`, `skipStep(invoiceId)`, `cancelReminder(invoiceId)` | FR-REM-001 |
| P12-FE-002  | Create `app/(dashboard)/settings/reminders/page.tsx` — liste des séquences : cards avec badge "Par défaut", toggle actif/inactif, menu kebab (Modifier, Supprimer, Définir par défaut). Bouton "+ Nouvelle séquence". Message si aucune séquence (onboarding). | FR-REM-001 |
| P12-FE-003  | Create `app/(dashboard)/settings/reminders/new/page.tsx` — formulaire création séquence avec step builder. Submit → POST → redirect `/settings/reminders/{id}`. | FR-REM-001 |
| P12-FE-004  | Create `app/(dashboard)/settings/reminders/[id]/page.tsx` — détail séquence : en-tête (nom, statut, badge défaut), liste des étapes, bouton Modifier, aperçu email (modal). | FR-REM-001 |
| P12-FE-005  | Create `components/reminders/reminder-sequence-form.tsx` — formulaire réutilisable (new + edit). Props : `defaultValues?`, `onSubmit(data)`, `isLoading`. Inclut `ReminderStepBuilder`. Zod schema exporté. | FR-REM-001 |
| P12-FE-006  | Create `components/reminders/reminder-sequence-card.tsx` — card shadcn/ui : nom, badge "Par défaut", toggle actif (Switch), nb d'étapes, menu kebab (Modifier, Définir par défaut, Supprimer). Style archivé si is_active=false. | FR-REM-001 |
| P12-FE-007  | Create `components/reminders/reminder-step-builder.tsx` — liste d'étapes ordonnées avec drag-and-drop (`@dnd-kit/sortable`). Chaque étape : step_number (auto), delay_days (NumberInput), subject (Input), body (Textarea). Bouton "+ Ajouter une étape" (max 10). Bouton corbeille par étape. | FR-REM-001 |
| P12-FE-008  | Create `components/reminders/invoice-reminder-panel.tsx` — panel affiché dans l'onglet "Relances" d'une facture. Si pas de schedule : bouton "Attacher une séquence" + Select séquences. Si schedule actif : séquence attachée, timeline des étapes, boutons Pause/Reprendre, Sauter l'étape, Détacher. | FR-REM-002 |
| P12-FE-009  | Create `components/reminders/reminder-timeline.tsx` — timeline verticale des étapes (shadcn/ui ou custom). Chaque nœud : icône statut (horloge/check/croix/skip), label "J+{delay_days}", subject, date d'envoi si sent. Couleur selon statut (pending=gris, sent=vert, failed=rouge, skipped=orange). | FR-REM-002 |
| P12-FE-010  | Create `components/reminders/email-preview-modal.tsx` — modal qui affiche le rendu HTML de l'email d'une étape avec les variables interpolées avec des valeurs d'exemple (ex: `{{client_name}}` → "Jean Dupont"). | FR-REM-003 |
| P12-FE-011  | Extend `app/(dashboard)/invoices/[id]/page.tsx` — ajouter onglet "Relances" (Tabs shadcn/ui) contenant `InvoiceReminderPanel`. Chargement indépendant, pas de blocage des autres onglets. | FR-REM-002 |
| P12-FE-012  | Extend sidebar navigation — ajouter entrée "Relances" avec icône `Bell` (lucide-react) dans le sous-menu Paramètres, entre les entrées existantes. | FR-REM-001 |
| P12-FE-013  | Extend `app/(dashboard)/page.tsx` — ajouter widget "Factures en retard" : count + montant total des factures overdue avec relances en attente. Lien vers `/invoices?filter=overdue`. Masqué si 0 facture overdue. Chargement indépendant. | FR-REM-005 |

#### 5.3.2 Front-end Tests

| ID          | Test File | Status | Owner |
|-------------|-----------|--------|-------|
| P12-FT-001  | `tests/unit/stores/reminders.test.ts` | todo | — |
| P12-FT-002  | `tests/components/reminders/reminder-sequence-card.test.tsx` | todo | — |
| P12-FT-003  | `tests/components/reminders/reminder-sequence-form.test.tsx` | todo | — |
| P12-FT-004  | `tests/components/reminders/reminder-step-builder.test.tsx` | todo | — |
| P12-FT-005  | `tests/components/reminders/invoice-reminder-panel.test.tsx` | todo | — |
| P12-FT-006  | `tests/components/reminders/reminder-timeline.test.tsx` | todo | — |
| P12-FT-007  | `tests/components/reminders/email-preview-modal.test.tsx` | todo | — |
| P12-FT-008  | `tests/e2e/reminders/reminder-sequence-crud.spec.ts` | todo | — |
| P12-FT-009  | `tests/e2e/reminders/invoice-reminder-flow.spec.ts` | todo | — |

#### 5.3.3 Scénarios E2E (Playwright)

| Scénario | Description |
|----------|-------------|
| `reminder-sequence-crud.spec.ts` | Créer une séquence "Relance standard" avec 3 étapes → la définir par défaut → la modifier (changer le délai de l'étape 2) → la supprimer |
| `invoice-reminder-flow.spec.ts` | Créer une facture → passer en overdue → vérifier que le schedule est attaché automatiquement → ouvrir l'onglet Relances → mettre en pause → reprendre → sauter l'étape 1 → vérifier ReminderDelivery skipped |

---

## 6. Exit Criteria

| Critère | Vérification |
|---------|-------------|
| Toutes les tâches `P12-BE-*` et `P12-FE-*` en statut `done` | `docs/dev/phase12.md` |
| Backend coverage >= 80% (`make test-be`) | CI green |
| Frontend coverage >= 80% (`pnpm vitest run --coverage`) | CI green |
| PHPStan level 8 (`./vendor/bin/phpstan analyse`) | 0 erreur |
| Pint (`./vendor/bin/pint --test`) | 0 erreur |
| ESLint + Prettier (`pnpm lint && pnpm format:check`) | 0 erreur |
| `tsc --noEmit` sans erreur | CI uniquement (pas de `pnpm build` local) |
| 2 scénarios E2E Playwright verts | `make test-e2e` |
| Commande `php artisan reminders:dispatch` opérationnelle | Test manuel |
| Webhook `invoice.reminder_sent` dispatché | Test manuel |
| Export GDPR contient `reminder_deliveries.csv` | Test manuel |
| Dashboard widget "Factures en retard" visible | Test manuel |
| Tag v1.8.0 poussé sur GitHub | `git tag v1.8.0` |

---

## 7. Récapitulatif

| Sprint    | Semaines | Livrable principal                                     | Tasks                    |
|-----------|----------|--------------------------------------------------------|--------------------------|
| Sprint 37 | 90–93    | Backend fondations — migrations, modèles, observers    | 4 INF + 8 BE + 2 tests   |
| Sprint 38 | 94–97    | Services & API — dispatch, job, mail, controller       | 13 BE + 6 tests          |
| Sprint 39 | 98–101   | Frontend — settings, onglet facture, wizard, E2E       | 13 FE + 9 tests          |
| **Total** | **12 sem** | **v1.8.0**                                           | **~38 tâches + 17 tests** |
