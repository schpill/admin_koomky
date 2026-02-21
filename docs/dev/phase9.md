# Phase 9 — Task Tracking

> **Status**: Planned
> **Prerequisite**: Phase 8 fully merged and tagged `v1.4.0`
> **Spec**: [docs/phases/phase9.md](../phases/phase9.md)

---

## Sprint 29 — Backend Core: Models, CRUD, Messages & GED Integration (Weeks 58–61)

### Backend — Models & Migrations

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P9-BE-001 | Create Ticket model (relationships: user/owner, assignee, client, project, messages, documents via pivot; scopes: byStatus, byPriority, byClient, byAssignee, overdue; Scout Searchable) | todo | |
| P9-BE-002 | Create TicketMessage model (relationships: ticket, user; scopes: isPublic, isInternal) | todo | |
| P9-BE-003 | Create TicketFactory + TicketMessageFactory | todo | |
| P9-BE-004 | Create TicketPolicy (owner: all actions; assignee: add message + change status; others: no access) | todo | |
| P9-BE-005 | Migration: create_tickets_table | todo | |
| P9-BE-006 | Migration: create_ticket_messages_table | todo | |
| P9-BE-007 | Migration: create_ticket_documents_table (pivot: ticket_id, document_id, attached_at; unique index) | todo | |

### Backend — Controllers & Requests

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P9-BE-008 | Create TicketController (index, store, show, update, destroy, changeStatus, assign, stats, overdue) | todo | |
| P9-BE-009 | Create TicketMessageController (index, store, update, destroy) | todo | |
| P9-BE-010 | Create TicketDocumentController (index, store/upload, attach existing GED doc, detach) | todo | |
| P9-BE-011 | Create StoreTicketRequest (title, description, client_id nullable+owned, project_id nullable+owned+belongs to client, assigned_to nullable, priority enum, category max 100, tags, deadline nullable future date) | todo | |
| P9-BE-012 | Create UpdateTicketRequest (same rules as Store, all optional) | todo | |
| P9-BE-013 | Create StoreTicketMessageRequest (content required, is_internal boolean) | todo | |

### Backend — Services & Observers

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P9-BE-014 | Create TicketNotificationService (notifyAssigned, notifyOwnerResolved, notifyOwnerClosed, notifyParticipantsNewMessage — all queued) | todo | |
| P9-BE-015 | Create TicketObserver (assigned_to default on created, webhooks on open/assign/resolve/close/delete, set resolved_at / closed_at) | todo | |
| P9-BE-016 | Create TicketMessageObserver (set first_response_at on first public message from assignee, trigger notifyParticipantsNewMessage) | todo | |
| P9-BE-017 | Configure Meilisearch Scout index for Ticket (searchable: title, description; filterable: user_id, assigned_to, client_id, project_id, status, priority, category, tags; sortable: created_at, updated_at, deadline, priority) | todo | |
| P9-BE-018 | Add tickets + ticket_messages (public) to DataExportService (GDPR export) | todo | |
| P9-BE-019 | Dispatch webhooks for ticket events via existing WebhookDispatchService (ticket.opened, ticket.assigned, ticket.resolved, ticket.closed, ticket.deleted) | todo | |

### Backend Tests

| ID | Test File | Status | Owner |
|----|-----------|--------|-------|
| P9-BT-001 | tests/Unit/Models/TicketTest.php | todo | |
| P9-BT-002 | tests/Unit/Models/TicketMessageTest.php | todo | |
| P9-BT-003 | tests/Unit/Services/TicketNotificationServiceTest.php | todo | |
| P9-BT-004 | tests/Unit/Observers/TicketObserverTest.php | todo | |
| P9-BT-005 | tests/Unit/Observers/TicketMessageObserverTest.php | todo | |
| P9-BT-006 | tests/Feature/Tickets/TicketCrudTest.php | todo | |
| P9-BT-007 | tests/Feature/Tickets/TicketStatusTest.php | todo | |
| P9-BT-008 | tests/Feature/Tickets/TicketFilterTest.php | todo | |
| P9-BT-009 | tests/Feature/Tickets/TicketSearchTest.php | todo | |
| P9-BT-010 | tests/Feature/Tickets/TicketMessageTest.php | todo | |
| P9-BT-011 | tests/Feature/Tickets/TicketDocumentTest.php | todo | |
| P9-BT-012 | tests/Feature/Tickets/TicketStatsTest.php | todo | |
| P9-BT-013 | tests/Feature/Tickets/TicketAssignTest.php | todo | |

