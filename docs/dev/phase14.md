# Phase 14 — Task Tracking

> **Status**: todo
> **Prerequisite**: Phase 13 fully merged and tagged `v1.9.0`
> **Spec**: [docs/phases/phase14.md](../phases/phase14.md)

---

## Sprint 43 — Backend Enrichissement Client & Segmentation (Weeks 111–113)

### Infrastructure & Database

| ID              | Task                                                                                                                                                                                | Status | Owner |
|-----------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P14-BE-INF-01   | Migration `add_prospecting_fields_to_clients_table` — colonnes `industry VARCHAR(255) nullable`, `department VARCHAR(10) nullable`. Index `(user_id, industry)`, `(user_id, department)`. | todo | — |

### Modèle, Service & Controller

| ID          | Task                                                                                                                                                                                                                                                                  | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P14-BE-001  | Extend `Client` model — `industry`, `department` dans `$fillable`. `toSearchableArray()` mis à jour. Scopes `byIndustry()`, `byDepartment()`, `prospects()`.                                                                                                          | todo | — |
| P14-BE-002  | Extend `StoreClientRequest` / `UpdateClientRequest` — règles `industry` nullable max 255, `department` nullable max 10. Ajout `prospect` aux statuts valides.                                                                                                          | todo | — |
| P14-BE-003  | Extend `SegmentFilterEngine` — critère `industry` (equals/not_equals/contains/in), critère `department` (equals/not_equals/in). Join depuis `contacts` vers `clients`.                                                                                                | todo | — |
| P14-BE-004  | Create `ProspectMetaController` — `GET /api/v1/prospects/industries` (valeurs distinctes user), `GET /api/v1/prospects/departments` (liste statique 101 départements FR).                                                                                             | todo | — |
| P14-BE-005  | Extend `ClientController::index()` — filtres `?industry=`, `?department=`, `?status=prospect`. Mise à jour attributs filtrables Meilisearch.                                                                                                                          | todo | — |
| P14-BE-006  | Extend `WebhookDispatchService` — événement `client.imported` avec payload `{session_id, success_rows, error_rows, tags_applied}`.                                                                                                                                    | todo | — |
| P14-BE-007  | Extend `DataExportService` — inclure `industry` et `department` dans l'export GDPR des clients.                                                                                                                                                                       | todo | — |

### Backend Tests (TDD)

| ID          | Test File                                                                         | Status | Owner |
|-------------|-----------------------------------------------------------------------------------|--------|-------|
| P14-BT-001  | `tests/Unit/Services/SegmentFilterEngineTest.php` (extension — critères industry/department) | todo | — |
| P14-BT-002  | `tests/Feature/Clients/ClientProspectFilterTest.php`                              | todo | — |

---

## Sprint 44 — Backend Import CSV/Excel (Weeks 114–116)

### Infrastructure & Database

| ID              | Task                                                                                                                                                                                | Status | Owner |
|-----------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P14-BE-INF-02   | Migration `create_import_sessions_table` — UUID PK, user_id FK, filename, original_filename, status ENUM, compteurs (total/processed/success/error rows), column_mapping JSON, default_tags JSON, options JSON, error_summary, completed_at, timestamps. | todo | — |
| P14-BE-INF-03   | Migration `create_import_session_errors_table` — UUID PK, session_id FK CASCADE, row_number INT, raw_data JSON, error_message TEXT, timestamps. Index `(session_id, row_number)`. | todo | — |
| P14-BE-INF-04   | `composer require phpoffice/phpspreadsheet` — vérifier si `league/csv` est déjà présent, sinon l'ajouter.                                                                          | todo | — |

### Modèles, Services & Controller

