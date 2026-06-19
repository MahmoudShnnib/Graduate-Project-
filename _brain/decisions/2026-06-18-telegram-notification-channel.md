---
date: 2026-06-18
tags: [decision, telegram, notifications, messaging, whatsapp, otp]
related:
  - "[[../project-context/whatsapp-integration-guide]]"
  - "[[../bugs-and-fixes/2026-06-18-whatsapp-log-status-reconciliation]]"
---

# Decision: add Telegram as a second notification channel alongside WhatsApp

## Context
Qonvo/WhatsApp is unreliable — it drives unofficial WhatsApp-Web automation and the
sender number keeps getting temp-banned (a real number caught a 17-hour ban on
2026-06-18 from burst testing). We need a free, ban-proof second channel.

## Decision
Add **Telegram Bot API** as a parallel channel that mirrors the existing WhatsApp
pipeline. Telegram is first-party, free, and — unlike Qonvo — `sendMessage` returns
the real delivery result **synchronously** (2xx = delivered, 4xx like `403 bot was
blocked by the user` = not), so there is **no pending/reconcile step**.

Key property confirmed during research: once a user has **started the bot once**, the
bot can message them **anytime, forever**, with no further interaction and **no
24-hour window** (that window is a WhatsApp-Cloud-API concept, not Telegram). The only
new work is capturing each user's `chat_id` (the "linking" step).

### Scope (from user, 2026-06-18)
- **Audience:** students + applicants, and most importantly the **/apply OTP**.
- **Channel policy when a user has both phone + Telegram:** an **admin setting**
  (`notification_channel_policy`), like the OTP toggle — not hardcoded.
- **Coverage:** start with high-value notifications (WelcomeStudent +
  ApplicationInitiallyApproved) + the OTP path; expand later.
- **Apply page:** WhatsApp stays the **primary/default** verification method; Telegram
  is an **opt-in alternative** shown alongside it, and when chosen the page displays
  **explicit numbered instructions** (open bot → Start → Share my phone → auto-✓).

### Telegram OTP model — **LOCKED: share-contact** (2026-06-18)
**Share-contact** is the chosen anti-mass-applying gate: applicant starts
the bot → bot shows a "Share my phone number" button → Telegram sends the bot the
user's **Telegram-verified** phone + `chat_id` → we check it matches the entered phone
(or adopt the verified one). This binds each application to a distinct verified phone
(Telegram accounts are themselves phone-gated), matching the WhatsApp-OTP deterrent and
capturing `chat_id` for free. (Note: OTP is a **spam/sybil** gate, not a DDoS defense —
DDoS is handled by the existing route throttles.)

## Alternatives considered
- **Start-link = verified** (tap unique link = done): smoothest UX, but one Telegram
  account can verify unlimited applications → weak anti-spam. Rejected as the gate.
- **Code via Telegram** (type a 6-digit code): familiar UI, but same one-account-many-
  apps weakness. Rejected as the gate.
- **Email second channel:** already coded (`toMail` existed) but notifications are
  WhatsApp-only by design now, and Libyan applicant email adoption is low. Kept as a
  possible future, not the primary fallback.

## Phasing
- **Phase 1 — DONE (this commit):** sending pipeline. `TelegramService`,
  `TelegramMessage`, `TelegramChannel`, `config/services.php` telegram block,
  `NotificationChannel::Telegram` enum case, `toTelegram()` on the two notifications,
  logging into the existing `notification_logs` (channel `telegram`). 8 new tests
  (`TelegramNotificationTest`) green; 38 notification tests total green. No reconcile
  needed. `via()` not yet rewired — there is no `chat_id` source until linking lands,
  so the channel is exercised directly for now.
- **Phase 2 — DONE (2026-06-18):** Telegram share-contact OTP on `/apply`.
  `telegram_verifications` table + model; `TelegramVerificationService` (start/deepLink/
  status/hasVerified/processUpdate with the `contact.user_id === from.id` anti-spoof +
  entered-phone match); `TelegramService` gains `getUpdates`/`sendMessage`(+request-contact
  keyboard)/`toLocalLibyan`; `telegram:poll` command (`--loop` for realtime dev, single-
  drain scheduled everyMinute as fallback); routes `registration.telegram.init|status`;
  generalized `store()` guard ("verified via WhatsApp OR Telegram") + `create()` flags;
  `applications.telegram_chat_id` captured on submit. Apply page: opt-in Telegram panel
  below the WhatsApp widget (WhatsApp stays primary) with numbered instructions +
  background polling; the WhatsApp script is untouched (tests depend on its `let verified`
  line) and the Telegram script guards against fighting it on load. 15 tests
  (`TelegramVerificationTest`); full suite **423 green**. Live bot connectivity verified
  (getMe/getUpdates). `phpunit.xml` now nulls `TELEGRAM_BOT_TOKEN` so the real .env bot
  creds don't leak into tests.
  Remaining manual check: real human share-contact end-to-end (needs a Telegram client +
  `php artisan telegram:poll --loop` running).
- **Phase 3 — DONE (2026-06-18):** student-portal linking. `users.telegram_chat_id` +
  `telegram_linked_at`; `telegram_verifications.user_id`. `startForUser` + `purpose
  'student_link'` (authenticated → no contact step; `/start` alone captures the chat id and
  writes it to the user). `isUserLinked`/`unlinkUser`. UI: a card on the student dashboard
  ("ربط حساب تيليجرام" → deep link + `wire:poll` until linked → ✓ + unlink). `ApproveApplication`
  propagates the application's `telegram_chat_id` → the new user. 5 tests.
- **Phase 4 — DONE (2026-06-18):** policy-aware routing. `HasMessagingChannels` trait reads
  `notification_channel_policy` (`both` **default** since 2026-06-19 / `whatsapp_first` /
  `telegram_first`) and
  returns WhatsApp and/or Telegram channels by availability; swapped into WelcomeStudent +
  ApplicationInitiallyApproved. Admin selector in the dashboard الإعدادات modal ("قنوات
  الإشعارات"). 6 tests. **Notifications now actually deliver over Telegram.**
- **All notifications mirrored — DONE (2026-06-18):** every `toWhatsApp()` now has a verbatim
  `toTelegram()` twin. `GradesPublishedNotification` → `HasMessagingChannels`.
  `ContactInfoChangedNotification` (security alert) keeps a **custom `via()`** that sends over
  **both** channels regardless of policy (best chance to reach the real owner); `UserObserver`
  routes the old-phone `whatsapp` + the user's `telegram` chat. `TelegramMessageParityTest`
  enforces `toTelegram === toWhatsApp`. The `/apply` OTP **code** is intentionally not mirrored
  (Telegram uses share-contact). +6 tests.

Full feature suite **440 green**. Remaining: prod **webhook** (or the already-wired scheduler)
for inbound linking/OTP — outbound notifications need neither; a QR code on `/apply` for
desktop applicants (deferred — share-contact is mobile-only anyway); a live click-through of
the student-portal link card (backend tested, not yet exercised by a human).

## Consequences
- New env keys: `TELEGRAM_BOT_TOKEN`, `TELEGRAM_BOT_USERNAME`, `TELEGRAM_WEBHOOK_SECRET`
  (gitignored, set per environment). Dormant until `bot_token` is set (`isConfigured()`).
- Bot must be created via @BotFather to get the token.
- Receiving `/start` + contact needs either a webhook (public HTTPS, prod) or
  `getUpdates` long-polling (dev/no-webhook fallback) — mirrors the WhatsApp
  command+button fallback pattern.
