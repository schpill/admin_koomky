# Phase 10 Full Strict Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Deliver Phase 10 end-to-end (Backend RAG pipeline, MCP server, Frontend chatbot/admin) with tests.

**Architecture:** Backend Laravel indexes GED documents into pgvector chunks and exposes RAG APIs. A TypeScript MCP server calls these APIs through secure headers/scoped tokens. Frontend adds a portal chat widget and admin RAG controls, backed by Zustand stores.

**Tech Stack:** Laravel 12, PostgreSQL + pgvector, Sanctum, Pest, Next.js + Zustand + Vitest, TypeScript MCP SDK.

---

### Task 1: Backend schema and configuration
**Files:**
- Create: `backend/database/migrations/*_enable_pgvector_extension.php`
- Create: `backend/database/migrations/*_create_document_chunks_table.php`
- Create: `backend/database/migrations/*_add_embedding_status_to_documents_table.php`
- Create: `backend/database/migrations/*_create_rag_usage_logs_table.php`
- Modify: `backend/.env.example`, `docker-compose.yml`, `backend/config/services.php`, `backend/config/queue.php`, `backend/composer.json`

Steps:
1. Add failing backend tests that rely on new schema/config fields.
2. Run tests (expect failure).
3. Implement migrations/config/deps.
4. Run tests and migrations (expect pass).

### Task 2: Backend RAG domain and services
**Files:**
- Create: `backend/app/Exceptions/GeminiException.php`
- Create: `backend/app/Models/DocumentChunk.php`, `backend/app/Models/RagUsageLog.php`
- Create: `backend/app/Services/GeminiService.php`
- Create: `backend/app/Services/DocumentTextExtractorService.php`
- Create: `backend/app/Services/DocumentChunkService.php`
- Create: `backend/app/Services/DocumentEmbeddingService.php`
- Create: `backend/app/Services/VectorSearchService.php`
- Create: `backend/app/Services/RagService.php`
- Modify: `backend/app/Models/Document.php`, `backend/app/Models/User.php`

Steps:
1. Write unit tests for each new service/model behavior.
2. Verify RED for each test file.
3. Implement minimal code to pass.
4. Refactor while keeping tests green.

### Task 3: Backend jobs, observer, middleware, controllers, requests, routes
**Files:**
- Create: `backend/app/Jobs/ProcessDocumentEmbeddingJob.php`
- Create: `backend/app/Http/Requests/Api/V1/Rag/AskRagRequest.php`
- Create: `backend/app/Http/Controllers/Api/V1/RagController.php`
- Create: `backend/app/Http/Controllers/Api/V1/McpTokenController.php`
- Create: `backend/app/Http/Middleware/McpScopeGuard.php`
- Modify: `backend/app/Observers/DocumentObserver.php`, `backend/bootstrap/app.php`, `backend/routes/api.php`, `backend/app/Http/Controllers/Api/V1/Settings/PersonalAccessTokenController.php`

Steps:
1. Write failing feature/unit tests for endpoints/job/observer/middleware.
2. Implement minimal API surface and auth rules.
3. Re-run focused tests then full backend suite.

### Task 4: MCP service implementation
**Files:**
- Create: `mcp/package.json`, `mcp/tsconfig.json`, `mcp/Dockerfile`
- Create: `mcp/src/index.ts`, `mcp/src/server.ts`, `mcp/src/koomkyClient.ts`
- Create: `mcp/src/tools/*.ts`
- Create: `mcp/src/__tests__/*.test.ts`
- Create: `docs/mcp/claude-desktop.md`

Steps:
1. Write failing MCP unit tests.
2. Implement client/tools/transports.
3. Run MCP test suite and build.

### Task 5: Frontend RAG store, widget, admin status pages
**Files:**
- Create: `frontend/lib/stores/rag.ts`
- Create: `frontend/components/rag/chat-widget.tsx`
- Create: `frontend/components/rag/embedding-status-badge.tsx`
- Create: `frontend/app/(dashboard)/settings/rag/page.tsx`
- Modify: `frontend/app/portal/layout.tsx`, `frontend/app/(dashboard)/documents/[id]/page.tsx`, `frontend/components/layout/sidebar.tsx`, `frontend/lib/i18n/messages.ts`
- Create: tests under `frontend/tests/unit`, `frontend/tests/components`, `frontend/tests/e2e/rag`

Steps:
1. Write failing frontend tests for store/components.
2. Implement minimal UI and API calls.
3. Run targeted tests, then full frontend unit tests.

### Task 6: End-to-end verification
**Files:**
- Modify: `docs/dev/phase10.md` progress statuses if needed

Steps:
1. Run backend tests for new units/features.
2. Run MCP tests/build.
3. Run frontend tests for RAG additions.
4. Report exact pass/fail evidence and remaining gaps.
