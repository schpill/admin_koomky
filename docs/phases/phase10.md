# Phase 10 — RAG + MCP Server (Document Intelligence) (v1.6)

| Field               | Value                                                        |
|---------------------|--------------------------------------------------------------|
| **Phase**           | 10                                                           |
| **Name**            | Document Intelligence — RAG & MCP Server                    |
| **Duration**        | Weeks 66–77 (12 weeks)                                       |
| **Milestone**       | M10 — v1.6.0 Release                                        |
| **PRD Sections**    | §4.14 FR-RAG, §4.15 FR-MCP                                  |
| **Prerequisite**    | Phase 9 fully completed and validated                        |
| **Status**          | Planned                                                      |

---

## 1. Phase Objectives

| ID        | Objective                                                                                                                                  |
|-----------|--------------------------------------------------------------------------------------------------------------------------------------------|
| P10-OBJ-1 | Indexer automatiquement les documents de la GED en chunks vectoriels lors de l'upload ou du re-upload                                      |
| P10-OBJ-2 | Permettre une recherche sémantique (RAG) sur la base documentaire via l'API Gemini (embeddings `text-embedding-004`)                       |
| P10-OBJ-3 | Générer des réponses contextualisées aux questions en s'appuyant sur les chunks pertinents et `gemini-2.5-flash` comme modèle de génération |
| P10-OBJ-4 | Exposer un serveur MCP (Model Context Protocol) TypeScript consommable par Claude Desktop et tout client MCP compatible                    |
| P10-OBJ-5 | Intégrer un widget chatbot dans le portail client (Phase 6) pour répondre aux FAQ à partir des documents partagés                         |
| P10-OBJ-6 | Fournir une interface d'administration dans Koomky (statut d'indexation, re-indexation manuelle)                                           |
| P10-OBJ-7 | Maintenir une couverture de tests >= 80% backend et frontend                                                                               |

---

## 2. Choix techniques

### 2.1 Modèles Gemini

| Usage          | Modèle                  | Dimensions | Justification                                                                                       |
|----------------|-------------------------|------------|-----------------------------------------------------------------------------------------------------|
| Embeddings     | `text-embedding-004`    | 768        | Modèle dédié Google, excellent support multilingue (FR/EN), Matryoshka truncation, coût très faible |
| Génération RAG | `gemini-2.5-flash`      | —          | Rapidité + qualité sur tâches de synthèse, thinking intégré, bon rapport coût/performance           |

> **Alternative génération** : `gemini-2.5-pro` si la qualité de réponse prime sur le coût (tâches complexes, documents techniques).

### 2.2 Vector Store

**pgvector** (extension PostgreSQL) — pas d'infra supplémentaire, transactions ACID, filtrage SQL natif sur `user_id`/`client_id`, compatible avec les connexions Eloquent existantes.

```sql
-- Extension à activer (migration)
CREATE EXTENSION IF NOT EXISTS vector;
```

### 2.3 Serveur MCP

**TypeScript** avec `@modelcontextprotocol/sdk` officiel Anthropic — transport `stdio` (Claude Desktop) et `SSE` (clients HTTP). Service Docker dédié dans `mcp/`.

### 2.4 Variables d'environnement

| Variable          | Scope           | Description                                             |
|-------------------|-----------------|---------------------------------------------------------|
| `GEMINI_API_KEY`  | backend + mcp   | Clé API Google Gemini (déjà définie en prod)            |
| `MCP_API_SECRET`  | backend + mcp   | Secret partagé pour l'auth interne MCP ↔ API Koomky    |
| `MCP_KOOMKY_URL`  | mcp             | URL de l'API Koomky (ex: `http://api:8000`)             |

Tous les services Docker reçoivent ces variables via leur bloc `environment` dans `docker-compose.yml` et via un fichier `.env` à la racine.

---

## 3. Entry Criteria

