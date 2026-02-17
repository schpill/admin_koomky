# API Reference

This reference covers the current HTTP endpoints exposed by Koomky.

## Base URLs

- API: `/api/v1`
- Public web hooks/tracking routes: see `routes/web.php`

## Authentication

- Auth scheme: Bearer token (Laravel Sanctum).
- Protected API routes require:
  - `auth:sanctum`
  - `two-factor` middleware (after 2FA activation)

Example header:

```http
Authorization: Bearer <token>
Accept: application/json
```

## Response Envelope

Most API responses use a consistent envelope:

```json
{
  "status": "Success",
  "message": "Human readable message",
  "data": {}
}
```

Validation and domain errors return an error status code with message details.

## Rate Limits

- Auth endpoints: `10 requests/minute` (`api_auth` limiter).
- Webhook endpoints: `60 requests/minute` (`webhooks` limiter).

---

## 1. Health

| Method | Path | Auth | Description |
|---|---|---|---|
| GET | `/api/v1/health` | No | Service health details (DB, Redis, Meilisearch, queue, storage, cache). |

## 2. Auth

### Public

| Method | Path | Description |
|---|---|---|
| POST | `/api/v1/auth/login` | Login and token issuance. |
| POST | `/api/v1/auth/forgot-password` | Start password reset flow. |
| POST | `/api/v1/auth/reset-password` | Complete password reset. |

### Protected

| Method | Path | Description |
|---|---|---|
| GET | `/api/v1/auth/me` | Current authenticated user. |
| POST | `/api/v1/auth/refresh` | Refresh token/session. |
| POST | `/api/v1/auth/logout` | Revoke current token. |
| POST | `/api/v1/auth/2fa/verify` | Validate current 2FA code. |

## 3. Dashboard

| Method | Path | Description |
|---|---|---|
| GET | `/api/v1/dashboard` | Dashboard metrics/cards. |

## 4. User Settings

| Method | Path | Description |
|---|---|---|
| GET | `/api/v1/settings/profile` | Fetch profile settings. |
| PUT | `/api/v1/settings/profile` | Update profile settings. |
| PUT | `/api/v1/settings/business` | Update business settings. |
| GET | `/api/v1/settings/invoicing` | Fetch invoicing settings. |
| PUT | `/api/v1/settings/invoicing` | Update invoicing settings. |
| PUT | `/api/v1/settings/email` | Update email provider settings. |
| PUT | `/api/v1/settings/sms` | Update SMS provider settings. |
| PUT | `/api/v1/settings/notifications` | Update notification preferences. |
| POST | `/api/v1/settings/2fa/enable` | Enable 2FA setup. |
| POST | `/api/v1/settings/2fa/confirm` | Confirm 2FA enrollment. |
| POST | `/api/v1/settings/2fa/disable` | Disable 2FA. |

## 5. Data Portability and Account

| Method | Path | Description |
|---|---|---|
| GET | `/api/v1/export/full` | Download full account export as ZIP (JSON payload). |
| POST | `/api/v1/import/{entity}` | CSV import for `projects`, `invoices`, `contacts`. |
| DELETE | `/api/v1/account` | Soft-delete account and schedule permanent purge. |

## 6. Clients and Contacts

| Method | Path | Description |
|---|---|---|
| GET | `/api/v1/clients` | List clients (search/filter/pagination). |
| POST | `/api/v1/clients` | Create client. |
| GET | `/api/v1/clients/{client}` | Get client details. |
| PUT/PATCH | `/api/v1/clients/{client}` | Update client. |
| DELETE | `/api/v1/clients/{client}` | Archive client (soft delete). |
| POST | `/api/v1/clients/{client}/restore` | Restore archived client. |
| GET | `/api/v1/clients/export/csv` | Export clients to CSV. |
| POST | `/api/v1/clients/import/csv` | Import clients from CSV. |
| GET | `/api/v1/clients/{client}/contacts` | List contacts for client. |
| POST | `/api/v1/clients/{client}/contacts` | Create contact. |
| PUT/PATCH | `/api/v1/clients/{client}/contacts/{contact}` | Update contact. |
| DELETE | `/api/v1/clients/{client}/contacts/{contact}` | Delete contact. |

