# Phase 9 — Support Ticket System (v1.5)

| Field               | Value                                              |
|---------------------|----------------------------------------------------|
| **Phase**           | 9                                                  |
| **Name**            | Système de tickets support client                  |
| **Duration**        | Weeks 58–65 (8 weeks)                              |
| **Milestone**       | M9 — v1.5.0 Release                               |
| **PRD Sections**    | §4.13 FR-TKT                                       |
| **Prerequisite**    | Phase 8 fully completed and validated              |
| **Status**          | Planned                                            |

---

## 1. Phase Objectives

| ID       | Objective                                                                                                          |
|----------|--------------------------------------------------------------------------------------------------------------------|
| P9-OBJ-1 | Permettre la création et la gestion complète de tickets de support client                                         |
| P9-OBJ-2 | Lier optionnellement un ticket à un client et/ou à un projet (filtré par client)                                  |
| P9-OBJ-3 | Gérer le cycle de vie du ticket : statut, priorité, assignation, deadline                                          |
| P9-OBJ-4 | Permettre l'ajout de messages (thread) sur chaque ticket, avec distinction note interne / message public          |
| P9-OBJ-5 | Réutiliser le module GED (Phase 8) pour les pièces jointes des tickets (upload, détection MIME, téléchargement)   |
| P9-OBJ-6 | Envoyer des notifications email aux acteurs concernés (assigné, propriétaire)                                     |
| P9-OBJ-7 | Fournir des stats et un widget dashboard (tickets ouverts, par priorité, délai de résolution)                     |
| P9-OBJ-8 | Maintenir une couverture de tests >= 80% backend et frontend                                                      |

---

## 2. Entry Criteria

- Phase 8 exit criteria 100% satisfaits.
- Tous les checks CI Phase 8 verts sur `main`.
- v1.4.0 tagué et déployé en production.
- Module GED stable (DocumentStorageService, DocumentTypeDetectorService) disponible pour réutilisation.

---

## 3. Scope — Requirement Traceability

| Feature                                                               | Priority | Included |
|-----------------------------------------------------------------------|----------|----------|
| Création de ticket (titre, description, client optionnel, projet optionnel) | High  | Yes      |
| Liaison client optionnelle                                            | High     | Yes      |
| Liaison projet optionnelle, filtrée par client (affichage "Divers" si aucun client) | High | Yes |
| Propriétaire du ticket (user_id)                                      | High     | Yes      |
| Personne assignée (assigned_to, défaut = propriétaire)               | High     | Yes      |
| Statuts : open → in_progress → pending → resolved → closed            | High     | Yes      |
| Priorités : low, normal, high, urgent                                 | High     | Yes      |
| Deadline optionnelle                                                  | High     | Yes      |
| Thread de messages (illimités)                                        | High     | Yes      |
| Notes internes (is_internal) vs messages publics                      | Medium   | Yes      |
| Pièces jointes via GED (illimitées, réutilisation DocumentStorageService) | High | Yes   |
| Attacher un document GED existant à un ticket                         | Medium   | Yes      |
| Détacher une pièce jointe sans la supprimer du GED                   | Medium   | Yes      |
| Édition du ticket (propriétaire uniquement)                          | High     | Yes      |
| Suppression du ticket (propriétaire uniquement)                       | High     | Yes      |
| Réouverture d'un ticket fermé / résolu (propriétaire)                | Medium   | Yes      |
| Catégorie libre (bug, facturation, technique, autre…)                 | Medium   | Yes      |
| Tags                                                                  | Medium   | Yes      |
| Recherche Meilisearch (titre, description, messages)                  | High     | Yes      |
| Filtres : statut, priorité, client, assigné, catégorie, date, deadline | High   | Yes      |
| Notifications email à l'assigné (assignation)                        | Medium   | Yes      |
| Notifications email au propriétaire (résolution, clôture)            | Medium   | Yes      |
| Notifications email à tous les participants (nouveau message public)  | Low      | Yes      |
| SLA — first_response_at : heure de la première réponse de l'assigné  | Medium   | Yes      |
| Stats : total, par statut, par priorité, temps moyen de résolution   | Medium   | Yes      |
| Widget dashboard "Tickets ouverts urgents"                            | Medium   | Yes      |
| Webhooks : ticket.opened, ticket.assigned, ticket.resolved, ticket.closed | Low | Yes   |
| Inclusion dans l'export GDPR                                          | High     | Yes      |

