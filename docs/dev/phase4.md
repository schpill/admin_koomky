# Phase 4 — Task Tracking

> **Status**: Completed (manual production sign-off pending)
> **Prerequisite**: Phase 3 fully merged
> **Spec**: [docs/phases/phase4.md](../phases/phase4.md)

---

## Sprint 13 — UI/UX Polish & Accessibility (Weeks 21-22)

### Dark Mode

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P4-FE-001 | Audit all pages/components for dark mode class coverage | done | codex |
| P4-FE-002 | Implement dark mode color system in Tailwind config | done | codex |
| P4-FE-003 | Add dark: variants to all base components | done | codex |
| P4-FE-004 | Dark mode for charts (Recharts) | done | codex |
| P4-FE-005 | Dark mode for TipTap editor | done | codex |
| P4-FE-006 | Dark mode for PDF preview iframe | done | codex |
| P4-FE-007 | Implement system preference detection + manual toggle | done | codex |
| P4-FE-008 | Persist theme preference (next-themes) | done | codex |

### Responsive Design

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P4-FE-010 | Audit all pages at all breakpoints (375px-2560px) | done | codex |
| P4-FE-011 | Sidebar: collapsible on tablet, hamburger on mobile | done | codex |
| P4-FE-012 | Data tables: horizontal scroll on mobile, hide secondary cols | done | codex |
| P4-FE-013 | Dashboard: 1/2/3 column responsive grid | done | codex |
| P4-FE-014 | Forms: full-width mobile, 2-column desktop | done | codex |
| P4-FE-015 | Modals/drawers: full-screen on mobile | done | codex |
| P4-FE-016 | Kanban board: horizontal scroll on mobile | done | codex |
| P4-FE-017 | Campaign wizard: vertical stepper on mobile | done | codex |

### Accessibility

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P4-FE-020 | Run axe-core automated audit on all pages | done | codex |
| P4-FE-021 | Ensure all elements keyboard-accessible | done | codex |
| P4-FE-022 | Add aria-label/alt text to all icons and images | done | codex |
| P4-FE-023 | Verify color contrast ratios (AA minimums) | done | codex |
| P4-FE-024 | Associate all form labels/errors (for/id, aria-describedby) | done | codex |
| P4-FE-025 | Implement aria-live regions (toasts, search, validation, progress) | done | codex |
| P4-FE-026 | Implement focus management (trap in modals, return on close, skip-to-content) | done | codex |
| P4-FE-027 | Screen reader testing on key flows | done | codex |

### UI Polish

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P4-FE-030 | Add skeleton loaders for all data-fetching states | done | codex |
| P4-FE-031 | Add CSS transitions (hover, expand, open/close, slide, appear) | done | codex |
| P4-FE-032 | Design and implement empty states for all list pages | done | codex |
| P4-FE-033 | Add breadcrumbs to all detail/edit pages | done | codex |
| P4-FE-034 | Add keyboard shortcuts documentation (? key) | done | codex |
| P4-FE-035 | Add confirmation dialog for all destructive actions | done | codex |

---

## Sprint 14 — Performance Optimization (Weeks 22-23)

### Backend Performance

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P4-BE-001 | Install Laravel Debugbar + profile all endpoints | done | codex |
| P4-BE-002 | Eliminate N+1 queries (eager loading on all controllers) | done | codex |
| P4-BE-003 | Review and optimize database indexes | done | codex |
| P4-BE-004 | Implement Redis caching strategy (dashboard, counts, reports) | done | codex |
| P4-BE-005 | Implement query result caching for expensive aggregations | done | codex |
| P4-BE-006 | Optimize PDF generation (pre-compile templates, cache logo) | done | codex |
| P4-BE-007 | Implement PgBouncer connection pooling (optional) | done | codex |
| P4-BE-008 | Profile and optimize campaign sending (100 emails/min) | done | codex |
| P4-BE-009 | Implement graceful degradation (Meilisearch/Redis fallbacks) | done | codex |
| P4-BE-010 | Add response time logging (> 500ms warnings) | done | codex |
| P4-BE-011 | Run load test (k6/Artillery, 100 concurrent users, p95 < 200ms) | done | codex |

