# Phase 11 — Catalogue Produits/Services + Campagnes IA (v1.7)

| Field               | Value                                                        |
|---------------------|--------------------------------------------------------------|
| **Phase**           | 11                                                           |
| **Name**            | Catalogue Produits/Services — Ventes & Campagnes IA          |
| **Duration**        | Weeks 78–89 (12 weeks)                                       |
| **Milestone**       | M11 — v1.7.0 Release                                        |
| **PRD Sections**    | §4.16 FR-CAT (nouveau)                                       |
| **Prerequisite**    | Phase 10 fully completed and validated                        |
| **Status**          | Planned                                                      |

---

## 1. Phase Objectives

| ID        | Objective                                                                                                                                  |
|-----------|--------------------------------------------------------------------------------------------------------------------------------------------|
| P11-OBJ-1 | Permettre la création et la gestion d'un catalogue de produits/services réutilisables (formations, prestations packagées, abonnements)     |
| P11-OBJ-2 | Intégrer le catalogue dans les formulaires de devis et factures via un picker de ligne rapide                                              |
| P11-OBJ-3 | Générer automatiquement une campagne email de prospection (objet + corps HTML) via GeminiService à partir d'un produit catalogue           |
| P11-OBJ-4 | Tracker les ventes par produit (déclenchement automatique via InvoiceObserver + QuoteObserver sur statut `paid` / `accepted`)              |
| P11-OBJ-5 | Fournir des analytics par produit : CA total, nombre de ventes, taux de conversion devis → facture, évolution dans le temps               |
| P11-OBJ-6 | Exposer un widget "Top Produits du mois" sur le dashboard principal                                                                       |
| P11-OBJ-7 | Maintenir une couverture de tests >= 80% backend et frontend                                                                               |

---

## 2. Choix techniques

### 2.1 Pas d'infrastructure supplémentaire

Toutes les briques nécessaires sont déjà en place :

| Besoin | Brique existante | Phase d'origine |
|--------|-----------------|-----------------|
| Génération IA email | `GeminiService::generate()` | Phase 10 |
| Indexation full-text produits | Meilisearch + Laravel Scout | Phase 2 |
| Campagnes email | `Campaign`, `CampaignTemplate`, `Segment` | Phase 3 |
| Lignes de devis/factures | `LineItem` (polymorphique) | Phase 1 |
| Observation des factures | `InvoiceObserver` | Phase 1 |
| Export GDPR | `DataExportService` | Phase 4 |
| Webhooks | `WebhookDispatchService` | Phase 7 |
| Multi-devise | `CurrencyConversionService` | Phase 5 |

### 2.2 Modèle de données

Quatre migrations sont nécessaires :

| Migration | Description |
|-----------|-------------|
| `create_products_table` | Table principale — UUID PK, user_id, name, slug, type, description, short_description, price, price_type, currency_code, vat_rate, duration, duration_unit, sku, tags (JSON), is_active, meta (JSON), timestamps, softDeletes |
| `create_product_sales_table` | Suivi des ventes — product_id, user_id, client_id (nullable), invoice_id (nullable), quote_id (nullable), quantity, unit_price, total_price, currency_code, status, sold_at, notes |
| `create_product_campaigns_table` | Pivot produit ↔ campagne — product_id, campaign_id, generation_model, generated_at |
| `add_product_id_to_line_items_table` | Colonne `product_id UUID nullable FK` sur la table existante `line_items` |

### 2.3 Génération email IA