---

## 4. Detailed Sprint Breakdown

### 4.1 Sprint 29 — Ticket Core: Backend Models, CRUD, Messages & GED Integration (Weeks 58–61)

#### 4.1.1 Database Migrations

| Migration                        | Description                                                                                                                                                                                                                                                                                    |
|----------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `create_tickets_table`           | id (UUID, PK), user_id (UUID, FK → users, CASCADE) — propriétaire, assigned_to (UUID, FK → users, nullable, SET NULL) — assigné, client_id (UUID, FK → clients, nullable, SET NULL), project_id (UUID, FK → projects, nullable, SET NULL), title (VARCHAR 255), description (TEXT), status ENUM('open','in_progress','pending','resolved','closed') default 'open', priority ENUM('low','normal','high','urgent') default 'normal', category (VARCHAR 100, nullable), tags (JSONB, default '[]'), deadline (DATE, nullable), resolved_at (TIMESTAMP, nullable), closed_at (TIMESTAMP, nullable), first_response_at (TIMESTAMP, nullable), timestamps. Indexes: user_id, assigned_to, client_id, project_id, status, priority, (user_id, status), (client_id, status), (assigned_to, status). |
| `create_ticket_messages_table`   | id (UUID, PK), ticket_id (UUID, FK → tickets, CASCADE), user_id (UUID, FK → users, CASCADE) — auteur, content (TEXT), is_internal (BOOLEAN, default false), timestamps. Indexes: ticket_id, (ticket_id, is_internal). |
| `create_ticket_documents_table`  | id (BIGINT, PK, auto-increment), ticket_id (UUID, FK → tickets, CASCADE), document_id (UUID, FK → documents, CASCADE), attached_at (TIMESTAMP, default NOW()). Unique index: (ticket_id, document_id). |

> **Projet "Divers"** : si `client_id` est NULL, `project_id` est également NULL. Le frontend affiche "Divers" comme libellé non-sélectionnable dans la zone projet. Aucun projet fictif n'est créé en base.
>
> **Réutilisation GED** : les pièces jointes sont des `Document` du GED (Phase 8). La table pivot `ticket_documents` relie un ticket à ses documents. L'upload d'une pièce jointe depuis un ticket crée un `Document` via `DocumentStorageService` (MIME detection, quota enforcement) et l'associe au client du ticket. Le détachement supprime uniquement la ligne pivot, pas le `Document`.

#### 4.1.2 Status Transition Rules

| From         | To allowed                                      |
|--------------|-------------------------------------------------|
| open         | in_progress, pending, resolved, closed          |
| in_progress  | open, pending, resolved, closed                 |
| pending      | open, in_progress, resolved, closed             |
| resolved     | open (réouverture), closed                      |
| closed       | open (réouverture par le propriétaire)          |

> Seul le propriétaire peut clôturer ou rouvrir un ticket. L'assigné peut changer le statut vers in_progress, pending, resolved.

#### 4.1.3 Back-end Tasks

