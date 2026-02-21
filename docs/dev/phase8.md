# Phase 8 — Task Tracking

> **Status**: Planned
> **Prerequisite**: Phase 7 fully merged and tagged `v1.3.0`
> **Spec**: [docs/phases/phase8.md](../phases/phase8.md)

---

## Sprint 27 — GED Core: Storage, CRUD & Search (Weeks 52–54)

### Backend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P8-BE-001 | Create Document model (relationships: user, client; scopes: byType, byClient, byTag; Scout Searchable) | done | |
| P8-BE-002 | Create DocumentFactory | done | |
| P8-BE-003 | Create DocumentPolicy (user owns document) | done | |
| P8-BE-004 | Create DocumentTypeDetectorService (finfo MIME inspection, extension fallback, dangerous MIME rejection, returns DocumentTypeResult DTO) | done | |
| P8-BE-005 | Create DocumentStorageService (store, overwrite, delete, streamDownload, quota check) | done | |
| P8-BE-006 | Create DocumentController (index, store, show, update, destroy, reupload, download, sendEmail, bulkDestroy, stats) | done | |
| P8-BE-007 | Create StoreDocumentRequest (file required, max size, dangerous MIME rejected, title max 255, client_id ownership, tags validation) | done | |
| P8-BE-008 | Create UpdateDocumentRequest (title, client_id nullable, tags) | done | |
| P8-BE-009 | Create DocumentMailService + DocumentAttachmentMail (queued, attachment from storage, update last_sent_at/last_sent_to) | done | |
| P8-BE-010 | Configure Meilisearch Scout index for Document (searchable: title, original_filename, tags; filterable: user_id, client_id, document_type; sortable: created_at, title, file_size) | done | |
| P8-BE-011 | Add documents metadata to DataExportService (GDPR export) | done | |
| P8-BE-012 | Dispatch webhooks for document events (document.uploaded, document.updated, document.deleted, document.sent) | done | |
| P8-BE-013 | Migration: create_documents_table | done | |
| P8-BE-014 | Migration: add_document_quota_to_settings | done | |

### Backend Tests

| ID | Test File | Status | Owner |
|----|-----------|--------|-------|
| P8-BT-001 | tests/Unit/Services/DocumentTypeDetectorServiceTest.php | done | |
| P8-BT-002 | tests/Unit/Services/DocumentStorageServiceTest.php | done | |
| P8-BT-003 | tests/Unit/Services/DocumentMailServiceTest.php | done | |
| P8-BT-004 | tests/Unit/Models/DocumentTest.php | done | |
| P8-BT-005 | tests/Feature/Documents/DocumentUploadTest.php | done | |
| P8-BT-006 | tests/Feature/Documents/DocumentCrudTest.php | done | |
| P8-BT-007 | tests/Feature/Documents/DocumentSearchTest.php | done | |
| P8-BT-008 | tests/Feature/Documents/DocumentDownloadTest.php | done | |
| P8-BT-009 | tests/Feature/Documents/DocumentReuploadTest.php | done | |
| P8-BT-010 | tests/Feature/Documents/DocumentEmailTest.php | done | |
| P8-BT-011 | tests/Feature/Documents/DocumentBulkDeleteTest.php | done | |
| P8-BT-012 | tests/Feature/Documents/DocumentStatsTest.php | done | |

---

## Sprint 28 — GED Frontend, Preview & Delivery (Weeks 55–57)

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P8-FE-001 | Create stores/documents.ts Zustand store (fetchDocuments, uploadDocument, updateDocument, deleteDocument, bulkDelete, reuploadDocument, downloadDocument, sendEmail, fetchStats) | done | |
| P8-FE-002 | Create app/documents/page.tsx (grid/list toggle, search bar, filters sidebar, sort selector, stats bar, bulk select) | done | |
| P8-FE-003 | Create app/documents/[id]/page.tsx (detail header, tags inline edit, action buttons, last sent info, preview panel) | done | |
| P8-FE-004 | Create components/documents/document-upload-dialog.tsx (drag & drop zone, title field, client selector, tags input, progress bar, error states) | done | |
| P8-FE-005 | Create components/documents/document-reupload-dialog.tsx (version warning N → N+1, drag & drop zone, progress bar) | done | |
| P8-FE-006 | Create components/documents/document-preview.tsx (PDF iframe, image blob, syntax-highlighted pre for text/script, placeholder for other types) | done | |
| P8-FE-007 | Create components/documents/document-card.tsx (grid card: type icon, title, client badge, version badge, file size, date, hover actions, checkbox) | done | |
| P8-FE-008 | Create components/documents/document-type-badge.tsx (icon + label + color per document_type, script_language label for script type) | done | |
| P8-FE-009 | Create components/documents/document-send-email-dialog.tsx (recipient email, optional message, large-file warning, pre-fill from client email) | done | |
| P8-FE-010 | Create components/documents/document-filters.tsx (type checkboxes, client combobox, tags multi-select, date range picker, clear filters) | done | |
| P8-FE-011 | Add Documents entry to sidebar navigation (between Leads and Clients, with total document count badge) | done | |
| P8-FE-012 | Add "Documents récents" widget to dashboard (5 most recent documents with icon, title, type badge, date, view all link) | done | |

### Frontend Tests

| ID | Test File | Status | Owner |
|----|-----------|--------|-------|
| P8-FT-001 | tests/unit/stores/documents.test.ts | done | |
| P8-FT-002 | tests/components/documents/document-upload-dialog.test.ts | done | |
| P8-FT-003 | tests/components/documents/document-reupload-dialog.test.ts | done | |
| P8-FT-004 | tests/components/documents/document-preview.test.ts | done | |
| P8-FT-005 | tests/components/documents/document-send-email-dialog.test.ts | done | |
| P8-FT-006 | tests/components/documents/document-card.test.ts | done | |
| P8-FT-007 | tests/components/documents/document-type-badge.test.ts | done | |
| P8-FT-008 | tests/e2e/documents/document-upload.spec.ts | done | |
| P8-FT-009 | tests/e2e/documents/document-search.spec.ts | done | |
| P8-FT-010 | tests/e2e/documents/document-reupload.spec.ts | done | |
| P8-FT-011 | tests/e2e/documents/document-email.spec.ts | done | |
| P8-FT-012 | tests/e2e/documents/document-bulk-delete.spec.ts | done | |
