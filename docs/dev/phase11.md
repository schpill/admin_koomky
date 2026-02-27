# Phase 11 — Task Tracking

> **Status**: merged
> **Prerequisite**: Phase 10 fully merged and tagged `v1.6.0`
> **Spec**: [docs/phases/phase11.md](../phases/phase11.md)

---

## Sprint 34 — Backend Fondations (Weeks 78–81)

### Infrastructure & Database

| ID              | Task                                                                                                                                                                    | Status | Owner |
|-----------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P11-BE-INF-01   | Migration `create_products_table` — UUID PK, user_id FK, name, slug unique, type ENUM, description, short_description, price, price_type ENUM, currency_code, vat_rate, duration, duration_unit ENUM, sku, tags JSON, is_active, meta JSON, timestamps, softDeletes. Index `(user_id, is_active)`, `(user_id, type)`. | merged | dev   |
| P11-BE-INF-02   | Migration `add_product_id_to_line_items_table` — colonne `product_id UUID nullable FK → products SET NULL`. Index `product_id`. Migration sûre (nullable, aucun back-fill). | merged | dev   |
| P11-BE-INF-03   | Migration `create_product_sales_table` — product_id, user_id, client_id (nullable), invoice_id (nullable), quote_id (nullable), quantity, unit_price, total_price, currency_code, status ENUM, sold_at, notes. Index `(product_id, status)`, `(user_id, sold_at)`. Contrainte UNIQUE `(invoice_id, product_id)` WHERE invoice_id IS NOT NULL. | merged | dev   |
| P11-BE-INF-04   | Migration `create_product_campaigns_table` — product_id FK, campaign_id FK, generation_model, generated_at, timestamps.                                                 | merged | dev   |
| P11-BE-INF-05   | Migration `create_product_campaign_generation_logs_table` — product_id FK, campaign_id (nullable FK), user_id FK, model, tokens_used, latency_ms, success, error_message, generated_at. Index `(user_id, generated_at)`. | merged | dev   |

### Modèles & Policies

| ID          | Task                                                                                                                                                                           | Status | Owner |
|-------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P11-BE-001  | Create `Product` model — HasUuids, HasFactory, SoftDeletes, Searchable. Fillable, casts, relations (user, sales, campaigns BelongsToMany, lineItems). Scopes : active, archived, byType, byTag. `toSearchableArray()` complet. | merged | dev   |
| P11-BE-002  | Create `ProductSale` model — HasUuids, HasFactory. Fillable, casts (quantity/unit_price/total_price décimaux, sold_at datetime). Relations : product, user, client, invoice, quote. Scopes : byStatus, confirmed, forPeriod. | merged | dev   |
| P11-BE-003  | Create `ProductCampaignGenerationLog` model — HasUuids. Fillable, casts (success boolean, generated_at datetime). Relations : product, campaign, user.                          | merged | dev   |
| P11-BE-004  | Update `LineItem` model — ajouter `product_id` au fillable. Ajouter relation `product(): BelongsTo<Product, LineItem>`.                                                        | merged | dev   |
| P11-BE-005  | Create `ProductPolicy` — ownership standard (user_id match). Méthodes : viewAny, view, create, update, delete, restore, forceDelete. Enregistrement dans AuthServiceProvider.   | merged | dev   |
| P11-BE-006  | Create `ProductSalePolicy` — ownership via product→user_id. Méthodes : viewAny, view.                                                                                         | merged | dev   |
| P11-BE-007  | Extend `InvoiceObserver::updated()` — si status change vers `paid` : créer `ProductSale` (status `confirmed`) pour chaque LineItem avec product_id non null. Guard firstOrCreate sur `(invoice_id, product_id)`. Dispatcher webhook `product.sold`. | merged | dev   |
| P11-BE-008  | Extend `QuoteObserver::updated()` — si status change vers `accepted` : créer `ProductSale` (status `pending`, quote_id renseigné) pour chaque LineItem avec product_id non null. Guard firstOrCreate sur `(quote_id, product_id)`. | merged | dev   |
| P11-BE-009  | Create `ProductFactory` — faker pour tous les champs. États : active(), archived(), training(), subscription(), service().                                                      | merged | dev   |
| P11-BE-010  | Create `ProductSaleFactory` — faker. États : confirmed(), pending(), cancelled().                                                                                               | merged | dev   |

### Backend Tests (TDD)

| ID          | Test File                                                              | Status | Owner |
|-------------|------------------------------------------------------------------------|--------|-------|
| P11-BT-001  | `tests/Unit/Models/ProductTest.php`                                    | merged | dev   |
| P11-BT-002  | `tests/Feature/Products/ProductCrudTest.php`                           | merged | dev   |
| P11-BT-003  | `tests/Feature/Products/ProductObserverTest.php`                       | merged | dev   |

---

## Sprint 35 — Services & API (Weeks 82–85)

### Services

