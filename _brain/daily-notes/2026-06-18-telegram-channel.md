---
date: 2026-06-18
tags: [telegram, notifications, otp, apply, messaging]
related:
  - "[[../decisions/2026-06-18-telegram-notification-channel]]"
  - "[[../project-context/whatsapp-integration-guide]]"
---

# Telegram channel — Phase 1 + Phase 2 (branch `feature/telegram-notifications`)

Adding Telegram as a free, ban-proof second channel alongside the flaky Qonvo/WhatsApp.
Bot created: **@The_Creators67_bot** (token in `.env`, gitignored). **Uncommitted.**

## Phase 1 — sending pipeline (DONE)
`TelegramService` (sendText/sendMessage/getUpdates/requestContactKeyboard/isConfigured/
botUsername/toLocalLibyan), `TelegramMessage` DTO, `TelegramChannel` (logs to
`notification_logs` channel `telegram`; **no reconcile** — Telegram is synchronous, 403 =
blocked), `config/services.php` telegram block, `NotificationChannel::Telegram` enum case,
`toTelegram()` on WelcomeStudent + ApplicationInitiallyApproved. 8 tests.

## Phase 2 — share-contact OTP on /apply (DONE)
Why share-contact: it's the only verification model that binds each application to a
distinct **Telegram-verified** phone (Telegram accounts are phone-gated), so it's a real
anti-mass-applying gate — see the decision note.

- `telegram_verifications` table + model; `applications.telegram_chat_id`.
- `TelegramVerificationService`: start (reuses open row), deepLink, status (polled),
  hasVerified (30-min grace), processUpdate (`/start <token>` → bind chat + request-contact
  keyboard; contact → **anti-spoof** `contact.user_id === from.id` + entered-phone match →
  verified, else `mismatch`).
- `telegram:poll` command — `--loop` long-polls for realtime dev linking; single-drain
  scheduled everyMinute as the no-webhook fallback (offset in cache). Webhook = future.
- Routes `registration.telegram.init|status`; `store()` guard now "verified via WhatsApp
  OR Telegram"; chat_id saved on the application.
- Apply page: WhatsApp widget stays primary/untouched; a collapsible **Telegram** panel
  ("لا تستخدم واتساب؟") with 4 numbered steps + background polling sits below it. The
  Telegram script guards on `otpVerified` so it doesn't re-lock submit when WhatsApp
  verified on load.

## Tests / verification
- 8 (`TelegramNotificationTest`) + 15 (`TelegramVerificationTest`); **full suite 423 green**.
- Blade compiles (`view:cache`); live bot reachable (getMe + getUpdates).
- `phpunit.xml` nulls `TELEGRAM_BOT_TOKEN`/`_USERNAME` so real .env creds don't leak into
  tests (mirrors how WhatsApp tests set Qonvo explicitly).

## Live end-to-end — VERIFIED (2026-06-18)
Real bot, real phone: started `telegram:poll --loop`, opened `/apply`, clicked the Telegram
button, shared contact → row went `verified` (chat_id 964396285, telegram_phone matched the
entered 0945108260) and the apply page flipped to ✓. `hasVerified()` true.

**Gotchas hit during the live test:**
- **The bot is silent unless `telegram:poll` (or a webhook) is running** — without a
  processor, `/start` gets no reply. Run `php artisan telegram:poll --loop` in dev.
- **Telegram hides the `?start=<token>` payload** — the chat shows just "/start", but the
  token IS sent (the row bound correctly). Don't be fooled by the bare "/start" display.
- **request_contact (share-phone) reliably works only on the MOBILE Telegram app.**
  Telegram **Desktop** often shows the reply-keyboard button (behind the ⊞ icon by the
  input box) but won't actually prompt the share. Fine for real applicants (mobile), but
  note it for testing/support.

## Phase 3 — student-portal linking (DONE)
`users.telegram_chat_id`+`telegram_linked_at`; `telegram_verifications.user_id`.
`startForUser` + `purpose 'student_link'` (authenticated → `/start` alone captures chat_id,
no contact step). Student dashboard card ("ربط حساب تيليجرام" → deep link + `wire:poll` → ✓ +
unlink). `ApproveApplication` propagates `telegram_chat_id` app→user. 5 tests.

## Phase 4 — policy-aware routing (DONE) — notifications now send over Telegram
`HasMessagingChannels` trait reads `notification_channel_policy` (whatsapp_first default /
telegram_first / both) → returns WhatsApp and/or Telegram by availability; swapped into the 2
notifications. Admin "قنوات الإشعارات" selector in the الإعدادات modal. 6 tests.

**Regression caught & fixed:** my approval edit dropped the `password` line (Pint then removed
`Hash`/`Str` imports) → User insert failed NOT NULL. Restored. Lesson: when inserting fields
mid-array, re-check the surrounding lines weren't displaced.

## All notifications mirrored to Telegram (DONE, later 2026-06-18)
Per user "make all WhatsApp messages work in Telegram": added a verbatim `toTelegram()` to
every notification that had a `toWhatsApp()`.
- `GradesPublishedNotification` → swapped to `HasMessagingChannels` + `toTelegram()`.
- `ContactInfoChangedNotification` (security alert, on-demand `Notification::route`) → kept its
  **custom `via()`** but it now sends over **BOTH** channels regardless of policy; shared text
  extracted into `alertText()`. `UserObserver` builds an `AnonymousNotifiable` routing the
  old-phone `whatsapp` **and** the user's `telegram` chat (chat id isn't contact info → still
  the real owner).
- Welcome + ApplicationInitiallyApproved were already done.
- `TelegramMessageParityTest` asserts `toTelegram === toWhatsApp` for all 4 (guards drift).
- The `/apply` **OTP code is intentionally NOT mirrored** — Telegram uses share-contact, not a
  typed code.
+6 tests → **full suite 440 green**.

## Live demo (2026-06-18 ~9:40pm)
Sent all 11 possible messages (4 notifications + 5 bot replies) to chat 964396285 — all
delivered. Earlier also fired a real ApplicationInitiallyApproved via the policy path
(`telegram_first` → `via()` chose `[TelegramChannel]` → status `sent`).

## Status: ALL PHASES DONE — full suite **440 green**, Pint clean, Blade compiles.
Durable guide: [[../project-context/telegram-integration-guide]].

## Not yet done (future)
- Prod **webhook** (replace `telegram:poll` long-poll) once a hosting platform is chosen — or
  rely on the scheduler (already wired every minute). NOTE: inbound linking/OTP needs the
  processor running; **outbound notifications do not** (they fire synchronously).
- **Student-portal link (Phase 3) not yet clicked live by the user** — backend tested, bot
  success message verified, but the real `/portal` card→Start→✓ flow is unexercised by a human.
- QR on `/apply` for desktop applicants (share-contact is mobile-only anyway).
- WhatsApp PR #62 still open; this branch is off `origin/main` → rebase once #62 merges.
- **Not committed** (per user) — awaiting their go-ahead.