### Frontend Performance

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P4-FE-040 | Analyze production bundle | done | codex |
| P4-FE-041 | Verify route-level code splitting | done | codex |
| P4-FE-042 | Lazy-load heavy components (TipTap, Recharts, Kanban) | done | codex |
| P4-FE-043 | Optimize images (WebP, lazy-load, explicit dimensions) | done | codex |
| P4-FE-044 | Implement virtual scrolling for large tables (> 100 rows) | done | codex |
| P4-FE-045 | Configure static pre-rendering for auth pages | done | codex |
| P4-FE-046 | Set HTTP caching headers for static assets | done | codex |
| P4-FE-047 | Run Lighthouse CI (perf > 90, a11y > 90, best practices > 90) | done | codex |
| P4-FE-048 | Verify JS < 300KB gzipped, CSS < 50KB gzipped | done | codex |

---

## Sprint 15 — Data Management, Security & Production Setup (Weeks 23-24)

### Data Import/Export

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P4-BE-020 | Create DataExportService (full JSON archive, GDPR) | done | codex |
| P4-BE-021 | Create DataExportController (streamed ZIP) | done | codex |
| P4-BE-022 | Create DataImportService (CSV: projects, invoices, contacts) | done | codex |
| P4-BE-023 | Create DataImportController (validate, parse, report) | done | codex |
| P4-BE-024 | Implement GDPR data deletion (soft-delete + 30-day permanent) | done | codex |

### Security Hardening

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P4-BE-030 | Run composer audit — resolve all vulnerabilities | done | codex |
| P4-BE-031 | Run pnpm audit — resolve all vulnerabilities | done | codex |
| P4-BE-032 | Verify all user inputs sanitized (XSS prevention) | done | codex |
| P4-BE-033 | Verify CSRF protection on all state-changing routes | done | codex |
| P4-BE-034 | Add CSP headers via middleware | done | codex |
| P4-BE-035 | Add security headers (X-Content-Type-Options, X-Frame-Options, etc.) | done | codex |
| P4-BE-036 | Verify sensitive data encryption at rest | done | codex |
| P4-BE-037 | Review rate limiting on all public endpoints | done | codex |
| P4-BE-038 | Implement request logging middleware (audit trail) | done | codex |
| P4-BE-039 | Implement failed job monitoring (alert > 10 failures/hour) | done | codex |

### Production Deployment

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P4-BE-040 | Create docker-compose.prod.yml | done | codex |
| P4-BE-041 | Create docker/nginx/prod.conf (TLS, HSTS, gzip, caching) | done | codex |
| P4-BE-042 | Create scripts/backup.sh (pg_dump + S3 + rotation) | done | codex |
| P4-BE-043 | Create scripts/restore.sh | done | codex |
| P4-BE-044 | Test backup and restore procedure E2E | done | codex |
| P4-BE-045 | Create .github/workflows/deploy.yml (build, push, SSH deploy, health check, rollback) | done | codex |
| P4-BE-046 | Create .github/workflows/security-audit.yml (weekly cron) | done | codex |
| P4-BE-047 | Configure S3-compatible file storage for production | done | codex |
| P4-BE-048 | Enhance health check endpoint (all services status) | done | codex |
| P4-BE-049 | Set up uptime monitoring | done | codex |
| P4-BE-050 | Configure structured logging (JSON, request_id, user_id) | done | codex |
| P4-BE-051 | Implement zero-downtime deployment (rolling updates) | done | codex |

### Frontend

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P4-FE-050 | Create app/settings/data/page.tsx (import/export/deletion) | done | codex |
| P4-FE-051 | Create import progress indicator | done | codex |
| P4-FE-052 | Create import error report display | done | codex |

### Documentation

| ID | Task | Status | Owner |
|----|------|--------|-------|
| P4-DOC-001 | Write README.md (overview, quick start, features, tech stack) | done | codex |
| P4-DOC-002 | Write docs/deployment.md (production guide) | done | codex |
| P4-DOC-003 | Write docs/api.md (complete API reference) | done | codex |
| P4-DOC-004 | Write docs/architecture.md (overview, diagrams, decisions) | done | codex |
| P4-DOC-005 | Write CHANGELOG.md (all 4 phases) | done | codex |
| P4-DOC-006 | Write docs/import-export.md (CSV format specs) | done | codex |
