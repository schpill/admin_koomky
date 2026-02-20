# Phase 8 — GED (Document Management) (v1.4)

| Field               | Value                                              |
|---------------------|----------------------------------------------------|
| **Phase**           | 8 of 8                                             |
| **Name**            | Gestion Électronique de Documents (GED)            |
| **Duration**        | Weeks 52–57 (6 weeks)                              |
| **Milestone**       | M8 — v1.4.0 Release                               |
| **PRD Sections**    | §4.12 FR-GED                                       |
| **Prerequisite**    | Phase 7 fully completed and validated              |
| **Status**          | Planned                                            |

---

## 1. Phase Objectives

| ID       | Objective                                                                                                    |
|----------|--------------------------------------------------------------------------------------------------------------|
| P8-OBJ-1 | Deliver a document library allowing upload, storage, and retrieval of any file type                         |
| P8-OBJ-2 | Auto-detect document type (PDF, spreadsheet, word, text, script, image, archive, presentation)              |
| P8-OBJ-3 | Enable simple search by title via Meilisearch and filtering by client, type, tags, and date range           |
| P8-OBJ-4 | Allow sending a document by email as an attachment directly from the interface                              |
| P8-OBJ-5 | Support re-uploading (overwriting) a document and tracking version increments                               |
| P8-OBJ-6 | Provide an inline document preview (PDF embed, image, syntax-highlighted code for scripts and text)         |
| P8-OBJ-7 | Maintain >= 80% test coverage on both back-end and front-end                                                |

---

## 2. Entry Criteria

- Phase 7 exit criteria 100% satisfied.
- All Phase 7 CI checks green on `main`.
- v1.3.0 tagged and deployed to production.
- Accounting, Public API, webhooks, and lead pipeline stable in production.

---

## 3. Scope — Requirement Traceability

| Feature                                          | Priority | Included |
|--------------------------------------------------|----------|----------|
| Document upload with optional title (fallback to filename) | High     | Yes      |
| Optional client association                      | High     | Yes      |
| Automatic MIME/type detection + script language  | High     | Yes      |
| Document library (grid/list view, sort, paginate)| High     | Yes      |
| Meilisearch full-text search on title            | High     | Yes      |
| Filter by client, type, tags, date range         | High     | Yes      |
| File download                                    | High     | Yes      |
| Document deletion (metadata + file)              | High     | Yes      |
| Re-upload (overwrite file, increment version)    | High     | Yes      |
| Send document by email as attachment             | High     | Yes      |
| Inline preview (PDF, image, code/text)           | Medium   | Yes      |
| Tags                                             | Medium   | Yes      |
| Stats (count by type, total storage used)        | Medium   | Yes      |
| Bulk delete                                      | Medium   | Yes      |
| Storage quota per user (configurable)            | Low      | Yes      |
| GDPR data export inclusion                       | High     | Yes      |
| Webhook events for document lifecycle            | Medium   | Yes      |

---

## 4. Detailed Sprint Breakdown

### 4.1 Sprint 27 — GED Core: Storage, CRUD & Search (Weeks 52–54)

#### 4.1.1 Database Migrations

| Migration                   | Description                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
|-----------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `create_documents_table`    | id (UUID, PK), user_id (UUID, FK → users, CASCADE), client_id (UUID, FK → clients, nullable, SET NULL), title (VARCHAR 255), original_filename (VARCHAR 500), storage_path (VARCHAR 1000) — UUID-based path within disk, storage_disk (VARCHAR 50, default 'local'), mime_type (VARCHAR 150), document_type ENUM('pdf', 'spreadsheet', 'document', 'text', 'script', 'image', 'archive', 'presentation', 'other', default 'other'), script_language (VARCHAR 30, nullable — e.g. 'python', 'php', 'javascript', 'typescript', 'html', 'css', 'shell', 'ruby', 'go'), file_size (BIGINT UNSIGNED) — bytes, version (TINYINT UNSIGNED, default 1), tags (JSONB, default '[]'), last_sent_at (TIMESTAMP, nullable), last_sent_to (VARCHAR 500, nullable), timestamps. Indexes: user_id, client_id, document_type, (user_id, document_type), (user_id, created_at). |
| `add_document_quota_to_settings` | Add to existing user settings: `document_storage_quota_mb` (INTEGER, default 512) — maximum total storage in MB. |

