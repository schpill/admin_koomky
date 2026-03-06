# Phase 17 Design

**Date:** 2026-03-06
**Phase:** 17
**Source Specs:** `docs/phases/phase17.md`, `docs/dev/phase17.md`

## Goal

Deliver phase 17 end-to-end by extending the existing campaign stack with lead scoring, send time optimization, dynamic content, frontend controls, and GDPR export support without introducing a new template engine or separate scoring subsystem.

## Existing Context

- Backend is a Laravel API with campaign sending, personalization, segments, drip campaigns, suppression lists, and GDPR export already implemented.
- Frontend is a Next.js app with Zustand stores, dashboard/settings pages, campaign builders, and component-oriented tests.
- Current campaign behavior already tracks `opened_at`, `clicked_at`, bounce metadata, and unsubscribe state on recipients/contacts.

## Design Decisions

### 1. Lead Scoring

- Add `email_score` and `email_score_updated_at` directly on `contacts`.
- Persist scoring configuration per user in `scoring_rules`.
- Persist applied point events in `contact_score_events` for auditability and expiry-based recalculation.
- Centralize score mutation in `ContactScoreService`.
- Integrate score writes at the source of truth:
  - email open tracking
  - email click tracking
  - unsubscribe handling
  - hard bounce observer
  - campaign send dispatch
- Extend the segment engine to filter on `contacts.email_score`.

### 2. Send Time Optimization

- Store STO activation on the campaign model with `use_sto` and `sto_window_hours`.
- Compute the best hour from historical `opened_at` timestamps in `ContactSendTimeService`.
- Keep current dispatch flow but compute a per-recipient delay before dispatching `SendCampaignEmailJob`.
- Fall back to the existing immediate/throttled schedule if a contact has fewer than three historical opens.

### 3. Dynamic Content

- Keep templating in `PersonalizationService`.
- Add a small parser/evaluator for `{{#if}}`, optional `{{else}}`, and `{{/if}}`.
- Allow only whitelisted operands:
  - `contact.*`
  - `client.*`
  - `email_score`
- Enforce max nesting depth of 2 in a dedicated validator service.
- Render conditional blocks before variable substitution so the retained branch still benefits from normal personalization.

### 4. Frontend

- Add a dedicated scoring rules store and settings page.
- Extend existing campaign create/edit flows rather than introducing new standalone forms.
- Reuse existing segment builder structure for score filters.
- Add a focused dynamic-content helper/editor that inserts valid conditional snippets rather than inventing a rich visual block model.

### 5. GDPR / Export

- Extend `DataExportService` to include scoring rules and contact score events.
- Do not introduce separate export endpoints; keep the current full export shape and append new collections.

## Trade-offs

- A custom dynamic parser is less flexible than a full template engine, but it avoids introducing an unbounded runtime and matches the phase scope.
- STO is heuristic rather than predictive; that keeps the implementation deterministic and testable.
- Storing a denormalized `email_score` on `contacts` improves filtering and UI performance while preserving full audit history in score events.

## Testing Strategy

- TDD by slice:
  - scoring domain tests first
  - STO/dynamic backend tests second
  - frontend store/component tests third
  - targeted E2E for dynamic content and STO last
- Keep red/green cycles narrow and verify failing tests before writing implementation code.

## Risks

- Campaign sending logic is shared with existing AB test flows, so STO changes must avoid breaking variant assignment or throttling.
- Dynamic content parsing can regress current personalization if block parsing is not isolated.
- Segment filter additions must not alter current SQL semantics for existing filters.