| ID          | Task                                                                                                                                                                           | Status | Owner |
|-------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P11-BE-011  | Create `ProductCampaignGeneratorService` — injection GeminiService. Méthode `generate(Product, Segment, User): Campaign` : construction prompt, appel Gemini, parsing JSON (regex extraction si bloc markdown), fallback template Blade, création CampaignTemplate + Campaign draft, pivot product_campaigns, log ProductCampaignGenerationLog. | merged | dev   |
| P11-BE-012  | Create template Blade fallback `resources/views/mail/campaign/product-fallback.blade.php` — template HTML générique français avec variables `{{first_name}}`, `{{company}}`, `{{product_name}}`, `{{product_price}}`, `{{unsubscribe_link}}`. Rendu via `Blade::render()`. | merged | dev   |
| P11-BE-013  | Create `ProductAnalyticsService` — méthodes : `productStats(Product, ?Carbon, ?Carbon): array` (total_revenue, total_sales, avg_order_value, conversion_rate, monthly_breakdown), `globalStats(User, ?Carbon, ?Carbon): array` (top_products[], total_revenue, total_sales), `topProducts(User, int): Collection`. | merged | dev   |
| P11-BE-014  | Extend `DataExportService::exportAll()` — inclure ProductSale dans export GDPR (fichier `product_sales.csv` : product_name, client_name, quantity, total_price, currency, status, sold_at). | merged | dev   |
| P11-BE-015  | Extend `WebhookDispatchService` — ajouter événement `product.sold` avec payload (product_id, product_name, client_id, sale_id, total_price, currency_code, sold_at).           | merged | dev   |

### Controllers & Routes

| ID          | Task                                                                                                                                                                           | Status | Owner |
|-------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P11-BE-016  | Create `ProductController` — méthodes : index (paginate 15, filtres type/is_active/search), store, show, update, destroy (soft delete), restore, sales (paginate 15), productAnalytics, globalAnalytics. ProductPolicy appliqué. | merged | dev   |
| P11-BE-017  | Create `ProductCampaignController` — méthode `generate(StoreProductCampaignRequest, Product): JsonResponse` : valide segment_id owned, appelle ProductCampaignGeneratorService, retourne Campaign 201. | merged | dev   |
| P11-BE-018  | Create `StoreProductRequest` — validation : name (requis max 255), type (ENUM), price (numeric min 0), price_type (ENUM), vat_rate (numeric 0–100), currency_code (3 chars), duration (int nullable min 1), duration_unit (ENUM nullable), description (max 5000), short_description (max 500), sku (max 100), tags (array of strings), is_active (boolean). | merged | dev   |
| P11-BE-019  | Create `UpdateProductRequest` — mêmes règles que StoreProductRequest, tous les champs optionnels (`sometimes`).                                                                 | merged | dev   |
| P11-BE-020  | Create `StoreProductCampaignRequest` — segment_id requis UUID, existe dans segments, appartient à l'utilisateur authentifié.                                                    | merged | dev   |
| P11-BE-021  | Register routes dans `routes/api.php` : GET/POST /products, GET /products/analytics, GET/PATCH/DELETE /products/{product}, POST /products/{product}/restore, POST /products/{product}/campaigns/generate, GET /products/{product}/sales, GET /products/{product}/analytics. | merged | dev   |
| P11-BE-022  | Configure index Meilisearch pour Product dans `toSearchableArray()` : searchable (name, description, short_description, tags), filterable (user_id, type, is_active), sortable (created_at, name, price). | merged | dev   |
| P11-BE-023  | Extend `DashboardService` — ajouter `topProductsWidget(User): array` → top 3 produits du mois courant (CA + nb ventes).                                                        | merged | dev   |

### Backend Tests (TDD)

| ID          | Test File                                                                     | Status | Owner |
|-------------|-------------------------------------------------------------------------------|--------|-------|
| P11-BT-004  | `tests/Unit/Services/ProductCampaignGeneratorServiceTest.php`                 | merged | dev   |
| P11-BT-005  | `tests/Unit/Services/ProductAnalyticsServiceTest.php`                         | merged | dev   |
| P11-BT-006  | `tests/Feature/Products/ProductCampaignTest.php`                              | merged | dev   |
| P11-BT-007  | `tests/Feature/Products/ProductAnalyticsTest.php`                             | merged | dev   |
| P11-BT-008  | `tests/Feature/Products/ProductSearchTest.php`                                | merged | dev   |
| P11-BT-009  | `tests/Feature/Products/ProductGdprTest.php`                                  | merged | dev   |

---

## Sprint 36 — Frontend (Weeks 86–89)

### Frontend Tasks

