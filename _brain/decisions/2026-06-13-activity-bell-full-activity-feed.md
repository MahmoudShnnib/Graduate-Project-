---
date: 2026-06-13
tags: [decision, bell, audit-log, observer, ux]
related:
  - "[[2026-06-13-activity-bell-read-unread-model]]"
  - "[[../daily-notes/2026-06-13-activity-bell-only-new]]"
---

# Decision — Activity Bell as full "who did what" supervisor feed

## Context

After the read/unread rework ([[2026-06-13-activity-bell-read-unread-model]]), a bulk "قبول
مبدئي" didn't appear in the bell. Investigation showed the bell only surfaces hand-placed
`AuditService::log()` decision calls; the **bulk** approval had none, and creates/deletes
were never tracked at all. The user then asked for the bell to show **all** admin activity.

## Decision

1. **Capture everything in the audit log.** Extend `AuditObserver` to log create / update /
   delete (`Model_created|_updated|_deleted`) across all 20 admin-managed models, with a
   noise blocklist. The audit log is the complete trail.
2. **Collapse only at display.** Correlate rows written in one operation via a shared
   `batch_id`; the bell hides the generic edit rows of a batch that also produced a decision,
   showing the decision line instead. Storage is never collapsed.
3. **Capture a `subject_label` at log time** so deletions still read with a name.
4. **Summarized one-line rendering** — `إضافة / تعديل / حذف {noun}: {name}` for generic rows;
   decision rows keep their existing Arabic label. Dropped the 5 old `*_updated` custom
   labels for uniform phrasing.
5. **Audience:** bell visible to **super-admin only**; shows activity by super-admins +
   manager-assistants **excluding the viewer's own** actions (a supervisor watching staff).

## Alternatives considered

- **Per-action patching** (add a `->log()` to every missing action — bulk approve, reject,
  delete one by one). Rejected: brittle, endless, and the next new action would be invisible
  again. The generic observer covers all of them at once and future-proofs it.
- **Collapse in storage** (don't write the generic row when a decision exists). Rejected: the
  user explicitly wants the full trail in the audit log — collapse must be display-only.
- **Transaction-id correlation** instead of a per-request memoized uuid. Rejected as
  overkill; one operation ≈ one request here, so a request-scoped batch id is enough.
- **Include faculty / student-portal activity.** Rejected by the user — staff (admins +
  managers) only, to keep the monitoring feed focused.

## Consequences

- Every save on 20 models now writes an audit row — richer trail, slightly more rows. Login
  and auto-computed churn is filtered by the blocklist so it stays meaningful.
- `AuditObserver`'s old "watched fields" restriction is gone; `*_updated` now records all
  meaningful changed fields. The audit-log page reads them via the generic label.
- Two migrations (`users.last_seen_activity_id`, `audit_logs.batch_id` + `subject_label`)
  must run on deploy.
- Bulk-approval and application-rejection visibility are fixed as a side effect — no separate
  work needed.
