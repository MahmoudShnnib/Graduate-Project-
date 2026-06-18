---
tags: [project-context, telegram, notifications, otp, messaging, durable]
related:
  - "[[whatsapp-integration-guide]]"
  - "[[../decisions/2026-06-18-telegram-notification-channel]]"
  - "[[gotchas]]"
---

# Telegram integration — durable reference

Free, ban-proof second messaging channel alongside Qonvo/WhatsApp, via the official
**Telegram Bot API**. Bot: **@The_Creators67_bot** (token in `.env`, gitignored). Added
2026-06-18 (branch `feature/telegram-notifications`).

## 1. Why Telegram (vs WhatsApp/Qonvo)
Qonvo drives unofficial WhatsApp-Web automation → sender numbers get banned. Telegram is
first-party + free, and `sendMessage` returns the real result **synchronously** (2xx =
delivered, `403` = blocked) — so **no pending/reconcile step** like WhatsApp needs. The only
new work was capturing each recipient's `chat_id` ("linking"). Once a user has started the
bot once, the bot can message them **forever** — there is **no 24-hour window** (that's a
WhatsApp-Cloud-API concept, not Telegram).

## 2. Configuration
`config/services.php` → `telegram` block:
```php
'telegram' => [
    'bot_token'      => env('TELEGRAM_BOT_TOKEN'),     // from @BotFather
    'bot_username'   => env('TELEGRAM_BOT_USERNAME'),  // no @, no t.me/
    'api_base'       => 'https://api.telegram.org',
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),// reserved for prod webhook
],
```
Dormant until `bot_token` is set (`TelegramService::isConfigured()`). After editing `.env`,
run `php artisan config:clear`. `phpunit.xml` nulls `TELEGRAM_BOT_TOKEN`/`_USERNAME` so the
real bot creds never leak into tests (each test sets config explicitly).

## 3. Files
| File | Role |
|------|------|
| `app/Services/TelegramService.php` | Low-level API: `sendText`/`sendMessage`(+reply markup), `getUpdates`, `requestContactKeyboard`, `isConfigured`, `botUsername`, `toLocalLibyan` (phone normaliser). |
| `app/Channels/TelegramMessage.php` | DTO: `message` + optional `parseMode`. |
| `app/Channels/TelegramChannel.php` | Notification channel → logs to `notification_logs` (channel `telegram`); `sent` on success, `failed` on 403/exception. No reconcile. |
| `app/Notifications/Concerns/HasMessagingChannels.php` | Policy-aware `via()` (WhatsApp/Telegram). Used by WelcomeStudent + ApplicationInitiallyApproved. |
| `app/Services/TelegramVerificationService.php` | Apply-OTP (share-contact) **and** student-link flows + `processUpdate` (the bot brain). |
| `app/Models/TelegramVerification.php` | `telegram_verifications` table. |
| `app/Console/Commands/PollTelegramUpdates.php` | `telegram:poll` — processes bot updates. |

## 4. Sending notifications (Phase 4)
`HasMessagingChannels::via()` reads the admin setting **`notification_channel_policy`**:
- `whatsapp_first` (default) — WhatsApp if reachable, else Telegram.
- `telegram_first` — Telegram if linked, else WhatsApp.
- `both` — send over both.
"Reachable" = `notifiable->phone` + Qonvo token (WhatsApp) / `notifiable->telegram_chat_id`
+ bot token (Telegram). Set the policy in the dashboard **الإعدادات** modal → "قنوات
الإشعارات".

**All notifications are Telegram-enabled** — every `toWhatsApp()` has a verbatim
`toTelegram()` twin (enforced by `TelegramMessageParityTest`):
- `WelcomeStudentNotification`, `ApplicationInitiallyApprovedNotification`,
  `GradesPublishedNotification` → `HasMessagingChannels` (policy-routed).
- `ContactInfoChangedNotification` (security alert) → custom `via()` that sends over
  **both** channels regardless of policy. Dispatched from `UserObserver` to an
  `AnonymousNotifiable` carrying the old-phone `whatsapp` route + the user's `telegram`
  chat (the chat id isn't contact info, so it still reaches the real owner).

To Telegram-enable a new notification: add a `toTelegram(): TelegramMessage` mirroring
`toWhatsApp()` and use `HasMessagingChannels`. The `/apply` OTP is **not** mirrored — Telegram
uses the share-contact flow, not a code.

## 5. Capturing chat_id (linking)
The bot can't message a `chat_id` it has never received. Two flows, both via a deep link
`t.me/<bot>?start=<token>` and the `telegram:poll` processor:

**A) Applicant share-contact OTP on `/apply`** (anti-mass-applying gate)
`/apply` → "التحقق عبر تيليجرام" → `POST registration.telegram.init` → deep link → applicant
taps Start → bot sends a **request-contact** keyboard → applicant shares → Telegram returns
their **verified** phone + `chat_id`. We **anti-spoof** (`contact.user_id === sender`) and
require the shared phone to match the entered phone, then mark verified. `/apply` polls
`registration.telegram.status` → ✓; `store()` accepts (guard = "verified via WhatsApp OR
Telegram") and stores `applications.telegram_chat_id`.

**B) Student-portal link** (authenticated → simpler, no contact step)
Student dashboard card "ربط حساب تيليجرام" → `startForUser` → deep link → student taps Start →
`/start <token>` alone captures `chat_id` → written to `users.telegram_chat_id`
(+`telegram_linked_at`). `wire:poll` flips the card to ✓; "إلغاء الربط" unlinks.

On approval, `ApproveApplication` propagates `applications.telegram_chat_id` →
`users.telegram_chat_id`, so a Telegram-verified applicant keeps Telegram as a student.

## 6. Running the bot processor
The bot is **silent unless something processes its updates**:
- **Dev / realtime:** `php artisan telegram:poll --loop` (long-polls `getUpdates`). Required
  for the apply-OTP and link flows to respond in seconds.
- **Fallback:** `telegram:poll` (single drain) is scheduled `everyMinute` in
  `routes/console.php` (needs `schedule:work`/cron).
- **Prod (future):** a webhook (`TELEGRAM_WEBHOOK_SECRET`) replaces polling.

## 7. Testing
- `TelegramNotificationTest` (channel), `TelegramVerificationTest` (apply share-contact),
  `TelegramStudentLinkTest` (portal link), `MessagingChannelPolicyTest` (via() policy),
  +`ApproveApplicationTest` propagation. All use `Http::fake()`. Full suite **434 green**.
- Verify a live bot without exposing the token:
  `php artisan tinker --execute="dump(app(App\Services\TelegramService::class)->getUpdates());"`

## 8. Gotchas (learned live 2026-06-18)
- **Bot silent without `telegram:poll`/webhook running** — `/start` gets no reply otherwise.
- **Telegram hides the `?start=<token>` payload** — the chat shows a bare "/start" but the
  token IS sent (the verification still binds).
- **Share-contact (request_contact) reliably works only on the MOBILE Telegram app** —
  Desktop shows the reply-keyboard button (behind the ⊞ icon) but usually won't prompt.
  Fine for real applicants (mobile); use a phone to test.
- **`getUpdates` and a webhook are mutually exclusive** — don't set both.
- **`.env` Telegram creds must be nulled in `phpunit.xml`** or they leak into tests and flip
  "configured" checks.

## 9. Not yet built
Prod webhook; QR code on `/apply` for desktop applicants. (All current notifications are
Telegram-enabled as of 2026-06-18.)