## 7. Tags

| Method | Path | Description |
|---|---|---|
| GET | `/api/v1/tags` | List tags. |
| POST | `/api/v1/tags` | Create tag. |
| DELETE | `/api/v1/tags/{tag}` | Delete tag. |
| POST | `/api/v1/clients/{client}/tags` | Attach tag to client. |
| DELETE | `/api/v1/clients/{client}/tags/{tag}` | Detach tag from client. |

## 8. Projects, Tasks, Time Entries

| Method | Path | Description |
|---|---|---|
| GET | `/api/v1/projects` | List projects. |
| POST | `/api/v1/projects` | Create project. |
| GET | `/api/v1/projects/{project}` | Project details. |
| PUT/PATCH | `/api/v1/projects/{project}` | Update project. |
| DELETE | `/api/v1/projects/{project}` | Delete project. |
| POST | `/api/v1/projects/{project}/generate-invoice` | Create invoice from project data. |
| GET | `/api/v1/projects/{project}/tasks` | List project tasks. |
| POST | `/api/v1/projects/{project}/tasks` | Create task. |
| POST | `/api/v1/projects/{project}/tasks/reorder` | Reorder tasks. |
| GET | `/api/v1/projects/{project}/tasks/{task}` | Task details. |
| PUT | `/api/v1/projects/{project}/tasks/{task}` | Update task. |
| DELETE | `/api/v1/projects/{project}/tasks/{task}` | Delete task. |
| POST | `/api/v1/projects/{project}/tasks/{task}/dependencies` | Add task dependency. |
| POST | `/api/v1/projects/{project}/tasks/{task}/attachments` | Upload task attachment. |
| GET | `/api/v1/projects/{project}/tasks/{task}/attachments/{attachment}` | Download attachment. |
| DELETE | `/api/v1/projects/{project}/tasks/{task}/attachments/{attachment}` | Delete attachment. |
| POST | `/api/v1/projects/{project}/tasks/{task}/time-entries` | Create time entry. |
| PUT | `/api/v1/projects/{project}/tasks/{task}/time-entries/{timeEntry}` | Update time entry. |
| DELETE | `/api/v1/projects/{project}/tasks/{task}/time-entries/{timeEntry}` | Delete time entry. |

## 9. Invoices

| Method | Path | Description |
|---|---|---|
| GET | `/api/v1/invoices` | List invoices. |
| POST | `/api/v1/invoices` | Create invoice. |
| GET | `/api/v1/invoices/{invoice}` | Invoice details. |
| PUT/PATCH | `/api/v1/invoices/{invoice}` | Update draft invoice. |
| DELETE | `/api/v1/invoices/{invoice}` | Delete draft invoice. |
| POST | `/api/v1/invoices/{invoice}/send` | Send invoice (async job). |
| POST | `/api/v1/invoices/{invoice}/duplicate` | Duplicate invoice. |
| POST | `/api/v1/invoices/{invoice}/payments` | Register payment. |

## 10. Quotes

| Method | Path | Description |
|---|---|---|
| GET | `/api/v1/quotes` | List quotes. |
| POST | `/api/v1/quotes` | Create quote. |
| GET | `/api/v1/quotes/{quote}` | Quote details. |
| PUT/PATCH | `/api/v1/quotes/{quote}` | Update draft quote. |
| DELETE | `/api/v1/quotes/{quote}` | Delete draft quote. |
| POST | `/api/v1/quotes/{quote}/send` | Send quote. |
| POST | `/api/v1/quotes/{quote}/accept` | Mark quote accepted. |
| POST | `/api/v1/quotes/{quote}/reject` | Mark quote rejected. |
| POST | `/api/v1/quotes/{quote}/convert` | Convert quote to invoice. |
| GET | `/api/v1/quotes/{quote}/pdf` | Render quote PDF. |

