---
date: 2026-06-11
tags: [daily, apply, auth, otp, notifications, filament, track]
related:
  - "[[2026-06-10-whatsapp-otp-expiry]]"
  - "[[../bugs-and-fixes/2026-06-10-whatsapp-otp-no-push-notification]]"
  - "[[../decisions/2026-06-10-forgot-password-identifier-by-panel]]"
  - "[[2026-06-09-apply-form-enhancements]]"
---

# 2026-06-10/11 — apply polish, OTP timers, exam notify, and auth recovery

A long multi-task session. Each task finished + tested before moving on.

## What was done

1. **WhatsApp OTP timing** ([[2026-06-10-whatsapp-otp-expiry]]) — expiry 60s → **120s**,
   resend cooldown 60s → **120s** (`WhatsAppOtpService`), and the blade resend countdowns
   (apply + forgot-password) hardcoded `60` → `120` to match the server.

2. **WhatsApp "no notification" issue** ([[../bugs-and-fixes/2026-06-10-whatsapp-otp-no-push-notification]])
   — diagnosed as a degraded Qonvo *session* on the new sender number; reconnecting in the
   Qonvo dashboard fixed it. Not a code issue — our app can't control WhatsApp push.

3. **"Copy code" OTP button request** — explained it requires a WhatsApp **authentication
   template** (official Cloud API), which Qonvo (unofficial linked-session gateway) likely
   can't send. No code change; pending confirmation from Qonvo.

4. **`/apply/track` redesign** (`track.blade.php`, `components/layout/app.blade.php`):
   - Hero made skippable per-page via a new `hideHero` prop on the layout; track page opens
     straight at the lookup form.
   - Added a read-only **3-step status tracker** (تقديم → مراجعة → القرار النهائي) above the
     existing detailed timeline. Steps: submit always done; review done once status ≠ pending;
     final = green ✓ accepted / red ✕ rejected / grey waiting.
   - After a successful lookup the **search form is hidden** (visitor just needs their status);
     the "تقديم طلب جديد" link still allows another lookup. Test: `TrackApplicationStatusTest`.

5. **Exam date picker** (`ApplicationResource`) — both `exam_date` DateTimePickers switched
   from the raw native input to Filament's non-native picker (`native(false)`, `seconds(false)`,
   `minutesStep(5)`, `displayFormat('d/m/Y - H:i')`, calendar icon).

6. **Notify initially-approved applicants** — new `ApplicationInitiallyApprovedNotification`
   (email + WhatsApp) with the exam date + tracking link, wired into both the bulk and single
   "قبول مبدئي" actions via `NotificationService::notifyApplicationInitiallyApproved()`.
   Test: `ApplicationInitiallyApprovedNotificationTest`. **Email only sends once prod `MAIL_*`
   is wired** (still an open loose end).

7. **Academic certificate label + mandatory** — label "شهادة البكالوريوس" →
   **"الشهادة العلمية (بكالوريوس/دبلوم عالي)"** everywhere (public form, admin form, infolist;
   prose softened to "الشهادة العلمية"). Field made **required** end-to-end: asterisk +
   `required` attr + validation `nullable`→`required` with Arabic messages. Remedial statement
   stays optional. Updated submission test payloads + added a "certificate required" test.

8. **Change-password live OTP** (`ChangesPasswordWithOtp` + `filament/pages/change-password`)
   — OTP now verifies **in real time** as the 6th digit is entered (`updatedOtpCode` hook +
   `wire:model.live`), shows a green check, locks the field, and enables submit only when
   verified. Submit trusts DB-backed `hasVerified()` (fallback `verify()`), avoiding the
   double-verify problem. Shared by both panels.

9. **Two-path forgot-password** ([[../decisions/2026-06-10-forgot-password-identifier-by-panel]])
   — `/portal/forgot-password` (students, national ID, `role=student`) and
   `/admin/forgot-password` (staff, email, `role!=student`). Server-side role scoping is the
   real boundary; OTP always to the phone on file. Rewrote `PasswordResetFlowTest`.

## Side fix

- Reset the local primary super-admin password (`admin@college.edu` → temp `Admin@12345`)
  after the user forgot it. Local DB only; prod is separate.

## Tests

- All touched suites pass: Track (5), InitiallyApproved (2), Nationality+OTP (24),
  ChangePassword (7), PasswordResetFlow (13), broader auth/password (28). Pint clean.

## Loose ends / next steps

- **Commit + Dokploy redeploy** of `main` for all the above.
- Prod **`MAIL_*`** still unset → confirmation/exam emails don't send; WhatsApp does.
- Prod **`LOG_LEVEL`** still `debug` (lower to `warning`).
- Qonvo: confirm whether authentication templates (copy-code button) are supported.
- Pre-existing test failures unchanged & unrelated: `ExampleTest` (stale `/admin` redirect),
  `ExamListPrint` PDF 500, `ProductionHardening` student 403→302.
- Uncommitted, NOT mine this session: a one-line wording tweak in
  `thesis-registration.blade.php` (left for the user to commit separately).