| ID          | Task                                                                                                                                                                                                                                                                  | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P14-BE-008  | Create `ImportSession` model — `HasUuids`, `HasFactory`. Fillable complet. Casts : column_mapping/default_tags/options array, completed_at datetime. Relations : `user()`, `errors()`. Méthodes `isProcessing()`, `progressPercent()`.                                | todo | — |
| P14-BE-009  | Create `ImportSessionError` model — `HasUuids`, `HasFactory`. Fillable : session_id, row_number, raw_data, error_message. Cast `raw_data` array. Relation `session()`.                                                                                               | todo | — |
| P14-BE-010  | Create `ImportSessionPolicy` — ownership standard. Méthodes : `viewAny`, `view`, `update`, `delete`.                                                                                                                                                                 | todo | — |
| P14-BE-011  | Create `FileParserService` — `parse(string $path, string $extension): array` → `{headers, rows}`. Supporte CSV + XLSX. Max 10 000 lignes. Normalisation UTF-8.                                                                                                       | todo | — |
| P14-BE-012  | Create `ColumnDetectorService` — `detect(array $headers): array` → mapping `{header → detected_field\|null}` via alias matching (insensible casse + accents). Voir §3.2 de la spec.                                                                                  | todo | — |
| P14-BE-013  | Create `ProspectImportService` — `import(ImportSession $session): void`. Mapping colonnes → Client + Contact + Tags. Gestion doublons (skip/update). Mise à jour compteurs tous les 50 enregistrements. Status final + completed_at.                                  | todo | — |
| P14-BE-014  | Create `ImportSessionController` — méthodes : `index`, `store` (upload + parse + detect), `show`, `update` (mapping/options), `process` (dispatch job 202), `errors` (paginé), `exportErrors` (CSV), `destroy`.                                                     | todo | — |
| P14-BE-015  | Create `ProcessProspectImportJob` — queue `imports`. Charge fichier via `FileParserService`, appelle `ProspectImportService`. Gestion exception fatale → status=failed. Webhook `client.imported` en fin.                                                             | todo | — |
| P14-BE-016  | Create `StoreImportSessionRequest` — règles : `file` required, mimes csv/xlsx/xls, max 5120 Ko.                                                                                                                                                                      | todo | — |
| P14-BE-017  | Register routes — `GET/POST /import-sessions`, `GET/PATCH/DELETE /import-sessions/{session}`, `POST /import-sessions/{session}/process`, `GET /import-sessions/{session}/errors`, `GET /import-sessions/{session}/errors/export`.                                    | todo | — |

### Backend Tests (TDD)

| ID          | Test File                                                                         | Status | Owner |
|-------------|-----------------------------------------------------------------------------------|--------|-------|
| P14-BT-003  | `tests/Unit/Services/FileParserServiceTest.php`                                   | todo | — |
| P14-BT-004  | `tests/Unit/Services/ColumnDetectorServiceTest.php`                               | todo | — |
| P14-BT-005  | `tests/Unit/Services/ProspectImportServiceTest.php`                               | todo | — |
| P14-BT-006  | `tests/Feature/Import/ImportSessionCrudTest.php`                                  | todo | — |

---

## Sprint 45 — Frontend Import Wizard & Vue Prospects (Weeks 117–119)

### Frontend Tasks

| ID          | Task                                                                                                                                                                                                                                  | Status | Owner |
|-------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P14-FE-001  | Create `lib/stores/prospect-import.ts` Zustand store — state session, columnList, previewRows, detectedMapping, columnMapping, defaultTags, options, progress. Actions : uploadFile, updateMapping, updateOptions, processImport, pollStatus (3s), fetchErrors, exportErrors, reset. | todo | — |
| P14-FE-002  | Create `app/(dashboard)/prospects/import/page.tsx` — wizard 4 étapes avec stepper : Upload → Mapping → Options → Résultats.                                                                                                          | todo | — |
| P14-FE-003  | Create `components/prospects/import-wizard/step-upload.tsx` — drag-and-drop zone (.xlsx/.csv), barre de progression upload, gestion erreur taille/format.                                                                            | todo | — |
| P14-FE-004  | Create `components/prospects/import-wizard/step-mapping.tsx` — tableau header → Select champ CRM. Pré-rempli avec detectedMapping. Aperçu 5 premières lignes.                                                                       | todo | — |
| P14-FE-005  | Create `components/prospects/import-wizard/step-options.tsx` — ComboBox tags multi-sélection, Select statut par défaut, RadioGroup stratégie doublons.                                                                               | todo | — |
| P14-FE-006  | Create `components/prospects/import-wizard/step-results.tsx` — barre de progression live, résumé final (importés/mis à jour/erreurs), tableau erreurs paginé, bouton export CSV erreurs.                                             | todo | — |
| P14-FE-007  | Create `lib/stores/prospects.ts` Zustand store — state clients[], total, page, filters. Actions : fetchProspects, convertToClient, bulkUpdateStatus, bulkAddTags, exportCsv.                                                         | todo | — |
| P14-FE-008  | Create `app/(dashboard)/prospects/page.tsx` — liste prospects, barre recherche, filtres, bouton "Importer", bouton "Créer une campagne", sélection bulk + actions bulk.                                                              | todo | — |
| P14-FE-009  | Create `components/prospects/prospect-filters.tsx` — Search, industry Combobox (autocomplétion API), department Select (101 depts FR), tags MultiSelect, ville Input. Badge filtres actifs + reset.                                 | todo | — |
| P14-FE-010  | Create `components/prospects/prospect-table.tsx` — colonnes : checkbox, nom, secteur, département, ville, téléphone, email, tags, actions. Tri + pagination.                                                                         | todo | — |
| P14-FE-011  | Create `components/prospects/convert-to-client-dialog.tsx` — dialog confirmation passage status=active.                                                                                                                              | todo | — |
| P14-FE-012  | Extend `components/clients/client-form.tsx` — champ `industry` (Input autocomplétion) + `department` (Select depts FR) + statut `prospect`.                                                                                          | todo | — |
| P14-FE-013  | Extend `components/segments/segment-builder.tsx` — critères `industry` (equals/contains/in + Input) et `department` (in/not_in + Select multi depts FR).                                                                            | todo | — |
| P14-FE-014  | Create `components/campaigns/create-campaign-from-prospects-dialog.tsx` — pré-crée un Segment depuis les filtres actifs, propose un nom de campagne, redirige vers `/campaigns/create?segment_id={id}`.                              | todo | — |
| P14-FE-015  | Extend sidebar — entrée "Prospects" (icône Users, badge count) entre Clients et Projets. Sous-menu "Importer".                                                                                                                       | todo | — |