- Phase 9 exit criteria 100% satisfaits.
- Tous les checks CI Phase 9 verts sur `main`.
- v1.5.0 tagué et déployé en production.
- Module GED stable (`DocumentStorageService`, `DocumentTypeDetectorService`) disponible pour réutilisation.
- Extension pgvector disponible sur l'instance PostgreSQL de production.
- `GEMINI_API_KEY` définie dans les variables d'environnement production.

---

## 4. Scope — Requirement Traceability

| Feature                                                                                | Priority | Included |
|----------------------------------------------------------------------------------------|----------|----------|
| Extraction de texte des documents GED (PDF, DOCX, TXT, MD)                           | High     | Yes      |
| Chunking des documents (512 tokens, overlap 64 tokens, par paragraphe si possible)   | High     | Yes      |
| Génération d'embeddings via `text-embedding-004` (Gemini API)                        | High     | Yes      |
| Stockage des chunks + vecteurs dans `document_chunks` (pgvector)                     | High     | Yes      |
| Job asynchrone d'indexation déclenché par `DocumentObserver` à l'upload/re-upload    | High     | Yes      |
| Recherche sémantique par similarité cosinus (top-K chunks)                           | High     | Yes      |
| Génération de réponse RAG via `gemini-2.5-flash` avec citation des sources           | High     | Yes      |
| Isolation des données : filtrage par `user_id` et optionnellement `client_id`        | High     | Yes      |
| Serveur MCP TypeScript — tool `search_documents`                                     | High     | Yes      |
| Serveur MCP TypeScript — tool `ask_question` (RAG complet)                           | High     | Yes      |
| Serveur MCP TypeScript — tool `list_topics`                                          | Medium   | Yes      |
| Serveur MCP TypeScript — tool `get_document_context`                                 | Medium   | Yes      |
| Transport `stdio` (Claude Desktop)                                                   | High     | Yes      |
| Transport `SSE` (clients HTTP / portail)                                             | Medium   | Yes      |
| Widget chatbot RAG dans le portail client (Phase 6)                                  | High     | Yes      |
| Badge statut d'indexation sur la fiche document GED                                  | Medium   | Yes      |
| Page admin : liste documents indexés, statut, re-indexation manuelle                 | Medium   | Yes      |
| Re-indexation automatique au re-upload (DocumentObserver)                            | High     | Yes      |
| Suppression des chunks à la suppression du document                                  | High     | Yes      |
| Limiter la RAG aux documents sans `client_id` ou appartenant au client connecté      | High     | Yes      |
| Logs d'usage RAG (question, chunks utilisés, latence, tokens)                       | Low      | Yes      |
| Extraction texte images via OCR (Tesseract)                                          | Low      | No       |
| Support XLSX/CSV (extraction tabulaire)                                               | Low      | No       |

---

## 5. Detailed Sprint Breakdown

### 5.1 Sprint 31 — Backend RAG Pipeline (Weeks 66–69)

#### 5.1.1 Infrastructure & Database

| Migration / Config                     | Description                                                                                                                                                                                                                                  |
|----------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `enable_pgvector_extension`            | `CREATE EXTENSION IF NOT EXISTS vector;` — migration sans down (irréversible en prod)                                                                                                                                                        |
| `create_document_chunks_table`         | id (UUID, PK), document_id (UUID, FK → documents CASCADE DELETE), user_id (UUID, FK → users CASCADE DELETE), chunk_index (INT), content (TEXT), embedding (vector(768)), token_count (INT), created_at. Index: `document_id`, `user_id`. Index HNSW sur `embedding` : `CREATE INDEX ON document_chunks USING hnsw (embedding vector_cosine_ops)` |
| `.env` / `docker-compose.yml`          | Ajouter `GEMINI_API_KEY`, `MCP_API_SECRET`, `MCP_KOOMKY_URL` dans les services `api` et `mcp`. Documenter dans `.env.example`.                                                                                                              |

