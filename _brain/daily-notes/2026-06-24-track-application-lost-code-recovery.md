---
date: 2026-06-24
tags: [tracking, applications, security, public-form, recovery, daily]
---

# 2026-06-24 — Track application: "forgot code" recovery (national ID + phone)

## Context

`/apply/track` required **tracking code + national ID**. The tracking code is shown once after
applying — a student who refreshed/forgot it was locked out. User wanted to relax tracking to
"just the code" with a "forgot code" fallback to national-ID-only.

## Decision — rejected single-factor, kept two-factor

`TrackingCodeService` generates **sequential** codes (`APP-2026-0001`, `0002`, …). Tracking by
code alone = trivial enumeration of every applicant's name, status, exam date, and rejection
reason — a real privacy leak now that the live server has real students. National-ID-only is also
a downgrade (IDs aren't secret). So **neither single-factor path is allowed.**

Instead: the lost-code problem is solved with a recovery path using two values the student always
knows and that aren't enumerable — **national ID + phone** (both already required at apply time,
both existing columns). Chose **Option 1**: keep the code+ID default, add a recovery toggle. No
tables/models/data touched (serious-mode constraint).

## Done

- `ApplicationTrackingController::track()` now branches on `mode`:
  `trackByCode()` (tracking_code + national_id, unchanged) and `trackByRecovery()`
  (national_id + phone). Recovery failure flashes `track_mode=recovery` so the view reopens the
  right form.
- `resources/views/registration/track.blade.php` — single Alpine `x-data="{ mode }"` wrapper with
  two forms (`x-show`). Primary has a **"نسيت رمز التتبع؟"** button → recovery; recovery has a
  "لديّ رمز التتبع" button back. Initial mode from `old('mode', session('track_mode','code'))`.
  Dropped native `required` on inputs (the hidden form's required fields would block the visible
  form's submit) — server-side `required` rules are the backstop.
- POST route already had `throttle:5,1` — no change needed.
- `tests/Feature/TrackApplicationStatusTest.php` +4 tests: forgot-code link present, recover by
  ID+phone, reject on phone mismatch, reject ID-only-without-phone (anti-leak). **9 passed.**

## Next steps

- Browser-verify at `/apply/track`: toggle works, recovery returns status, error re-opens
  recovery form. Then commit (no auto-commit).
