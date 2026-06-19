---
date: 2026-06-19
tags: [notifications, notification-logs, telegram, filament, batching, daily]
related:
  - "[[../decisions/2026-06-19-notification-log-batching]]"
  - "[[../project-context/telegram-integration-guide]]"
---

# Notification-logs overhaul + Telegram polish + two small fixes

Branch `feature/telegram-notifications` (PR #63). **Committed `c3e50af`.** Full suite **453 green**.

## 1. Default channel policy → `both` (was `whatsapp_first`)
A linked student was getting WhatsApp-only (which fails — Qonvo banned) while their working
Telegram was ignored. Changed `HasMessagingChannels` default + admin selector default to
`both`, set the live setting. Live-verified: all 4 notification types delivered to Telegram
(4/4) for the linked student while WhatsApp failed.

## 2. notification-logs table fixes (`NotificationLogResource`)
- Added a **channel column with icons** (تيليجرام = paper-airplane/blue, واتساب =
  chat-bubble/green) + a channel filter — Telegram sends were invisible before.
- **الطالب bug:** column read `student.name` (null — name lives on `student.user.name`), so it
  was `—` for every row. Fixed to `student.user.name` + eager-load; shows name + رقم القيد.
- سبب الفشل: shrunk (limit 22) + full error on hover (tooltip) + click-to-copy.
- Fixed stale event-label key `grade_published` → `grades_published`.

## 3. Batching (the big one) — see [[../decisions/2026-06-19-notification-log-batching]]
**Only broadcasts get a `batch_id`** (singles = null = plain rows). The ONE resource
`/admin/notification-logs` deduplicates broadcasts (one summary row per batch) + shows singles
flat. A broadcast row = "📢 بث جماعي · N مستلمين · <event> · ✓x ✗y" + a "عرض الرسائل" modal with
the per-recipient detail (the proof). Grade loops wrapped in `NotificationService::batch()`.
Browser-verified end-to-end (singles flat, broadcast one row, modal drill-in). Council-consulted.
Iterated a lot: separate page → table grouping → tabs+conditional-grouping (all rejected:
Filament grouping applies to ALL rows and can't read the active tab on SPA nav) → **per-record
dedup + column branching** (reliable). See decision note for the why.
Modal blade `filament/notification-logs/batch-detail`: dark-mode contrast fix — name
`text-gray-950 dark:text-white`, رقم القيد `text-gray-600 dark:text-gray-300` (was
`dark:text-gray-100` which wasn't compiled → invisible on dark). Rebuilt assets.

## 4. Telegram portal card → compact chip after linking
Student dashboard: the big "linked" card is gone once linked; instead a compact
"✓ تيليجرام مربوط [إلغاء]" chip sits in the welcome banner. The before-linking CTA card stays.

## 5. Unlink confirm → proper Filament modal
Replaced the native `wire:confirm` on the unlink with a Filament `Action`
(`unlinkTelegramAction`, `requiresConfirmation()`) → a real modal ("إلغاء ربط حساب تيليجرام"
+ نعم/تراجع). Mirrors `ExamListPage` page-action pattern.

## 6. Survey results: don't re-open already-active courses
`/admin/courses-survey-results` "فتح المقررات المختارة": `fillForm` now `reject()`s any selected
course that already has a `CourseOffering` in the active semester, so select-all doesn't show
already-open courses in the popup. Test asserts "تم فتح 1 مقرر" (the active one excluded).

## 7. Admin can CLOSE either channel (whatsapp_enabled / telegram_enabled)
New settings (default '1') + two toggles in the dashboard "قنوات الإشعارات" modal. Gated via a
new `WhatsAppService::isEnabled()` / `TelegramService::isEnabled()` (= isConfigured && setting),
wired into: `HasMessagingChannels::via()`, `ContactInfoChanged::via()`, both channel `send()`
guards, `WhatsAppOtpService::isRequired()`/`request()`, `TelegramVerificationService::isConfigured()`.
So closing a channel stops ALL of its notifications + /apply verification + linking. Reconcile
still runs on `isConfigured()` (finalizes old pendings even after close).

## 8. Status truthfulness — WhatsApp `sent` is now GUARANTEED real
Qonvo's send is a known false-positive (2xx "pending"/"sent" even for failures — obs 5028). Hardened
`WhatsAppChannel`: a successful send is **downgraded to `pending`** (never `sent`); only
`reconcilePendingLogs()` (GET /messages/{id}, via button/scheduled `whatsapp:reconcile`) may promote
to `sent`/`failed`. So: WhatsApp `sent` = Qonvo GET confirmed; `failed` = GET-confirmed or send threw;
`pending` = queued/unconfirmed (honest). Telegram is synchronous + authoritative (ok:true = delivered;
403 = failed) — already real. Test: "never records a WhatsApp send as sent — only reconcile can".

## Tests added
`NotificationBatchTest` (4), `CoursesSurveyOpenTest` (1), + dashboard chip/unlink + parity +
resilience tests across the day. **449 green.**

## Loose ends
- Committed `c3e50af` + pushed to PR #63. Retention/prune of old logs not built (sent kept indefinitely).
- A few demo NotificationLog rows + one demo batch left in dev DB (dummy, refreshable).
