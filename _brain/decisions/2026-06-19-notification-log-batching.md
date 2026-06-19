---
date: 2026-06-19
tags: [decision, notifications, notification-logs, filament, batching]
related:
  - "[[2026-06-18-telegram-notification-channel]]"
  - "[[../project-context/telegram-integration-guide]]"
---

# Decision: two-tier notification logs (batch summary + detail) instead of a flat list

## Context
After adding Telegram, every notification logs to **both** WhatsApp and Telegram (2 rows
per recipient), and broadcasts (e.g. "grades published" — sent per-student in a loop) fan
out to all students → one admin action made **hundreds** of `notification_logs` rows,
drowning the failures staff actually need. Consulted the LLM council (Gemini): converged on
a `batch_id` + two-tier design; explicitly **rejected discarding successful logs** (they're
the delivery proof when a student disputes "I wasn't notified").

## Decision
Add a **`batch_id`** (uuid) per send-operation and surface a **summary tier** without
removing the **detail tier**:
- **`notification_logs.batch_id`** (nullable, indexed). Stamped per dispatch: shared across
  a broadcast (so 240 rows = 1 batch), and across the WhatsApp+Telegram rows of one send.
- **Stamping path:** `NotificationService::batch(Closure)` sets a shared id; `dispatch()`
  does `$notification->batchId ??= currentBatch ?? Str::uuid()` (so even a single send groups
  its channel rows). A `Batchable` trait gives the 4 notifications the `batchId` property.
  `UserObserver` stamps the contact-alert too. Grade-publish loops (`ViewSemester`,
  `ViewCourseGrades`) are wrapped in `$notifier->batch(fn () => …)`.
- **One unified resource (final, 2026-06-19).** It all lives in `/admin/notification-logs`.
  **Only broadcasts get a `batch_id`** — `NotificationService::dispatch()` stamps the current
  `batch()` id; a single send (welcome, OTP, contact alert) gets **null** batch_id and shows as a
  **plain row**. The table query deduplicates broadcasts: `whereNull('batch_id') OR id IN
  (MIN(id) per batch_id)` → singles individually + **one summary row per broadcast**. Columns
  branch per-record (`$record->batch_id`): a broadcast row shows "📢 بث جماعي · N مستلمين ·
  <event> · ✓x · ✗y" and a **"عرض الرسائل" action → modal** listing that batch's per-recipient/
  channel messages (the **proof**, blade `filament/notification-logs/batch-detail`). Helpers
  `batchRecipients()` + `batchStatusLine()`.
  - **Why not Filament grouping/tabs:** grouping applies to ALL rows (singles would group too),
    and conditional grouping by active tab is unreliable — `table()` can't read the tab on
    Livewire SPA navigation (only on full reload). Per-record column branching is reliable.
  - **Trade-off:** status/channel filters and per-student search operate on the deduped result,
    so a student inside a broadcast is found via the broadcast's modal, not the top-level search.
    The earlier standalone `NotificationBatches` page was deleted.

## Alternatives rejected
- **Only keep failures (option c):** destroys the delivery audit/proof. Rejected (council + user).
- **Collapse the 2 channel rows into one DB row:** loses per-channel status (WhatsApp can fail
  while Telegram succeeds). Channel collapse happens only in the summary *counts*, not storage.
- **Grouped query on the existing resource:** breaks per-student search (the proof). Hence a
  separate summary Page.

## Consequences
- Cross-DB note: counts use `SUM(CASE WHEN …)` (works SQLite + Postgres); avoided
  `GROUP_CONCAT`/boolean-SUM. `last_sent_at` added to NotificationLog casts.
- Old rows (pre-feature) have null batch_id → excluded from the summary (`whereNotNull`).
- Retention/prune (90-day) was discussed but NOT built — `sent` proof is kept indefinitely
  for now. Add later if the table grows.
- 5 tests (`NotificationBatchTest`); full suite **449 green**; browser-verified (summary row +
  drill-in to the batch's rows).