> Files are stored at `storage/app/documents/{user_id}/{uuid}.{ext}`. The original filename is never used as storage path to prevent path traversal. On re-upload, the existing file is overwritten at the same path; version counter is incremented.

#### 4.1.2 MIME → document_type mapping

| MIME types (examples)                                                                                          | document_type | script_language |
|----------------------------------------------------------------------------------------------------------------|---------------|-----------------|
| `application/pdf`                                                                                              | pdf           | —               |
| `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`, `application/vnd.ms-excel`, `text/csv`   | spreadsheet   | —               |
| `application/vnd.openxmlformats-officedocument.wordprocessingml.document`, `application/msword`, `application/vnd.oasis.opendocument.text` | document | —          |
| `application/vnd.openxmlformats-officedocument.presentationml.presentation`, `application/vnd.ms-powerpoint`  | presentation  | —               |
| `text/plain`, `text/markdown`                                                                                  | text          | —               |
| `text/x-python`, `application/x-python-code`, `.py` extension                                                 | script        | python          |
| `application/x-php`, `text/x-php`, `.php` extension                                                           | script        | php             |
| `text/javascript`, `application/javascript`, `.js` / `.ts` extension                                          | script        | javascript / typescript |
| `text/html`                                                                                                    | script        | html            |
| `text/css`                                                                                                     | script        | css             |
| `application/x-sh`, `text/x-sh`, `.sh` extension                                                              | script        | shell           |
| `text/x-ruby`, `.rb` extension                                                                                 | script        | ruby            |
| `text/x-go`, `.go` extension                                                                                   | script        | go              |
| `image/jpeg`, `image/png`, `image/gif`, `image/svg+xml`, `image/webp`                                         | image         | —               |
| `application/zip`, `application/x-tar`, `application/gzip`, `application/x-7z-compressed`                     | archive       | —               |
| Anything else                                                                                                   | other         | —               |

> Detection uses PHP `finfo` (content inspection) as primary source, falling back to file extension. Dangerous executable MIME types (`application/x-executable`, `application/x-dosexec`, etc.) are rejected at upload time.

#### 4.1.3 Back-end Tasks

