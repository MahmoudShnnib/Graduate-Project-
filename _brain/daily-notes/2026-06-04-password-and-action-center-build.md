---
date: 2026-06-04
tags: [daily, password, otp, whatsapp, auth, filament, dashboard, widget, implementation]
related:
  - "[[../project-context/password-management-guide]]"
  - "[[../project-context/admin-action-center-guide]]"
  - "[[2026-06-04-password-management-design]]"
  - "[[2026-06-04-admin-action-center-design]]"
---

# 2026-06-04 — Built: password management + admin Action Center

Implemented all designed work in one session. **Full suite 211 green (was 188), Pint clean,
left uncommitted** per [[no-auto-commit]].

## Password management (4 parts)
- **Part 1 — public reset:** `PasswordResetController` (show/sendOtp/reset) +
  `resources/views/auth/forgot-password.blade.php` + 3 routes (`password.forgot`,
  `password.otp.send` throttle 6,1, `password.reset` throttle 10,1). Identify by national ID
  → OTP (`password_reset`) to the phone on file (masked) → set new password → redirect to
  the role's login. "نسيت كلمة المرور؟" link injected into both login pages via
  `PanelsRenderHook::AUTH_LOGIN_FORM_AFTER`.
- **Part 2 — logged-in change:** trait `App\Filament\Concerns\ChangesPasswordWithOtp` +
  thin `ChangePassword` page in each panel (shared view `filament.pages.change-password`) +
  user-menu item in both providers. Current password + OTP (`password_change`), submit
  `wire:confirm` + disabled until OTP sent.
- **Part 3 — student edit:** email + phone now editable on `/admin/students/{id}/edit`
  (account section no longer create-only; name/password stay create-only). `EditStudent`
  reads/writes them on the related `User`.
- **Part 4 — user edit:** password field is `visibleOn('create')` — gone from
  `/admin/users/{id}/edit`, still required on create.

New OTP purposes reuse `WhatsAppOtpService` unchanged; `/apply`'s `apply_phone` untouched.
Password writes rely on the User `'hashed'` cast (no manual `Hash::make`).

## Admin Action Center
- `ActionCenterWidget` (sort -11, top of dashboard), super_admin + secretary only. 8 clickable
  cards mirroring each resource's nav-badge "pending" rule; zero shown greyed.
- **Dedup applied:** removed pending-applications + pending-grades stats from
  `StatsOverviewWidget` (now pure info).

## Tests added
`PasswordResetFlowTest` (8), `ChangePasswordTest` (6), `AdminCredentialEditTest` (5),
`ActionCenterWidgetTest` (4). Gotcha hit: a global `configureQonvo()` already exists in
`WhatsAppOtpVerificationTest` — inlined config instead of redeclaring.

## Loose ends / next steps
- **Uncommitted** — user reviews, then says when to commit.
- Worth a manual smoke in the browser: login-page "forgot password" link, the reset page JS
  (OTP send → reveal form), and the user-menu "تغيير كلمة المرور" in both panels.
- WhatsApp sends only work once the Qonvo sender is `authenticated` (see
  [[../project-context/whatsapp-integration-guide]]); otherwise `isConfigured()` is false and
  reset/​change report the "service not enabled" message.
