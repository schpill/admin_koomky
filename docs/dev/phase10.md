# Phase 10 — Task Tracking

> **Status**: Planned
> **Prerequisite**: Phase 9 fully merged and tagged `v1.5.0`
> **Spec**: [docs/phases/phase10.md](../phases/phase10.md)

---

## Sprint 31 — Backend RAG Pipeline (Weeks 66–69)

### Infrastructure & Database

| ID          | Task                                                                                                         | Status | Owner |
|-------------|--------------------------------------------------------------------------------------------------------------|--------|-------|
| P10-BE-INF-01 | Migration `enable_pgvector_extension` — `CREATE EXTENSION IF NOT EXISTS vector`                            | todo   |       |
| P10-BE-INF-02 | Migration `create_document_chunks_table` — id, document_id, user_id, chunk_index, content, embedding vector(768), token_count, created_at + index HNSW | todo   |       |
| P10-BE-INF-03 | Migration `add_embedding_status_to_documents` — colonne `embedding_status VARCHAR(20) nullable`             | todo   |       |
| P10-BE-INF-04 | Ajouter `GEMINI_API_KEY`, `MCP_API_SECRET`, `MCP_KOOMKY_URL` dans `.env.example` + `docker-compose.yml` (services `api` et `mcp`) | todo   |       |
| P10-BE-INF-05 | Ajouter dépendances Composer : `smalot/pdfparser`, `phpoffice/phpword`                                      | todo   |       |
| P10-BE-INF-06 | Déclarer queue `embeddings` dans config Laravel (`config/queue.php`) + worker dans `docker-compose.yml`      | todo   |       |

### Backend — Services

| ID          | Task                                                                                                         | Status | Owner |
|-------------|--------------------------------------------------------------------------------------------------------------|--------|-------|
| P10-BE-001  | Create `GeminiService` — `embed(string): float[]` via `text-embedding-004` + `generate(string, array): string` via `gemini-2.5-flash` + gestion erreurs API | todo   |       |
| P10-BE-002  | Create `DocumentTextExtractorService` — extraction texte par MIME (PDF via smalot, DOCX via PhpWord, TXT/MD direct) + retourne `null` si non supporté | todo   |       |
| P10-BE-003  | Create `DocumentChunkService` — découpage paragraphes, fusion < 100 tokens, split > 512 tokens, overlap 64 tokens | todo   |       |
| P10-BE-004  | Create `DocumentEmbeddingService` — `indexDocument()`, `deleteDocumentChunks()`, `reindexDocument()`, gestion `embedding_status` | todo   |       |
| P10-BE-005  | Create `ProcessDocumentEmbeddingJob` — ShouldQueue, queue `embeddings`, timeout 120s, tries 3, backoff [30, 120, 300] | todo   |       |
| P10-BE-006  | Update `DocumentObserver` — `created` dispatch job si MIME indexable ; `updated` (re-upload) dispatch job ; `deleted` supprime chunks | todo   |       |
| P10-BE-007  | Update `Document` model — cast + fillable `embedding_status`                                                 | todo   |       |
| P10-BE-008  | Create `VectorSearchService` — `search(query, userId, topK, clientId?)` — embed query + requête pgvector cosinus + filtrage user_id/client_id | todo   |       |
| P10-BE-009  | Create `RagService` — `answer(question, userId, clientId?)` — search top-5 + prompt construction + génération Gemini + retour answer/sources/tokens/latency | todo   |       |
| P10-BE-010  | Create `RagController` — `POST /api/v1/rag/ask`, `GET /api/v1/rag/search`, `GET /api/v1/rag/status`, `POST /api/v1/rag/reindex/{id}` | todo   |       |
| P10-BE-011  | Create `AskRagRequest` — validation question (requis, max 1000 chars) + client_id (optionnel, owned)         | todo   |       |
| P10-BE-012  | Create `RagUsageLog` model + migration — log appels RAG (user_id, question, chunks_used, tokens_used, latency_ms) | todo   |       |
| P10-BE-013  | Créer `McpTokenController` — `GET /api/v1/mcp/token` — émet PAT scope `mcp:read`                            | todo   |       |
| P10-BE-014  | Créer `McpScopeGuard` middleware — vérifie scope `mcp:read` sur les routes RAG accessibles au MCP            | todo   |       |

### Backend Tests (TDD)

| ID          | Test File                                                              | Status | Owner |
|-------------|------------------------------------------------------------------------|--------|-------|
| P10-BT-001  | `tests/Unit/Services/GeminiServiceTest.php`                            | todo   |       |
| P10-BT-002  | `tests/Unit/Services/DocumentTextExtractorServiceTest.php`             | todo   |       |
| P10-BT-003  | `tests/Unit/Services/DocumentChunkServiceTest.php`                     | todo   |       |
| P10-BT-004  | `tests/Unit/Services/DocumentEmbeddingServiceTest.php`                 | todo   |       |
| P10-BT-005  | `tests/Unit/Services/VectorSearchServiceTest.php`                      | todo   |       |
| P10-BT-006  | `tests/Unit/Services/RagServiceTest.php`                               | todo   |       |
| P10-BT-007  | `tests/Unit/Jobs/ProcessDocumentEmbeddingJobTest.php`                  | todo   |       |
| P10-BT-008  | `tests/Feature/Rag/RagAskTest.php`                                     | todo   |       |
| P10-BT-009  | `tests/Feature/Rag/RagSearchTest.php`                                  | todo   |       |
| P10-BT-010  | `tests/Feature/Rag/RagStatusTest.php`                                  | todo   |       |

