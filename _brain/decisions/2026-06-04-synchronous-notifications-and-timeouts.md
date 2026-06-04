---
date: 2026-06-04
tags: [decision, notifications, whatsapp, queue, performance, timeouts, deployment]
related:
  - "[[../daily-notes/2026-06-04-deploy-readiness-plan]]"
  - "[[../project-context/whatsapp-integration-guide]]"
---

# Decision: notifications stay synchronous; safety comes from HTTP timeouts (not a queue)

## Context

Heading into real production use, event notifications (welcome, grade published,
add-drop/suspension/thesis/clearance decisions — 9 classes) send **email + WhatsApp synchronously**
inside the admin/faculty's HTTP request (`NotificationService::dispatch()` → `$notifiable->notify()`,
`via()` = `['mail', WhatsAppChannel]`). Once `MAIL_MAILER` flips from `log` to real SMTP, each notify is
**two blocking external calls**. `WhatsAppService::sendText()` currently sets **no HTTP timeout**, so a
stalled Qonvo can hang the whole request. OTP on `/apply` has the same blocking exposure on send.

## Decision

**Keep notifications synchronous. Do NOT introduce a queue worker.** Mitigate the blocking risk with
explicit, tight HTTP timeouts instead:

- `WhatsAppService::sendText()` → `->connectTimeout(5)->timeout(10)`.
- Production mailer → a `timeout`.
- OTP send stays synchronous regardless (the applicant is actively waiting for the code).

The existing `try/catch` + `Log::warning` in both `WhatsAppChannel` and `WhatsAppOtpService` already
degrades gracefully on failure; the timeout simply bounds the worst case to ~10s instead of an open-ended
hang.

## Alternatives considered

- **Queue the 9 notifications (`implements ShouldQueue`) + `queue:work` worker + scheduler in
  supervisord.** Best raw performance/robustness (instant action response, automatic retries), but:
  reverses the documented "synchronous by design" decision, adds a long-running worker process that must
  be kept alive and monitored, pulls in `failed_jobs` handling, and adds operational surface a
  single-faculty graduation project doesn't need. Rejected for now.

## Consequences

- Admin/faculty actions still block **briefly** (≤ ~10s worst case) on each notification — acceptable at
  this scale.
- No worker/scheduler to operate → `whats_app_otps` pruning is done **opportunistically inside
  `request()`** instead of via cron.
- If the system ever scales beyond a single faculty or staff complain about action latency, revisit:
  queueing is the clean upgrade path and this note is the reason it wasn't done yet.

## Trigger to revisit
Action latency complaints, a move off single-faculty scale, or adding deadline-reminder notifications
(which are inherently batch and *should* be queued/scheduled).
</content>