| ID        | Task                                                                                                | PRD Ref    |
|-----------|-----------------------------------------------------------------------------------------------------|------------|
| P8-BE-001 | Create `Document` model — relationships (user, client), scopes (byType, byClient, byTag), Scout Searchable | FR-GED-001 |
| P8-BE-002 | Create `DocumentFactory`                                                                            | §10.3.1    |
| P8-BE-003 | Create `DocumentPolicy` — user owns document                                                        | NFR-SEC-004 |
| P8-BE-004 | Create `DocumentTypeDetectorService` — inspect MIME via finfo, fall back to extension, map to document_type + script_language: | FR-GED-003 |
|           | — Reject dangerous MIME types (executable, dll, msi, etc.)                                        | |
|           | — Return `DocumentTypeResult` DTO: `{document_type, script_language, mime_type}`                   | |
| P8-BE-005 | Create `DocumentStorageService` — manage file lifecycle on disk:                                   | FR-GED-002 |
|           | — `store(UploadedFile, userId): string` — persist to `documents/{userId}/{uuid}.{ext}`, return storage_path | |
|           | — `overwrite(string $path, UploadedFile): void` — replace file in-place for re-upload             | |
|           | — `delete(string $path): void` — remove file from disk                                             | |
|           | — `streamDownload(string $path, string $mimeType, string $filename): StreamedResponse`             | |
|           | — Check storage quota before any store/overwrite call                                              | |
| P8-BE-006 | Create `DocumentController` — full CRUD + auxiliary actions:                                       | FR-GED-001 |
|           | — `GET /api/v1/documents` — paginated list; filters: client_id, document_type, tags[], date_from/date_to; sort: created_at, title, file_size; search via Meilisearch when `q` param present | |
|           | — `POST /api/v1/documents` — multipart upload: `file` (required), `title` (optional, defaults to original filename stripped of extension), `client_id` (optional), `tags[]` (optional) | |
|           | — `GET /api/v1/documents/{id}` — document detail (all metadata)                                   | |
|           | — `PUT /api/v1/documents/{id}` — update metadata only: title, client_id, tags                     | |
|           | — `DELETE /api/v1/documents/{id}` — delete record + file from storage                             | |
|           | — `POST /api/v1/documents/{id}/reupload` — multipart: new `file`; overwrites stored file, increments version, updates mime_type/document_type/script_language/file_size/original_filename | |
|           | — `GET /api/v1/documents/{id}/download` — stream file download with `Content-Disposition: attachment` | |
|           | — `POST /api/v1/documents/{id}/email` — send document by email as attachment (body: `recipient_email`, `message` optional) | |
|           | — `DELETE /api/v1/documents` — bulk delete (body: `ids[]`)                                        | |
|           | — `GET /api/v1/documents/stats` — total count, total size (bytes), count and size by document_type | |
| P8-BE-007 | Create `StoreDocumentRequest` — validate: file required, max size (default 50 MB, overridable by env `MAX_DOCUMENT_UPLOAD_MB`), dangerous MIME rejected, title max 255 chars, client_id exists + owned by user when present, tags array of strings max 10 items each 50 chars | FR-GED-001 |
| P8-BE-008 | Create `UpdateDocumentRequest` — validate: title max 255 chars, client_id nullable + owned, tags array | FR-GED-001 |
| P8-BE-009 | Create `DocumentMailService` — compose and dispatch queued mailable:                               | FR-GED-006 |
|           | — `DocumentAttachmentMail` Mailable: recipient_email, document title, optional message, file attached from storage | |
|           | — Validate recipient email format                                                                   | |
|           | — After successful dispatch: update `last_sent_at`, `last_sent_to` on Document                    | |
|           | — Warn (log) if file size > 10 MB; still attempt delivery                                          | |
| P8-BE-010 | Configure Meilisearch index for Document via Scout:                                                | FR-GED-004 |
|           | — Searchable attributes: title, original_filename, tags                                            | |
|           | — Filterable attributes: user_id, client_id, document_type, script_language                        | |
|           | — Sortable attributes: created_at, title, file_size                                                | |
| P8-BE-011 | Add documents to `DataExportService` (GDPR export) — include document metadata (not file contents) | NFR-SEC-008 |
| P8-BE-012 | Dispatch webhooks on document events:                                                              | FR-WBH-008 |
|           | — `document.uploaded`, `document.updated`, `document.deleted`, `document.sent`                    | |

#### 4.1.4 Back-end Tests (TDD)

| Test File                                                            | Test Cases                                                                                           |
|----------------------------------------------------------------------|------------------------------------------------------------------------------------------------------|
| `tests/Unit/Services/DocumentTypeDetectorServiceTest.php`            | PDF MIME → pdf, XLSX MIME → spreadsheet, DOCX MIME → document, text/plain → text, .py extension → script/python, .php → script/php, .js → script/javascript, .ts → script/typescript, text/html → script/html, .sh → script/shell, image/png → image, .zip → archive, dangerous MIME rejected |
| `tests/Unit/Services/DocumentStorageServiceTest.php`                 | Store returns UUID-based path, file exists after store, overwrite replaces content, delete removes file, quota exceeded throws exception |
| `tests/Unit/Services/DocumentMailServiceTest.php`                    | Mail queued with correct recipient, attachment present, subject contains document title, last_sent_at updated, invalid email rejected |
| `tests/Unit/Models/DocumentTest.php`                                 | Factory creates valid document, scopes (byType, byClient, byTag), client relationship nullable      |
| `tests/Feature/Documents/DocumentUploadTest.php`                     | Upload PDF with title, upload without title (filename used), upload with client association, upload without client, quota exceeded returns 422, dangerous file rejected |
| `tests/Feature/Documents/DocumentCrudTest.php`                       | List returns paginated results, detail returns all metadata, update title only, update client_id, update tags, delete removes record and file, other user cannot access |
| `tests/Feature/Documents/DocumentSearchTest.php`                     | Search by title returns matching documents, filter by client_id, filter by document_type, filter by tag, date range filter, combined filters |
| `tests/Feature/Documents/DocumentDownloadTest.php`                   | Download returns StreamedResponse with correct Content-Disposition and MIME type, download unknown id returns 404 |
| `tests/Feature/Documents/DocumentReuploadTest.php`                   | Reupload replaces file content, version incremented from 1 to 2, mime_type and document_type updated, original_filename updated, storage_path unchanged |
| `tests/Feature/Documents/DocumentEmailTest.php`                      | Email dispatch queued, recipient matches request, last_sent_at set, last_sent_to set, invalid email returns 422 |
| `tests/Feature/Documents/DocumentBulkDeleteTest.php`                 | Bulk delete removes all specified documents and files, non-owned documents ignored, partial ownership: owned removed, others untouched |
| `tests/Feature/Documents/DocumentStatsTest.php`                      | Returns total count, total size, per-type counts, empty library returns zeros                        |

