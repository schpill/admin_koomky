## Analysis

### Project Structure

- `backend/` contient l'API Laravel, les jobs queue, les observers, le scheduler et les tests Pest.
- `frontend/` contient l'UI Next.js, les stores Zustand, les pages dashboard/settings/campaigns et les tests Vitest/Playwright.
- `docs/phases/phase18.md` et `docs/dev/phase18.md` définissent le périmètre intégral de la phase.

### Existing Patterns

- Tracking email existant dans `backend/app/Http/Controllers/Api/V1/EmailTrackingController.php`.
- Analytics campagne existant via `backend/app/Services/CampaignAnalyticsService.php` et `backend/app/Http/Controllers/Api/V1/CampaignAnalyticsController.php`.
- Webhooks sortants existants via `backend/app/Services/WebhookDispatchService.php` et l'UI settings webhooks.
- Dashboard frontend/backend déjà extensibles via `backend/app/Services/DashboardService.php`, `frontend/lib/stores/dashboard.ts`, `frontend/app/(dashboard)/page.tsx`.

### Related Files

- Backend: `backend/app/Services/PersonalizationService.php`, `backend/app/Jobs/SendEmailCampaignJob.php`, `backend/app/Observers/CampaignRecipientObserver.php`, `backend/app/Http/Controllers/UnsubscribeController.php`, `backend/app/Services/DataExportService.php`, `backend/routes/api.php`, `backend/routes/console.php`.
- Frontend: `frontend/app/(dashboard)/campaigns/[id]/analytics/page.tsx`, `frontend/lib/stores/campaigns.ts`, `frontend/lib/constants/webhook-events.ts`, `frontend/components/layout/sidebar.tsx`, `frontend/app/(dashboard)/settings/webhooks/page.tsx`.

### Dependencies

- Laravel queue/scheduler, Pest, factories, Sanctum.
- Next.js app router, Zustand, Vitest, Playwright.

### Current Architecture

- Les emails sont envoyés via `SendEmailCampaignJob` puis `SendCampaignEmailJob`.
- Le tracking email repose sur un token recipient-only; aucun stockage des clics par URL n'existe encore.
- Les webhooks filtrent par `events` JSON côté `WebhookEndpoint`.
- Le dashboard consomme un payload agrégé via `/api/v1/dashboard`.

### Key Constraints

- Respecter TDD: tests rouges avant code de prod.
- Minimiser les refactors hors périmètre.
- Préserver les APIs existantes analytics/webhooks/dashboard.
- Implémenter toute la phase 18, incluant backend, frontend et hardening GDPR.

### Open Questions

- La spec warm-up mentionne une file d'attente des recipients restants sans table explicite; l'implémentation sera portée par la queue existante avec re-dispatch différé des recipients restants dans le job d'envoi.

## Implementation Plan

### Files to Create

| File | Purpose |
| --- | --- |
| `backend/app/Models/CampaignLinkClick.php` | Stockage des clics par URL |
| `backend/app/Models/EmailWarmupPlan.php` | Modèle de plan warm-up |
| `backend/app/Services/WarmupGuardService.php` | Gestion quotas/réinitialisation warm-up |
| `backend/app/Jobs/ResetWarmupCountersJob.php` | Reset quotidien + progression plan |
| `backend/app/Http/Controllers/Api/V1/EmailWarmupPlanController.php` | CRUD + pause/reprise warm-up |
| `backend/database/migrations/*_create_campaign_link_clicks_table.php` | Schéma click tracking |
| `backend/database/migrations/*_create_email_warmup_plans_table.php` | Schéma warm-up |
| `backend/database/migrations/*_add_warmup_fields_to_users_table.php` | Compteurs warm-up user |
| `backend/database/factories/CampaignLinkClickFactory.php` | Tests click tracking |
| `backend/database/factories/EmailWarmupPlanFactory.php` | Tests warm-up |
| `frontend/lib/stores/warmup-plans.ts` | Store Zustand warm-up |
| `frontend/app/(dashboard)/settings/warmup/page.tsx` | UI warm-up |
| `frontend/components/dashboard/warmup-status-widget.tsx` | Widget dashboard |

### Files to Modify

| File | Changes |
| --- | --- |
| `backend/app/Services/PersonalizationService.php` | Réécriture des liens HTML trackés |
| `backend/app/Http/Controllers/Api/V1/EmailTrackingController.php` | Log click/open + webhooks |
| `backend/app/Services/CampaignAnalyticsService.php` | Stats par lien |
| `backend/app/Http/Controllers/Api/V1/CampaignController.php` | Endpoints links + export CSV |
| `backend/app/Services/WebhookDispatchService.php` | Event types email |
| `backend/app/Observers/CampaignRecipientObserver.php` | Webhook bounce |
| `backend/app/Http/Controllers/UnsubscribeController.php` | Webhook unsubscribe |
| `backend/app/Jobs/SendEmailCampaignJob.php` | Quotas warm-up + replanification + webhook sent |
| `backend/app/Services/DashboardService.php` | Résumé warm-up actif |
| `backend/app/Services/DataExportService.php` | Export GDPR link clicks/warmup |
| `backend/routes/api.php` | Routes links + warmup |
| `backend/routes/console.php` | Schedule reset warm-up |
| `frontend/lib/stores/campaigns.ts` | Typage analytics lien |
| `frontend/app/(dashboard)/campaigns/[id]/analytics/page.tsx` | Onglet/table liens + export |
| `frontend/lib/constants/webhook-events.ts` | Ajouter événements email |
| `frontend/components/layout/sidebar.tsx` | Entrée warm-up IP |
| `frontend/app/(dashboard)/page.tsx` | Widget warm-up |
| `frontend/app/(dashboard)/settings/webhooks/page.tsx` | Nouvelles options événements |

### Implementation Order

1. Écrire les tests backend rouges pour link rewrite, click logging et analytics.
2. Implémenter migrations/modèles/services/controllers backend du click tracking.
3. Écrire les tests backend rouges pour webhooks email et warm-up.
4. Implémenter webhooks email, warm-up, scheduler et GDPR export.
5. Écrire les tests frontend rouges pour analytics liens, warm-up UI/store/widget et webhooks settings.
6. Implémenter UI/stores/pages frontend.
7. Exécuter validation, review adversariale, correctifs, puis préparation PR.

### Acceptance Criteria

- [ ] Les liens email sont réécrits en URLs trackées sans toucher `mailto:`/`tel:` ni les URLs déjà trackées.
- [ ] Chaque clic URL crée une trace exploitable et l'analytics par lien est exposée en API/CSV/UI.
- [ ] Les événements `email.opened`, `email.clicked`, `email.bounced`, `email.unsubscribed`, `email.campaign_sent` sont dispatchés en webhook.
- [ ] Un plan warm-up actif limite les envois quotidiens et permet une progression quotidienne.
- [ ] Le dashboard et les settings exposent l'état du warm-up.
- [ ] L'export GDPR inclut les clics de campagne.

### Risks

- HIGH: le quota warm-up dans `SendEmailCampaignJob` doit rester compatible avec STO/A-B tests et ne pas dupliquer les recipients.
- MEDIUM: la réécriture HTML des liens doit rester sûre sur du contenu email arbitraire.
- MEDIUM: l'UI analytics existante n'a pas encore d'onglets, donc l'ajout doit rester simple sans casser la page.
- LOW: l'export CSV et l'export GDPR peuvent nécessiter des ajustements de format attendus par les tests.
