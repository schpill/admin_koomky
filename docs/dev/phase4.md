# Phase 4 — Task Tracking

> **Status**: Not Started
> **Prerequisite**: Phase 3 fully merged
> **Spec**: [docs/phases/phase4.md](../phases/phase4.md)

---

## Sprint 13 — UI/UX Polish & Accessibility (Weeks 21-22)

### Dark Mode

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P4-FE-001 | Audit all pages/components for dark mode class coverage | wip | codex |
| P4-FE-002 | Implement dark mode color system in Tailwind config | wip | codex |
| P4-FE-003 | Add dark: variants to all base components | wip | codex |
| P4-FE-004 | Dark mode for charts (Recharts) | wip | codex |
| P4-FE-005 | Dark mode for TipTap editor | wip | codex |
| P4-FE-006 | Dark mode for PDF preview iframe | wip | codex |
| P4-FE-007 | Implement system preference detection + manual toggle | wip | codex |
| P4-FE-008 | Persist theme preference (next-themes) | wip | codex |

### Responsive Design

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P4-FE-010 | Audit all pages at all breakpoints (375px-2560px) | wip | codex |
| P4-FE-011 | Sidebar: collapsible on tablet, hamburger on mobile | wip | codex |
| P4-FE-012 | Data tables: horizontal scroll on mobile, hide secondary cols | wip | codex |
| P4-FE-013 | Dashboard: 1/2/3 column responsive grid | wip | codex |
| P4-FE-014 | Forms: full-width mobile, 2-column desktop | wip | codex |
| P4-FE-015 | Modals/drawers: full-screen on mobile | wip | codex |
| P4-FE-016 | Kanban board: horizontal scroll on mobile | wip | codex |
| P4-FE-017 | Campaign wizard: vertical stepper on mobile | wip | codex |

### Accessibility

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P4-FE-020 | Run axe-core automated audit on all pages | wip | codex |
| P4-FE-021 | Ensure all elements keyboard-accessible | wip | codex |
| P4-FE-022 | Add aria-label/alt text to all icons and images | wip | codex |
| P4-FE-023 | Verify color contrast ratios (AA minimums) | wip | codex |
| P4-FE-024 | Associate all form labels/errors (for/id, aria-describedby) | wip | codex |
| P4-FE-025 | Implement aria-live regions (toasts, search, validation, progress) | wip | codex |
| P4-FE-026 | Implement focus management (trap in modals, return on close, skip-to-content) | wip | codex |
| P4-FE-027 | Screen reader testing on key flows | wip | codex |

### UI Polish

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P4-FE-030 | Add skeleton loaders for all data-fetching states | wip | codex |
| P4-FE-031 | Add CSS transitions (hover, expand, open/close, slide, appear) | wip | codex |
| P4-FE-032 | Design and implement empty states for all list pages | wip | codex |
| P4-FE-033 | Add breadcrumbs to all detail/edit pages | wip | codex |
| P4-FE-034 | Add keyboard shortcuts documentation (? key) | wip | codex |
| P4-FE-035 | Add confirmation dialog for all destructive actions | wip | codex |

---

## Sprint 14 — Performance Optimization (Weeks 22-23)

### Backend Performance

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P4-BE-001 | Install Laravel Debugbar + profile all endpoints | todo | |
| P4-BE-002 | Eliminate N+1 queries (eager loading on all controllers) | todo | |
| P4-BE-003 | Review and optimize database indexes | todo | |
| P4-BE-004 | Implement Redis caching strategy (dashboard, counts, reports) | todo | |
| P4-BE-005 | Implement query result caching for expensive aggregations | todo | |
| P4-BE-006 | Optimize PDF generation (pre-compile templates, cache logo) | todo | |
| P4-BE-007 | Implement PgBouncer connection pooling (optional) | todo | |
| P4-BE-008 | Profile and optimize campaign sending (100 emails/min) | todo | |
| P4-BE-009 | Implement graceful degradation (Meilisearch/Redis fallbacks) | todo | |
| P4-BE-010 | Add response time logging (> 500ms warnings) | todo | |
| P4-BE-011 | Run load test (k6/Artillery, 100 concurrent users, p95 < 200ms) | todo | |

