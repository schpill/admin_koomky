# Changelog

All notable changes to this project are documented in this file.

The format is based on Keep a Changelog and this project follows Semantic Versioning.

## [1.0.0] - 2026-02-17

### Added

- Phase 4 UI polish and accessibility updates:
  - breadcrumbs and keyboard shortcuts help,
  - improved mobile navigation and fullscreen dialogs,
  - additional aria-live announcements and focus-flow improvements.
- Performance and resilience improvements:
  - request telemetry middleware with `X-Request-Id`,
  - slow request logging threshold,
  - cache fallback behavior for dashboard and reports,
  - Meilisearch fallback to database search.
- Data governance features:
  - full account export (`GET /api/v1/export/full`),
  - CSV import for projects/invoices/contacts,
  - account deletion scheduling endpoint.
- Security hardening:
  - CSP and security headers middleware,
  - structured logging channel,
  - webhook throttling,
  - failed jobs monitoring command (`queue:monitor-failures`).
- Production delivery assets:
  - `docker-compose.prod.yml`,
  - hardened Nginx prod config,
  - backup/restore scripts,
  - deploy and security-audit GitHub workflows.
- Documentation set for v1.0:
  - `README.md`,
  - `docs/deployment.md`,
  - `docs/api.md`,
  - `docs/architecture.md`,
  - `docs/import-export.md`.

### Changed

- Frontend dependency update to patched Next.js 15.5.12 line.
- Additional frontend component tests and backend feature tests for phase-4 behaviors.

## [0.4.0] - 2026-02-16

### Added

- Phase 3 financial domain: invoices, quotes, credit notes and reports.
- Campaign module: templates, sending flows, analytics and segment integration.
- Expanded API coverage and feature tests for billing/campaign workflows.

## [0.3.0] - 2026-02-15

### Added

- Phase 2 project management: projects, tasks, dependencies and time tracking.
- Dashboard expansion and cross-module activity integration.
- Additional role/policy and validation coverage.

## [0.2.0] - 2026-02-14

### Added

- Phase 1 CRM baseline:
  - authentication,
  - clients and contacts,
  - tags,
  - settings baseline,
  - search and activity timeline.

## [0.1.0] - 2026-02-14

### Added

- Initial monorepo bootstrap:
  - Laravel backend,
  - Next.js frontend,
  - Docker local development stack,
  - CI pipeline foundations.