### Frontend Tests

| ID          | Test File                                                                                           | Status | Owner |
|-------------|-----------------------------------------------------------------------------------------------------|--------|-------|
| P14-FT-001  | `tests/unit/stores/prospect-import.test.ts`                                                         | todo | — |
| P14-FT-002  | `tests/unit/stores/prospects.test.ts`                                                               | todo | — |
| P14-FT-003  | `tests/components/prospects/import-wizard/step-upload.test.tsx`                                     | todo | — |
| P14-FT-004  | `tests/components/prospects/import-wizard/step-mapping.test.tsx`                                    | todo | — |
| P14-FT-005  | `tests/components/prospects/import-wizard/step-options.test.tsx`                                    | todo | — |
| P14-FT-006  | `tests/components/prospects/import-wizard/step-results.test.tsx`                                    | todo | — |
| P14-FT-007  | `tests/components/prospects/prospect-filters.test.tsx`                                              | todo | — |
| P14-FT-008  | `tests/components/prospects/prospect-table.test.tsx`                                                | todo | — |
| P14-FT-009  | `tests/components/prospects/convert-to-client-dialog.test.tsx`                                      | todo | — |
| P14-FT-010  | `tests/components/segments/segment-builder-industry.test.tsx`                                       | todo | — |
| P14-FT-011  | `tests/e2e/prospects/import-wizard-flow.spec.ts`                                                    | todo | — |
| P14-FT-012  | `tests/e2e/prospects/prospect-to-campaign.spec.ts`                                                  | todo | — |

---

## Sprint 46 — Hardening GDPR, Prune & Dashboard (Weeks 120–122)

### Backend Tasks

| ID          | Task                                                                                                                                                                           | Status | Owner |
|-------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P14-BE-018  | Extend `DataExportService` — inclure `ImportSession` dans l'export GDPR (liste imports passés : date, compteurs, sans fichier brut).                                           | todo | — |
| P14-BE-019  | Add command `import-sessions:prune` — supprime sessions complétées/failed de plus de 30 jours + fichiers associés. Planifiée hebdomadairement dans `Console/Kernel.php`.       | todo | — |
| P14-BE-020  | PHPStan level 8 — vérification 0 erreur sur tous les fichiers nouveaux/modifiés de la phase.                                                                                   | todo | — |
| P14-BE-021  | Pint — formatage 0 erreur sur tous les fichiers PHP nouveaux/modifiés.                                                                                                         | todo | — |

### Frontend Tasks

| ID          | Task                                                                                                                                                                           | Status | Owner |
|-------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|-------|
| P14-FE-016  | Extend `app/(dashboard)/page.tsx` — widget "Prospects du mois" : count prospects importés ce mois + bouton "Voir les prospects". Masqué si 0.                                 | todo | — |
| P14-FE-017  | ESLint + Prettier — 0 erreur sur tous les fichiers nouveaux/modifiés de la phase.                                                                                              | todo | — |

### Backend Tests

| ID          | Test File                                                                         | Status | Owner |
|-------------|-----------------------------------------------------------------------------------|--------|-------|
| P14-BT-007  | `tests/Feature/Import/ImportSessionPruneTest.php`                                 | todo | — |
| P14-BT-008  | `tests/Feature/Import/ImportSessionGdprTest.php`                                  | todo | — |

---

## Récapitulatif

| Sprint    | Semaines | Livrable principal                                          | Tasks                      |
|-----------|----------|-------------------------------------------------------------|----------------------------|
| Sprint 43 | 111–113  | Backend enrichissement client (industry, dept, prospect)    | 1 INF + 7 BE + 2 tests     |
| Sprint 44 | 114–116  | Backend import CSV/Excel (sessions, parser, job)            | 3 INF + 10 BE + 4 tests    |
| Sprint 45 | 117–119  | Frontend wizard import + vue Prospects + segment builder    | 15 FE + 12 tests           |
| Sprint 46 | 120–122  | Hardening GDPR, prune, dashboard widget, CI                 | 4 BE/FE + 2 tests          |
| **Total** | **12 sem** | **v2.0.0**                                               | **~42 tâches + 20 tests**  |