---

## Sprint 30 — Frontend: Liste, Détail, Formulaires, Stats & Dashboard (Weeks 62–65)

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P9-FE-001 | Create stores/tickets.ts Zustand store (fetchTickets, createTicket, updateTicket, deleteTicket, changeStatus, reassign, fetchStats, fetchOverdue) | todo | |
| P9-FE-002 | Create stores/ticketDetail.ts Zustand store (fetchTicket, addMessage, editMessage, deleteMessage, uploadDocument, attachDocument, detachDocument) | todo | |
| P9-FE-003 | Create app/tickets/page.tsx (list: tableau, search bar, filters sidebar, sort, overdue indicator) | todo | |
| P9-FE-004 | Create app/tickets/[id]/page.tsx (detail: header, tabs Conversation/PJ, status change, reassign, edit, delete, reopen) | todo | |
| P9-FE-005 | Create components/tickets/ticket-form-dialog.tsx (title, description, client selector → project filtered, assignee selector, priority, category, tags, deadline) | todo | |
| P9-FE-006 | Create components/tickets/ticket-status-badge.tsx (5 statuts, couleurs distinctes) | todo | |
| P9-FE-007 | Create components/tickets/ticket-priority-badge.tsx (4 priorités, icône alerte sur urgent) | todo | |
| P9-FE-008 | Create components/tickets/ticket-message-thread.tsx (thread chronologique, notes internes différenciées, éditer/supprimer ses messages) | todo | |
| P9-FE-009 | Create components/tickets/ticket-message-composer.tsx (textarea, toggle note interne pour owner/assigné, validation) | todo | |
| P9-FE-010 | Create components/tickets/ticket-attachments-panel.tsx (liste PJ, upload via DocumentUploadDialog GED, attacher doc existant, détacher, DocumentTypeBadge) | todo | |
| P9-FE-011 | Create components/tickets/ticket-stats-card.tsx (total par statut, par priorité, overdue count, avg resolution time) | todo | |
| P9-FE-012 | Create components/tickets/ticket-status-change-dialog.tsx (transitions valides, commentaire optionnel → message public) | todo | |
| P9-FE-013 | Add Tickets entry to sidebar navigation (after Documents, with open ticket count badge) | todo | |
| P9-FE-014 | Add "Tickets urgents" widget to dashboard (5 most critical open tickets: title, client, priority badge, deadline, overdue highlight) | todo | |

### Frontend Tests

| ID | Test File | Status | Owner |
|----|-----------|--------|-------|
| P9-FT-001 | tests/unit/stores/tickets.test.ts | todo | |
| P9-FT-002 | tests/unit/stores/ticketDetail.test.ts | todo | |
| P9-FT-003 | tests/components/tickets/ticket-form-dialog.test.ts | todo | |
| P9-FT-004 | tests/components/tickets/ticket-status-badge.test.ts | todo | |
| P9-FT-005 | tests/components/tickets/ticket-priority-badge.test.ts | todo | |
| P9-FT-006 | tests/components/tickets/ticket-message-thread.test.ts | todo | |
| P9-FT-007 | tests/components/tickets/ticket-message-composer.test.ts | todo | |
| P9-FT-008 | tests/components/tickets/ticket-attachments-panel.test.ts | todo | |
| P9-FT-009 | tests/components/tickets/ticket-status-change-dialog.test.ts | todo | |
| P9-FT-010 | tests/e2e/tickets/ticket-create.spec.ts | todo | |
| P9-FT-011 | tests/e2e/tickets/ticket-workflow.spec.ts | todo | |
| P9-FT-012 | tests/e2e/tickets/ticket-messages.spec.ts | todo | |
| P9-FT-013 | tests/e2e/tickets/ticket-attachments.spec.ts | todo | |
| P9-FT-014 | tests/e2e/tickets/ticket-search-filter.spec.ts | todo | |