---

### 4.2 Sprint 28 — GED Frontend, Preview & Delivery (Weeks 55–57)

#### 4.2.1 Front-end Tasks

| ID        | Task                                                                                          | PRD Ref    |
|-----------|-----------------------------------------------------------------------------------------------|------------|
| P8-FE-001 | Create `stores/documents.ts` Zustand store:                                                   | §6.2.2     |
|           | — State: documents[], stats, loading, error, searchQuery, filters (client_id, document_type, tags, date range), sort, pagination | |
|           | — Actions: fetchDocuments, uploadDocument, updateDocument, deleteDocument, bulkDelete, reuploadDocument, downloadDocument, sendEmail, fetchStats | |
| P8-FE-002 | Create `app/documents/page.tsx` — document library:                                           | FR-GED-001 |
|           | — View toggle: grid (cards) / list (data table)                                                | |
|           | — Top bar: search input (debounced → Meilisearch), upload button, bulk actions (delete)       | |
|           | — Sidebar filters: document_type checkboxes, client selector, tags multi-select, date range picker | |
|           | — Sort selector: date (newest/oldest), title (A–Z), file size                                 | |
|           | — Infinite scroll or paginated (page size: 24 grid / 50 list)                                 | |
|           | — Stats bar: total documents, total size used, quota bar                                       | |
| P8-FE-003 | Create `app/documents/[id]/page.tsx` — document detail:                                       | FR-GED-001 |
|           | — Header: title (editable inline), type badge, version badge, file size, upload date, client link | |
|           | — Tags: display and inline edit                                                                | |
|           | — Action buttons: Download, Send by email, Re-upload, Delete                                   | |
|           | — Last sent info: date + recipient (if sent at least once)                                     | |
|           | — Preview panel (see P8-FE-006)                                                                | |
| P8-FE-004 | Create `components/documents/document-upload-dialog.tsx` — upload dialog:                     | FR-GED-001 |
|           | — Drag-and-drop zone + click to browse                                                         | |
|           | — File selected: show name, size, detected type icon                                           | |
|           | — Title field (placeholder: "Leave blank to use filename")                                     | |
|           | — Client selector (optional, searchable combobox)                                              | |
|           | — Tags input (comma-separated or tag chips)                                                    | |
|           | — Upload progress bar                                                                          | |
|           | — Error states: file too large, dangerous type rejected, quota exceeded                        | |
| P8-FE-005 | Create `components/documents/document-reupload-dialog.tsx` — re-upload dialog:               | FR-GED-005 |
|           | — Warning message: "This will replace the current file. Version N → N+1."                    | |
|           | — Drag-and-drop zone (same as upload)                                                          | |
|           | — Current metadata preserved (title, client, tags)                                             | |
|           | — Progress bar + success/error feedback                                                        | |
| P8-FE-006 | Create `components/documents/document-preview.tsx` — preview panel:                          | FR-GED-007 |
|           | — `pdf`: `<iframe>` embedding `/api/v1/documents/{id}/download` with `Content-Disposition: inline` | |
|           | — `image`: `<img>` tag with blob URL fetched from download endpoint                           | |
|           | — `text` / `script`: fetch raw content, render in `<pre>` with syntax highlighting (Shiki or highlight.js); language derived from script_language | |
|           | — `spreadsheet`, `document`, `presentation`, `archive`, `other`: "Preview not available" placeholder with download CTA | |
| P8-FE-007 | Create `components/documents/document-card.tsx` — grid view card:                            | FR-GED-001 |
|           | — Type icon (large), title (truncated), client badge (if linked), version badge (if > 1), file size, upload date | |
|           | — Hover actions: Download, Preview, Send email, Delete                                         | |
|           | — Checkbox for bulk selection                                                                   | |
| P8-FE-008 | Create `components/documents/document-type-badge.tsx` — reusable badge:                      | FR-GED-001 |
|           | — Icon + label per type: PDF (red), Spreadsheet (green), Document (blue), Text (gray), Script (purple, shows language), Image (orange), Archive (yellow), Presentation (teal), Other (slate) | |
| P8-FE-009 | Create `components/documents/document-send-email-dialog.tsx` — send by email dialog:         | FR-GED-006 |
|           | — Recipient email input (required, validated)                                                  | |
|           | — Optional message textarea                                                                    | |
|           | — Warning if file > 10 MB                                                                      | |
|           | — Pre-fill recipient from linked client's email if available                                   | |
|           | — Success state: show last_sent_at after confirm                                               | |
| P8-FE-010 | Create `components/documents/document-filters.tsx` — filter sidebar:                         | FR-GED-004 |
|           | — Document type checkboxes with count badges                                                   | |
|           | — Client combobox (searchable, optional)                                                       | |
|           | — Tags multi-select (shows all used tags)                                                      | |
|           | — Date range picker (from / to)                                                                | |
|           | — Clear filters button                                                                         | |
| P8-FE-011 | Add Documents entry to sidebar navigation (between Leads and Clients, with total doc count badge) | FR-GED-001 |
| P8-FE-012 | Add "Documents récents" widget to dashboard:                                                  | FR-GED-001 |
|           | — 5 most recently uploaded documents: icon, title, type badge, date                           | |
|           | — "View all" link to documents library                                                         | |

