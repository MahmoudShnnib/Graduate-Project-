---
date: 2026-06-10
tags: [daily, otp, whatsapp, apply, registration]
related:
  - "[[2026-06-09-apply-form-enhancements]]"
  - "[[../bugs-and-fixes/2026-06-10-whatsapp-otp-no-push-notification]]"
---

# 2026-06-10 — WhatsApp OTP expiry & resend cooldown → 120 seconds

## What was done

Extended the `/apply` phone-verification OTP lifetime from **1 minute → 120 seconds**
per user request, then matched the resend cooldown to it. Single-file changes in
`app/Services/WhatsAppOtpService.php`:

- `EXPIRES_MINUTES = 1` → `EXPIRES_SECONDS = 120`.
- `now()->addMinutes(self::EXPIRES_MINUTES)` → `now()->addSeconds(self::EXPIRES_SECONDS)`.
- WhatsApp message text: *"صالح لمدة دقيقة واحدة فقط"* → *"صالح لمدة دقيقتين فقط"*.
- `RESEND_COOLDOWN_SECONDS = 60` → `120` (follow-up, so resend unlocks exactly as the
  code expires — both timers now aligned at 2 min).

## Why / non-obvious

- The **resend cooldown** now also = 120s, so resend unlocks exactly when the code
  expires (clean pairing — no window where a still-valid code coexists with a resend).
- The blade countdown in `registration/create.blade.php` (`startCooldown`, ~line 1136)
  is the **resend timer**. The *failed-send* branch reads `retry_after` from the server
  (auto-correct), but the *successful-send* branch had a **hardcoded `startCooldown(60)`**
  (line 1170) — that was the visible "60 ثانية" the user spotted. Bumped to `120`.
  Public Tailwind-CDN page → no `npm` build, shows on refresh.
- The OTP verification tests set explicit `expires_at` values rather than relying on the
  constant, so they're unaffected by the change.

## Files touched

- `app/Services/WhatsAppOtpService.php`

## Tests

- `php artisan test --filter=WhatsAppOtp` → **14 passed (47 assertions)**. Pint clean.

## Next steps / loose ends

- Not yet committed/deployed — ships with the next `main` push → Dokploy redeploy.
