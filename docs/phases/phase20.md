# Phase 20 — Preference Center RGPD, Timezone STO & Rapports Exportables (v2.6)

| Field               | Value                                                        |
|---------------------|--------------------------------------------------------------|
| **Phase**           | 20                                                           |
| **Name**            | Preference Center RGPD, Timezone STO & Rapports Exportables  |
| **Duration**        | Weeks 183–194 (12 weeks)                                     |
| **Milestone**       | M20 — v2.6.0 Release                                        |
| **PRD Sections**    | §4.36 FR-PREF (nouveau), §4.37 FR-TZ (nouveau), §4.38 FR-RPT (nouveau) |
| **Prerequisite**    | Phase 19 fully completed and tagged `v2.5.0`                 |
| **Status**          | todo                                                         |

---

## 1. Phase Objectives

| ID        | Objective                                                                                                                                                            |
|-----------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| P20-OBJ-1 | Permettre aux contacts de gérer leurs **préférences d'abonnement** par catégorie (newsletter, promotions, transactionnel) via une page dédiée dans le portail client  |
| P20-OBJ-2 | Respecter ces préférences dans tous les envois (campagnes, drip, workflows) : ne jamais envoyer une catégorie refusée                                                |
| P20-OBJ-3 | Rendre le **STO timezone-aware** : utiliser le fuseau horaire du contact pour calculer l'heure d'envoi en heure locale, pas en heure serveur                         |
| P20-OBJ-4 | Permettre l'**export des rapports de campagne** en PDF et CSV (métriques complètes + évolution temporelle)                                                            |
| P20-OBJ-5 | Exposer un **score de délivrabilité** de l'email avant envoi : analyse des éléments susceptibles de déclencher les filtres anti-spam                                  |
| P20-OBJ-6 | Maintenir une couverture de tests >= 80% backend et frontend                                                                                                         |

---

## 2. Contexte & Analyse de l'existant

### 2.1 Ce qui est déjà en place

| Besoin | Brique existante | Phase d'origine |
|--------|-----------------|-----------------|
| Portail client (magic link, dashboard, factures) | Phase 6 | Phase 6 |
| `UnsubscribeController` + `SuppressionService` | Phase 16 | Phase 16 |
| `ContactSendTimeService` (heure modale par contact) | Phase 17 | Phase 17 |
| `CampaignAnalyticsService` (métriques globales) | Phase 3 | Phase 3 |
| `PersonalizationService` avec valeurs contact | Phase 3/15 | Phase 3/15 |
| `DataExportService` GDPR | Phase 8 | Phase 8 |

### 2.2 Analyse du gap

| Besoin | Gap actuel | Solution Phase 20 |
|--------|-----------|-------------------|
| Préférences par catégorie | Désabonnement global uniquement | Table `communication_preferences` + `PreferenceCenterService` + page portail |
| Respect préférences à l'envoi | Aucun filtre par catégorie | Extension `SendEmailCampaignJob`, `SendDripStepEmailJob`, `SendWorkflowEmailJob` |
| STO en heure locale | STO calcule en UTC serveur | Colonne `timezone` sur `Contact` + conversion dans `ContactSendTimeService` |
| Export rapport PDF | Aucun export | `CampaignReportService` + dompdf/Browsershot + endpoint stream |
| Export rapport CSV | CSV des recipients uniquement | Export structuré avec métriques + courbe temporelle |
| Score délivrabilité | Aucun | `DeliverabilityScoreService` (analyse heuristique du HTML) |

---

## 3. Choix techniques

### 3.1 Modèle de données

**Migrations :**

