---
date: 2026-06-10
tags: [decision, auth, password-reset, security, filament]
related:
  - "[[../project-context/password-management-guide]]"
  - "[[2026-06-11-apply-admin-auth-polish]]"
---

# Forgot-password identifies by panel: students by national ID, staff by email

## Context

The public WhatsApp-OTP forgot-password flow originally identified **every** account
by **national ID**, then sent the OTP to the phone on file. Problem: office admins
(`super_admin`, `secretary`/`manager_assistant`) have **no national ID stored** (0% in
the DB), so they could never recover their password — they were prompted for a field
that doesn't exist for them. Faculty and students do have national IDs; everyone has a
phone (100%).

## Decision

Split the flow into **two entry points**, the identifier chosen per panel:

- **`/portal/forgot-password`** — students identify by **national ID**, lookup scoped to
  `role = student`.
- **`/admin/forgot-password`** — staff identify by **email** (what they log in with),
  lookup scoped to `role != student`.

The OTP is **always** sent to the phone already on file for the matched account — never a
typed number — so possession of the registered phone remains the gate.

## Alternatives considered

- **Phone as the admin identifier.** Simpler, but collapses the flow to a single factor
  (whoever holds the phone can reset). Email keeps two factors: *know the email* AND
  *possess the phone* — matching the student flow's "national ID + phone" strength.
- **One combined "national ID or email" field.** Rejected by the user — wanted each panel
  to ask for exactly one thing.
- **Query flag (`?as=admin`) on a single route.** Works, but two real paths read clearer
  and aren't user-editable in a way that looks odd. Chose the two-path version.

## Consequences

- The `?as` / path only chooses **which form** is shown; the real boundary is **server-side
  role scoping** in `PasswordResetController::lookup()` — the email path can never resolve a
  student, the national-ID path can never resolve a staff member, regardless of crafted input.
- Faculty (have national ID) recover via **email** because they log in through `/admin`.
- Shared POST endpoints (`password.otp.send`, `password.reset`) stay single and
  field-driven (presence of `email` vs `national_id` selects the path).
- `/admin/forgot-password` and `/portal/forgot-password` are plain web routes and are **not**
  shadowed by the Filament panels (verified by route:list + a render test).
- Route name `password.forgot` was replaced by `password.forgot.student` / `password.forgot.admin`.
