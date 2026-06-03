---
date: 2026-06-01
tags: [daily, whatsapp, qonvo, otp, registration, apply, security, notifications]
related:
  - "[[../project-context/architecture]]"
---

# 2026-06-01 — WhatsApp OTP phone verification on /apply (Qonvo)

First phase of a larger WhatsApp roadmap (OTP login, 2FA, password reset, deadline reminders — deferred). This session activated the Qonvo integration and built **phone verification on the public application form**. Suite green for the new feature (10/10), Pint clean. Phased per project rules: this one feature only.

## Context — the integration already existed but was dormant

`WhatsAppService` (`POST {base_url}/whatsapp/messages/send`, Bearer auth, body: `sender_whatsapp_number_phone` / `recipient_number` / `type` / `message`), `WhatsAppChannel`, the `HasWhatsAppChannel` trait, and 9 `toWhatsApp()` notifications were all already in place — but `.env` had **no Qonvo credentials**, so nothing sent. The Qonvo docs page (`qonvo.ly/api-docs`) is a JS SPA and can't be scraped; the existing service already encodes the working API contract.

> Kept existing config key names `QONVO_API_TOKEN` / `QONVO_SENDER_PHONE` (did **not** rename to the API_KEY/SECRET names suggested in the request) — renaming would break the 9 live notifications. Added only `QONVO_WEBHOOK_SECRET` for future webhook support.

## What was built

- **`whats_app_otps` table** + `WhatsAppOtp` model — columns: `phone`, `purpose` (default `apply_phone`), `code_hash` (hashed, never plaintext), `attempts`, `verified_at`, `expires_at`. Indexed on `(phone, purpose)` and `expires_at`.
- **`WhatsAppOtpService`** — `request()` / `verify()` / `hasVerified()`. 6-digit code, 5-min expiry, 60s resend cooldown, max 3 sends / 15 min, lockout after 5 wrong attempts. Sends via the existing `WhatsAppService`; send failures are caught + `Log::warning` (no token/code logged).
- **`RegistrationController`** — new `sendOtp()` / `verifyOtp()` JSON endpoints; extracted the Libyan-phone rule into a shared `phoneRules()` (was duplicated). `store()` now requires `otpService->hasVerified($phone)` **only when** `WhatsAppService::isConfigured()` — so /apply keeps working until the sender number is set.
- **Routes** — `POST /apply/otp/send` (throttle:6,1), `POST /apply/otp/verify` (throttle:10,1).
- **`registration/create.blade.php`** — inline verify widget by the phone field (send button → code input → verified badge), gated on `$whatsappEnabled`. Vanilla JS matching the existing `fetch()` pattern (no Livewire). Changing the phone resets verification. Submit button disabled client-side until verified (server is the real guard).
- **Verification is keyed by phone** (DB-backed, not session) — verifying number A can't authorize submitting number B.

## Bug fixed along the way: `APP_URL=cl`

`.env` had `APP_URL=cl` (garbage). Harmless when browsing via Herd, but in tests `route()` built malformed URLs → every endpoint 404'd. Fixed to `APP_URL=http://pgsms.test`, and pinned `APP_URL=http://localhost` in `phpunit.xml` so tests no longer depend on `.env`. This also corrects generated links / PDF asset URLs app-wide.

## Files touched

- `database/migrations/2026_06_01_185930_create_whats_app_otps_table.php` (new)
- `app/Models/WhatsAppOtp.php`
- `app/Services/WhatsAppOtpService.php` (new)
- `app/Http/Controllers/RegistrationController.php`
- `routes/web.php`
- `config/services.php` (+`webhook_secret`)
- `.env` (token + `APP_URL` fix), `.env.example` (+`QONVO_WEBHOOK_SECRET`)
- `resources/views/registration/create.blade.php`
- `phpunit.xml` (+`APP_URL`)
- `tests/Feature/WhatsAppOtpVerificationTest.php` (new, 10 tests)
- `app/Console/Commands/WhatsAppTest.php` (new) — `php artisan whatsapp:test {phone} {--type=}` sends a real OTP or any of the 9 notifications to a number for live delivery/wording testing.

## Open items

- **Sender number still blank** — `QONVO_SENDER_PHONE` is empty, so OTP stays disabled until the user provides the Qonvo WhatsApp sender number. API token is wired.
- **Token exposed in chat** — user pasted the API token in plaintext; advised to rotate it in the Qonvo dashboard.
- Pre-existing suite issues (not mine): full suite OOMs at the default 128MB memory limit (run with `-d memory_limit=1G`); `ExamListPrintTest` fails with `Class "Mpdf\Config\ConfigVariables" not found` (mPDF autoload in test env).
- Roadmap remaining (deferred): OTP login, admin/faculty 2FA, password reset via WhatsApp, deadline reminders (application/survey/add-drop/exam/thesis/fees), Qonvo webhook for delivery status.