---

## Sprint 32 — Serveur MCP TypeScript (Weeks 70–73)

### Infrastructure MCP

| ID           | Task                                                                                                                 | Status | Owner |
|--------------|----------------------------------------------------------------------------------------------------------------------|--------|-------|
| P10-MCP-INF-01 | Initialiser `mcp/` : `package.json`, `tsconfig.json` strict ESM, `Dockerfile` multi-stage node:20-slim             | todo   |       |
| P10-MCP-INF-02 | Ajouter service `mcp` dans `docker-compose.yml` (port 3100, env GEMINI_API_KEY + MCP_API_SECRET + MCP_KOOMKY_URL)  | todo   |       |

### MCP Tasks

| ID           | Task                                                                                                                  | Status | Owner |
|--------------|-----------------------------------------------------------------------------------------------------------------------|--------|-------|
| P10-MCP-001  | Create `koomkyClient.ts` — client HTTP vers API Koomky avec auth `X-MCP-Secret` + méthodes ask/search/listTopics/getContext | todo   |       |
| P10-MCP-002  | Create tool `search_documents` — input schema Zod, appel koomkyClient.search(), formatage résultats                  | todo   |       |
| P10-MCP-003  | Create tool `ask_question` — input schema Zod, appel koomkyClient.ask(), retour answer + sources formatées           | todo   |       |
| P10-MCP-004  | Create tool `list_topics` — agrégation tags + catégories documents indexés, triés alphabétiquement                   | todo   |       |
| P10-MCP-005  | Create tool `get_document_context` — input schema Zod, retourne chunks d'un document spécifique                      | todo   |       |
| P10-MCP-006  | Implémenter transport `stdio` (Claude Desktop) + transport `SSE` sur port 3100, sélection via `MCP_TRANSPORT` env    | todo   |       |
| P10-MCP-007  | Create `server.ts` — enregistrement des 4 tools, gestion erreurs MCP, logging                                        | todo   |       |
| P10-MCP-008  | Rédiger `docs/mcp/claude-desktop.md` — config JSON Claude Desktop, commande stdio, exemples d'usage                  | todo   |       |

### MCP Tests

| ID           | Test File                                                  | Status | Owner |
|--------------|------------------------------------------------------------|--------|-------|
| P10-MT-001   | `mcp/src/__tests__/koomkyClient.test.ts`                   | todo   |       |
| P10-MT-002   | `mcp/src/__tests__/tools/searchDocuments.test.ts`          | todo   |       |
| P10-MT-003   | `mcp/src/__tests__/tools/askQuestion.test.ts`              | todo   |       |
| P10-MT-004   | `mcp/src/__tests__/tools/listTopics.test.ts`               | todo   |       |
| P10-MT-005   | `mcp/src/__tests__/server.test.ts`                         | todo   |       |

---

## Sprint 33 — Frontend Chatbot & Administration (Weeks 74–77)

### Frontend Tasks

| ID          | Task                                                                                                                  | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------|--------|-------|
| P10-FE-001  | Create `stores/rag.ts` — Zustand store (messages historique, loading, error, sources, askQuestion, searchDocuments, clearHistory) | todo   |       |
| P10-FE-002  | Create `components/rag/chat-widget.tsx` — bulle chatbot portail client (historique, spinner, sources cliquables, disclaimer) | todo   |       |
| P10-FE-003  | Intégrer `chat-widget.tsx` dans `app/(portal)/layout.tsx` — visible si documents RAG disponibles pour le client      | todo   |       |
| P10-FE-004  | Update `app/(dashboard)/documents/[id]/page.tsx` — badge `embedding_status` + bouton "Relancer l'indexation" si `failed` | todo   |       |
| P10-FE-005  | Create `app/(dashboard)/settings/rag/page.tsx` — liste documents indexés, stats globales, re-indexation par document et globale | todo   |       |
| P10-FE-006  | Add "Intelligence documentaire" dans settings sidebar (après Webhooks)                                               | todo   |       |

### Frontend Tests

| ID          | Test File                                                         | Status | Owner |
|-------------|-------------------------------------------------------------------|--------|-------|
| P10-FT-001  | `tests/unit/stores/rag.test.ts`                                   | todo   |       |
| P10-FT-002  | `tests/components/rag/chat-widget.test.tsx`                       | todo   |       |
| P10-FT-003  | `tests/components/rag/embedding-status-badge.test.tsx`            | todo   |       |
| P10-FT-004  | `tests/e2e/rag/rag-portal-chat.spec.ts`                           | todo   |       |
| P10-FT-005  | `tests/e2e/rag/rag-admin-status.spec.ts`                          | todo   |       |

---

## Récapitulatif

| Sprint   | Semaines | Livrable principal                                  | Tasks |
|----------|----------|-----------------------------------------------------|-------|
| Sprint 31 | 66–69   | Backend RAG pipeline complet                        | 14 BE + 10 tests |
| Sprint 32 | 70–73   | Serveur MCP TypeScript (4 tools, stdio + SSE)       | 8 MCP + 5 tests  |
| Sprint 33 | 74–77   | Frontend chatbot + admin + badge GED                | 6 FE + 5 tests   |
| **Total** | **12 sem** | **v1.6.0**                                       | **48 tâches**    |
