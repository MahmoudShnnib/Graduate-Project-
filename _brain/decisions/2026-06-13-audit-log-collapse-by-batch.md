---
date: 2026-06-13
tags: [decision, audit-log, filament, sql, ux]
related:
  - "[[2026-06-13-activity-bell-full-activity-feed]]"
  - "[[../daily-notes/2026-06-13-activity-bell-only-new]]"
---

# Decision — Collapse one-action-many-logs in the audit list by batch

## Context

The full CRUD audit trail means one business action writes many rows (e.g. accepting a
student ≈ 6 rows). All rows of one operation already share a `batch_id`. The audit-log **list**
showed every row, which is noisy. Storage must keep all rows (hard requirement).

## Decision

Collapse the **list display** to one "representative" row per batch; keep all rows in storage;
show the rest on the detail page.

- **Representative** = the decision row (action not ending `_(created|updated|deleted)`), else
  the lowest id. `batch_id IS NULL` rows stand alone.
- **Portable SQL** — `NOT EXISTS` correlated subquery + `substr(action,-8) IN (...)`. No
  `REGEXP` and no window functions, because the dev/test DB is **SQLite** and prod may be
  MySQL; this runs identically on both.
- **`batch_size`** via a correlated `selectSub` (sub-table aliased `audit_logs as sub`) →
  drives a "+N" badge.
- **Batch-aware action filter**: filtering by a generic action matches operations that
  *contain* it and surfaces their representative (else generic actions would filter to
  nothing, since they're never representatives).
- **Detail page** lists batch siblings so the full operation is visible in one place.
- **Bulk actions collapse to one row** (user confirmed) — one click = one operation = one row.

## Alternatives considered

- **Filament native row-grouping** — groups under headers but still lists every row; doesn't
  actually collapse. Rejected (Gemini agreed).
- **Stored `is_primary` flag** — the decision row is written *after* the generic rows in the
  request, so ordering at write time is unreliable. Rejected.
- **Window function `ROW_NUMBER()`** — cleaner but SQLite-version-dependent and the REGEXP
  variant Gemini suggested isn't available in SQLite. Used the portable `NOT EXISTS` instead.
- **Reduce write fan-out at the source** (suppress redundant rows when a decision is logged).
  Rejected: full trail is a hard requirement; collapse must be display-only. (Gemini agreed —
  an audit log records what happened, not an opinionated summary.)

## Consequences

- Audit-log list reads as one row per action; the detail page + "show system" toggle still
  expose every row.
- A correlated subquery per row — fine at this scale; added a `(batch_id, id)` index. If the
  table grows to millions, revisit (Gemini: materialised summary table).
- Filter caveat: filters now operate batch-aware for actions; user/date/model filters act on
  the representative row.

## Council

`llm-council`: ChatGPT unavailable (OpenAI quota). Gemini endorsed the representative-row
approach, batch-aware filtering, detail-page siblings, display-collapse over source-reduction,
and the `(batch_id, id)` index.