| ID        | Task                                                                                                              | PRD Ref     |
|-----------|-------------------------------------------------------------------------------------------------------------------|-------------|
| P9-BE-001 | Create `Ticket` model — relationships (user/owner, assignee, client, project, messages, documents via pivot); scopes (byStatus, byPriority, byClient, byAssignee, overdue); Scout Searchable | FR-TKT-001 |
| P9-BE-002 | Create `TicketMessage` model — relationships (ticket, user); scope isPublic / isInternal                         | FR-TKT-004 |
| P9-BE-003 | Create `TicketFactory` + `TicketMessageFactory`                                                                   | §10.3.1    |
| P9-BE-004 | Create `TicketPolicy` — owner: toutes actions; assigné: ajouter message, changer statut; autres users: aucun accès | FR-TKT-006 |
| P9-BE-005 | Create `TicketController` — CRUD + actions auxiliaires :<br>— `GET /api/v1/tickets` — liste paginée ; filtres : status, priority, client_id, assigned_to, category, tags[], date_from/to, deadline_from/to, overdue (boolean) ; sort : created_at, deadline, priority, updated_at ; search Meilisearch quand `q` présent<br>— `POST /api/v1/tickets` — créer ticket<br>— `GET /api/v1/tickets/{id}` — détail (ticket + messages + documents)<br>— `PUT /api/v1/tickets/{id}` — éditer (propriétaire)<br>— `DELETE /api/v1/tickets/{id}` — supprimer (propriétaire)<br>— `PATCH /api/v1/tickets/{id}/status` — changer statut (owner ou assigné selon règles)<br>— `PATCH /api/v1/tickets/{id}/assign` — réassigner (owner)<br>— `GET /api/v1/tickets/stats` — statistiques globales<br>— `GET /api/v1/tickets/overdue` — liste des tickets en retard | FR-TKT-001 |
| P9-BE-006 | Create `TicketMessageController` :<br>— `GET /api/v1/tickets/{id}/messages` — liste messages (public pour tous, internes pour owner/assigné)<br>— `POST /api/v1/tickets/{id}/messages` — ajouter message<br>— `PUT /api/v1/tickets/{id}/messages/{msgId}` — éditer son propre message<br>— `DELETE /api/v1/tickets/{id}/messages/{msgId}` — supprimer son propre message | FR-TKT-004 |
| P9-BE-007 | Create `TicketDocumentController` — gestion pièces jointes via GED :<br>— `GET /api/v1/tickets/{id}/documents` — liste documents liés<br>— `POST /api/v1/tickets/{id}/documents` — upload nouvelle PJ (→ crée Document GED + ligne pivot)<br>— `POST /api/v1/tickets/{id}/documents/attach` — attacher Document GED existant (body: `document_id`)<br>— `DELETE /api/v1/tickets/{id}/documents/{docId}` — détacher (supprime pivot, conserve Document GED) | FR-TKT-005 |
| P9-BE-008 | Create `StoreTicketRequest` — titre requis max 255, description requise, client_id nullable + owned, project_id nullable + owned + appartient au client si client_id fourni, assigned_to nullable + user valide, priority enum, category max 100, tags array max 10×50 chars, deadline date future nullable | FR-TKT-001 |
| P9-BE-009 | Create `UpdateTicketRequest` — mêmes règles que Store, tous champs optionnels                                    | FR-TKT-001 |
| P9-BE-010 | Create `StoreTicketMessageRequest` — content requis, is_internal boolean                                         | FR-TKT-004 |
| P9-BE-011 | Create `TicketNotificationService` — envoi emails :<br>— `notifyAssigned(Ticket)` — email à l'assigné lors de l'assignation<br>— `notifyOwnerResolved(Ticket)` — email au propriétaire à la résolution<br>— `notifyOwnerClosed(Ticket)` — email au propriétaire à la clôture<br>— `notifyParticipantsNewMessage(Ticket, TicketMessage)` — email à owner + assigné sur nouveau message public<br>— Toutes les notifications sont en queue (`ShouldQueue`) | FR-TKT-007 |
| P9-BE-012 | Create `TicketObserver` — sur `created`: set `assigned_to` = `user_id` si null, dispatch webhook `ticket.opened`; sur `updated` changement `assigned_to`: dispatch `ticket.assigned`, envoyer `notifyAssigned`; sur `updated` changement status → resolved: set `resolved_at`, dispatch `ticket.resolved`, `notifyOwnerResolved`; sur `updated` status → closed: set `closed_at`, dispatch `ticket.closed`, `notifyOwnerClosed`; sur `deleted`: dispatch webhook `ticket.deleted` | FR-TKT-007 |
| P9-BE-013 | Create `TicketMessageObserver` — sur `created` message public : set `first_response_at` sur ticket si null ET auteur est l'assigné ; dispatch `notifyParticipantsNewMessage` | FR-TKT-007 |
| P9-BE-014 | Configure Meilisearch Scout index pour Ticket :<br>— Searchable: title, description<br>— Filterable: user_id, assigned_to, client_id, project_id, status, priority, category, tags<br>— Sortable: created_at, updated_at, deadline, priority | FR-TKT-003 |
| P9-BE-015 | Add tickets + ticket_messages à `DataExportService` (export GDPR) — métadonnées ticket et contenu des messages publics | NFR-SEC-008 |
| P9-BE-016 | Dispatch webhooks sur événements ticket (ticket.opened, ticket.assigned, ticket.resolved, ticket.closed, ticket.deleted) via `WebhookDispatchService` existant | FR-WBH-008 |

