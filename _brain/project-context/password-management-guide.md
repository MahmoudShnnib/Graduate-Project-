---
tags: [project-context, password, otp, whatsapp, auth, security, filament, durable]
related:
  - "[[whatsapp-integration-guide]]"
  - "[[architecture]]"
  - "[[gotchas]]"
  - "[[../daily-notes/2026-06-04-password-management-design]]"
---

# Password & Credential Management — durable reference

How a student / admin / faculty / secretary changes or resets their password, and the
two related credential-editing tweaks on the admin side. All password recovery is gated
by a **WhatsApp OTP** sent to the phone **on file** for the account (never a phone typed
by the requester). Built on the existing [[whatsapp-integration-guide|Qonvo OTP service]].

> **STATUS (2026-06-04): IMPLEMENTED & TESTED, UNCOMMITTED.** All four parts built; full
> suite 211 green, Pint clean. The file paths below are the real ones. Left uncommitted for
> review per [[no-auto-commit]]. Tests: `tests/Feature/PasswordResetFlowTest.php` (8),
> `tests/Feature/ChangePasswordTest.php` (6), `tests/Feature/AdminCredentialEditTest.php` (5).
>
> **Build note — confirmation popup (Part 2):** the change-password submit uses Livewire
> native `wire:confirm` (browser confirm), and is `:disabled` until an OTP has been sent.
> **Reset final step (Part 1):** a normal POST→redirect form (only the OTP send is `fetch`),
> exactly like `/apply`'s `store`.

## 0. The four parts (one cohesive change)

| # | What | Surface | Who |
|---|------|---------|-----|
| 1 | **Forgot-password reset** (public) | `GET /forgot-password` (Blade page) | any role, identified by **national ID** |
| 2 | **Change password** (logged-in) | Filament Page in **both** panels | the signed-in user |
| 3 | **Edit student phone + email** | `/admin/students/{id}/edit` | secretary / super_admin |
| 4 | **Remove password field on user edit** | `/admin/users/{id}/edit` | secretary / super_admin |

Parts 1 & 2 are the WhatsApp-OTP password feature. Parts 3 & 4 are the related
credential-editing adjustments requested alongside it.

## 1. Shared foundation — OTP purposes

The flows reuse `WhatsAppOtpService` (see [[whatsapp-integration-guide]] §1) **unchanged**.
The only new thing is two `purpose` strings (the service is purpose-keyed; `/apply` uses
`apply_phone` and is untouched):

- `password_reset`  — public forgot-password flow (Part 1)
- `password_change` — logged-in change-password flow (Part 2)

`request($phone, $purpose)` / `verify($phone, $code, $purpose)` / `hasVerified($phone, $purpose)`
all take the purpose. OTP tunables (length 6, expiry 5 min, resend cooldown 60 s, max 3
sends / 15 min, max 5 verify attempts) live as consts at the top of
`app/Services/WhatsAppOtpService.php` and apply to every purpose at once.

**The phone is always resolved server-side from the looked-up account.** The client never
sends the phone. This is the core security invariant — verifying a code proves possession
of the registered number, so an attacker who only knows a national ID cannot reset.

## 2. Part 1 — Public forgot-password reset

**Pattern:** controller + Blade + vanilla-JS `fetch()`, mirroring the `/apply` OTP widget
(`RegistrationController::sendOtp/verifyOtp`). Chosen to match the existing public-page
convention — there are no public Livewire pages in this project.

**Files (planned):**
- `app/Http/Controllers/PasswordResetController.php` — `show`, `sendOtp`, `reset`.
- `resources/views/auth/forgot-password.blade.php` — standalone RTL page (same layout
  family as `resources/views/registration/create.blade.php`).
- Routes in `routes/web.php`:
  - `GET  /forgot-password`           → `password.forgot`
  - `POST /forgot-password/send-otp`  → `password.otp.send`   (`throttle:6,1`)
  - `POST /forgot-password/reset`     → `password.reset`       (`throttle:10,1`)

**Flow:**
1. **Identify** — user types national ID → `sendOtp`. `User::where('national_id', $id)->first()`:
   - not found → `لا يوجد حساب بهذا الرقم الوطني.`
   - found, **no phone** → blocked: `لا يوجد رقم هاتف مسجل لهذا الحساب. يرجى التواصل مع إدارة الدراسات العليا.`
   - found, has phone → `WhatsAppOtpService::request($user->phone, 'password_reset')`; JSON
     returns the **masked** phone (e.g. `09•••••1234`).
2. **Reset** — user enters code + new password + confirm → `reset`. Re-look-up by national
   ID, `verify($user->phone, $code, 'password_reset')`, validate `min:8` + `confirmed`,
   then `$user->forceFill(['password' => Hash::make($new)])->save()`. Redirect to
   `/portal/login` if `role === 'student'`, else `/admin/login`.

**Login-page link:** `PanelsRenderHook::AUTH_LOGIN_FORM_AFTER` registered in **both**
`AdminPanelProvider` and `StudentPanelProvider` injects a "نسيت كلمة المرور؟" link →
`route('password.forgot')`.