## 11. Credit Notes

| Method | Path | Description |
|---|---|---|
| GET | `/api/v1/credit-notes` | List credit notes. |
| POST | `/api/v1/credit-notes` | Create credit note. |
| GET | `/api/v1/credit-notes/{credit_note}` | Credit note details. |
| PUT/PATCH | `/api/v1/credit-notes/{credit_note}` | Update draft credit note. |
| DELETE | `/api/v1/credit-notes/{credit_note}` | Delete draft credit note. |
| POST | `/api/v1/credit-notes/{credit_note}/send` | Send credit note. |
| POST | `/api/v1/credit-notes/{credit_note}/apply` | Apply credit note to invoice. |
| GET | `/api/v1/credit-notes/{credit_note}/pdf` | Render credit note PDF. |

## 12. Reports

| Method | Path | Description |
|---|---|---|
| GET | `/api/v1/reports/revenue` | Revenue report. |
| GET | `/api/v1/reports/outstanding` | Outstanding balances report. |
| GET | `/api/v1/reports/vat-summary` | VAT summary report. |
| GET | `/api/v1/reports/export` | Export report in CSV/PDF depending on params. |

## 13. Segments and Campaigns

| Method | Path | Description |
|---|---|---|
| GET | `/api/v1/segments` | List segments. |
| POST | `/api/v1/segments` | Create segment. |
| GET | `/api/v1/segments/{segment}` | Segment details. |
| PUT/PATCH | `/api/v1/segments/{segment}` | Update segment. |
| DELETE | `/api/v1/segments/{segment}` | Delete segment. |
| GET | `/api/v1/segments/{segment}/preview` | Preview matching contacts. |
| GET | `/api/v1/campaigns` | List campaigns. |
| POST | `/api/v1/campaigns` | Create campaign. |
| GET | `/api/v1/campaigns/{campaign}` | Campaign details. |
| PUT/PATCH | `/api/v1/campaigns/{campaign}` | Update campaign. |
| DELETE | `/api/v1/campaigns/{campaign}` | Delete campaign. |
| POST | `/api/v1/campaigns/{campaign}/send` | Send campaign. |
| POST | `/api/v1/campaigns/{campaign}/pause` | Pause sending. |
| POST | `/api/v1/campaigns/{campaign}/duplicate` | Duplicate campaign. |
| POST | `/api/v1/campaigns/{campaign}/test` | Send test message. |
| GET | `/api/v1/campaigns/{campaign}/analytics` | Campaign analytics. |
| GET | `/api/v1/campaigns/{campaign}/analytics/export` | Export campaign analytics. |
| GET | `/api/v1/campaigns/compare` | Compare campaigns. |

## 14. Campaign Templates

| Method | Path | Description |
|---|---|---|
| GET | `/api/v1/campaign-templates` | List templates. |
| POST | `/api/v1/campaign-templates` | Create template. |
| PUT/PATCH | `/api/v1/campaign-templates/{campaign_template}` | Update template. |
| DELETE | `/api/v1/campaign-templates/{campaign_template}` | Delete template. |

## 15. Search and Activity Feed

| Method | Path | Description |
|---|---|---|
| GET | `/api/v1/search?q=<term>` | Global search across entities. |
| GET | `/api/v1/activities` | Activity stream. |

---

## Public Web Routes (non `/api/v1`)

| Method | Path | Description |
|---|---|---|
| GET | `/unsubscribe/{contact}` | Contact unsubscribe page/action. |
| GET | `/t/open/{token}` | Email open tracking. |
| GET | `/t/click/{token}` | Email click tracking + redirect. |
| POST | `/webhooks/email` | Email provider webhook endpoint. |
| POST | `/webhooks/sms` | SMS provider webhook endpoint. |