#### 4.1.4 Back-end Tests (TDD)

| Test File                                                                   | Test Cases                                                                                                    |
|-----------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------|
| `tests/Unit/Models/TicketTest.php`                                          | Factory creates valid ticket, assigned_to defaults to user_id, scopes (byStatus, byPriority, byClient, byAssignee, overdue), canTransitionTo rules, relationships (messages, documents) |
| `tests/Unit/Models/TicketMessageTest.php`                                   | Factory creates valid message, isPublic/isInternal scopes, ticket relationship                                |
| `tests/Unit/Services/TicketNotificationServiceTest.php`                     | notifyAssigned queues mail to assigné, notifyOwnerResolved queues mail to owner, notifyParticipantsNewMessage queues to both, internal message does not trigger notification |
| `tests/Unit/Observers/TicketObserverTest.php`                               | assigned_to set to user_id on creation if null, webhooks dispatched on open/assign/resolve/close/delete, resolved_at set on resolve, closed_at set on close |
| `tests/Unit/Observers/TicketMessageObserverTest.php`                        | first_response_at set on first public message from assigné, not set for owner's message, not set for internal message, not overwritten on subsequent messages |
| `tests/Feature/Tickets/TicketCrudTest.php`                                  | Create ticket sans client, avec client, avec client+projet, sans assigné (auto-assign), avec deadline; list retourne paginé; show retourne tout; update titre/statut/priorité; delete par propriétaire; delete refusé pour autre user |
| `tests/Feature/Tickets/TicketStatusTest.php`                                | Toutes les transitions valides acceptées; transitions invalides retournent 422; seul owner peut clôturer; assigné peut passer en resolved; réouverture depuis resolved et closed |
| `tests/Feature/Tickets/TicketFilterTest.php`                                | Filtre par status; par priority; par client_id; par assigned_to; overdue=true retourne uniquement tickets deadline passée + non résolus; combinaison filtres; sort par deadline |
| `tests/Feature/Tickets/TicketSearchTest.php`                                | Recherche par titre; par description; filtre statut combiné avec search; résultats paginés |
| `tests/Feature/Tickets/TicketMessageTest.php`                               | Ajout message public; ajout note interne; liste messages (internes cachés aux non-owners); éditer son message; supprimer son message; ne pas éditer le message d'un autre |
| `tests/Feature/Tickets/TicketDocumentTest.php`                              | Upload PJ → Document GED créé + pivot créé; attacher Document GED existant → seul pivot créé; détacher → pivot supprimé, Document GED conservé; liste documents liés; quota GED respecté lors de l'upload |
| `tests/Feature/Tickets/TicketStatsTest.php`                                 | Retourne total par statut, total par priorité, avg resolution time, 0 quand aucun ticket; overdue count |
| `tests/Feature/Tickets/TicketAssignTest.php`                                | Owner peut réassigner; assigné change; non-owner ne peut pas réassigner; self-assign autorisé                |

---

### 4.2 Sprint 30 — Ticket Frontend: Liste, Détail, Formulaires, Stats & Dashboard (Weeks 62–65)

#### 4.2.1 Front-end Tasks

