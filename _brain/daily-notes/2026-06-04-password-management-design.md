---
date: 2026-06-04
tags: [daily, password, otp, whatsapp, auth, filament, design, planning]
related:
  - "[[../project-context/password-management-guide]]"
  - "[[../project-context/whatsapp-integration-guide]]"
---

# 2026-06-04 — Password management via WhatsApp OTP (design)

**Design session only — nothing implemented yet.** Brainstormed + locked the design for
letting students *and* admin-side users (super_admin / secretary / faculty) recover and
change their password through a WhatsApp OTP, plus two related credential-editing tweaks.

Full design-of-record + "where to edit" map → [[../project-context/password-management-guide]].

## Scope (4 parts)
1. **Public forgot-password reset** — `/forgot-password`, identify by **national ID**, OTP
   to the phone on file, set new password. Blade + controller + vanilla JS (mirrors the
   `/apply` OTP widget). Linked from both Filament login pages via a render hook.
2. **Logged-in change-password** — Filament Page in both panels (shared trait), current
   password **+** WhatsApp OTP required, behind a confirmation modal. User-menu item.
3. **Student edit** — expose editable `email` + `phone` (on the `User`) on
   `/admin/students/{id}/edit` (was create-only).
4. **User edit** — remove the password field from `/admin/users/{id}/edit` (keep on create).

## Decisions locked (via Q&A)
- Forgot-password identifier = **national ID** (all seeded roles have one). OTP goes to the
  registered phone, **never** a client-supplied number.
- Logged-in change requires **current password + OTP**.
- **No phone on file → block** with a clear Arabic message (no email fallback;
  `MAIL_MAILER=log` today).
- New OTP purposes `password_reset` / `password_change` reuse `WhatsAppOtpService` unchanged;
  `/apply`'s `apply_phone` flow stays untouched.
- User create still keeps its password field; only the **edit** page loses it.

## Next steps
- Approved approach = **A** (Blade public page + Filament in-panel page). Implementation not
  started.
- Resume with: *"build the password-management feature per the guide, Part 1 first"* →
  write tests alongside each part, `pint` + `php artisan test --compact` after each.
- Leave uncommitted for review (per [[no-auto-commit]] preference).
