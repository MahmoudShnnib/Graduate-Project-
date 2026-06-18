---
date: 2026-06-18
tags: [whatsapp, qonvo, notifications, notification-logs, delivery-status]
related:
  - "[[../bugs-and-fixes/2026-06-18-whatsapp-log-status-reconciliation]]"
  - "[[../project-context/gotchas]]"
  - "[[../project-context/whatsapp-integration-guide]]"
---

# WhatsApp notification-logs accuracy + notification copy tweak

Branch `chore/go-live-high-medium-fixes` (the grades/suspension/survey work landed as PR #58).
**Uncommitted.** Full suite **393 green**. Backend verified against real Qonvo.

## 1. Notification copy — append college name from settings
The two kept WhatsApp notifications now append the college name after
"في برنامج الدراسات العليا", sourced from `Setting::get('college_name')` (single source of
truth, same as the prints) instead of a hardcoded string:
- `ApplicationInitiallyApprovedNotification`
- `WelcomeStudentNotification`

## 2. notification-logs showed "sent" for messages Qonvo actually failed
**Root cause:** Qonvo's `POST .../send` only **queues** — returns `2xx` + `data.status="pending"`
even for sends that fail. The channel logged a blanket `sent`. The real status is only in
`GET /whatsapp/messages/{id}` (`status` + `error_message`); the user's Qonvo dashboard showed
those same sends as **Failed**.

**Fix (reconciliation):**
- Migration `notification_logs.provider_message_id`; model fillable updated.
- `WhatsAppChannel` logs the **mapped** Qonvo status (queued → `pending`) + stores the provider
  id; an exception still logs `failed`.
- `WhatsAppService`: `getMessageStatus($id)`, `mapStatus()`, `reconcilePendingLogs()`.
- Runners: `whatsapp:reconcile` command (scheduled every 5 min in `routes/console.php`) + a
  "تحديث حالات الإرسال" header button on the NotificationLogResource (badge = pending count) —
  button is the no-scheduler fallback.
- Resource: `pending` ("قيد الإرسال") badge + status-filter option.

**Verified:** 4 new tests (`WhatsAppReconcileTest`) + updated `NotificationLogPhoneTest`
(queued send → `pending` + provider id). **Real Qonvo**: a pending log pointing at a
known-failed message id flipped to `failed` with "Failed to send message through WhatsApp
service" after `php artisan whatsapp:reconcile`. See
[[../bugs-and-fixes/2026-06-18-whatsapp-log-status-reconciliation]] + the updated gotcha
("WhatsApp NotificationLog status is reconciled, not optimistic").

## 3. Admin switch to disable the /apply OTP requirement
New setting `apply_otp_required` ('1' default). `WhatsAppOtpService::isRequired()` =
`isConfigured() && apply_otp_required==='1'` is now the single gate; `RegistrationController`
`create()` + `store()` use it (dropped the now-unused `WhatsAppService` param).

UI (per user iteration): the **actual toggle lives inside the الإعدادات العامة modal** —
`DashboardHeaderWidget::editSettingsAction` form gained an "إعدادات التقديم" section with a
`Toggle` ("طلب رمز تحقق (OTP) من المتقدمين عبر واتساب", on=success/off=danger, danger
helperText warning). `fillForm` casts it to bool; the save action persists booleans as '1'/'0'.
The **dashboard header shows a display-only status pill** "رمز التحقق: مُفعّل/مُعطّل" (green/amber,
not clickable) — the earlier clickable `toggleApplyOtp` confirmation-action was removed.
+3 tests (isRequired logic, submit-without-OTP-when-disabled, settings-form toggle persists the
setting). Useful given the WhatsApp number got rate-limited — admissions can keep running with
OTP off.

### Testing gotcha hit
`Setting`'s per-request **static memo survives RefreshDatabase** (rolls back rows, not statics),
so a value set in one test leaked into the next. Added `Setting::flushCache()` + call it in the
OTP test `beforeEach`. Any test mutating settings should flush.

## Next
- User to review + commit. If/when a scheduler runs in prod (`schedule:work`/cron), pending
  logs auto-reconcile; otherwise the admin clicks the button.
- Dev DB has a couple of `pending`/`failed` test NotificationLog rows from verification (dummy).
