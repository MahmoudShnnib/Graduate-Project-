---
date: 2026-06-10
tags: [bug, whatsapp, qonvo, otp, notifications, infra]
related:
  - "[[../daily-notes/2026-06-10-whatsapp-otp-expiry]]"
---

# WhatsApp OTP delivered but no push notification

## Symptom

After switching `QONVO_SENDER_PHONE` to a new sender number (Jun 9), `/apply` OTP codes
**still arrived** in WhatsApp but the recipient got **no push notification** — the message
only appeared when they manually opened the app. The previous sender number notified fine.

## Root cause

**Not a code bug.** `WhatsAppService::sendText` and the whole payload were unchanged; only
the env value `QONVO_SENDER_PHONE` (read in `config/services.php`) was swapped. Qonvo
(`qonvo.ly`) is an **unofficial gateway** that sends via a *linked* WhatsApp session, not the
official Cloud API. The new number's session on Qonvo had gone into a degraded/disconnected
state → Qonvo accepted the message (our code saw success) and WhatsApp delivered it on next
sync, but no real-time push fired.

## Fix

**Reconnect the sender number's session in the Qonvo dashboard.** After reconnect, push
notifications worked immediately. No app/code change.

## Guard / takeaway

- Our app cannot control WhatsApp push notifications — there is no notify/silent flag in the
  send payload. "Delivered but no notification" is **always** a Qonvo-session / WhatsApp-account
  issue, never our code.
- First diagnostic step for any future "OTP not arriving / not notifying": check the Qonvo
  sender session is **connected/online**, then reconnect if degraded.
- Changing `QONVO_SENDER_PHONE` requires confirming the new number's Qonvo session is fully
  linked + warmed before relying on it in production.
