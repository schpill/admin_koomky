# Import / Export Specifications

This document defines CSV import formats and export behavior for Koomky.

## 1. Endpoints

### Export

- `GET /api/v1/export/full`
- Auth required (Sanctum + 2FA middleware)
- Returns ZIP file containing `export.json`

### Import

- `POST /api/v1/import/{entity}`
- Supported entities: `projects`, `invoices`, `contacts`
- Multipart upload, field name: `file`
- Max file size: 5 MB

## 2. Full Export Payload

`export.json` includes:

- user profile summary,
- settings,
- clients, contacts, tags,
- projects,
- invoices,
- quotes,
- credit notes,
- campaigns, templates,
- segments.

This endpoint is intended for GDPR portability.

## 3. Import Response Shape

Successful imports return:

```json
{
  "status": "Success",
  "message": "Import completed",
  "data": {
    "entity": "projects",
    "imported": 12,
    "errors": [
      { "row": 3, "field": "client_reference", "message": "Client reference not found" }
    ]
  }
}
```

Rows are 1-based CSV rows; header is row `1`.

## 4. CSV Formats

## 4.1 Projects (`entity=projects`)

Required columns:

- `name`
- `client_reference`

Optional columns:

- `description`
- `status` (`draft`, `proposal_sent`, `in_progress`, `on_hold`, `completed`, `cancelled`)
- `billing_type` (`hourly`, `fixed`)
- `hourly_rate`
- `fixed_price`
- `estimated_hours`
- `start_date` (`YYYY-MM-DD`)
- `deadline` (`YYYY-MM-DD`)

Example:

```csv
name,client_reference,description,status,billing_type,hourly_rate,estimated_hours,start_date,deadline
Website redesign,CLI-0001,Corporate site refresh,in_progress,hourly,85,42,2026-02-01,2026-03-15
```

## 4.2 Contacts (`entity=contacts`)

Required columns:

- `first_name`
- `client_reference`

Optional columns:

- `last_name`
- `email`
- `phone`
- `position`
- `is_primary` (`true/false`, `1/0`)

Example:

```csv
first_name,last_name,client_reference,email,phone,position,is_primary
Alice,Martin,CLI-0001,alice@client.tld,+33102030405,CEO,true
```

## 4.3 Invoices (`entity=invoices`)

Required columns:

- `client_reference`
- `issue_date` (`YYYY-MM-DD`)
- `due_date` (`YYYY-MM-DD`)

Optional columns:

- `status` (`draft`, `sent`, `viewed`, `paid`, `partially_paid`, `overdue`, `cancelled`)
- `currency` (default `EUR`)
- `notes`
- `line_item_description`
- `line_item_quantity`
- `line_item_unit_price`
- `line_item_vat_rate`

Example:

```csv
client_reference,issue_date,due_date,status,currency,line_item_description,line_item_quantity,line_item_unit_price,line_item_vat_rate,notes
CLI-0001,2026-02-01,2026-02-15,sent,EUR,Monthly retainer,1,1200,20,Imported invoice
```

## 5. Validation Behavior

- Unknown entities return `422`.
- Empty CSV files return validation errors.
- Missing required values produce row-level errors.
- Unknown `client_reference` values produce row-level errors.
- Invalid enum values are normalized to defaults when possible.

## 6. Best Practices

- Use UTF-8 CSV files with a single header row.
- Keep date format strict (`YYYY-MM-DD`).
- Validate `client_reference` values before import.
- Start with small batches to verify mapping.
- Keep exported ZIP archives encrypted at rest.

## 7. GDPR Account Deletion

- Endpoint: `DELETE /api/v1/account`
- Behavior:
  - soft-deletes account,
  - revokes tokens,
  - sets `deletion_scheduled_at` to `now + 30 days`.