Le `ProductCampaignGeneratorService` appelle `GeminiService::generate()` avec un prompt structuré demandant un JSON `{subject, html_body}`. Le parsing inclut :
- Extraction du JSON même s'il est entouré d'un bloc markdown (` ```json `)
- Fallback sur un template Blade générique si le JSON est invalide ou absent
- Logging dans une table `product_campaign_generation_logs` (product_id, campaign_id, model, tokens_used, latency_ms, generated_at)

### 2.4 Déclenchement automatique des ventes

| Événement | Observer | Action |
|-----------|----------|--------|
| `Invoice` passe en `paid` | `InvoiceObserver::updated()` | Crée `ProductSale` pour chaque `LineItem` avec `product_id` non null |
| `Quote` passe en `accepted` | `QuoteObserver::updated()` | Crée `ProductSale` (status `pending`) pour chaque `LineItem` avec `product_id` non null |

Un guard `unique_on_invoice_line_item` empêche le double-comptage si l'observer est déclenché plusieurs fois.

### 2.5 Types de produits

| Type | Description | Exemple |
|------|-------------|---------|
| `service` | Prestation sur-mesure | Développement d'une API REST |
| `training` | Formation packagée | Formation Laravel Avancé — 10 jours |
| `product` | Livrable tangible | Thème WordPress personnalisé |
| `subscription` | Abonnement récurrent | Maintenance mensuelle — forfait 5h |

### 2.6 Price types

| price_type | Description |
|-----------|-------------|
| `fixed` | Prix forfaitaire total |
| `hourly` | Tarif horaire |
| `daily` | Tarif journalier |
| `per_unit` | Prix à l'unité |

---

## 3. Entry Criteria

- Phase 10 exit criteria 100% satisfaits.
- Tous les checks CI Phase 10 verts sur `main`.
- v1.6.0 tagué et déployé en production.
- `GeminiService` stable et opérationnel en production.
- `InvoiceObserver` et `QuoteObserver` existants disponibles pour extension.
- `GEMINI_API_KEY` définie dans les variables d'environnement production.

---

## 4. Scope — Requirement Traceability

| Feature                                                                                            | Priority | Included |
|----------------------------------------------------------------------------------------------------|----------|----------|
| CRUD catalogue produits (create, read, update, soft-delete, restore)                               | High     | Yes      |
| Types produits : service, training, product, subscription                                          | High     | Yes      |
| Price types : fixed, hourly, daily, per_unit                                                       | High     | Yes      |
| Archivage (soft delete) — un produit avec ventes ne peut pas être hard-deleted                     | High     | Yes      |
| Index Meilisearch produits (name, description, tags — filterable: type, is_active)                | High     | Yes      |
| Recherche globale Ctrl+K étendue aux produits                                                      | High     | Yes      |
| Picker produit dans les formulaires Invoice et Quote (line-item-product-picker)                    | High     | Yes      |
| Colonne `product_id` nullable sur `line_items` — pré-remplit description, prix, TVA               | High     | Yes      |
| Génération campagne email via GeminiService (ProductCampaignGeneratorService)                      | High     | Yes      |
| Wizard frontend 3 étapes : sélection segment → génération IA → révision et création draft          | High     | Yes      |
| Campagne créée en statut `draft` — jamais envoyée sans validation manuelle                         | High     | Yes      |
| Fallback template Blade si JSON Gemini invalide                                                     | High     | Yes      |
| Logging des générations IA (model, tokens, latency) dans `product_campaign_generation_logs`        | Medium   | Yes      |
| ProductSale auto-créée via InvoiceObserver (facture `paid`)                                        | High     | Yes      |
| ProductSale auto-créée via QuoteObserver (devis `accepted`, status `pending`)                      | Medium   | Yes      |
| Guard contre le double-comptage (unique_on_line_item)                                              | High     | Yes      |
| Analytics produit : CA total, nb ventes, taux conversion, évolution mensuelle                      | High     | Yes      |
| Analytics globales : top produits, CA total catalogue, nb ventes all-time                          | Medium   | Yes      |
| Dashboard widget "Top Produits du mois" (3 lignes max)                                             | Medium   | Yes      |
| Événement webhook `product.sold` dispatché via WebhookDispatchService                              | Low      | Yes      |
| Inclusion `ProductSale` dans l'export GDPR (DataExportService)                                     | High     | Yes      |
| Multi-devise : affichage du prix produit converti dans la devise active (CurrencyConversionService) | Medium   | Yes      |
| Sidebar entry "Catalogue" avec icône Package                                                        | High     | Yes      |
| Option segment rapide "Tous mes leads qualifiés" dans le wizard (sans segment pré-créé)             | High     | Yes      |
| Alerte UX wizard : "Campagne créée en brouillon — relisez avant d'envoyer"                         | High     | Yes      |
| Validation wizard : avertissement si segment résulte en 0 destinataire                             | Medium   | Yes      |
| Page `/products` — liste cards avec filtres actif/archivé et type                                  | High     | Yes      |
| Page `/products/[id]` — onglets : Détails / Ventes / Campagnes / Analytics                        | High     | Yes      |
| Import produits CSV                                                                                 | Low      | No       |
| API publique produits (PAT scopes)                                                                  | Low      | No       |
| Portail client : affichage du catalogue                                                             | Low      | No       |

---

## 5. Detailed Sprint Breakdown

### 5.1 Sprint 34 — Backend Fondations (Weeks 78–81)

#### 5.1.1 Infrastructure & Database

| Migration / Config                              | Description                                                                                                                                                                                                                                     |
|-------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `create_products_table`                         | id (UUID PK), user_id (FK → users CASCADE), name (VARCHAR 255), slug (VARCHAR 255 UNIQUE), type (ENUM service/training/product/subscription), description (TEXT nullable), short_description (VARCHAR 500 nullable), price (DECIMAL 10,2), price_type (ENUM fixed/hourly/daily/per_unit), currency_code (CHAR 3 DEFAULT 'EUR'), vat_rate (DECIMAL 5,2 DEFAULT 20.00), duration (INT nullable), duration_unit (ENUM hours/days/weeks/months nullable), sku (VARCHAR 100 nullable), tags (JSON nullable), is_active (BOOLEAN DEFAULT true), meta (JSON nullable), timestamps, softDeletes. Index: `(user_id, is_active)`, `(user_id, type)`. |
| `add_product_id_to_line_items_table`            | Ajouter colonne `product_id UUID nullable FK → products SET NULL` sur la table `line_items` existante. Index `product_id`. Migration sûre (nullable, aucun back-fill requis).                                                                    |
| `create_product_sales_table`                    | id (UUID PK), product_id (FK → products CASCADE), user_id (FK → users CASCADE), client_id (UUID nullable FK → clients SET NULL), invoice_id (UUID nullable FK → invoices SET NULL), quote_id (UUID nullable FK → quotes SET NULL), quantity (DECIMAL 8,2 DEFAULT 1), unit_price (DECIMAL 10,2), total_price (DECIMAL 10,2), currency_code (CHAR 3), status (ENUM pending/confirmed/delivered/cancelled/refunded), sold_at (TIMESTAMP nullable), notes (TEXT nullable), timestamps. Index: `(product_id, status)`, `(user_id, sold_at)`. Contrainte UNIQUE `(invoice_id, product_id)` WHERE invoice_id IS NOT NULL pour éviter le double-comptage. |
| `create_product_campaigns_table`                | id (UUID PK), product_id (FK → products CASCADE), campaign_id (FK → campaigns CASCADE), generation_model (VARCHAR 100 nullable), generated_at (TIMESTAMP nullable), timestamps. Index: `product_id`, `campaign_id`. |
| `create_product_campaign_generation_logs_table` | id (UUID PK), product_id (FK → products CASCADE), campaign_id (UUID nullable FK → campaigns SET NULL), user_id (FK → users CASCADE), model (VARCHAR 100), tokens_used (INT nullable), latency_ms (INT nullable), success (BOOLEAN), error_message (TEXT nullable), generated_at TIMESTAMP. Index: `(user_id, generated_at)`. |

#### 5.1.2 Backend — Modèles & Policies

| ID           | Task                                                                                                                                                                                                                                                                      | PRD Ref    |
|--------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------|
| P11-BE-001   | Create `Product` model — Traits : `HasUuids`, `HasFactory`, `SoftDeletes`, `Searchable`. Fillable complet. Casts : price/vat_rate/duration → numeric, tags → array, meta → array. Relations : `user()`, `sales()`, `campaigns()` (BelongsToMany via product_campaigns), `lineItems()`. Scopes : `active()`, `archived()`, `byType(string)`, `byTag(string)`. `toSearchableArray()` : id, user_id, name, type, description, short_description, tags, price, price_type, currency_code, is_active, created_at. | FR-CAT-001 |
| P11-BE-002   | Create `ProductSale` model — Traits : `HasUuids`, `HasFactory`. Fillable + casts (quantity/unit_price/total_price → decimal, sold_at → datetime). Relations : `product()`, `user()`, `client()`, `invoice()`, `quote()`. Scopes : `byStatus(string)`, `confirmed()`, `forPeriod(Carbon, Carbon)`. | FR-CAT-002 |
| P11-BE-003   | Create `ProductCampaignGenerationLog` model — Traits : `HasUuids`. Fillable + casts. Relations : `product()`, `campaign()`, `user()`. | FR-CAT-003 |
| P11-BE-004   | Update `LineItem` model — ajouter `product_id` au fillable. Ajouter relation `product(): BelongsTo<Product, LineItem>`. | FR-CAT-001 |
| P11-BE-005   | Create `ProductPolicy` — ownership standard (`user_id` match). Méthodes : `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`. Enregistrement dans `AuthServiceProvider`. | FR-CAT-001 |
| P11-BE-006   | Create `ProductSalePolicy` — ownership via `product->user_id`. Méthodes : `viewAny`, `view`. (Les ventes sont créées automatiquement, pas par l'utilisateur direct.) | FR-CAT-002 |
| P11-BE-007   | Extend `InvoiceObserver::updated()` — si `status` change vers `paid` : pour chaque `LineItem` avec `product_id` non null, créer `ProductSale` (status `confirmed`, sold_at = now) avec guard `firstOrCreate` sur `(invoice_id, product_id)` pour éviter le double-comptage. Dispatcher webhook `product.sold` via `WebhookDispatchService`. | FR-CAT-002 |
| P11-BE-008   | Extend `QuoteObserver::updated()` — si `status` change vers `accepted` : même logique que P11-BE-007, `ProductSale` avec status `pending` et `quote_id` renseigné. | FR-CAT-002 |
| P11-BE-009   | Create `ProductFactory` — faker pour tous les champs, états `active()`, `archived()`, par type (`training()`, `subscription()`). | FR-CAT-001 |
| P11-BE-010   | Create `ProductSaleFactory` — faker avec états `confirmed()`, `pending()`, `cancelled()`. | FR-CAT-002 |

#### 5.1.3 Backend Tests — Modèles & Observers (TDD)

| Test File                                                              | Test Cases                                                                                                                                          |
|------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/Unit/Models/ProductTest.php`                                    | Relations correctes, scopes active/archived/byType/byTag, toSearchableArray complet, softDelete/restore                                             |
| `tests/Feature/Products/ProductCrudTest.php`                           | index (liste paginée, filtres type/is_active), store (201 + slug auto-généré), show (200), update (200), destroy (soft-delete 204), restore (200), ownership 403 sur produit d'un autre user |
| `tests/Feature/Products/ProductObserverTest.php`                       | InvoiceObserver crée ProductSale sur `paid`, pas de double-comptage (idempotent), QuoteObserver crée ProductSale sur `accepted`, LineItem sans product_id ignoré, webhook `product.sold` dispatché |

---

### 5.2 Sprint 35 — Services & API (Weeks 82–85)

#### 5.2.1 Backend — Services

| ID           | Task                                                                                                                                                                                                                                                                      | PRD Ref    |
|--------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------|
| P11-BE-011   | Create `ProductCampaignGeneratorService` — injection `GeminiService`. Méthode principale `generate(Product $product, Segment $segment, User $user): Campaign` :<br>1. Construction du prompt (nom, type, description, prix, durée, expéditeur) demandant JSON `{subject, html_body}`<br>2. Appel `GeminiService::generate()`<br>3. Parsing JSON : regex extraction si bloc markdown, validation keys, fallback template Blade si invalide<br>4. Création `CampaignTemplate` (draft, type email)<br>5. Création `Campaign` (status `draft`, segment_id, template_id)<br>6. Attach campaign ↔ product via table pivot<br>7. Log `ProductCampaignGenerationLog`<br>8. Retourne la `Campaign` créée | FR-CAT-003 |
| P11-BE-012   | Create template Blade fallback `resources/views/mail/campaign/product-fallback.blade.php` — template HTML générique en français avec variables `{{first_name}}`, `{{company}}`, `{{product_name}}`, `{{product_price}}`, `{{unsubscribe_link}}`. Rendu via `Blade::render()`. | FR-CAT-003 |
| P11-BE-013   | Create `ProductAnalyticsService` — méthodes :<br>— `productStats(Product $product, ?Carbon $from, ?Carbon $to): array` → `{total_revenue, total_sales, avg_order_value, conversion_rate, monthly_breakdown}`<br>— `globalStats(User $user, ?Carbon $from, ?Carbon $to): array` → `{top_products[], total_revenue, total_sales}`<br>— `topProducts(User $user, int $limit = 5): Collection` → produits triés par CA sur le mois en cours | FR-CAT-004 |
| P11-BE-014   | Extend `DataExportService::exportAll()` — inclure les `ProductSale` de l'utilisateur dans le ZIP d'export GDPR (fichier `product_sales.csv` : product_name, client_name, quantity, total_price, currency, status, sold_at). | FR-CAT-005 |
| P11-BE-015   | Extend `WebhookDispatchService` — ajouter événement `product.sold` avec payload `{product_id, product_name, client_id, sale_id, total_price, currency_code, sold_at}`. | FR-CAT-005 |

#### 5.2.2 Backend — Controllers & Routes

| ID           | Task                                                                                                                                                                                                                                                                      | PRD Ref    |
|--------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------|
| P11-BE-016   | Create `ProductController` — méthodes : `index` (paginate 15, filtres type/is_active/search, `with(['sales' => fn → count])`), `store`, `show` (with sales count + campaigns count), `update`, `destroy` (soft delete), `restore`, `sales` (paginate 15, filtre status/période), `productAnalytics` (délègue à `ProductAnalyticsService`), `globalAnalytics`. Utilise `ProductPolicy`. | FR-CAT-001 |
| P11-BE-017   | Create `ProductCampaignController` — méthode `generate(StoreProductCampaignRequest $request, Product $product): JsonResponse` : valide `segment_id` (owned), appelle `ProductCampaignGeneratorService::generate()`, retourne la campagne créée (201 avec redirect vers `/campaigns/{id}`). | FR-CAT-003 |
| P11-BE-018   | Create `StoreProductRequest` — règles : `name` requis max 255, `type` ENUM, `price` numeric min 0, `price_type` ENUM, `vat_rate` numeric 0–100, `currency_code` 3 chars, `duration` integer nullable min 1, `duration_unit` ENUM nullable, `description` max 5000, `short_description` max 500, `sku` max 100, `tags` array of strings, `is_active` boolean. | FR-CAT-001 |
| P11-BE-019   | Create `UpdateProductRequest` — mêmes règles que `StoreProductRequest` mais tous les champs optionnels (`sometimes`). | FR-CAT-001 |
| P11-BE-020   | Create `StoreProductCampaignRequest` — règles : `segment_id` requis UUID, existe dans `segments`, appartient à l'utilisateur authentifié. | FR-CAT-003 |
| P11-BE-021   | Register routes dans `routes/api.php` (groupe `v1` authentifié) :<br>`GET /products`, `POST /products`, `GET /products/analytics`<br>`GET /products/{product}`, `PATCH /products/{product}`, `DELETE /products/{product}`, `POST /products/{product}/restore`<br>`POST /products/{product}/campaigns/generate`<br>`GET /products/{product}/sales`, `GET /products/{product}/analytics` | FR-CAT-001 |
| P11-BE-022   | Configure index Meilisearch pour `Product` : searchable attributes (`name`, `description`, `short_description`, `tags`), filterable (`user_id`, `type`, `is_active`), sortable (`created_at`, `name`, `price`). Scout sync dans `Product::toSearchableArray()`. | FR-CAT-001 |
| P11-BE-023   | Extend `DashboardService` — ajouter méthode `topProductsWidget(User $user): array` → top 3 produits du mois courant avec CA et nb ventes. | FR-CAT-004 |

#### 5.2.3 Backend Tests — Services & API (TDD)

| Test File                                                                         | Test Cases                                                                                                                                                           |
|-----------------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `tests/Unit/Services/ProductCampaignGeneratorServiceTest.php`                     | generate() crée CampaignTemplate + Campaign en draft, parsing JSON valide, fallback si JSON invalide, fallback si réponse Gemini vide, log création dans ProductCampaignGenerationLog, pivot product_campaigns créé, mock GeminiService |
| `tests/Unit/Services/ProductAnalyticsServiceTest.php`                             | productStats() retourne shape correct, totalRevenue somme les ventes confirmed, conversion_rate calculé, monthly_breakdown 12 mois, globalStats top produits triés par CA |
| `tests/Feature/Products/ProductCampaignTest.php`                                  | POST /products/{id}/campaigns/generate → 201 campaign draft créée, segment non-owned → 403, segment inexistant → 422, Gemini timeout → 201 avec fallback template, campagne liée au produit en base |
| `tests/Feature/Products/ProductAnalyticsTest.php`                                 | GET /products/{id}/analytics → 200 shape correct, GET /products/analytics → 200 top produits, filtres période, ownership 403                                       |
| `tests/Feature/Products/ProductSearchTest.php`                                    | Produit indexé dans Meilisearch, recherche par name retourne résultat, filtre is_active=false exclut produits archivés, orphaned search global (Ctrl+K) retourne produits                                                    |
| `tests/Feature/Products/ProductGdprTest.php`                                      | Export GDPR contient product_sales.csv avec ventes de l'utilisateur, pas de ventes d'autres utilisateurs                                                            |

---

### 5.3 Sprint 36 — Frontend (Weeks 86–89)

#### 5.3.1 Front-end Tasks

| ID          | Task                                                                                                                                                                                                                                | PRD Ref    |
|-------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------|
| P11-FE-001  | Create `stores/products.ts` Zustand store :<br>— State : `products[]`, `selectedProduct`, `filters {type?, isActive?, search?}`, `isLoading`, `error`<br>— Actions : `fetchProducts()`, `createProduct(data)`, `updateProduct(id, data)`, `deleteProduct(id)`, `restoreProduct(id)`, `fetchProductSales(id)`, `fetchProductAnalytics(id)`, `fetchGlobalAnalytics()`, `generateCampaign(productId, segmentId)` | FR-CAT-001 |
| P11-FE-002  | Create `app/(dashboard)/products/page.tsx` — page liste catalogue :<br>— Grid de `ProductCard` (layout cards) + toggle vue liste<br>— Barre de filtres : search input, filtre type (badge pills), toggle Actif/Archivé<br>— Barre de stats : nb produits actifs, CA total this month, nb ventes this month<br>— Bouton "+" → `/products/new`<br>— Pagination | FR-CAT-001 |
| P11-FE-003  | Create `app/(dashboard)/products/new/page.tsx` — formulaire création :<br>— react-hook-form + Zod schema aligné avec `StoreProductRequest`<br>— Champs : name, type (Select), short_description, description (Textarea), price, price_type (Select), currency_code, vat_rate, duration + duration_unit, sku, tags (Input multi-tags), is_active (Switch)<br>— Submit → POST /api/v1/products → redirect `/products/{id}` | FR-CAT-001 |
| P11-FE-004  | Create `app/(dashboard)/products/[id]/page.tsx` — page détail :<br>— Header : nom, type badge, prix, statut actif/archivé, boutons Modifier / Archiver / Restaurer<br>— Onglets (Tabs shadcn) : Détails \| Ventes \| Campagnes \| Analytics<br>— Tab Détails : description, caractéristiques produit, SKU, tags<br>— Tab Ventes : `ProductSalesTable` avec filtre statut/période<br>— Tab Campagnes : liste des campagnes générées pour ce produit, bouton "Créer une campagne"<br>— Tab Analytics : `ProductAnalyticsChart` (CA mensuel) + stats (CA total, nb ventes, taux conversion) | FR-CAT-001 |
| P11-FE-005  | Create `app/(dashboard)/products/[id]/campaigns/generate/page.tsx` — wizard 3 étapes :<br>— Étape 1 "Cibler" : Dropdown segments existants + option rapide "Tous mes leads qualifiés" (auto-filtre leads status qualified/proposal_sent). Affiche compteur "X prospects ciblés". Alerte si 0 destinataires.<br>— Étape 2 "Générer" : Bouton "✨ Générer avec l'IA", progress bar animée, affichage du sujet + aperçu corps généré. Bouton "🔄 Régénérer".<br>— Étape 3 "Réviser" : Éditeur rich-text (sujet modifiable, corps HTML éditable), alerte "Cette campagne sera créée en BROUILLON — relisez avant d'envoyer". Bouton "Créer la campagne en brouillon" → redirect `/campaigns/{id}`. | FR-CAT-003 |
| P11-FE-006  | Create `components/products/product-card.tsx` — card shadcn/ui : nom, type badge coloré, prix formaté avec devise, durée si présente, bouton "Créer une campagne" (secondary), menu kebab (Modifier, Archiver). Classe CSS pour état archivé (opacité). | FR-CAT-001 |
| P11-FE-007  | Create `components/products/product-form.tsx` — composant formulaire réutilisable (page new + page edit). Props : `defaultValues?`, `onSubmit(data)`, `isLoading`. Zod schema exporté pour réutilisation dans les tests. | FR-CAT-001 |
| P11-FE-008  | Create `components/products/product-type-badge.tsx` — badge coloré selon type : `service` (bleu), `training` (violet), `product` (vert), `subscription` (orange). Props : `type: ProductType`. | FR-CAT-001 |
| P11-FE-009  | Create `components/products/product-stats-bar.tsx` — barre stats : nb produits actifs, CA total ce mois, nb ventes ce mois. Données issues du store `globalAnalytics`. Skeleton loader pendant fetch. | FR-CAT-004 |
| P11-FE-010  | Create `components/products/product-sales-table.tsx` — tableau DataTable (shadcn) des ventes : client, montant, statut badge, date. Filtre statut + filtre période (date range picker). Pagination. | FR-CAT-002 |
| P11-FE-011  | Create `components/products/product-analytics-chart.tsx` — graphique Recharts (LineChart ou BarChart) CA mensuel sur les 12 derniers mois. Skeleton pendant fetch. Tooltip avec CA + nb ventes par mois. | FR-CAT-004 |
| P11-FE-012  | Create `components/products/campaign-generator-dialog.tsx` — dialog (multi-step) réutilisable depuis la page produit (Tab Campagnes) : encapsule le wizard de l'étape 1 à 3 en modale pour les flux secondaires. | FR-CAT-003 |
| P11-FE-013  | Create `components/invoices/line-item-product-picker.tsx` — Combobox de sélection produit catalogue dans le formulaire de ligne de devis/facture :<br>— Search autocomplete des produits actifs<br>— Sélection → pré-remplit description, unit_price, vat_rate de la ligne<br>— Bouton "X" pour dissocier sans effacer les valeurs manuelles<br>— Intégrer dans les formulaires `InvoiceForm` et `QuoteForm` existants (nouveaux composants) | FR-CAT-001 |
| P11-FE-014  | Extend `app/(dashboard)/page.tsx` (dashboard) — ajouter widget "Top Produits ce mois" : 3 lignes (nom produit, nb ventes, CA), lien vers `/products`, chargé indépendamment (pas de blocage du dashboard). Masqué si aucune vente ce mois. | FR-CAT-004 |
| P11-FE-015  | Extend sidebar navigation — ajouter entrée "Catalogue" avec icône `Package` (lucide-react), entre Leads et GED. Sous-menu : "Tous les produits" + "+ Nouveau produit". | FR-CAT-001 |

#### 5.3.2 Front-end Tests

| ID          | Test File                                                                  | Status | Owner |
|-------------|----------------------------------------------------------------------------|--------|-------|
| P11-FT-001  | `tests/unit/stores/products.test.ts`                                       | todo   | —     |
| P11-FT-002  | `tests/components/products/product-card.test.tsx`                          | todo   | —     |
| P11-FT-003  | `tests/components/products/product-form.test.tsx`                          | todo   | —     |
| P11-FT-004  | `tests/components/products/product-type-badge.test.tsx`                    | todo   | —     |
| P11-FT-005  | `tests/components/products/product-stats-bar.test.tsx`                     | todo   | —     |
| P11-FT-006  | `tests/components/products/product-sales-table.test.tsx`                   | todo   | —     |
| P11-FT-007  | `tests/components/products/product-analytics-chart.test.tsx`               | todo   | —     |
| P11-FT-008  | `tests/components/products/campaign-generator-dialog.test.tsx`             | todo   | —     |
| P11-FT-009  | `tests/components/invoices/line-item-product-picker.test.tsx`              | todo   | —     |
| P11-FT-010  | `tests/e2e/products/product-crud.spec.ts`                                  | todo   | —     |
| P11-FT-011  | `tests/e2e/products/product-campaign-generate.spec.ts`                     | todo   | —     |
| P11-FT-012  | `tests/e2e/products/product-sale-tracking.spec.ts`                         | todo   | —     |

#### 5.3.3 Scénarios E2E (Playwright)

| Scénario | Description |
|----------|-------------|
| `product-crud.spec.ts` | Créer un produit "Formation Laravel" → vérifier qu'il apparaît dans la liste → le modifier → l'archiver → le restaurer |
| `product-campaign-generate.spec.ts` | Depuis la page produit, ouvrir le wizard → sélectionner un segment → cliquer "Générer" (mock API) → réviser → créer la campagne → vérifier la redirection vers `/campaigns/{id}` en statut `draft` |
| `product-sale-tracking.spec.ts` | Créer un devis avec un produit catalogue → accepter le devis → vérifier que la vente apparaît dans l'onglet Ventes du produit avec statut `pending` |

---

## 6. Exit Criteria

| Critère | Vérification |
|---------|-------------|
| Toutes les tâches `P11-BE-*` et `P11-FE-*` en statut `done` | `docs/dev/phase11.md` |
| Backend coverage >= 80% (`make test-be`) | CI green |
| Frontend coverage >= 80% (`pnpm vitest run --coverage`) | CI green |
| PHPStan level 8 (`./vendor/bin/phpstan analyse`) | 0 erreur |
| Pint (`./vendor/bin/pint --test`) | 0 erreur |
| ESLint + Prettier (`pnpm lint && pnpm format:check`) | 0 erreur |
| `tsc --noEmit` sans erreur | `docker compose run --rm --no-deps frontend pnpm tsc --noEmit` |
| 3 scénarios E2E Playwright verts | `make test-e2e` |
| Meilisearch index `products` opérationnel | Test manuel Ctrl+K |
| Export GDPR contient `product_sales.csv` | Test manuel |
| Webhook `product.sold` dispatché après paiement | Test manuel |
| Dashboard widget "Top Produits" visible | Test manuel |
| Tag v1.7.0 poussé sur GitHub | `git tag v1.7.0` |

---

## 7. Récapitulatif

| Sprint    | Semaines | Livrable principal                                     | Tasks                       |
|-----------|----------|--------------------------------------------------------|-----------------------------|
| Sprint 34 | 78–81    | Backend fondations — modèles, factories, observers     | 10 BE + 3 fichiers tests    |
| Sprint 35 | 82–85    | Services & API — controllers, routes, analytics, GDPR  | 13 BE + 6 fichiers tests    |
| Sprint 36 | 86–89    | Frontend — pages, composants, wizard IA, E2E           | 15 FE + 12 fichiers tests   |
| **Total** | **12 sem** | **v1.7.0**                                           | **~59 tâches + 21 tests**   |