#### 5.1.2 Back-end Tasks

| ID         | Task                                                                                                                                                                                                                                                               | PRD Ref      |
|------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------|
| P10-BE-001 | Create `GeminiService` — wrapper HTTP pour l'API Google Gemini :<br>— `embed(string $text): array` → appelle `text-embedding-004`, retourne vecteur float[768]<br>— `generate(string $prompt, array $context = []): string` → appelle `gemini-2.5-flash`, retourne texte<br>— Gestion des erreurs API (rate limit, quota, timeout) avec exceptions métier<br>— Config via `config/services.php` clé `gemini` (url, key, embedding_model, generation_model) | FR-RAG-001   |
| P10-BE-002 | Create `DocumentTextExtractorService` — extraction texte brut selon MIME :<br>— `application/pdf` → `smalot/pdfparser` (paragraphe par paragraphe)<br>— `application/vnd.openxmlformats-officedocument.wordprocessingml.document` (DOCX) → `PhpOffice/PhpWord`<br>— `text/plain`, `text/markdown`, `text/html` → stripping HTML + retour direct<br>— Autres MIME → retourne `null` (non indexable, logué)<br>— Retourne `string\|null` | FR-RAG-002   |
| P10-BE-003 | Create `DocumentChunkService` — découpage en chunks :<br>— Stratégie : découpage par double-saut de ligne (paragraphes), puis re-fusion si < 100 tokens, split si > 512 tokens<br>— Overlap : 64 tokens entre chunks consécutifs<br>— Tokenisation approximative : `mb_str_split` + comptage mots × 1.3<br>— Retourne `array<int, array{index: int, content: string, token_count: int}>` | FR-RAG-002   |
| P10-BE-004 | Create `DocumentEmbeddingService` — pipeline complet :<br>— `indexDocument(Document $document): void` — extrait, découpe, embed (via `GeminiService`), stocke dans `document_chunks`, set `Document::embedding_status = 'indexed'`<br>— `deleteDocumentChunks(Document $document): void` — supprime tous les chunks, set `embedding_status = null`<br>— `reindexDocument(Document $document): void` — supprime + réindexe<br>— Gère `embedding_status` : `'pending'`, `'indexing'`, `'indexed'`, `'failed'`, `null` (non indexable) | FR-RAG-001   |
| P10-BE-005 | Create `ProcessDocumentEmbeddingJob` — job async :<br>— `ShouldQueue`, queue `embeddings`, timeout 120s, tries 3, backoff [30, 120, 300]<br>— Reçoit `Document $document`<br>— Appelle `DocumentEmbeddingService::indexDocument()`<br>— En cas d'échec : set `embedding_status = 'failed'`, log erreur | FR-RAG-001   |
| P10-BE-006 | Update `DocumentObserver` :<br>— `created` : si MIME indexable → dispatch `ProcessDocumentEmbeddingJob`<br>— `updated` (re-upload détecté via `wasChanged('storage_path')`) → dispatch `ProcessDocumentEmbeddingJob` (reindexation)<br>— `deleted` → appelle `DocumentEmbeddingService::deleteDocumentChunks()` synchrone | FR-RAG-001   |
| P10-BE-007 | Add `embedding_status` column to `documents` table (migration) : `VARCHAR(20) nullable default null`. Cast + fillable dans `Document` model. | FR-RAG-001   |
| P10-BE-008 | Create `VectorSearchService` :<br>— `search(string $query, string $userId, int $topK = 5, ?string $clientId = null): Collection` — embed la query via `GeminiService`, exécute requête pgvector :<br>`SELECT c.*, 1 - (c.embedding <=> ?) AS score FROM document_chunks c JOIN documents d ON d.id = c.document_id WHERE d.user_id = ? [AND d.client_id = ?] ORDER BY c.embedding <=> ? LIMIT ?`<br>— Retourne collection de chunks avec score de similarité et métadonnées document | FR-RAG-003   |
| P10-BE-009 | Create `RagService` :<br>— `answer(string $question, string $userId, ?string $clientId = null): array` — pipeline RAG complet :<br>  1. `VectorSearchService::search()` → top-5 chunks<br>  2. Construit le prompt avec contexte + question<br>  3. `GeminiService::generate()` → réponse<br>  4. Retourne `{answer: string, sources: [{document_id, title, chunk_index, score}], tokens_used: int, latency_ms: int}`<br>— Prompt système : "Tu es un assistant basé sur les documents fournis. Réponds uniquement à partir du contexte ci-dessous. Si la réponse n'est pas dans les documents, dis-le clairement. Cite les sources." | FR-RAG-004   |
| P10-BE-010 | Create `RagController` — endpoints RAG :<br>— `POST /api/v1/rag/ask` — body: `{question: string, client_id?: string}` → `RagService::answer()` → réponse JSON<br>— `GET /api/v1/rag/search` — query: `{q: string, limit?: int, client_id?: string}` → `VectorSearchService::search()` → chunks + scores<br>— `GET /api/v1/rag/status` — liste documents avec `embedding_status`, pagination<br>— `POST /api/v1/rag/reindex/{documentId}` — re-indexation manuelle (admin) | FR-RAG-004   |
| P10-BE-011 | Create `AskRagRequest` — validation : `question` requis, string, max 1000 chars ; `client_id` optionnel UUID valide et appartenant à l'user | FR-RAG-004   |
| P10-BE-012 | Create `RagUsageLog` model + migration — log des appels RAG : user_id, question (TEXT), chunks_used (JSONB), tokens_used (INT), latency_ms (INT), created_at. Index : user_id, created_at. | FR-RAG-005   |

