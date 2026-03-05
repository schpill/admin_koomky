# Phase 14 — Prospection Ciblée & Import de Prospects (v2.0)

| Field               | Value                                                        |
|---------------------|--------------------------------------------------------------|
| **Phase**           | 14                                                           |
| **Name**            | Prospection Ciblée & Import de Prospects                     |
| **Duration**        | Weeks 111–122 (12 weeks)                                     |
| **Milestone**       | M14 — v2.0.0 Release                                        |
| **PRD Sections**    | §4.20 FR-PRO (nouveau), §4.21 FR-IMP (nouveau)              |
| **Prerequisite**    | Phase 13 fully completed and tagged `v1.9.0`                 |
| **Status**          | Planned                                                      |

---

## 1. Phase Objectives

| ID        | Objective                                                                                                                                           |
|-----------|-----------------------------------------------------------------------------------------------------------------------------------------------------|
| P14-OBJ-1 | Permettre l'import en masse de prospects depuis un fichier Excel (.xlsx) ou CSV avec wizard de mapping de colonnes                                  |
| P14-OBJ-2 | Enrichir le modèle Client avec les champs `industry` (secteur d'activité) et `department` (département français, ex. "60") pour la segmentation     |
| P14-OBJ-3 | Enrichir le moteur de segmentation (`SegmentFilterEngine`) avec les critères `industry` et `department`                                             |
| P14-OBJ-4 | Offrir une vue "Prospects" dédiée dans l'interface (clients avec status `prospect`) avec filtres métier et géographiques                             |
| P14-OBJ-5 | Permettre de créer une campagne email ciblée directement depuis la vue Prospects, avec pré-sélection du segment                                     |
| P14-OBJ-6 | Valider le tracking d'ouverture (pixel GIF) et de clic (redirect trackée) déjà en place sur les envois de campagne                                 |
| P14-OBJ-7 | Maintenir une couverture de tests >= 80% backend et frontend                                                                                        |

---

## 2. Contexte & Analyse de l'existant

### 2.1 Ce qui est déjà en place (aucune réimplémentation)

| Besoin | Brique existante | Phase d'origine |
|--------|-----------------|-----------------|
| Tracking ouverture email (pixel GIF 1x1) | `EmailTrackingController::open()` | Phase 3 |
| Tracking clic email (redirect trackée) | `EmailTrackingController::click()` | Phase 3 |
| Statuts bounce/complaint/delivery SES | `CampaignWebhookController` | Phase 3 |
| Système de campagnes email | `Campaign`, `CampaignTemplate`, `CampaignRecipient` | Phase 3 |
| Moteur de segmentation | `Segment`, `SegmentFilterEngine` | Phase 3 |
| Désabonnement GDPR | `UnsubscribeController`, `Contact::emailSubscribed` scope | Phase 3 |
| Consentement email | `email_consent`, `email_unsubscribed_at` sur `Contact` | Phase 3 |
| Analytics campagnes | `CampaignAnalyticsController` | Phase 3 |
| Tags clients | `Tag`, `client_tag` pivot, `BelongsToMany` | Phase 2 |
| Envoi SES API | `MailConfigService`, SES credentials par user | Phase 11 |
| Auth + Policy | Sanctum + ownership standard | Phase 1 |
| Webhooks | `WebhookDispatchService` | Phase 7 |
| Export GDPR | `DataExportService` | Phase 4 |
| Queue / Jobs | Laravel Horizon | Phase 5 |

### 2.2 Analyse du gap

| Besoin | Gap actuel | Solution Phase 14 |
|--------|-----------|-------------------|
| Champ secteur d'activité | `Client` n'a pas de colonne `industry` | Migration + fillable + Meilisearch |
| Champ département | `Client` n'a que `city`/`country`, pas de département | Migration colonne `department` (VARCHAR 10) |
| Statut prospect | `Client.status` ne comprend pas `prospect` | Ajout `prospect` aux valeurs ENUM/check |
| Segmentation par métier | `SegmentFilterEngine` ne connaît pas `industry` | Nouveau critère `industry` |
| Segmentation par département | `SegmentFilterEngine` ne connaît pas `department` | Nouveau critère `department` |
| Import Excel/CSV | Aucun endpoint d'import de prospects | `ImportSession` + `ProspectImportService` |
| Vue Prospects | Pas de page dédiée "Prospects" | Page filtrée + composants dédiés |
| Campagne depuis vue Prospects | Pas de raccourci | Bouton "Créer une campagne" depuis vue |

---

## 3. Choix techniques

### 3.1 Modèle de données

**Trois migrations nécessaires :**

| Migration | Description |
|-----------|-------------|
| `add_prospecting_fields_to_clients_table` | Ajout de `industry VARCHAR(255) nullable`, `department VARCHAR(10) nullable` sur `clients`. Index simple sur `industry` et `department`. |
| `create_import_sessions_table` | UUID PK, `user_id` FK → users CASCADE, `filename` VARCHAR (nom stockage), `original_filename` VARCHAR, `status` ENUM(pending/parsing/mapping/processing/completed/failed) DEFAULT pending, `total_rows` INT DEFAULT 0, `processed_rows` INT DEFAULT 0, `success_rows` INT DEFAULT 0, `error_rows` INT DEFAULT 0, `column_mapping` JSON nullable, `default_tags` JSON nullable (liste de noms de tags à appliquer à tous les imports), `options` JSON nullable (duplicate_strategy: skip\|update, default_status: prospect\|lead\|active), `error_summary` TEXT nullable, `completed_at` TIMESTAMP nullable, timestamps. |
| `create_import_session_errors_table` | UUID PK, `session_id` FK → import_sessions CASCADE, `row_number` INT, `raw_data` JSON, `error_message` TEXT, timestamps. Index `(session_id, row_number)`. |

### 3.2 Mapping de colonnes

Le wizard détecte automatiquement les colonnes du fichier importé et permet de les associer aux champs CRM :

| Champ CRM (Client) | Aliases détectés automatiquement |
|--------------------|----------------------------------|
| `name` | nom, name, société, company, raison sociale, business name |
| `email` | email, e-mail, mail, courriel |
| `phone` | téléphone, phone, tel, mobile |
| `address` | adresse, address, rue, street |
| `city` | ville, city |
| `zip_code` | code postal, zip, cp, postal code |
| `department` | département, department, dept |
| `country` | pays, country |
| `industry` | secteur, industry, métier, activité, profession |
| `notes` | notes, commentaires, remarks |
| Contact `first_name` | prénom, first name, firstname |
| Contact `last_name` | nom contact, last name, lastname |
| Contact `position` | poste, position, titre, title |

### 3.3 Stratégies de doublons

| Stratégie | Comportement |
|-----------|-------------|
| `skip` (défaut) | Si un client avec le même email existe déjà, la ligne est ignorée et comptée dans `error_rows` |
| `update` | Si un client avec le même email existe, ses champs sont mis à jour avec les données de l'import |

Détection de doublon : par email (`clients.email`) si présent, sinon par (`name` + `phone`).

### 3.4 Critères de segmentation enrichis

Ajout dans `SegmentFilterEngine` de deux nouveaux types de critères :

| Type | Opérateurs | Exemple |
|------|-----------|---------|
| `industry` | `equals`, `not_equals`, `contains`, `in` (liste) | `industry equals "Wedding Planner"` |
| `department` | `equals`, `not_equals`, `in` (liste) | `department in ["60", "80", "02"]` |

### 3.5 Traitement asynchrone de l'import

```
1. Upload fichier → POST /api/v1/import-sessions
   → Crée ImportSession (status: pending)
   → Parse les headers → retourne column_list + aperçu 5 premières lignes
2. Mapping + options → PATCH /api/v1/import-sessions/{id}
   → Enregistre column_mapping + default_tags + options
   → Status: mapping
3. Lancement → POST /api/v1/import-sessions/{id}/process
   → Status: processing → Dispatch ProcessProspectImportJob (queue: imports)
4. Progression → GET /api/v1/import-sessions/{id}
   → Retourne processed_rows, total_rows, status, success_rows, error_rows
5. Résultats → GET /api/v1/import-sessions/{id}/errors
   → Liste paginée des ImportSessionError (row_number, raw_data, error_message)
```

### 3.6 Librairies PHP requises

| Librairie | Usage |
|-----------|-------|
| `league/csv` (déjà dans composer ?) | Parsing CSV robuste |
| `phpoffice/phpspreadsheet` | Parsing XLSX |

---

## 4. Entry Criteria

- Phase 13 exit criteria 100% satisfaits.
- Tous les checks CI Phase 13 verts sur `main`.
- v1.9.0 tagué et déployé en production.
- `SegmentFilterEngine` stable et couvert par les tests existants.
- `Client` model et `ClientController` stables.

---

## 5. Scope — Requirement Traceability

### 5.1 Module Enrichissement Client & Segmentation (FR-PRO)

| Feature | Priority | Included |
|---------|----------|----------|
| Champ `industry` sur Client (CRUD + recherche Meilisearch) | High | Yes |
| Champ `department` sur Client (CRUD + recherche Meilisearch) | High | Yes |
| Statut `prospect` ajouté aux statuts client | High | Yes |
| Critère `industry` dans SegmentFilterEngine | High | Yes |
| Critère `department` dans SegmentFilterEngine | High | Yes |
| Autocomplétion des valeurs `industry` existantes (endpoint dédié) | Medium | Yes |
| Autocomplétion des `department` valides (liste des 101 départements FR) | Medium | Yes |
| Webhook `client.imported` (batch) | Low | Yes |

### 5.2 Module Import de Prospects (FR-IMP)

| Feature | Priority | Included |
|---------|----------|----------|
| Upload fichier .xlsx ou .csv (max 5 Mo) | High | Yes |
| Détection automatique des colonnes (alias matching) | High | Yes |
| Aperçu des 5 premières lignes avant mapping | High | Yes |
| Wizard de mapping manuel des colonnes | High | Yes |
| Tags automatiques appliqués à tous les imports | High | Yes |
| Stratégie doublons : skip ou update | High | Yes |
| Traitement asynchrone via queue `imports` | High | Yes |
| Suivi de progression (polling) | High | Yes |
| Rapport d'erreurs ligne par ligne | Medium | Yes |
| Export CSV des lignes en erreur | Medium | Yes |
| Historique des imports passés | Medium | Yes |
| Import > 5 Mo (chunks) | Low | No |
| Import via URL distante | Low | No |
| Déduplication phonétique (soundex) | Low | No |

### 5.3 Module Vue Prospects & Campagnes Ciblées

| Feature | Priority | Included |
|---------|----------|----------|
| Page `/prospects` : liste filtrée (status=prospect) | High | Yes |
| Filtres : industry, department, tags, ville | High | Yes |
| Bouton "Créer une campagne" depuis vue Prospects (pré-remplit le segment) | High | Yes |
| Bouton "Importer des prospects" dans la vue Prospects | High | Yes |
| Badge "Prospects" dans la sidebar (avec count) | Medium | Yes |
| Conversion prospect → client (changement de statut) | Medium | Yes |
| Sélection multiple + bulk tag/status change | Medium | Yes |
| Export CSV des prospects filtrés | Low | Yes |

---

## 6. Detailed Sprint Breakdown

### 6.1 Sprint 43 — Backend Enrichissement Client & Segmentation (Weeks 111–113)

#### 6.1.1 Infrastructure & Database

| Migration / Config | Description |
|--------------------|-------------|
| `add_prospecting_fields_to_clients_table` | Colonnes `industry VARCHAR(255) nullable`, `department VARCHAR(10) nullable`. Index `(user_id, industry)`, `(user_id, department)`. |

#### 6.1.2 Backend — Modèle, Service & Controller

| ID          | Task | PRD Ref |
|-------------|------|---------|
| P14-BE-001  | Extend `Client` model — Ajout `industry`, `department` dans `$fillable`. Mise à jour `toSearchableArray()` : inclure `industry`, `department`. Scope `byIndustry(string $industry)`, scope `byDepartment(string $department)`. Scope `prospects()` : `whereStatus('prospect')`. | FR-PRO |
| P14-BE-002  | Extend `StoreClientRequest` / `UpdateClientRequest` — Ajout règles : `industry` nullable string max 255, `department` nullable string max 10. Ajout `prospect` aux valeurs acceptées pour `status`. | FR-PRO |
| P14-BE-003  | Extend `SegmentFilterEngine` — Ajout critère `industry` : opérateurs `equals`, `not_equals`, `contains`, `in` (array). Ajout critère `department` : opérateurs `equals`, `not_equals`, `in` (array). Les critères s'appliquent sur la table `clients` via join depuis `contacts`. | FR-PRO |
| P14-BE-004  | Create `ProspectMetaController` — méthode `industries()` : `GET /api/v1/prospects/industries` → retourne les valeurs distinctes de `clients.industry` pour l'user (pour autocomplétion). Méthode `departments()` : `GET /api/v1/prospects/departments` → retourne la liste statique des 101 départements français (code + nom). | FR-PRO |
| P14-BE-005  | Extend `ClientController::index()` — Ajout filtres query params : `?industry=`, `?department=`, `?status=prospect`. Mise à jour Meilisearch filterable attributes : ajouter `industry`, `department`. | FR-PRO |
| P14-BE-006  | Extend `WebhookDispatchService` — événement `client.imported` avec payload `{session_id, success_rows, error_rows, tags_applied}`. Dispatché à la fin de `ProcessProspectImportJob`. | FR-PRO |
| P14-BE-007  | Extend `DataExportService` — inclure `industry` et `department` dans l'export GDPR des clients. | FR-PRO |

#### 6.1.3 Backend Tests — Enrichissement Client (TDD)

| Test File | Test Cases |
|-----------|-----------|
| `tests/Unit/Services/SegmentFilterEngineTest.php` (extension) | Filtre `industry equals "Wedding Planner"` retourne les bons clients, filtre `industry in [...]` retourne union, filtre `department equals "60"` retourne les bons clients, filtre `department in ["60","80"]` retourne union, combinaison `industry + department` (AND) |
| `tests/Feature/Clients/ClientProspectFilterTest.php` | `GET /clients?status=prospect` retourne uniquement les prospects, `GET /clients?industry=...` filtre par métier, `GET /clients?department=60` filtre par département, `GET /prospects/industries` retourne les valeurs distinctes, `GET /prospects/departments` retourne les 101 départements, création client avec `industry` + `department` + status `prospect` 201 |

---

### 6.2 Sprint 44 — Backend Import CSV/Excel (Weeks 114–116)

#### 6.2.1 Infrastructure & Database

| Migration / Config | Description |
|--------------------|-------------|
| `create_import_sessions_table` | Voir §3.1 — UUID PK, user_id, filename, original_filename, status ENUM, compteurs, column_mapping JSON, default_tags JSON, options JSON, error_summary, completed_at, timestamps. |
| `create_import_session_errors_table` | UUID PK, session_id FK CASCADE, row_number INT, raw_data JSON, error_message TEXT, timestamps. Index `(session_id, row_number)`. |
| `composer require phpoffice/phpspreadsheet` | Parsing XLSX. Vérifier si `league/csv` est déjà présent, sinon l'ajouter. |

#### 6.2.2 Backend — Modèles, Services & Controller

| ID          | Task | PRD Ref |
|-------------|------|---------|
| P14-BE-008  | Create `ImportSession` model — Traits : `HasUuids`, `HasFactory`. Fillable complet. Casts : `column_mapping` array, `default_tags` array, `options` array, `completed_at` datetime. Relations : `user()` BelongsTo, `errors()` HasMany → `ImportSessionError`. Méthode `isProcessing(): bool`. Méthode `progressPercent(): int`. | FR-IMP |
| P14-BE-009  | Create `ImportSessionError` model — Traits : `HasUuids`, `HasFactory`. Fillable : session_id, row_number, raw_data, error_message. Casts : `raw_data` array. Relation : `session()` BelongsTo. | FR-IMP |
| P14-BE-010  | Create `ImportSessionPolicy` — ownership standard (`user_id` match). Méthodes : `viewAny`, `view`, `update`, `delete`. | FR-IMP |
| P14-BE-011  | Create `FileParserService` — méthode `parse(string $path, string $extension): array` → retourne `{headers: string[], rows: array[]}`. Supporte `.csv` (via `league/csv` ou `fgetcsv`) et `.xlsx` (via `PhpSpreadsheet`). Max 10 000 lignes (rejet avec exception au-delà). Détecte l'encodage et normalise en UTF-8. | FR-IMP |
| P14-BE-012  | Create `ColumnDetectorService` — méthode `detect(array $headers): array` → retourne un mapping `{detected_field: string\|null}` pour chaque header, basé sur les aliases définis en §3.2 (matching insensible à la casse et aux accents). | FR-IMP |
| P14-BE-013  | Create `ProspectImportService` — méthode `import(ImportSession $session): void`. Pour chaque ligne du fichier parsé : (1) applique `column_mapping` pour construire les données Client + Contact ; (2) vérifie les doublons selon `options.duplicate_strategy` ; (3) crée ou met à jour le `Client` (avec `status = options.default_status ?? 'prospect'`) ; (4) crée le `Contact` principal si first_name ou last_name présent ; (5) applique les `default_tags` ; (6) incrémente `processed_rows` + `success_rows` ou crée `ImportSessionError` + incrémente `error_rows` ; (7) met à jour `ImportSession.processed_rows` tous les 50 enregistrements. À la fin : `completed_at = now()`, `status = completed` (ou `failed` si 0 success). | FR-IMP |
| P14-BE-014  | Create `ImportSessionController` — méthodes : `index` (liste paginée 15/page, sessions de l'user), `store` (upload fichier via `storeAs` dans disk `private`, parse headers via `FileParserService`, auto-detect via `ColumnDetectorService`, retourne session + column_list + preview_rows + detected_mapping, status: pending), `show` (état de la session : status, compteurs, column_mapping), `update` (PATCH — enregistre column_mapping + default_tags + options, status → mapping), `process` (POST /{id}/process — valide que status=mapping, dispatch `ProcessProspectImportJob`, status → processing, 202), `errors` (GET /{id}/errors — liste paginée des `ImportSessionError`), `exportErrors` (GET /{id}/errors/export — CSV des lignes en erreur), `destroy` (supprime session + fichier stocké si status != processing). | FR-IMP |
| P14-BE-015  | Create `ProcessProspectImportJob` — implémente `ShouldQueue`. Queue : `imports`. Charge le fichier stocké via `FileParserService`, appelle `ProspectImportService::import()`. En cas d'exception fatale : `ImportSession.status = failed`, `error_summary = message`. Dispatche webhook `client.imported` en fin de traitement. | FR-IMP |
| P14-BE-016  | Create `StoreImportSessionRequest` — règles : `file` required, mimes `csv,xlsx,xls`, max 5120 (5 Mo). | FR-IMP |
| P14-BE-017  | Register routes dans `routes/api.php` (groupe `v1` authentifié) : `GET /import-sessions`, `POST /import-sessions`, `GET /import-sessions/{session}`, `PATCH /import-sessions/{session}`, `POST /import-sessions/{session}/process`, `GET /import-sessions/{session}/errors`, `GET /import-sessions/{session}/errors/export`, `DELETE /import-sessions/{session}`. | FR-IMP |

#### 6.2.3 Backend Tests — Import (TDD)

| Test File | Test Cases |
|-----------|-----------|
| `tests/Unit/Services/FileParserServiceTest.php` | Parse CSV UTF-8 valide → headers + rows corrects, parse CSV avec séparateur `;`, parse XLSX → headers + rows corrects, fichier vide → exception, fichier > 10 000 lignes → exception, encodage latin-1 → normalisé UTF-8 |
| `tests/Unit/Services/ColumnDetectorServiceTest.php` | `Nom` → `name`, `Email` → `email`, `Téléphone` → `phone`, `Département` → `department`, `Secteur d'activité` → `industry`, colonne inconnue → null, matching insensible à la casse |
| `tests/Unit/Services/ProspectImportServiceTest.php` | Ligne valide → Client créé avec status=prospect, Client créé avec industry + department, Contact principal créé si prénom présent, default_tags appliqués, doublon email + strategy=skip → ignoré, doublon email + strategy=update → champs mis à jour, success_rows / error_rows incrémentés correctement, ligne invalide (email malformé) → ImportSessionError créé |
| `tests/Feature/Import/ImportSessionCrudTest.php` | `POST /import-sessions` 201 (fichier CSV) + headers détectés, `POST /import-sessions` 201 (fichier XLSX), `POST /import-sessions` 422 si type invalide, `POST /import-sessions` 422 si > 5 Mo, `GET /import-sessions/{id}` 200 (compteurs), `PATCH /import-sessions/{id}` 200 (mapping enregistré), `POST /import-sessions/{id}/process` 202 (job dispatché), `GET /import-sessions/{id}/errors` 200, `GET /import-sessions/{id}/errors/export` 200 CSV, `DELETE /import-sessions/{id}` 204, ownership 403 |

---

### 6.3 Sprint 45 — Frontend Import Wizard (Weeks 117–119)

#### 6.3.1 Frontend — Store & Pages

| ID          | Task | PRD Ref |
|-------------|------|---------|
| P14-FE-001  | Create `lib/stores/prospect-import.ts` Zustand store — State : `session`, `columnList`, `previewRows`, `detectedMapping`, `columnMapping`, `defaultTags`, `options`, `isUploading`, `isProcessing`, `progress`, `errors[]`. Actions : `uploadFile(file)`, `updateMapping(mapping)`, `updateOptions(opts)`, `processImport()`, `pollStatus()` (polling toutes les 3s si status=processing), `fetchErrors(page)`, `exportErrors()`, `reset()`. | FR-IMP |
| P14-FE-002  | Create `app/(dashboard)/prospects/import/page.tsx` — wizard 4 étapes avec stepper (Step 1: Upload → Step 2: Mapping → Step 3: Options → Step 4: Résultats). Navigation entre étapes conditionnelle (étape suivante débloquée après validation). | FR-IMP |
| P14-FE-003  | Create `components/prospects/import-wizard/step-upload.tsx` — zone drag-and-drop (accept `.xlsx,.csv`). Affichage du nom de fichier sélectionné. Bouton "Analyser le fichier". Barre de progression pendant l'upload. Gestion erreur taille/format. | FR-IMP |
| P14-FE-004  | Create `components/prospects/import-wizard/step-mapping.tsx` — tableau des colonnes détectées (header original + dropdown de sélection du champ CRM cible). Pré-rempli avec `detectedMapping`. Aperçu des 5 premières lignes en bas. Chaque ligne : colonne source (string) → Select CRM field (`name`, `email`, `phone`, `address`, `city`, `zip_code`, `department`, `country`, `industry`, `notes`, `contact.first_name`, `contact.last_name`, `contact.position`, `— ignorer`). | FR-IMP |
| P14-FE-005  | Create `components/prospects/import-wizard/step-options.tsx` — section "Tags à appliquer" : ComboBox multi-sélection des tags existants + création de nouveaux tags inline. Section "Statut par défaut" : Select (prospect/lead/active). Section "Doublons" : RadioGroup (skip / update). | FR-IMP |
| P14-FE-006  | Create `components/prospects/import-wizard/step-results.tsx` — affichage temps réel pendant processing : barre de progression (`processed_rows / total_rows`), statut texte ("En cours...", "Terminé"). Après complétion : résumé (X importés, Y mis à jour, Z erreurs). Si erreurs > 0 : tableau paginé des erreurs (numéro ligne, données brutes, message) + bouton "Exporter les erreurs (CSV)". Bouton "Voir les prospects importés". | FR-IMP |
| P14-FE-007  | Create `lib/stores/prospects.ts` Zustand store — State : `clients[]`, `total`, `page`, `filters` (industry, department, tags, city, search). Actions : `fetchProspects(filters?)`, `convertToClient(id)`, `bulkUpdateStatus(ids, status)`, `bulkAddTags(ids, tagIds)`, `exportCsv(filters)`. | FR-PRO |
| P14-FE-008  | Create `app/(dashboard)/prospects/page.tsx` — page "Prospects" : liste des clients avec status=prospect. Barre de recherche. Filtres latéraux (industry Select/Search, department Select, tags MultiSelect, city Input). Bouton "+ Importer des prospects". Bouton "Créer une campagne" (pré-remplit segment avec les filtres actifs). Sélection multiple + barre d'actions bulk (changer statut, ajouter tags, exporter CSV). | FR-PRO |
| P14-FE-009  | Create `components/prospects/prospect-filters.tsx` — panneau de filtres : Search full-text, industry (Combobox avec autocomplétion via `GET /prospects/industries`), department (Select avec liste des 101 départements FR), tags (MultiSelect), ville (Input). Badge de filtres actifs + bouton "Réinitialiser". | FR-PRO |
| P14-FE-010  | Create `components/prospects/prospect-table.tsx` — tableau des prospects : colonnes (checkbox, nom, secteur, département, ville, téléphone, email, tags, actions). Tri par nom/secteur/département. Pagination. Actions par ligne : "Voir", "Convertir en client", "Créer campagne". | FR-PRO |
| P14-FE-011  | Create `components/prospects/convert-to-client-dialog.tsx` — dialog de confirmation : "Passer ce prospect en client actif ?" — met à jour `status = active`. Toast de confirmation. | FR-PRO |
| P14-FE-012  | Extend `components/clients/client-form.tsx` — ajout champ `industry` (Input avec autocomplétion via `GET /prospects/industries`) et champ `department` (Select avec liste des départements FR). Ajout option `prospect` dans le Select de statut. | FR-PRO |
| P14-FE-013  | Extend `components/segments/segment-builder.tsx` — ajout des critères `industry` et `department` dans le dropdown des types de critères. Pour `industry` : champ texte + opérateur (equals/contains/in). Pour `department` : Select multi avec les 101 départements + opérateur (in/not_in). | FR-PRO |
| P14-FE-014  | Create `components/campaigns/create-campaign-from-prospects-dialog.tsx` — dialog lancée depuis la vue Prospects. Pré-crée un `Segment` avec les filtres actifs (industry, department, tags). Propose de nommer la campagne. Redirige vers `/campaigns/create?segment_id={id}`. | FR-PRO |
| P14-FE-015  | Extend sidebar navigation — ajout entrée "Prospects" avec icône `Users` (lucide-react) entre "Clients" et "Projets". Badge avec le count de prospects. Ajout entrée "Importer" dans le sous-menu Prospects. | FR-PRO |

#### 6.3.2 Frontend Tests

| ID          | Test File | Status | Owner |
|-------------|-----------|--------|-------|
| P14-FT-001  | `tests/unit/stores/prospect-import.test.ts` | todo | — |
| P14-FT-002  | `tests/unit/stores/prospects.test.ts` | todo | — |
| P14-FT-003  | `tests/components/prospects/import-wizard/step-upload.test.tsx` | todo | — |
| P14-FT-004  | `tests/components/prospects/import-wizard/step-mapping.test.tsx` | todo | — |
| P14-FT-005  | `tests/components/prospects/import-wizard/step-options.test.tsx` | todo | — |
| P14-FT-006  | `tests/components/prospects/import-wizard/step-results.test.tsx` | todo | — |
| P14-FT-007  | `tests/components/prospects/prospect-filters.test.tsx` | todo | — |
| P14-FT-008  | `tests/components/prospects/prospect-table.test.tsx` | todo | — |
| P14-FT-009  | `tests/components/prospects/convert-to-client-dialog.test.tsx` | todo | — |
| P14-FT-010  | `tests/components/segments/segment-builder-industry.test.tsx` | todo | — |
| P14-FT-011  | `tests/e2e/prospects/import-wizard-flow.spec.ts` | todo | — |
| P14-FT-012  | `tests/e2e/prospects/prospect-to-campaign.spec.ts` | todo | — |

#### 6.3.3 Scénarios E2E (Playwright)

| Scénario | Description |
|----------|-------------|
| `import-wizard-flow.spec.ts` | Aller sur Prospects → Importer → upload `wedding_planner_oise.xlsx` → vérifier colonnes détectées → mapper manuellement → définir tag "wedding-planner" + statut prospect → lancer → attendre completion → vérifier 0 erreur → cliquer "Voir les prospects importés" → vérifier liste non vide |
| `prospect-to-campaign.spec.ts` | Ouvrir vue Prospects → filtrer industry="Wedding Planner" + department="60" → cliquer "Créer une campagne" → vérifier dialog pré-remplie → confirmer → vérifier redirection vers `/campaigns/create` avec segment_id |

---

### 6.4 Sprint 46 — Finalisation & Hardening (Weeks 120–122)

#### 6.4.1 Backend — Hardening & GDPR

| ID          | Task | PRD Ref |
|-------------|------|---------|
| P14-BE-018  | Extend `DataExportService` — inclure `ImportSession` de l'user dans l'export GDPR (liste des imports passés avec dates et compteurs, sans le fichier brut). | FR-IMP |
| P14-BE-019  | Add cleanup command `import-sessions:prune` — supprime les `ImportSession` complétées (status=completed ou failed) de plus de 30 jours et leurs fichiers associés. Planifiée hebdomadairement dans `Console/Kernel.php`. | FR-IMP |
| P14-BE-020  | PHPStan level 8 — s'assurer que tous les nouveaux modèles, services et controllers passent sans erreur. | NFR |
| P14-BE-021  | Pint — vérifier le formatage de tous les fichiers PHP nouveaux/modifiés. | NFR |

#### 6.4.2 Frontend — Hardening

| ID          | Task | PRD Ref |
|-------------|------|---------|
| P14-FE-016  | Ajouter widget "Prospects du mois" sur `app/(dashboard)/page.tsx` — nombre de prospects importés ce mois-ci + bouton "Voir les prospects". Masqué si 0. | FR-PRO |
| P14-FE-017  | ESLint + Prettier — s'assurer que tous les fichiers nouveaux/modifiés passent sans erreur. | NFR |

#### 6.4.3 Tests de hardening

| Test File | Test Cases |
|-----------|-----------|
| `tests/Feature/Import/ImportSessionPruneTest.php` | La commande supprime les sessions > 30 jours, laisse les sessions < 30 jours, laisse les sessions en processing |
| `tests/Feature/Import/ImportSessionGdprTest.php` | L'export GDPR inclut les sessions de l'user, n'inclut pas les sessions d'un autre user |

---

## 7. Exit Criteria

| Critère | Vérification |
|---------|-------------|
| Toutes les tâches `P14-BE-*` et `P14-FE-*` en statut `done` | `docs/dev/phase14.md` |
| Backend coverage >= 80% (`make test-be`) | CI green |
| Frontend coverage >= 80% (`pnpm vitest run --coverage`) | CI green |
| PHPStan level 8 (`./vendor/bin/phpstan analyse`) | 0 erreur |
| Pint (`./vendor/bin/pint --test`) | 0 erreur |
| ESLint + Prettier (`pnpm lint && pnpm format:check`) | 0 erreur |
| `tsc --noEmit` sans erreur | CI uniquement |
| 2 scénarios E2E Playwright verts | `make test-e2e` |
| Import du fichier `wedding_planner_oise.xlsx` (fichier réel) sans erreur | Test manuel |
| Prospects visibles dans vue filtrée industry + department | Test manuel |
| Campagne créée depuis vue Prospects avec segment pré-rempli | Test manuel |
| Tracking ouverture + clic vérifié sur campagne de test | Test manuel |
| Command `import-sessions:prune` s'exécute sans erreur | Test manuel |
| Tag v2.0.0 poussé sur GitHub | `git tag v2.0.0` |

---

## 8. Récapitulatif

| Sprint    | Semaines | Livrable principal                                          | Tasks                      |
|-----------|----------|-------------------------------------------------------------|----------------------------|
| Sprint 43 | 111–113  | Backend enrichissement client (industry, dept, prospect)    | 1 INF + 7 BE + 2 tests     |
| Sprint 44 | 114–116  | Backend import CSV/Excel (sessions, parser, job)            | 2 INF + 10 BE + 4 tests    |
| Sprint 45 | 117–119  | Frontend wizard import + vue Prospects + segment builder    | 15 FE + 12 tests           |
| Sprint 46 | 120–122  | Hardening GDPR, prune, dashboard widget, CI                 | 4 BE/FE + 2 tests          |
| **Total** | **12 sem** | **v2.0.0**                                               | **~38 tâches + 20 tests**  |