| ID          | Task                                                                                                                                                                           | Status | Owner |
|-------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P11-FE-001  | Create `stores/products.ts` — Zustand store : state (products[], selectedProduct, filters, isLoading, error), actions (fetchProducts, createProduct, updateProduct, deleteProduct, restoreProduct, fetchProductSales, fetchProductAnalytics, fetchGlobalAnalytics, generateCampaign). | merged | dev   |
| P11-FE-002  | Create `app/(dashboard)/products/page.tsx` — liste catalogue : grid ProductCard, toggle liste, filtres (search, type pills, actif/archivé), barre de stats, bouton "+", pagination. | merged | dev   |
| P11-FE-003  | Create `app/(dashboard)/products/new/page.tsx` — formulaire création : react-hook-form + Zod, tous les champs StoreProductRequest, submit → POST → redirect `/products/{id}`.  | merged | dev   |
| P11-FE-004  | Create `app/(dashboard)/products/[id]/page.tsx` — page détail : header (nom, badge, prix, statut, actions), onglets Détails/Ventes/Campagnes/Analytics avec contenu par onglet. | merged | dev   |
| P11-FE-005  | Create `app/(dashboard)/products/[id]/campaigns/generate/page.tsx` — wizard 3 étapes : Étape 1 (sélection segment + option "Tous mes leads qualifiés" + compteur), Étape 2 (génération IA + progress bar + régénérer), Étape 3 (éditeur rich-text + alerte brouillon + submit). | merged | dev   |
| P11-FE-006  | Create `components/products/product-card.tsx` — card : nom, type badge, prix formaté, durée, bouton "Créer une campagne", menu kebab (Modifier, Archiver), style archivé.       | merged | dev   |
| P11-FE-007  | Create `components/products/product-form.tsx` — formulaire réutilisable (new + edit). Props : defaultValues?, onSubmit(data), isLoading. Zod schema exporté.                    | merged | dev   |
| P11-FE-008  | Create `components/products/product-type-badge.tsx` — badge coloré : service (bleu), training (violet), product (vert), subscription (orange).                                  | merged | dev   |
| P11-FE-009  | Create `components/products/product-stats-bar.tsx` — nb produits actifs, CA total ce mois, nb ventes ce mois. Skeleton loader pendant fetch.                                    | merged | dev   |
| P11-FE-010  | Create `components/products/product-sales-table.tsx` — DataTable shadcn : client, montant, statut badge, date. Filtre statut + filtre période. Pagination.                      | merged | dev   |
| P11-FE-011  | Create `components/products/product-analytics-chart.tsx` — Recharts BarChart ou LineChart CA mensuel 12 mois. Tooltip CA + nb ventes. Skeleton pendant fetch.                   | merged | dev   |
| P11-FE-012  | Create `components/products/campaign-generator-dialog.tsx` — dialog multi-step réutilisable (encapsule le wizard en modale pour l'onglet Campagnes du produit).                 | merged | dev   |
| P11-FE-013  | Create `components/invoices/line-item-product-picker.tsx` — Combobox autocomplete produits actifs. Sélection pré-remplit description/unit_price/vat_rate. Bouton "X" dissocier. Intégrer dans InvoiceForm et QuoteForm existants. | merged | dev   |
| P11-FE-014  | Extend `app/(dashboard)/page.tsx` — widget "Top Produits ce mois" : 3 lignes (nom, nb ventes, CA), lien /products, chargement indépendant, masqué si aucune vente.              | merged | dev   |
| P11-FE-015  | Extend sidebar — entrée "Catalogue" icône Package entre Leads et GED. Sous-menu : "Tous les produits" + "+ Nouveau produit".                                                    | merged | dev   |

### Frontend Tests

| ID          | Test File                                                                  | Status | Owner |
|-------------|----------------------------------------------------------------------------|--------|-------|
| P11-FT-001  | `tests/unit/stores/products.test.ts`                                       | merged | dev   |
| P11-FT-002  | `tests/components/products/product-card.test.tsx`                          | merged | dev   |
| P11-FT-003  | `tests/components/products/product-form.test.tsx`                          | merged | dev   |
| P11-FT-004  | `tests/components/products/product-type-badge.test.tsx`                    | merged | dev   |
| P11-FT-005  | `tests/components/products/product-stats-bar.test.tsx`                     | merged | dev   |
| P11-FT-006  | `tests/components/products/product-sales-table.test.tsx`                   | merged | dev   |
| P11-FT-007  | `tests/components/products/product-analytics-chart.test.tsx`               | merged | dev   |
| P11-FT-008  | `tests/components/products/campaign-generator-dialog.test.tsx`             | merged | dev   |
| P11-FT-009  | `tests/components/invoices/line-item-product-picker.test.tsx`              | merged | dev   |
| P11-FT-010  | `tests/e2e/products/product-crud.spec.ts`                                  | merged | dev   |
| P11-FT-011  | `tests/e2e/products/product-campaign-generate.spec.ts`                     | merged | dev   |
| P11-FT-012  | `tests/e2e/products/product-sale-tracking.spec.ts`                         | merged | dev   |

---

## Récapitulatif

| Sprint    | Semaines | Livrable principal                                     | Tasks                       |
|-----------|----------|--------------------------------------------------------|-----------------------------|
| Sprint 34 | 78–81    | Backend fondations — migrations, modèles, observers    | 5 INF + 10 BE + 3 tests     |
| Sprint 35 | 82–85    | Services & API — controllers, routes, analytics, GDPR  | 13 BE + 6 tests             |
| Sprint 36 | 86–89    | Frontend — pages, composants, wizard IA, E2E           | 15 FE + 12 tests            |
| **Total** | **12 sem** | **v1.7.0**                                           | **~59 tâches + 21 tests**   |