#### 5.1.3 Back-end Tests (TDD)

| Test File                                                              | Test Cases                                                                                                                                          |
|------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/Unit/Services/GeminiServiceTest.php`                            | embed() retourne vecteur float[768], generate() retourne string, erreur API rate-limit lève exception métier, config correctement injectée          |
| `tests/Unit/Services/DocumentTextExtractorServiceTest.php`             | Extrait texte d'un PDF simple, DOCX, TXT ; MIME non supporté retourne null ; fichier vide retourne null                                             |
| `tests/Unit/Services/DocumentChunkServiceTest.php`                     | Texte court → 1 chunk, texte long → N chunks, paragraphes respectés, overlap appliqué, chunks > 512 tokens re-splitté                              |
| `tests/Unit/Services/DocumentEmbeddingServiceTest.php`                 | indexDocument crée N chunks en base, deleteDocumentChunks supprime tous les chunks, reindexDocument = delete + index, embedding_status mis à jour   |
| `tests/Unit/Services/VectorSearchServiceTest.php`                      | search() retourne les N chunks les plus proches (mock pgvector), filtrage user_id, filtrage client_id optionnel, score normalisé entre 0 et 1       |
| `tests/Unit/Services/RagServiceTest.php`                               | answer() retourne shape correct, sources citées correspondent aux chunks, prompt construit correctement, aucun chunk → réponse "documents insuffisants" |
| `tests/Unit/Jobs/ProcessDocumentEmbeddingJobTest.php`                  | Job dispatch indexDocument, échec → embedding_status = 'failed', retry configuré correctement                                                       |
| `tests/Feature/Rag/RagAskTest.php`                                     | POST /api/v1/rag/ask → 200 avec answer + sources ; question vide → 422 ; question > 1000 chars → 422 ; client_id non-owned → 403                  |
| `tests/Feature/Rag/RagSearchTest.php`                                  | GET /api/v1/rag/search → 200 avec chunks ; filtre client_id ; pagination limit                                                                      |
| `tests/Feature/Rag/RagStatusTest.php`                                  | GET /api/v1/rag/status → liste documents avec statut d'indexation ; POST reindex/{id} → job dispatché                                               |

---

### 5.2 Sprint 32 — Serveur MCP TypeScript (Weeks 70–73)

#### 5.2.1 Infrastructure MCP

Structure du service :

```
mcp/
├── src/
│   ├── index.ts          # Entrypoint — démarre le serveur MCP
│   ├── server.ts         # Définition des tools MCP
│   ├── koomkyClient.ts   # Client HTTP vers l'API Koomky
│   └── tools/
│       ├── searchDocuments.ts
│       ├── askQuestion.ts
│       ├── listTopics.ts
│       └── getDocumentContext.ts
├── Dockerfile
├── package.json
└── tsconfig.json
```

Service Docker à ajouter dans `docker-compose.yml` :

```yaml
mcp:
  build: ./mcp
  environment:
    - GEMINI_API_KEY=${GEMINI_API_KEY}
    - MCP_KOOMKY_URL=http://api:8000
    - MCP_API_SECRET=${MCP_API_SECRET}
  depends_on:
    - api
  ports:
    - "3100:3100"   # SSE transport