#### 4.2.2 Front-end Tests

| Test File                                                              | Test Cases                                                                         |
|------------------------------------------------------------------------|------------------------------------------------------------------------------------|
| `tests/unit/stores/documents.test.ts`                                  | fetchDocuments updates list, uploadDocument adds to list, deleteDocument removes, bulkDelete removes multiple, sendEmail updates last_sent_at, fetchStats returns correct shape |
| `tests/components/documents/document-upload-dialog.test.ts`            | Renders drag zone, submits with correct FormData, title defaults to filename, shows error on oversized file, shows progress on upload |
| `tests/components/documents/document-reupload-dialog.test.ts`          | Shows version warning (N → N+1), submits with file, triggers reupload action       |
| `tests/components/documents/document-preview.test.ts`                  | Renders iframe for pdf type, img for image type, pre block for script type, placeholder for archive type |
| `tests/components/documents/document-send-email-dialog.test.ts`        | Validates email format, pre-fills client email, submits with correct payload, shows warning for large file |
| `tests/components/documents/document-card.test.ts`                     | Renders title, type badge, client badge when linked, shows version badge when version > 1, checkbox toggles |
| `tests/components/documents/document-type-badge.test.ts`               | Renders correct icon and label for each of the 9 document types, shows script_language for script type |
| `tests/e2e/documents/document-upload.spec.ts`                          | Upload PDF with custom title, verify in library; upload without title (filename used); upload and associate to client; quota exceeded shows error |
| `tests/e2e/documents/document-search.spec.ts`                          | Search by partial title returns matching documents; filter by client; filter by type=script; combined filter + search |
| `tests/e2e/documents/document-reupload.spec.ts`                        | Open document detail, click re-upload, upload new file, verify version badge incremented |
| `tests/e2e/documents/document-email.spec.ts`                           | Open send email dialog, fill recipient, send, verify success toast and last_sent_at updated |
| `tests/e2e/documents/document-bulk-delete.spec.ts`                     | Select 3 documents, click bulk delete, confirm, verify all removed from library     |

---

## 5. API Endpoints Delivered in Phase 8

| Method | Endpoint                                     | Controller / Action                     |
|--------|----------------------------------------------|-----------------------------------------|
| GET    | `/api/v1/documents`                          | DocumentController@index                |
| POST   | `/api/v1/documents`                          | DocumentController@store (multipart)    |
| GET    | `/api/v1/documents/stats`                    | DocumentController@stats                |
| DELETE | `/api/v1/documents`                          | DocumentController@bulkDestroy          |
| GET    | `/api/v1/documents/{id}`                     | DocumentController@show                 |
| PUT    | `/api/v1/documents/{id}`                     | DocumentController@update               |
| DELETE | `/api/v1/documents/{id}`                     | DocumentController@destroy              |
| POST   | `/api/v1/documents/{id}/reupload`            | DocumentController@reupload (multipart) |
| GET    | `/api/v1/documents/{id}/download`            | DocumentController@download             |
| POST   | `/api/v1/documents/{id}/email`               | DocumentController@sendEmail            |

