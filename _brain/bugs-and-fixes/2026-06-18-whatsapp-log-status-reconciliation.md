---
date: 2026-06-18
tags: [bug, fix, whatsapp, qonvo, notifications, notification-logs, delivery-status]
related:
  - "[[../project-context/gotchas]]"
  - "[[../project-context/whatsapp-integration-guide]]"
---

# WhatsApp notification-logs showed "sent" for messages Qonvo actually failed to deliver

## Symptom
`/admin/notification-logs` showed WhatsApp messages as **"تم الإرسال"** even when the
recipient never received them — defeating the page's purpose (knowing when delivery fails /
Qonvo is down). Reproduced: messages the app logged as `sent` appear as **Failed** in the
Qonvo dashboard ("Failed to send message through WhatsApp service").

## Root cause
Qonvo's `POST /whatsapp/messages/send` only **queues** the message and returns `2xx` with
`data.status = "pending"` — even for sends that ultimately fail. `WhatsAppService::sendText`
calls `$response->throw()`, so only a non-2xx / connection error throws → `failed`. A queued
"pending" came back `2xx`, so `WhatsAppChannel` logged a blanket **`sent`**. The true outcome
is only available afterwards from `GET /whatsapp/messages/{id}` (returns the real `status` +
`error_message`).

## Fix (reconciliation model)
- Migration: `notification_logs.provider_message_id` (Qonvo message id).
- `WhatsAppChannel::send` now logs the **mapped** Qonvo status (`WhatsAppService::mapStatus`)
  — a queued send becomes **`pending`** (not `sent`) and stores the provider id. An exception
  still logs `failed`.
- `WhatsAppService::getMessageStatus($id)` → `GET /whatsapp/messages/{id}`.
- `WhatsAppService::reconcilePendingLogs()` re-checks every `pending` log and flips it to
  `sent` / `failed` (+ Qonvo's `error_message`).
- Runners: `whatsapp:reconcile` command (scheduled every 5 min in `routes/console.php`) **and**
  a "تحديث حالات الإرسال" header button on the NotificationLogResource list (badge = pending
  count) — the button is the no-scheduler fallback.
- Resource: added the `pending` ("قيد الإرسال") badge + status filter.

## Verification
- 4 new tests in `WhatsAppReconcileTest` (pending→failed with error, pending→sent, stays
  pending, `mapStatus` mapping) + updated `NotificationLogPhoneTest` (queued send now logs
  `pending` + provider id). Full suite **393 green**.
- **Real Qonvo**: inserted a pending log with a known-failed message id, ran
  `php artisan whatsapp:reconcile` → flipped to `failed` with "Failed to send message through
  WhatsApp service". Matches the Qonvo dashboard exactly.

## Note
Status flow: `pending` (queued) → reconcile → `sent` / `failed`. Without the scheduler running,
logs stay `pending` until someone clicks the reconcile button. `failed` is now trustworthy.
