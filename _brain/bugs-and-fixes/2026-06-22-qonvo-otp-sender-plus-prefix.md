---
date: 2026-06-22
tags: [bug, whatsapp, qonvo, otp, apply, production]
related:
  - "[[../project-context/whatsapp-integration-guide]]"
  - "[[../daily-notes/2026-06-22-student-create-guards-docs-consolidation]]"
---

# Apply-page OTP fails: Qonvo rejects sender with leading "+"

## Symptom
`/apply` "send OTP" returns **"تعذّر إرسال رمز التحقق. يرجى المحاولة لاحقاً."**
Log (`storage/logs/laravel.log`):
```
WhatsApp OTP send failed for purpose apply_phone: HTTP request returned status code 422:
{"message":"The sender phone number is not registered.",
 "errors":{"sender_whatsapp_number_phone":["The sender phone number is not registered."]}}
```

## Root cause
Qonvo matches `sender_whatsapp_number_phone` against the **registered** WhatsApp number, which it
stores **without** a leading `+`. `QONVO_SENDER_PHONE` was set in international form `+218911011851`,
so it didn't match → 422. (Earlier `cURL error 28` lines were unrelated network timeouts.)

Proven live against the API (tinker, invalid recipient so nothing actually sends):
| sender sent | Qonvo |
|---|---|
| `+218911011851` | 422 "sender not registered" |
| `218911011851`  | 201 "Message queued for sending" |

Note the asymmetry: `WhatsAppService::formatPhone()` deliberately gives the **recipient** a `+`, but
the **sender** must have none. Different fields, different rules.

## Fix
1. **Immediate (dev .env):** `QONVO_SENDER_PHONE=218911011851` (dropped the `+`).
2. **Robust (code):** `WhatsAppService::senderPhone()` now `ltrim(trim(...), '+')` the configured
   sender before sending, so both `+218…` and `218…` resolve to the registered number — prevents the
   exact footgun recurring (esp. on production where an operator sets the env by hand).

## Regression guard
`WhatsAppReconcileTest`: *"strips a leading + from the sender phone so Qonvo recognises the
registered number"* — fakes Http, asserts the outgoing `sender_whatsapp_number_phone` has no `+`.
Full suite 478 green.

## Production note
The dev `.env` fix does **not** propagate to prod. Production `QONVO_SENDER_PHONE` must be set to a
number whose WhatsApp session is connected in the Qonvo dashboard for the same account as
`QONVO_API_TOKEN`. With the code fix the `+`/no-`+` form no longer matters; the **registration** does.