| ID        | Task                                                                                              | PRD Ref    |
|-----------|---------------------------------------------------------------------------------------------------|------------|
| P9-FE-001 | Create `stores/tickets.ts` Zustand store :<br>— State: tickets[], stats, loading, error, searchQuery, filters (status, priority, client_id, assigned_to, category, tags, date range, deadline range, overdue), sort, pagination<br>— Actions: fetchTickets, createTicket, updateTicket, deleteTicket, changeStatus, reassign, fetchStats, fetchOverdue | §6.2.2     |
| P9-FE-002 | Create `stores/ticketDetail.ts` Zustand store :<br>— State: ticket, messages, documents, loading<br>— Actions: fetchTicket, addMessage, editMessage, deleteMessage, uploadDocument, attachDocument, detachDocument | §6.2.2     |
| P9-FE-003 | Create `app/tickets/page.tsx` — liste des tickets :<br>— Vue tableau : titre, statut badge, priorité badge, client, projet, assigné avatar, deadline, date création<br>— Barre de recherche (debounced → Meilisearch)<br>— Bouton "Nouveau ticket"<br>— Filtres latéraux : statut (checkboxes), priorité (checkboxes), client (combobox), assigné (combobox), catégorie (combobox), overdue toggle, date range<br>— Sort : date création (desc), deadline, priorité<br>— Indicateur visuel deadline dépassée (rouge si overdue) | FR-TKT-001 |
| P9-FE-004 | Create `app/tickets/[id]/page.tsx` — détail ticket :<br>— En-tête : titre, statut badge, priorité badge, client/projet link, assigné, propriétaire, deadline, catégorie, tags<br>— Actions : Changer statut (dropdown), Réassigner, Éditer, Supprimer, Rouvrir<br>— Onglets : Conversation / Pièces jointes<br>— Onglet Conversation : thread messages (is_internal affiche fond différent + badge "Note interne"), formulaire ajout message, toggle note interne | FR-TKT-001 |
| P9-FE-005 | Create `components/tickets/ticket-form-dialog.tsx` — création/édition :<br>— Titre (required), Description (textarea, required)<br>— Client selector (combobox optionnel) — déclenche le chargement des projets du client<br>— Projet selector (combobox, disabled si pas de client, affiche "Divers" si pas de client, filtre projets par client_id)<br>— Assigné selector (combobox, défaut = utilisateur courant)<br>— Priorité (radio ou select : low/normal/high/urgent)<br>— Catégorie (combobox libre)<br>— Tags (chips input)<br>— Deadline (date picker optionnel) | FR-TKT-001 |
| P9-FE-006 | Create `components/tickets/ticket-status-badge.tsx` — badge statut coloré :<br>— open (gris), in_progress (bleu), pending (orange), resolved (vert), closed (slate) | FR-TKT-001 |
| P9-FE-007 | Create `components/tickets/ticket-priority-badge.tsx` — badge priorité :<br>— low (gris clair), normal (bleu clair), high (orange), urgent (rouge, avec icône d'alerte) | FR-TKT-001 |
| P9-FE-008 | Create `components/tickets/ticket-message-thread.tsx` — thread conversation :<br>— Messages ordonnés chronologiquement<br>— Avatar + nom auteur + date relative<br>— Notes internes : fond jaune pâle + badge "Note interne" (visible uniquement owner/assigné)<br>— Boutons Éditer/Supprimer sur ses propres messages<br>— Indicateur "Première réponse" si message déclenche first_response_at | FR-TKT-004 |
| P9-FE-009 | Create `components/tickets/ticket-message-composer.tsx` — formulaire ajout message :<br>— Textarea content (required)<br>— Toggle "Note interne" (checkbox, masqué si user = ni owner ni assigné)<br>— Bouton Envoyer | FR-TKT-004 |
| P9-FE-010 | Create `components/tickets/ticket-attachments-panel.tsx` — panneau pièces jointes :<br>— Liste documents liés : icône type GED, titre, taille, date, boutons Télécharger / Détacher<br>— Bouton "Ajouter une pièce jointe" → ouvre `DocumentUploadDialog` du GED (composant réutilisé de Phase 8) en passant ticket_id<br>— Bouton "Attacher un document GED existant" → combobox de recherche dans le GED de l'utilisateur<br>— Réutiliser `DocumentTypeBadge` de Phase 8 | FR-TKT-005 |
| P9-FE-011 | Create `components/tickets/ticket-stats-card.tsx` — carte stats :<br>— Total tickets ouverts, en cours, résolus, fermés<br>— Tickets urgents en cours<br>— Tickets en retard (overdue)<br>— Temps moyen de résolution (en heures) | FR-TKT-008 |
| P9-FE-012 | Create `components/tickets/ticket-status-change-dialog.tsx` — dialog changement statut :<br>— Dropdown statuts autorisés selon règles de transition<br>— Champ commentaire optionnel (ajoute automatiquement un message public au ticket) | FR-TKT-002 |
| P9-FE-013 | Add Tickets entry to sidebar navigation (après Documents, avec compteur tickets ouverts) | FR-TKT-001 |
| P9-FE-014 | Add "Tickets urgents" widget to dashboard :<br>— 5 tickets ouverts/in_progress les plus urgents (priorité desc, deadline asc)<br>— Colonnes : titre, client, priorité badge, deadline (rouge si overdue)<br>— "Voir tous les tickets" link | FR-TKT-008 |

#### 4.2.2 Front-end Tests

| Test File                                                                 | Test Cases                                                                              |
|---------------------------------------------------------------------------|-----------------------------------------------------------------------------------------|
| `tests/unit/stores/tickets.test.ts`                                       | fetchTickets met à jour la liste, createTicket ajoute, deleteTicket retire, changeStatus met à jour, fetchStats retourne le bon shape |
| `tests/unit/stores/ticketDetail.test.ts`                                  | fetchTicket charge ticket+messages+documents, addMessage ajoute au thread, detachDocument retire sans supprimer |
| `tests/components/tickets/ticket-form-dialog.test.ts`                     | Rendu avec champs requis, sélection client filtre les projets, projet = "Divers" si pas de client, assigné défaut = user courant, soumission avec payload correct, validation titre requis |
| `tests/components/tickets/ticket-status-badge.test.ts`                    | Rendu correct pour les 5 statuts (couleur + label)                                      |
| `tests/components/tickets/ticket-priority-badge.test.ts`                  | Rendu correct pour les 4 priorités, icône alerte sur urgent                             |
| `tests/components/tickets/ticket-message-thread.test.ts`                  | Affiche messages dans l'ordre, note interne masquée pour non-owner, badge "Note interne" visible pour owner, boutons éditer/supprimer sur ses propres messages |
| `tests/components/tickets/ticket-message-composer.test.ts`                | Toggle note interne visible pour owner/assigné, soumission avec content + is_internal, validation content requis |
| `tests/components/tickets/ticket-attachments-panel.test.ts`               | Liste documents liés, bouton détacher appelle detachDocument, bouton upload ouvre DocumentUploadDialog |
| `tests/components/tickets/ticket-status-change-dialog.test.ts`            | Affiche seulement les transitions valides, commentaire optionnel soumis comme message    |
| `tests/e2e/tickets/ticket-create.spec.ts`                                 | Créer ticket sans client → "Divers" affiché; créer avec client → projets filtrés; créer avec priorité urgente; vérifier ticket dans liste |
| `tests/e2e/tickets/ticket-workflow.spec.ts`                               | Changer statut open → in_progress → resolved; rouvrir depuis resolved; clôturer          |
| `tests/e2e/tickets/ticket-messages.spec.ts`                               | Ajouter message public; ajouter note interne; vérifier visibilité différenciée; éditer message |
| `tests/e2e/tickets/ticket-attachments.spec.ts`                            | Uploader PJ depuis ticket; vérifier document visible dans GED; détacher PJ du ticket; document GED toujours présent |
| `tests/e2e/tickets/ticket-search-filter.spec.ts`                          | Recherche par titre; filtre statut; filtre overdue; combinaison filtre + priorité urgente |

---

## 5. API Endpoints Delivered in Phase 9

| Method | Endpoint                                            | Controller / Action                          |
|--------|-----------------------------------------------------|----------------------------------------------|
| GET    | `/api/v1/tickets`                                   | TicketController@index                       |
| POST   | `/api/v1/tickets`                                   | TicketController@store                       |
| GET    | `/api/v1/tickets/stats`                             | TicketController@stats                       |
| GET    | `/api/v1/tickets/overdue`                           | TicketController@overdue                     |
| GET    | `/api/v1/tickets/{id}`                              | TicketController@show                        |
| PUT    | `/api/v1/tickets/{id}`                              | TicketController@update                      |
| DELETE | `/api/v1/tickets/{id}`                              | TicketController@destroy                     |
| PATCH  | `/api/v1/tickets/{id}/status`                       | TicketController@changeStatus                |
| PATCH  | `/api/v1/tickets/{id}/assign`                       | TicketController@assign                      |
| GET    | `/api/v1/tickets/{id}/messages`                     | TicketMessageController@index                |
| POST   | `/api/v1/tickets/{id}/messages`                     | TicketMessageController@store                |
| PUT    | `/api/v1/tickets/{id}/messages/{msgId}`             | TicketMessageController@update               |
| DELETE | `/api/v1/tickets/{id}/messages/{msgId}`             | TicketMessageController@destroy              |
| GET    | `/api/v1/tickets/{id}/documents`                    | TicketDocumentController@index               |
| POST   | `/api/v1/tickets/{id}/documents`                    | TicketDocumentController@store (multipart)   |
| POST   | `/api/v1/tickets/{id}/documents/attach`             | TicketDocumentController@attach              |
| DELETE | `/api/v1/tickets/{id}/documents/{docId}`            | TicketDocumentController@detach              |

---

## 6. Data Model Summary

### tickets

| Column            | Type                                                                 | Notes                                      |
|-------------------|----------------------------------------------------------------------|--------------------------------------------|
| id                | UUID, PK                                                             |                                            |
| user_id           | UUID, FK → users (CASCADE)                                           | Propriétaire                               |
| assigned_to       | UUID, FK → users (SET NULL), nullable                                | Défaut = user_id à la création             |
| client_id         | UUID, FK → clients (SET NULL), nullable                              |                                            |
| project_id        | UUID, FK → projects (SET NULL), nullable                             | Null si pas de client ; affiché "Divers"  |
| title             | VARCHAR(255)                                                         |                                            |
| description       | TEXT                                                                 |                                            |
| status            | ENUM(open, in_progress, pending, resolved, closed) default 'open'    |                                            |
| priority          | ENUM(low, normal, high, urgent) default 'normal'                     |                                            |
| category          | VARCHAR(100), nullable                                               |                                            |
| tags              | JSONB default '[]'                                                   |                                            |
| deadline          | DATE, nullable                                                       |                                            |
| resolved_at       | TIMESTAMP, nullable                                                  | Set automatiquement sur status = resolved  |
| closed_at         | TIMESTAMP, nullable                                                  | Set automatiquement sur status = closed    |
| first_response_at | TIMESTAMP, nullable                                                  | Première réponse publique de l'assigné     |
| created_at        | TIMESTAMP                                                            |                                            |
| updated_at        | TIMESTAMP                                                            |                                            |

### ticket_messages

| Column      | Type                           | Notes                           |
|-------------|--------------------------------|---------------------------------|
| id          | UUID, PK                       |                                 |
| ticket_id   | UUID, FK → tickets (CASCADE)   |                                 |
| user_id     | UUID, FK → users (CASCADE)     | Auteur                          |
| content     | TEXT                           |                                 |
| is_internal | BOOLEAN default false          | Note interne vs message public  |
| created_at  | TIMESTAMP                      |                                 |
| updated_at  | TIMESTAMP                      |                                 |

### ticket_documents (pivot)

| Column      | Type                            | Notes                                              |
|-------------|---------------------------------|----------------------------------------------------|
| id          | BIGINT, PK, auto-increment      |                                                    |
| ticket_id   | UUID, FK → tickets (CASCADE)    |                                                    |
| document_id | UUID, FK → documents (CASCADE)  | Document GED (Phase 8)                             |
| attached_at | TIMESTAMP default NOW()         |                                                    |
|             | UNIQUE (ticket_id, document_id) |                                                    |

---

## 7. GED Integration Strategy

Le module ticket réutilise le module GED de la Phase 8 de la façon suivante :

| Point d'intégration                           | Mécanisme                                                                      |
|-----------------------------------------------|--------------------------------------------------------------------------------|
| Upload pièce jointe depuis ticket             | Appel interne à `DocumentStorageService::store()` + `DocumentTypeDetectorService` → crée un `Document` GED avec `client_id` du ticket et tag `ticket:{ticket_id}` |
| Quota utilisateur                             | Vérifié par `DocumentStorageService` (quota existant Phase 8) lors de tout upload |
| Téléchargement / prévisualisation             | Réutilise `GET /api/v1/documents/{id}/download` sans modification              |
| Affichage type de fichier                     | Réutilise le composant `DocumentTypeBadge` (Phase 8)                           |
| Attacher un document GED existant             | Ajoute uniquement une ligne dans `ticket_documents` — aucune copie de fichier  |
| Détacher une pièce jointe                     | Supprime la ligne pivot — le Document GED et le fichier sont conservés         |
| Suppression du ticket                         | Les lignes pivot sont supprimées (CASCADE) — les Documents GED sont conservés  |

---

## 8. Exit Criteria

| #  | Criterion                                                                                                          | Validated |
|----|--------------------------------------------------------------------------------------------------------------------|-----------|
| 1  | Créer un ticket sans client → client = null, projet = null, affiché "Divers" en frontend                         | [ ]       |
| 2  | Créer un ticket avec client → liste projets filtrée par client                                                    | [ ]       |
| 3  | Créer un ticket avec client + projet → liaison correcte en base                                                   | [ ]       |
| 4  | assigned_to = user_id automatiquement si non fourni à la création                                                 | [ ]       |
| 5  | Toutes les transitions de statut valides acceptées ; invalides retournent 422                                      | [ ]       |
| 6  | Seul le propriétaire peut clôturer ou rouvrir un ticket                                                           | [ ]       |
| 7  | resolved_at set automatiquement au passage en resolved ; closed_at au passage en closed                           | [ ]       |
| 8  | Deadline dépassée → ticket visible dans liste overdue et mis en valeur (rouge) en frontend                        | [ ]       |
| 9  | Ajout message public → visible par tous les participants                                                           | [ ]       |
| 10 | Ajout note interne → visible uniquement par propriétaire et assigné                                               | [ ]       |
| 11 | first_response_at set sur le premier message public de l'assigné                                                  | [ ]       |
| 12 | Upload pièce jointe → Document GED créé, associé au client du ticket, tagué `ticket:{id}`                        | [ ]       |
| 13 | Détacher PJ → Document GED toujours présent dans la bibliothèque                                                  | [ ]       |
| 14 | Attacher un Document GED existant → seule une ligne pivot créée, pas de doublon fichier                           | [ ]       |
| 15 | Quota GED respecté lors d'un upload de PJ depuis un ticket                                                        | [ ]       |
| 16 | Notification email envoyée à l'assigné lors de l'assignation                                                      | [ ]       |
| 17 | Notification email envoyée au propriétaire à la résolution                                                        | [ ]       |
| 18 | Notification email envoyée aux participants sur nouveau message public                                             | [ ]       |
| 19 | Recherche par titre et description via Meilisearch retourne les bons tickets                                      | [ ]       |
| 20 | Filtres statut, priorité, client, overdue fonctionnent individuellement et combinés                               | [ ]       |
| 21 | Stats : total par statut, total par priorité, overdue count, avg resolution time corrects                         | [ ]       |
| 22 | Widget dashboard "Tickets urgents" affiche les 5 tickets ouverts les plus critiques                               | [ ]       |
| 23 | Édition ticket refusée pour un non-propriétaire (403)                                                             | [ ]       |
| 24 | Suppression ticket supprime messages et liens pivot mais conserve les Documents GED                                | [ ]       |
| 25 | Webhooks fired : ticket.opened, ticket.assigned, ticket.resolved, ticket.closed, ticket.deleted                   | [ ]       |
| 26 | Tickets + messages publics inclus dans l'export GDPR                                                              | [ ]       |
| 27 | Couverture tests backend >= 80%                                                                                    | [ ]       |
| 28 | Couverture tests frontend >= 80%                                                                                   | [ ]       |
| 29 | Pipeline CI entièrement vert sur `main`                                                                           | [ ]       |
| 30 | Version taguée `v1.5.0` sur GitHub                                                                                | [ ]       |

---

## 9. Risks Specific to Phase 9

| Risk                                                                        | Mitigation                                                                                             |
|-----------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------|
| Incohérence project/client (projet appartenant à un autre client)           | Validation dans `StoreTicketRequest` : project_id doit appartenir au client_id fourni                 |
| Visibilité des notes internes                                               | Filtrage systématique dans `TicketMessageController@index` selon rôle (owner/assigné vs autres)       |
| Suppression de ticket orphélinant des Documents GED                        | La suppression cascade uniquement le pivot `ticket_documents` ; le Document GED est conservé           |
| Quota GED dépassé à l'upload de PJ                                          | `DocumentStorageService::store()` lève une exception déjà gérée (422) — réutilisation sans code supplémentaire |
| Notifications email en masse (beaucoup de participants)                    | Toutes les notifications via queue (`ShouldQueue`) — pas d'impact synchrone sur la réponse API        |
| Transitions de statut non autorisées par un utilisateur non habilité       | `TicketPolicy` + validation dans `changeStatus` selon rôle                                             |
| Meilisearch index Ticket incluant des données sensibles (notes internes)   | Les messages ne sont pas indexés dans Meilisearch — seuls title et description du ticket sont searchables |

---

*End of Phase 9 — Système de tickets support client (v1.5)*