**Where to edit:**
- OTP message wording → `WhatsAppOtpService::request()` (shared with all purposes).
- Page wording / fields → the Blade view.
- Lookup identifier (national ID → email, etc.) → `sendOtp` + `reset` lookup + the Blade
  input + the validation rules. (If you change it, also revisit the "no phone" copy.)
- Password rules → `reset` validation (`min:8` + `confirmed`, matching `RegistrationController`).
- Masking format → the small mask helper in the controller.

## 3. Part 2 — Logged-in change-password

**Pattern:** a custom **Filament Page** per panel, sharing logic via a trait so the two
classes stay thin. Filament discovers pages per-panel, so the page must physically exist in
each panel's `Pages/` dir.

**Files (planned):**
- `app/Filament/Concerns/ChangesPasswordWithOtp.php` — the shared trait: form schema,
  `sendOtp()` action, `save()` action. **This is the one place to edit the behaviour.**
- `app/Filament/Pages/ChangePassword.php` — admin panel, `use ChangesPasswordWithOtp;`
- `app/Filament/Student/Pages/ChangePassword.php` — student panel, `use ChangesPasswordWithOtp;`
- User-menu item "تغيير كلمة المرور" added in **both** `AdminPanelProvider` and
  `StudentPanelProvider` (`->userMenuItems([...])`) pointing at the page.

**Flow:** form = current password + new password + confirm. "إرسال رمز التحقق" →
`WhatsAppOtpService::request(auth()->user()->phone, 'password_change')`; a code field
appears. Submit (behind a **confirmation modal** — project rule: every action button
confirms) checks, in order: `Hash::check(current, user->password)` → `verify(phone, code,
'password_change')` → `min:8` + `confirmed` → update. No phone on the account → the send
button is disabled with a clear message.

**Where to edit:** everything lives in the trait `ChangesPasswordWithOtp` — form fields,
the OTP send action, validation, the success/confirmation copy. The two page classes only
set the panel-specific shell (title, nav, icon). To add a third panel later, make one more
thin page that `use`s the trait.

## 4. Part 3 — Edit student phone + email on the edit page

Student contact fields (`email`, `phone`) live on the **`User`** row, not `Student`. The
`StudentResource` account section (`user_email` / `user_phone` / `user_password`) was
`->visibleOn('create')` only, so the edit page exposed none of them.

**Change:** add a section to `StudentResource::form()` **visible on edit** with just
`user_email` + `user_phone` (no password — that's what Parts 1–2 are for). Reuse the
create-side unique-email-ignoring-self rule.

Wiring lives in `app/Filament/Resources/Students/Pages/EditStudent.php`:
- `mutateFormDataBeforeFill()` — seed `$data['user_email'] / $data['user_phone']` from
  `$record->user`.
- `handleRecordUpdate()` — pull those two keys out of `$data`, write them to
  `$record->user`, `unset()` before `parent::handleRecordUpdate()` (they are **not**
  `Student` columns).

**Where to edit:** the form section in `StudentResource::form()`; the read/write wiring in
`EditStudent`. To add more editable User fields later, extend both spots.

## 5. Part 4 — Remove password field from user edit

`UserForm` (`app/Filament/Resources/Users/Schemas/UserForm.php`) shares one schema between
create and edit. The `password` `TextInput` becomes `->visibleOn('create')` (still required
on create). On the edit page it disappears entirely → password is untouched on save. Admins
no longer set other users' passwords by hand; users self-serve via Part 1 (forgot-password)
or Part 2 (change-password).

**Where to edit:** the `password` field in `UserForm::configure()`.

## 6. Testing (planned)

Pest feature tests, `Http::fake()` for Qonvo (the pattern in
`tests/Feature/WhatsAppOtpVerificationTest.php`); set config via
`config()->set('services.qonvo.api_token'|'sender_phone', ...)`.

- **Public reset:** success path; wrong national ID; account with no phone (blocked); bad
  OTP code.
- **Change password:** success; wrong current password rejected; bad OTP rejected; no-phone
  blocked. Filament page tested with `Livewire::test(ChangePassword::class)`.
- **Student edit:** updating email/phone persists to the related `User`.
- **User edit:** edit page has no password field; create still requires + sets one.

## 7. Gotchas / invariants

- **Never trust a client-supplied phone** for reset — always resolve from the account.
  Sending an OTP to a number the requester typed would let anyone hijack any account.
- **`users.phone` is nullable** → any flow must handle "no phone on file" (Parts 1 & 2 both
  block with a clear message; this was a deliberate decision over an email fallback).
- **All four parts touch shared code paths** but keep `/apply`'s `apply_phone` OTP flow
  untouched — distinct purpose strings keep the rate-limit windows independent.
- **Synchronous sends** — like all WhatsApp here, OTP sends are in-request (no queue). A
  stalled Qonvo is bounded by `WhatsAppService`'s connect/timeout (5 s / 10 s).
- **`MAIL_MAILER=log` today** — no email fallback exists by design, so there is no mail
  dependency in any of these flows.
