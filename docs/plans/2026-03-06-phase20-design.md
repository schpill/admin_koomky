# Phase 20 Design

**Date:** 2026-03-06
**Phase:** 20
**Source Specs:** `docs/phases/phase20.md`, `docs/dev/phase20.md`

## Goal

Deliver phase 20 end-to-end by extending the existing CRM email stack with a GDPR preference center, contact timezone-aware STO, exportable campaign reports, deliverability scoring, frontend surfaces for all new capabilities, and the required backend/frontend/E2E coverage.

## Existing Context

- Backend is a Laravel API with campaign CRUD/sending, portal auth, unsubscribe links, tracking pixels/clicks, drip and workflow email jobs, campaign analytics, and GDPR full export.
- Frontend is a Next.js app using the app router, Zustand stores, shared campaign components, Vitest component/store tests, and Playwright E2E flows.
- Current campaign behavior already supports STO, AB tests, suppression lists, analytics pages, and portal sessions, which gives phase 20 clear extension points instead of requiring new subsystems.

## Design Decisions

### 1. Preference Center

- Add `communication_preferences` as a contact-scoped table with one row per category:
  - `newsletter`
  - `promotional`
  - `transactional`
- Keep the decision logic in a dedicated `PreferenceCenterService`:
  - `getPreferences(Contact $contact): Collection`
  - `updatePreference(Contact $contact, string $category, bool $subscribed): void`
  - `isAllowed(Contact $contact, string $category): bool`
- Auto-create missing preference rows on first read/update so legacy contacts remain compatible.
- Integrate preference filtering at the source of truth:
  - `SendEmailCampaignJob`
  - `SendDripStepEmailJob`
  - `SendWorkflowEmailJob`
- Transactional emails bypass the filter exactly as specified.
- Add a signed web route for preference center access and pair it with portal API endpoints for the frontend page.
- Extend personalization with `{{preferences_url}}` so email content and footers can expose a stable signed link.

### 2. Timezone-Aware STO

- Add nullable `timezone` to `contacts`.
- Extend `EmailTrackingController::click()` so the first known click can populate a missing timezone from the requester IP through the existing `geoip()` helper pattern, with defensive fallback to `config('app.timezone')`.
- Update `ContactSendTimeService::getOptimalHour()` to compute the best hour in the contact’s local timezone instead of raw server time.
- Update `ContactSendTimeService::getNextSendDelay()` so it:
  - computes the next occurrence of the optimal local hour in the contact timezone
  - converts that candidate back to UTC
  - returns a delay clamped by the STO window as today’s logic already expects

### 3. Exportable Reports

- Keep the existing analytics endpoint and add a dedicated `CampaignReportService` for the richer report contract required by phase 20.
- `CampaignReportService` exposes:
  - `getFullReport(Campaign $campaign): array`
  - `exportCsv(Campaign $campaign): StreamedResponse`
  - `exportPdf(Campaign $campaign): string|Response`
- Reuse campaign recipients and link-click data already persisted to derive:
  - summary metrics
  - link metrics
  - daily timeline
- Generate PDFs from a Blade report view rendered through `barryvdh/laravel-dompdf`, which is the lightest fit for the existing Laravel stack.
- Expose the report through new campaign routes rather than mutating the legacy analytics export contract.

### 4. Deliverability Scoring

- Create `DeliverabilityScoreService` as a pure heuristic analyzer returning:
  - `score`
  - `issues`
- Invoke it from `CampaignController::store()` and `CampaignController::update()`.
- Keep the score non-blocking: campaign save still succeeds, but the API response includes `deliverability`.
- Start with deterministic local rules only:
  - spam words in subject
  - overlong subject
  - excessive uppercase
  - missing unsubscribe link
  - text/images ratio
  - missing `alt` attributes
  - malformed HTML
  - suspicious link domains

### 5. Frontend Integration

- Extend the campaign store payload/types instead of introducing a new store.
- Extend campaign create/edit UI with:
  - `email_category`
  - live deliverability badge/issues panel
- Extend campaign analytics UI with:
  - report export buttons for PDF/CSV
  - timeline chart for opens/clicks
- Add a dedicated portal preferences page under the existing portal route structure.
- Extend the contact editing surface with timezone input and current local time display rather than inventing a separate timezone settings flow.

### 6. GDPR Export

- Extend `DataExportService` to include `communication_preferences`.
- Do not create a separate GDPR endpoint; preserve the existing full export shape and append the new collection.

## Trade-offs

- Preference rows are normalized per category rather than stored as a JSON blob so filtering, auditability, and future category expansion stay straightforward.
- Timezone inference from click IP is heuristic and best-effort, but it is sufficient for the phase and remains overrideable by manual contact editing.
- PDF generation via Blade + dompdf is less visually rich than a headless browser workflow, but it avoids introducing heavier infrastructure and fits the repo’s current Laravel conventions.
- Deliverability scoring is intentionally heuristic and explainable, not predictive, which keeps tests deterministic and the UX understandable.

## Testing Strategy

- TDD by vertical slice:
  - preference center backend first
  - timezone STO backend second
  - report/export and deliverability backend third
  - frontend component/store coverage fourth
  - Playwright portal/report flows last
- Each slice follows red/green/refactor and is verified independently before moving on.

## Risks

- Preference filtering must not accidentally create recipients or activities for blocked contacts.
- STO timezone changes must remain compatible with existing throttling and AB test dispatch behavior.
- Portal preferences need signed-link access without breaking the current portal auth model.
- Report exports depend on consistent analytics data shape across both backend and frontend.
