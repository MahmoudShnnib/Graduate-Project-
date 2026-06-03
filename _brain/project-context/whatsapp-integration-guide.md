---
tags: [project-context, whatsapp, qonvo, otp, notifications, messaging, durable]
related:
  - "[[architecture]]"
  - "[[gotchas]]"
  - "[[../daily-notes/2026-06-01-whatsapp-otp-apply-verification]]"
---

# WhatsApp / Qonvo Integration — durable reference

How the system sends WhatsApp messages, where every message lives, how to edit them, and how to test them. Provider is **Qonvo** (`qonvo.ly`), a WhatsApp gateway. Added 2026-06-01 (PR #41).

## 1. Architecture at a glance

```
Notification (toWhatsApp)  ─┐
                            ├─► WhatsAppChannel ─► WhatsAppService::sendText ─► Qonvo API
WhatsAppOtpService::request ┘                          (Http::withToken, Bearer)
```

| File | Role |
|------|------|
| `app/Services/WhatsAppService.php` | Low-level sender. `sendText($phone, WhatsAppMessage)` → `POST {base_url}/whatsapp/messages/send`. `isConfigured()` gate. `formatPhone()` 09→+218. |
| `app/Channels/WhatsAppMessage.php` | Value object: `message` (string) + `type` (default `text`). |
| `app/Channels/WhatsAppChannel.php` | Laravel notification channel. Resolves phone, calls `toWhatsApp()`, swallows+logs failures. |
| `app/Notifications/Concerns/HasWhatsAppChannel.php` | Trait adding `via()` → `['mail', WhatsAppChannel]` **only when** `$notifiable->phone` and `QONVO_API_TOKEN` are set. |
| `app/Services/WhatsAppOtpService.php` | OTP generate/send/verify (transactional, NOT a notification). |
| `app/Models/WhatsAppOtp.php` | `whats_app_otps` table. Hashed code, attempts, expiry, verified_at. |
| `app/Console/Commands/WhatsAppTest.php` | `php artisan whatsapp:test` — live test sender. |

## 2. Configuration

`config/services.php` → `qonvo` block:

```php
'qonvo' => [
    'api_token'      => env('QONVO_API_TOKEN'),
    'sender_phone'   => env('QONVO_SENDER_PHONE'),
    'base_url'       => 'https://qonvo.ly/api',   // hardcoded
    'webhook_secret' => env('QONVO_WEBHOOK_SECRET'),
],
```

`.env` keys (`.env` is gitignored — set these per environment):
- `QONVO_API_TOKEN` — account API token (Bearer). **Never commit.**
- `QONVO_SENDER_PHONE` — the WhatsApp number registered + authenticated in the Qonvo account, e.g. `+218911011851`.
- `QONVO_WEBHOOK_SECRET` — reserved for a future delivery webhook (not yet used).

> If `QONVO_API_TOKEN` **or** `QONVO_SENDER_PHONE` is empty, `isConfigured()` is false → the whole integration is dormant: notifications send mail-only, and OTP on `/apply` is skipped (the form still works). This is the safe default.

After changing `.env`, run `php artisan config:clear`.

## 3. The Qonvo API contract (reverse-engineered — docs page is a JS SPA, unscrapable)

**Send a text message**
```
POST https://qonvo.ly/api/whatsapp/messages/send
Authorization: Bearer {QONVO_API_TOKEN}
Accept: application/json

{
  "sender_whatsapp_number_phone": "+218911011851",
  "recipient_number": "+2189XXXXXXXX",
  "type": "text",
  "message": "..."
}
```
Success → `201 {"message":"Message queued for sending.","data":{...,"status":"pending","id":...}}`

**List registered sender numbers** (useful for diagnosing setup):
```
GET https://qonvo.ly/api/whatsapp/numbers   → {"data":[{ "phone_number":"+218...", "status":"authenticated", ... }]}
```

**Known error**
- `422 {"message":"The sender phone number is not registered.","errors":{"sender_whatsapp_number_phone":[...]}}` — the sender number is **not connected/authenticated** in the Qonvo account. Fix in the Qonvo dashboard (WhatsApp → connect number → scan QR). Not a code/format issue: all formats (+218…, 218…, 09…) fail the same way until the number is `authenticated`. `200`/`422` (not `401`) means the **token is valid**.

## 4. The two kinds of messages

### A) OTP message (transactional)
`app/Services/WhatsAppOtpService.php`, in `request()`:
```php
message: "رمز التحقق الخاص بك في مكتب الدراسات العليا هو: {$code}\nصالح لمدة ".self::EXPIRES_MINUTES.' دقائق. لا تشاركه مع أحد.',
```
Tunables (consts at top of the service): `CODE_LENGTH` 6, `EXPIRES_MINUTES` 5, `RESEND_COOLDOWN_SECONDS` 60, `MAX_SENDS_PER_WINDOW` 3, `SEND_WINDOW_MINUTES` 15, `MAX_VERIFY_ATTEMPTS` 5.

### B) Notification messages (9)
Each is a class in `app/Notifications/`, with a `toWhatsApp(object $notifiable): WhatsAppMessage` method (and a parallel `toMail()` — the email version; **edit both** to keep them consistent).

| Type key (for `whatsapp:test`) | File | Constructor model |
|------|------|------|
| `application_received` | `ApplicationReceivedNotification` | `Application` |
| `exam_passed` | `ApplicationExamPassedNotification` | `Application` |
| `status_changed` | `ApplicationStatusChangedNotification` | `Application` |
| `welcome` | `WelcomeStudentNotification` | `Student`, `?string $plainPassword` |
| `grade` | `GradePublishedNotification` | `Grade` |
| `add_drop` | `AddDropDecisionNotification` | `AddDropRequest` |
| `suspension` | `SuspensionDecisionNotification` | `SuspensionRequest` |
| `thesis` | `ThesisDecisionNotification` | `ThesisRegistration` |
| `clearance` | `ClearanceIssuedNotification` | `ClearanceRequest` |

Common placeholders inside `message:` strings: `{$notifiable->name}`, `{$notifiable->email}`, model fields (`$course?->name`, `$this->grade->total`, `$semester`, `$decision`), and `\n` for line breaks.

> These notifications use the `Queueable` trait but do **not** `implements ShouldQueue`, so they send **synchronously** — no queue worker needed.

## 5. How to edit a message
1. Open the file (OTP → the service; a notification → its class).
2. Change the Arabic text in `message:` (and the matching `toMail()` lines).
3. Keep the `{$...}` placeholders intact. No build/compile needed — PHP changes are immediate.

## 6. How to test

**Dedicated command (preferred):**
```bash
php artisan whatsapp:test 0912345678                 # sends OTP + all 9
php artisan whatsapp:test 0912345678 --type=grade    # one type
php artisan whatsapp:test 0912345678 --type=otp
```
It builds each notification from the first seeded record (skips types with no data) and prints per-message ✔/✘ with the Qonvo error if any.

**Quick raw send (tinker):**
```php
app(\App\Services\WhatsAppService::class)->sendText('0912345678', new \App\Channels\WhatsAppMessage(message: 'نص تجريبي'));
```

**Real applicant flow:** open `/apply`, enter phone, click "إرسال رمز التحقق عبر واتساب", enter the code (60s resend cooldown).

**Automated:** `tests/Feature/WhatsAppOtpVerificationTest.php` uses `Http::fake()` — the pattern for asserting wording without real sends. Set config in-test via `config()->set('services.qonvo.api_token'|'sender_phone', ...)`.

## 7. OTP-on-/apply flow (PR #41)

- Routes (`routes/web.php`): `POST /apply/otp/send` (throttle:6,1), `POST /apply/otp/verify` (throttle:10,1).
- `RegistrationController::sendOtp/verifyOtp` — JSON endpoints; share `phoneRules()` with `store()`.
- `store()` guard: when `isConfigured()`, rejects with a `phone` error unless `WhatsAppOtpService::hasVerified($phone)` (a still-valid verified OTP within 30 min, **keyed by the exact phone** — verifying number A can't authorize submitting B).
- UI: `resources/views/registration/create.blade.php` — inline widget by the phone field, gated on `$whatsappEnabled`. Vanilla JS (`fetch()`), no Livewire. Changing the phone resets verification; submit is client-gated (server is the real boundary).

## 8. Gotchas

- **Sender must be `authenticated` in Qonvo first** — newly added numbers take ~1 min to finish QR authentication; sends `422` until then. Check `GET /whatsapp/numbers`.
- **`APP_URL` matters** — was `cl` (garbage), fixed to `http://pgsms.test`; also pinned in `phpunit.xml`. A broken `APP_URL` makes `route()` produce malformed URLs and tests 404.
- **`.env` is gitignored** — credentials must be set per environment; they are never in the repo.
- **Token hygiene** — the initial API token was shared in a chat session; rotate tokens that may have been exposed.

## 9. Roadmap (not yet built — see daily note for templates)
OTP login · admin/faculty 2FA · password reset via WhatsApp · deadline reminders (application / survey / add-drop / exam / thesis / fees) · Qonvo delivery webhook (`QONVO_WEBHOOK_SECRET`).