### Frontend Performance

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P4-FE-040 | Analyze production bundle | todo | |
| P4-FE-041 | Verify route-level code splitting | todo | |
| P4-FE-042 | Lazy-load heavy components (TipTap, Recharts, Kanban) | todo | |
| P4-FE-043 | Optimize images (WebP, lazy-load, explicit dimensions) | todo | |
| P4-FE-044 | Implement virtual scrolling for large tables (> 100 rows) | todo | |
| P4-FE-045 | Configure static pre-rendering for auth pages | todo | |
| P4-FE-046 | Set HTTP caching headers for static assets | todo | |
| P4-FE-047 | Run Lighthouse CI (perf > 90, a11y > 90, best practices > 90) | todo | |
| P4-FE-048 | Verify JS < 300KB gzipped, CSS < 50KB gzipped | todo | |

---

## Sprint 15 — Data Management, Security & Production Setup (Weeks 23-24)

### Data Import/Export

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P4-BE-020 | Create DataExportService (full JSON archive, GDPR) | todo | |
| P4-BE-021 | Create DataExportController (streamed ZIP) | todo | |
| P4-BE-022 | Create DataImportService (CSV: projects, invoices, contacts) | todo | |
| P4-BE-023 | Create DataImportController (validate, parse, report) | todo | |
| P4-BE-024 | Implement GDPR data deletion (soft-delete + 30-day permanent) | todo | |

### Security Hardening

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P4-BE-030 | Run composer audit — resolve all vulnerabilities | todo | |
| P4-BE-031 | Run pnpm audit — resolve all vulnerabilities | todo | |
| P4-BE-032 | Verify all user inputs sanitized (XSS prevention) | todo | |
| P4-BE-033 | Verify CSRF protection on all state-changing routes | todo | |
| P4-BE-034 | Add CSP headers via middleware | todo | |
| P4-BE-035 | Add security headers (X-Content-Type-Options, X-Frame-Options, etc.) | todo | |
| P4-BE-036 | Verify sensitive data encryption at rest | todo | |
| P4-BE-037 | Review rate limiting on all public endpoints | todo | |
| P4-BE-038 | Implement request logging middleware (audit trail) | todo | |
| P4-BE-039 | Implement failed job monitoring (alert > 10 failures/hour) | todo | |

### Production Deployment

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P4-BE-040 | Create docker-compose.prod.yml | todo | |
| P4-BE-041 | Create docker/nginx/prod.conf (TLS, HSTS, gzip, caching) | todo | |
| P4-BE-042 | Create scripts/backup.sh (pg_dump + S3 + rotation) | todo | |
| P4-BE-043 | Create scripts/restore.sh | todo | |
| P4-BE-044 | Test backup and restore procedure E2E | todo | |
| P4-BE-045 | Create .github/workflows/deploy.yml (build, push, SSH deploy, health check, rollback) | todo | |
| P4-BE-046 | Create .github/workflows/security-audit.yml (weekly cron) | todo | |
| P4-BE-047 | Configure S3-compatible file storage for production | todo | |
| P4-BE-048 | Enhance health check endpoint (all services status) | todo | |
| P4-BE-049 | Set up uptime monitoring | todo | |
| P4-BE-050 | Configure structured logging (JSON, request_id, user_id) | todo | |
| P4-BE-051 | Implement zero-downtime deployment (rolling updates) | todo | |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P4-FE-050 | Create app/settings/data/page.tsx (import/export/deletion) | todo | |
| P4-FE-051 | Create import progress indicator | todo | |
| P4-FE-052 | Create import error report display | todo | |

### Documentation

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P4-DOC-001 | Write README.md (overview, quick start, features, tech stack) | todo | |
| P4-DOC-002 | Write docs/deployment.md (production guide) | todo | |
| P4-DOC-003 | Write docs/api.md (complete API reference) | todo | |
| P4-DOC-004 | Write docs/architecture.md (overview, diagrams, decisions) | todo | |
| P4-DOC-005 | Write CHANGELOG.md (all 4 phases) | todo | |
| P4-DOC-006 | Write docs/import-export.md (CSV format specs) | todo | |