```

#### 5.2.2 MCP Tasks

| ID         | Task                                                                                                                                                                                                                                       | PRD Ref    |
|------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------|
| P10-MCP-001 | Initialiser projet TypeScript dans `mcp/` : `@modelcontextprotocol/sdk`, `axios`, `zod`, `typescript`, `tsx`. Config `tsconfig.json` (strict, ESM). `Dockerfile` multi-stage (build + runtime node:20-slim). | FR-MCP-001 |
| P10-MCP-002 | Create `koomkyClient.ts` — client HTTP authentifié vers API Koomky :<br>— Auth : header `X-MCP-Secret: ${MCP_API_SECRET}` + `Authorization: Bearer {PAT}` selon le contexte<br>— Méthodes : `ask(question, userId, clientId?)`, `search(query, userId, limit?)`, `listTopics(userId)`, `getDocumentContext(documentId, userId)` | FR-MCP-001 |
| P10-MCP-003 | Create MCP tool `search_documents` :<br>— Input schema : `{query: string, limit?: number (1-20, défaut 5), client_id?: string}`<br>— Action : `koomkyClient.search()` → formate résultats (titre, extrait, score, lien)<br>— Description : "Recherche sémantique dans les documents de la GED" | FR-MCP-002 |
| P10-MCP-004 | Create MCP tool `ask_question` :<br>— Input schema : `{question: string, client_id?: string}`<br>— Action : `koomkyClient.ask()` → retourne réponse + sources formatées<br>— Description : "Pose une question et obtiens une réponse basée sur les documents indexés (RAG)" | FR-MCP-002 |
| P10-MCP-005 | Create MCP tool `list_topics` :<br>— Input schema : `{}`<br>— Action : agrège les tags + catégories des documents indexés<br>— Description : "Liste les sujets et thématiques couverts par les documents disponibles" | FR-MCP-002 |
| P10-MCP-006 | Create MCP tool `get_document_context` :<br>— Input schema : `{document_id: string}`<br>— Action : retourne le contenu chunké d'un document spécifique<br>— Description : "Récupère le contenu complet d'un document par son identifiant" | FR-MCP-002 |
| P10-MCP-007 | Implémenter transport `stdio` (Claude Desktop) + transport `SSE` sur port 3100 (clients HTTP)<br>— `stdio` : lecture stdin / écriture stdout (protocole MCP standard)<br>— `SSE` : endpoint `GET /sse` pour la connexion, `POST /messages` pour les requêtes<br>— Le même `server.ts` gère les deux transports selon la variable `MCP_TRANSPORT` (`stdio` ou `sse`) | FR-MCP-001 |
| P10-MCP-008 | Ajouter middleware d'auth Koomky : nouveau endpoint `GET /api/v1/mcp/token` (PAT scope `mcp:read`) pour que le serveur MCP obtienne un token utilisateur. Middleware `McpScopeGuard` (réutilise le pattern Phase 7). | FR-MCP-003 |
| P10-MCP-009 | Documenter la configuration Claude Desktop dans `docs/mcp/claude-desktop.md` :<br>— Config JSON à ajouter dans `claude_desktop_config.json`<br>— Commande de démarrage du transport stdio<br>— Exemple de conversation avec les tools | FR-MCP-002 |

#### 5.2.3 MCP Tests

| Test File                                             | Test Cases                                                                                                              |
|-------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------|
| `mcp/src/__tests__/koomkyClient.test.ts`              | ask() appelle le bon endpoint, search() passe les bons paramètres, erreur 401 lève exception, erreur réseau retry       |
| `mcp/src/__tests__/tools/searchDocuments.test.ts`     | Formate correctement les résultats, limit respecté, client_id transmis                                                  |
| `mcp/src/__tests__/tools/askQuestion.test.ts`         | Retourne answer + sources formatées, aucun chunk → message explicite                                                    |
| `mcp/src/__tests__/tools/listTopics.test.ts`          | Agrège tags + catégories, dédupliqués, triés alphabétiquement                                                           |
| `mcp/src/__tests__/server.test.ts`                    | Tous les tools enregistrés, input invalide → erreur MCP schema, auth manquante → erreur 401                             |

---

### 5.3 Sprint 33 — Frontend Chatbot & Administration (Weeks 74–77)

#### 5.3.1 Front-end Tasks

| ID          | Task                                                                                                                                                                                                                                                | PRD Ref    |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------|
| P10-FE-001  | Create `stores/rag.ts` Zustand store :<br>— State: messages (historique conversation), loading, error, sources (derniers chunks utilisés)<br>— Actions: `askQuestion(question, clientId?)`, `searchDocuments(query)`, `clearHistory()` | §6.2.2     |
| P10-FE-002  | Create `components/rag/chat-widget.tsx` — widget chatbot intégré au portail client :<br>— Interface de chat (bulle en bas à droite)<br>— Historique des échanges (question + réponse + sources cliquables)<br>— Indicateur de chargement (streaming simulé)<br>— Disclaimer "Basé sur les documents partagés"<br>— Vide l'historique à la déconnexion | FR-RAG-004 |
| P10-FE-003  | Intégrer `chat-widget.tsx` dans `app/(portal)/layout.tsx` (portail client Phase 6) — visible uniquement si des documents RAG sont disponibles pour le client | FR-RAG-004 |
| P10-FE-004  | Update `app/(dashboard)/documents/[id]/page.tsx` (fiche document GED) :<br>— Ajouter badge `embedding_status` : `indexed` (vert ✓), `indexing` / `pending` (orange, spinner), `failed` (rouge, bouton "Relancer"), `null` (gris "Non indexable")<br>— Bouton "Relancer l'indexation" → `POST /api/v1/rag/reindex/{id}` | FR-RAG-001 |
| P10-FE-005  | Create `app/(dashboard)/settings/rag/page.tsx` — page admin RAG :<br>— Liste des documents indexés (titre, type, statut, date indexation, nombre de chunks)<br>— Filtre statut, search<br>— Bouton "Re-indexer tout" (confirmation)<br>— Bouton "Re-indexer" par document<br>— Stats globales : documents indexés / total, chunks totaux, dernière indexation | FR-RAG-005 |
| P10-FE-006  | Add "RAG / Intelligence documentaire" entry dans settings sidebar (après Webhooks) | FR-RAG-005 |

#### 5.3.2 Front-end Tests

| Test File                                                         | Test Cases                                                                                                              |
|-------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------|
| `tests/unit/stores/rag.test.ts`                                   | askQuestion ajoute question + réponse à l'historique, loading géré, error géré, clearHistory vide l'état              |
| `tests/components/rag/chat-widget.test.tsx`                       | Rendu initial vide, envoi question déclenche askQuestion, réponse affichée avec sources, spinner pendant loading        |
| `tests/components/rag/embedding-status-badge.test.tsx`            | Badge correct pour chaque statut (indexed/pending/failed/null), bouton relancer visible pour failed                     |
| `tests/e2e/rag/rag-portal-chat.spec.ts`                           | Portail client : widget visible, poser question → réponse avec sources, historique conservé, déconnexion vide historique |
| `tests/e2e/rag/rag-admin-status.spec.ts`                          | Admin : liste documents indexés, clic re-indexer déclenche job, badge passe en pending                                  |

---

## 6. API Endpoints Delivered in Phase 10

| Method | Endpoint                                    | Controller / Action                  |
|--------|---------------------------------------------|--------------------------------------|
| POST   | `/api/v1/rag/ask`                           | RagController@ask                    |
| GET    | `/api/v1/rag/search`                        | RagController@search                 |
| GET    | `/api/v1/rag/status`                        | RagController@status                 |
| POST   | `/api/v1/rag/reindex/{documentId}`          | RagController@reindex                |
| GET    | `/api/v1/mcp/token`                         | McpTokenController@issue             |

---

## 7. Infrastructure Changes

| Composant               | Changement                                                                                      |
|-------------------------|-------------------------------------------------------------------------------------------------|
| PostgreSQL              | Activation extension `vector` (pgvector). Index HNSW sur `document_chunks.embedding`.          |
| `docker-compose.yml`    | Nouveau service `mcp` (image Node 20, port 3100, env GEMINI_API_KEY + MCP_API_SECRET).         |
| `.env` / `.env.example` | Ajout `GEMINI_API_KEY`, `MCP_API_SECRET`, `MCP_KOOMKY_URL`.                                    |
| Queue                   | Nouvelle queue `embeddings` (priorité basse, worker séparé recommandé en prod).                |
| `composer.json`         | Dépendances : `smalot/pdfparser`, `phpoffice/phpword`.                                         |
| `mcp/package.json`      | Dépendances : `@modelcontextprotocol/sdk`, `axios`, `zod`.                                     |

---

## 8. Non-Functional Requirements

| ID          | Requirement                                                                                                   |
|-------------|---------------------------------------------------------------------------------------------------------------|
| P10-NFR-001 | Indexation asynchrone : l'upload d'un document ne doit pas être bloqué par l'embedding (job en queue)         |
| P10-NFR-002 | Latence RAG <= 5s pour une question standard (top-5 chunks + génération Gemini Flash)                         |
| P10-NFR-003 | Isolation stricte : un user ne peut interroger que ses propres documents                                       |
| P10-NFR-004 | Isolation client dans le portail : seuls les documents sans `client_id` ou appartenant au client sont indexés |
| P10-NFR-005 | En cas d'indisponibilité de l'API Gemini, l'upload continue normalement, le job sera retenté 3 fois           |
| P10-NFR-006 | Coût embeddings : `text-embedding-004` à ~$0.00004/1K tokens → budget ~$2 pour 50 000 chunks                 |

---

## 9. Exit Criteria

- [ ] pgvector activé, migration `document_chunks` jouée en prod
- [ ] `ProcessDocumentEmbeddingJob` indexe un PDF de test avec succès
- [ ] `POST /api/v1/rag/ask` retourne une réponse cohérente avec sources sur un document réel
- [ ] Serveur MCP répond aux 4 tools depuis Claude Desktop (transport stdio)
- [ ] Widget chatbot fonctionnel dans le portail client
- [ ] Badge `embedding_status` mis à jour dans la fiche document GED
- [ ] Couverture tests backend >= 80%, frontend >= 80%
- [ ] PHPStan level 8 sans erreur sur les nouveaux services
- [ ] CI verte sur `main`
- [ ] Tag `v1.6.0` publié