| Migration | Description |
|-----------|-------------|
| `create_communication_preferences_table` | UUID PK, `user_id` FK (l'opérateur CRM), `contact_id` FK CASCADE, `category` ENUM(`newsletter`, `promotional`, `transactional`), `subscribed` BOOLEAN DEFAULT true, `updated_at` TIMESTAMP, timestamps. Index unique `(contact_id, category)`. |
| `add_timezone_to_contacts_table` | `timezone` VARCHAR(64) nullable (ex: `Europe/Paris`, `America/New_York`). DEFAULT null = fuseau horaire serveur. |
| `add_category_to_campaigns_table` | `email_category` ENUM(`newsletter`, `promotional`, `transactional`) DEFAULT `promotional`. Les emails transactionnels ignorent les préférences. |

### 3.2 Logique Preference Center

```
PreferenceCenterService :
- getPreferences(Contact $contact): Collection  → toutes catégories (créées si absentes avec subscribed=true)
- updatePreference(Contact $contact, string $category, bool $subscribed): void
- isAllowed(Contact $contact, string $category): bool

Intégration à l'envoi :
- SendEmailCampaignJob, SendDripStepEmailJob, SendWorkflowEmailJob :
  Si email_category != 'transactional' :
    → PreferenceCenterService::isAllowed($contact, $campaign->email_category)
    → Si false : skip le contact (sans créer de CampaignRecipient)

Portail client (page /portal/preferences) :
- GET  → affiche les 3 catégories avec toggle activé/désactivé
- POST → met à jour les préférences + log dans ActivitiesLog
- URL signée temporaire (30 jours) accessible depuis le footer des emails

Footer email :
- Lien "Gérer mes préférences" → URL signée portail/preferences/{contact}
- Lien "Me désabonner de tout" → URL signée désabonnement global (comportement actuel)
```

### 3.3 Logique Timezone STO

```
Contact.timezone : stocké en base (ex: "Europe/Paris")
  → Peuplé automatiquement si non renseigné via l'IP du premier clic tracé (IP → timezone via libération locale)
  → Modifiable manuellement dans la fiche contact

ContactSendTimeService::getOptimalHour() modifié :
1. Récupérer le timezone du contact (default: config('app.timezone'))
2. Lors du regroupement par heure : convertir opened_at en heure locale du contact avant extraction
   → EXTRACT(HOUR FROM (opened_at AT TIME ZONE 'UTC' AT TIME ZONE :timezone))
   → Paramètre :timezone bindé (jamais concaténé)

getNextSendDelay() modifié :
1. Calculer la prochaine occurrence de optimal_hour EN HEURE LOCALE du contact
2. Convertir en UTC pour calculer le délai en secondes depuis now()
```

### 3.4 Logique Export Rapports

```
CampaignReportService :
- getFullReport(Campaign $campaign): array
  {
    summary: {sent, delivered, opened, clicked, bounced, unsubscribed, open_rate, click_rate, ctor},
    links: [{url, total_clicks, unique_clicks}],
    timeline: [{date, opens, clicks}]  (agrégé par jour)
  }
- exportPdf(Campaign $campaign): string  (chemin fichier temp)
  → Vue Blade dédiée compilée avec dompdf (ou Browsershot si Chrome disponible)
- exportCsv(Campaign $campaign): StreamedResponse
  → Colonnes : date, email, status, opened_at, clicked_at, bounce_type, unsubscribed_at

Endpoints :
- GET /campaigns/{campaign}/report           → JSON (getFullReport)
- GET /campaigns/{campaign}/report/pdf       → stream PDF
- GET /campaigns/{campaign}/report/csv       → stream CSV
```

### 3.5 Logique Score Délivrabilité

```
DeliverabilityScoreService::analyze(string $subject, string $htmlContent): array
{
  score: int (0–100, 100 = parfait),
  issues: [{severity: "error"|"warning"|"info", message: string}]
}

Règles heuristiques (non exhaustif) :
- Présence de mots spam courants dans le sujet (FREE, URGENT, !!!, ...)  → -10 pts chacun
- Ratio texte/images < 0.3  → warning -10
- Aucun lien de désabonnement  → error -20
- Sujet > 60 caractères  → warning -5
- Sujet en MAJUSCULES > 50%  → warning -10
- Pas d'attribut alt sur les images  → info -2 par image
- HTML non valide (balises non fermées)  → warning -5
- Liens pointant vers des domaines suspects (liste noire statique)  → error -15

Intégration :
- Appel depuis CampaignController::store()/update() → retourné dans la réponse (warnings non bloquants)
- UI : badge de score 0–100 avec liste des problèmes dans le formulaire de campagne
```

---

## 4. Entry Criteria

- Phase 19 exit criteria 100% satisfaits.
- CI verts sur `main`.
- `v2.5.0` tagué et déployé.
- `ContactSendTimeService`, `SendEmailCampaignJob`, `PortalController` stables.

---

## 5. Scope — Requirement Traceability

### 5.1 Preference Center (FR-PREF)

| Feature | Priority | Included |
|---------|----------|----------|
| Table `communication_preferences` | High | Yes |
| `PreferenceCenterService` (get, update, isAllowed) | High | Yes |
| Page portail `/portal/preferences` | High | Yes |
| Respect préférences dans campagnes email | High | Yes |
| Respect préférences dans drip + workflows | High | Yes |
| Lien "Gérer mes préférences" dans footer email | High | Yes |
| Emails transactionnels ignorent les préférences | High | Yes |
| Export GDPR des préférences | High | Yes |
| Préférences SMS | Low | No |
| Préférences par sous-catégorie personnalisée | Low | No |

### 5.2 Timezone STO (FR-TZ)

| Feature | Priority | Included |
|---------|----------|----------|
| Colonne `timezone` sur Contact | High | Yes |
| Détection automatique du timezone via IP du premier clic | Medium | Yes |
| Saisie manuelle du timezone dans la fiche contact | High | Yes |
| STO calcule l'heure optimale en heure locale du contact | High | Yes |
| `getNextSendDelay()` retourne un délai en UTC correct | High | Yes |
| Prévisualisation de l'heure d'envoi locale estimée dans l'UI | Medium | Yes |
| STO multi-timezone dans les rapports | Low | No |

### 5.3 Rapports Exportables (FR-RPT)

| Feature | Priority | Included |
|---------|----------|----------|
| `CampaignReportService::getFullReport()` | High | Yes |
| Export PDF (dompdf) | High | Yes |
| Export CSV (recipients + métriques) | High | Yes |
| Timeline journalière opens/clicks | Medium | Yes |
| Graphique dans le PDF | Medium | Yes |
| Export agrégé multi-campagnes | Low | No |
| Rapport programmé par email (hebdo) | Low | No |

### 5.4 Score Délivrabilité (FR-DELIV)

| Feature | Priority | Included |
|---------|----------|----------|
| `DeliverabilityScoreService` (analyse heuristique) | High | Yes |
| Intégration dans CampaignController (warnings non bloquants) | High | Yes |
| UI : badge score + liste des problèmes | High | Yes |
| Détection mots spam dans sujet | High | Yes |
| Vérification lien désabonnement | High | Yes |
| Ratio texte/images | Medium | Yes |
| Connexion à une API externe (Mail-Tester) | Low | No |

---

## 6. Detailed Sprint Breakdown

### 6.1 Sprint 67 — Backend Preference Center & Timezone STO (Weeks 183–185)

#### 6.1.1 Infrastructure & Database

| ID              | Migration |
|-----------------|-----------|
| P20-BE-INF-01   | `create_communication_preferences_table` — Voir §3.1 |
| P20-BE-INF-02   | `add_timezone_to_contacts_table` — Voir §3.1 |
| P20-BE-INF-03   | `add_category_to_campaigns_table` — Voir §3.1 |

#### 6.1.2 Backend Tasks

| ID          | Task |
|-------------|------|
| P20-BE-001  | Create `CommunicationPreference` model — `HasUuids`, `HasFactory`. Fillable, casts subscribed bool. Relations `contact()`. |
| P20-BE-002  | Create `PreferenceCenterService` — `getPreferences(Contact $contact): Collection`, `updatePreference(Contact $contact, string $category, bool $subscribed): void`, `isAllowed(Contact $contact, string $category): bool`. Crée les 3 préférences par défaut (subscribed=true) si absentes. |
| P20-BE-003  | Extend `Campaign` model — `email_category` dans `$fillable` + cast. |
| P20-BE-004  | Extend `StoreCampaignRequest` — `email_category` ENUM(newsletter/promotional/transactional) nullable (défaut promotional). |
| P20-BE-005  | Extend `SendEmailCampaignJob` — Avant dispatch de chaque email : `PreferenceCenterService::isAllowed($contact, $campaign->email_category)`. Skip si false (sauf transactionnel). |
| P20-BE-006  | Extend `SendDripStepEmailJob` et `SendWorkflowEmailJob` — Même logique de vérification préférence. |
| P20-BE-007  | Create `PreferenceCenterController` (portail) — `GET /portal/preferences/{contact}` (URL signée), `POST /portal/preferences/{contact}`. Retourne et met à jour les préférences. |
| P20-BE-008  | Extend `PersonalizationService` — Ajouter variable `{{preferences_url}}` dans les variables disponibles : génère l'URL signée portail/preferences. |
| P20-BE-009  | Extend `ContactSendTimeService::getOptimalHour()` — Prendre en compte `contact->timezone`. Requête SQL : `EXTRACT(HOUR FROM (opened_at AT TIME ZONE 'UTC' AT TIME ZONE :tz))`. Fallback sur UTC si timezone null. |
| P20-BE-010  | Extend `ContactSendTimeService::getNextSendDelay()` — Calculer la prochaine occurrence de optimal_hour en heure locale du contact, convertir en UTC pour le délai. |
| P20-BE-011  | Extend `Contact` model — `timezone` dans `$fillable`. Extend `EmailTrackingController::click()` — Si `contact->timezone` est null : détecter le timezone depuis `$request->ip()` via `geoip()` helper (dusk/geoip ou IP2Location light). |
| P20-BE-012  | PHPStan level 8 + Pint. |

#### 6.1.3 Backend Tests (TDD)

| ID          | Test File |
|-------------|-----------|
| P20-BT-001  | `tests/Unit/Services/PreferenceCenterServiceTest.php` — préférences par défaut créées, isAllowed respecte subscribed, transactionnel toujours autorisé |
| P20-BT-002  | `tests/Feature/Portal/PreferenceCenterPortalTest.php` — GET retourne les 3 catégories, POST met à jour, URL invalide → 403 |
| P20-BT-003  | `tests/Feature/Campaign/CampaignPreferenceFilterTest.php` — contact avec promotional=false ne reçoit pas la campagne promotional, reçoit la transactionnelle |
| P20-BT-004  | `tests/Unit/Services/ContactSendTimeServiceTimezoneTest.php` — heure optimale calculée en heure locale, délai converti en UTC correct |

---

### 6.2 Sprint 68 — Backend Rapports & Score Délivrabilité (Weeks 186–188)

#### 6.2.1 Backend Tasks

| ID          | Task |
|-------------|------|
| P20-BE-013  | Create `CampaignReportService` — `getFullReport(Campaign $campaign): array` (summary + links + timeline). `exportCsv(Campaign $campaign): StreamedResponse`. `exportPdf(Campaign $campaign): StreamedResponse` (dompdf via `barryvdh/laravel-dompdf`). |
| P20-BE-014  | Create `resources/views/reports/campaign-report.blade.php` — Template HTML du rapport PDF : logo user, titre campagne, tableau summary, tableau liens (top 10), graphique timeline (SVG inline ou tableau textuel). |
| P20-BE-015  | Extend `CampaignController` — `GET /campaigns/{campaign}/report` → JSON. `GET /campaigns/{campaign}/report/pdf` → stream. `GET /campaigns/{campaign}/report/csv` → stream. Policy : ownership. |
| P20-BE-016  | Create `DeliverabilityScoreService` — `analyze(string $subject, string $htmlContent): array{score:int, issues:array}`. Règles heuristiques : mots spam, ratio texte/images, lien désabonnement, longueur sujet, majuscules excessives, alt images manquants, HTML invalide. |
| P20-BE-017  | Extend `CampaignController::store()`/`update()` — Appeler `DeliverabilityScoreService::analyze()` sur subject + content. Retourner `deliverability` dans la réponse (warnings non bloquants, n'empêchent pas la création). |
| P20-BE-018  | PHPStan level 8 + Pint. |

#### 6.2.2 Backend Tests (TDD)

| ID          | Test File |
|-------------|-----------|
| P20-BT-005  | `tests/Unit/Services/CampaignReportServiceTest.php` — getFullReport retourne les bonnes métriques, timeline agrégée par jour, CSV streamé avec les bonnes colonnes |
| P20-BT-006  | `tests/Feature/Campaign/CampaignReportExportTest.php` — GET /report → JSON correct, GET /report/csv → Content-Type text/csv, GET /report/pdf → Content-Type application/pdf, ownership vérifié |
| P20-BT-007  | `tests/Unit/Services/DeliverabilityScoreServiceTest.php` — sujet spam → score réduit, lien désabonnement absent → error, score 100 sur contenu propre |

---

### 6.3 Sprint 69 — Frontend Preferences, Timezone & Rapports (Weeks 189–191)

#### 6.3.1 Frontend Tasks

| ID          | Task |
|-------------|------|
| P20-FE-001  | Create `app/(portal)/preferences/[contact]/page.tsx` — Page portail de gestion des préférences. Affiche les 3 catégories avec toggle activé/désactivé. Bouton "Se désabonner de tout". Message de confirmation après sauvegarde. |
| P20-FE-002  | Extend `components/campaigns/campaign-form.tsx` — Select `email_category` (newsletter / promotionnel / transactionnel). Badge de score délivrabilité (0–100) mis à jour en temps réel sur le contenu/sujet. Liste déroulante des problèmes détectés. |
| P20-FE-003  | Extend `components/clients/client-contact-detail.tsx` (ou équivalent) — Champ timezone : Select avec liste des timezones IANA (react-select filtrable). Affichage de l'heure locale actuelle du contact. |
| P20-FE-004  | Extend `components/campaigns/analytics.tsx` — Boutons "Exporter PDF" et "Exporter CSV" dans l'en-tête. Graphique timeline opens/clicks (recharts LineChart). |
| P20-FE-005  | Create `components/campaigns/deliverability-badge.tsx` — Badge coloré (vert ≥ 80, orange 50–79, rouge < 50) + popover listant les issues avec icône de sévérité. |
| P20-FE-006  | Extend `lib/stores/campaigns.ts` — Ajouter `email_category` dans le payload. Stocker `deliverability` retourné par l'API. |

#### 6.3.2 Frontend Tests

| ID          | Test File |
|-------------|-----------|
| P20-FT-001  | `tests/components/preferences/preference-center.test.tsx` — rendu des 3 toggles, mise à jour appelée au changement |
| P20-FT-002  | `tests/components/campaigns/deliverability-badge.test.tsx` — couleur selon score, liste issues affichée |
| P20-FT-003  | `tests/components/campaigns/campaign-analytics-export.test.tsx` — boutons export déclenchent le bon endpoint |
| P20-FT-004  | `tests/e2e/portal/preference-center-flow.spec.ts` — accès URL signée → toggle → sauvegarde → vérifier en base |
| P20-FT-005  | `tests/e2e/campaigns/report-export-flow.spec.ts` — exporter PDF → fichier téléchargé, exporter CSV → données correctes |

---

### 6.4 Sprint 70 — Hardening GDPR & CI (Weeks 192–194)

#### 6.4.1 Backend Tasks

| ID          | Task |
|-------------|------|
| P20-BE-019  | Extend `DataExportService` — Inclure `CommunicationPreference` dans l'export GDPR (catégories + statut abonnement). |
| P20-BE-020  | PHPStan level 8 + Pint — 0 erreur sur tous les fichiers nouveaux/modifiés. |

#### 6.4.2 Frontend Tasks

| ID          | Task |
|-------------|------|
| P20-FE-007  | ESLint + Prettier — 0 erreur sur tous les fichiers nouveaux/modifiés. |

#### 6.4.3 Backend Tests

| ID          | Test File |
|-------------|-----------|
| P20-BT-008  | `tests/Feature/Portal/PreferenceCenterGdprTest.php` — export GDPR inclut les préférences du contact, pas celles d'un autre contact |

---

## 7. Exit Criteria

| Critère | Vérification |
|---------|-------------|
| Toutes les tâches `P20-BE-*` et `P20-FE-*` en statut `done` | `docs/dev/phase20.md` |
| Backend coverage >= 80% | CI green |
| Frontend coverage >= 80% | CI green |
| PHPStan level 8 — 0 erreur | CI green |
| Pint + ESLint + Prettier — 0 erreur | CI green |
| Contact avec promotional=false ne reçoit pas les campagnes promo | Test manuel |
| STO envoie à l'heure locale du contact (vérifier sur contact TZ=America/New_York) | Test manuel |
| Export PDF téléchargeable avec les bonnes métriques | Test manuel |
| Badge délivrabilité rouge sur sujet contenant "GRATUIT URGENT" | Test manuel |
| Page portail préférences accessible via lien footer email | Test manuel |
| Tag `v2.6.0` poussé sur GitHub | `git tag v2.6.0` |

---

## 8. Récapitulatif

| Sprint    | Semaines | Livrable principal                                              | Tasks                           |
|-----------|----------|-----------------------------------------------------------------|---------------------------------|
| Sprint 67 | 183–185  | Backend preference center + timezone STO                       | 3 INF + 12 BE + 4 tests         |
| Sprint 68 | 186–188  | Backend rapports exportables + score délivrabilité             | 6 BE + 3 tests                  |
| Sprint 69 | 189–191  | Frontend préférences portail + timezone + exports + badge      | 6 FE + 5 tests                  |
| Sprint 70 | 192–194  | Hardening GDPR, PHPStan, ESLint, CI                            | 2 BE/FE + 1 test                |
| **Total** | **12 sem** | **v2.6.0**                                                   | **~3 INF + 26 BE/FE + 13 tests** |