---

## 6. Exit Criteria

| #  | Criterion                                                                                             | Validated |
|----|-------------------------------------------------------------------------------------------------------|-----------|
| 1  | Upload a document with a custom title → title stored correctly                                       | [ ]       |
| 2  | Upload a document without a title → original filename (without extension) used as title              | [ ]       |
| 3  | Upload with client association → document linked and filterable by client                            | [ ]       |
| 4  | Upload without client → document stored as standalone                                                | [ ]       |
| 5  | MIME auto-detection correct for: PDF, XLSX, DOCX, TXT, .py, .php, .js, .html (≥ 8 formats)         | [ ]       |
| 6  | Script language stored correctly (python, php, javascript, html, etc.)                               | [ ]       |
| 7  | Dangerous executable MIME types rejected with 422                                                    | [ ]       |
| 8  | Document listed in library with type badge, script language, size, date, client badge                | [ ]       |
| 9  | Grid view and list view both render correctly                                                         | [ ]       |
| 10 | Search by title via Meilisearch returns correct results                                               | [ ]       |
| 11 | Filter by client returns only that client's documents                                                 | [ ]       |
| 12 | Filter by document_type returns correct subset                                                        | [ ]       |
| 13 | Filter by tag returns correct subset                                                                  | [ ]       |
| 14 | Sort by date, title, and file size works correctly                                                    | [ ]       |
| 15 | Preview renders PDF (iframe embed), image, and plain text/scripts (syntax highlighted code block)     | [ ]       |
| 16 | Download returns correct file with correct Content-Disposition and MIME headers                       | [ ]       |
| 17 | Re-upload replaces file, version counter incremented, metadata (mime_type, document_type) updated     | [ ]       |
| 18 | Send by email: mail queued with attachment, recipient matches input, last_sent_at/last_sent_to updated | [ ]      |
| 19 | Delete removes metadata record and physical file from storage                                         | [ ]       |
| 20 | Bulk delete removes all selected documents and their files                                            | [ ]       |
| 21 | Storage quota check: upload rejected with 422 when user quota exceeded                               | [ ]       |
| 22 | Stats endpoint returns correct total count, total size, count per document_type                       | [ ]       |
| 23 | Documents metadata included in GDPR data export                                                       | [ ]       |
| 24 | Webhook events fired for document.uploaded, document.updated, document.deleted, document.sent         | [ ]       |
| 25 | "Documents récents" widget visible on dashboard with 5 most recent documents                          | [ ]       |
| 26 | Back-end test coverage >= 80%                                                                         | [ ]       |
| 27 | Front-end test coverage >= 80%                                                                        | [ ]       |
| 28 | CI pipeline fully green on `main`                                                                     | [ ]       |
| 29 | Version tagged as `v1.4.0` on GitHub                                                                  | [ ]       |

---

## 7. Risks Specific to Phase 8

| Risk                                                                 | Mitigation                                                                                             |
|----------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------|
| MIME spoofing — malicious file uploaded with misleading Content-Type | Use PHP `finfo` for content-based detection as primary; reject dangerous MIME types regardless of extension |
| XSS via inline preview of HTML/JavaScript files                      | Never render script file content as actual HTML in an iframe; use a syntax highlighter that escapes output (Shiki / highlight.js) in a sandboxed `<pre>` block |
| Large file uploads exhausting PHP memory                             | Use streaming upload to disk (`storeAs`); enforce `MAX_DOCUMENT_UPLOAD_MB` in php.ini and nginx `client_max_body_size` |
| Path traversal via original filename                                 | Always store files at UUID-based path; never use original_filename as storage path                     |
| Email attachment size limits (SMTP/SES)                              | Log warning for files > 10 MB; frontend warns user; backend still attempts delivery                    |
| Meilisearch tags indexing (JSONB array)                              | Configure Scout to index tags as array; add integration test verifying tag-based search                |
| Storage growth and disk saturation                                   | Configurable per-user quota; stats endpoint exposes total usage; monitoring alert when global usage > 80% |
| Preview fetch leaking sensitive files to browser                     | Download and preview endpoints are protected by Sanctum auth middleware; no public URLs               |

---

*End of Phase 8 — Gestion Électronique de Documents (v1.4)*
