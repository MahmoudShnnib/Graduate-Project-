---
date: 2026-06-15
tags: [bug, tests, flaky, auth, factory]
related: [[2026-06-15-unenroll-does-not-recalc-gpa]]
---

# Cleaned up 5 pre-existing failing tests (none were app bugs)

#tests #flaky

These were red before the GPA work; surfaced when running the **full** suite.
All were test-quality issues, not application bugs.

## Stale 403 assertions (3 tests)

`StudentPortalTest` (non-student + faculty on `/portal`) and
`ProductionHardeningTest` (student on an admin print route) asserted a bare
`403`. But `bootstrap/app.php`'s exception handler (added 2026-05-16)
deliberately converts a 403 for a user on the wrong panel into a **logout +
302 redirect** to the login of the panel they belong on. Updated the assertions
to the real, intended behavior:
- admin/faculty → `/portal` ⇒ `assertRedirect('/admin/login')`
- student → admin print ⇒ `assertRedirect('/portal/login')`

Verified live in Chrome (admin → `/portal` lands on `/admin/login`). The faculty
print test still asserts a real `403` (faculty *can* access admin) — left as-is.

## Stale home redirect (1 test)

`ExampleTest` asserted `/` → `/admin`. The root route now redirects to
`registration.create` (`/apply`). Updated to match (also covered by
`HomeRedirectTest`). Verified live (`/` → `/apply`).

## Flaky factory phone (1 test) — the subtle one

`TransferCourseEditTest > deletes transfer_courses` passed in isolation but
failed intermittently in the full suite with `data.user_phone => "صيغة رقم
النقال غير صالحة."`. Root cause: `UserFactory` used `fake()->phoneNumber()`,
which can emit extensions/letters (e.g. `+1-…x55`). Filament's `->tel()` on the
student form's `user_phone` adds a phone-format regex that rejects letters. The
specific value depends on Faker's call position, so it failed only at certain
suite positions. Fixed at the source: `UserFactory` now generates a valid
Libyan mobile `'09'.fake()->numerify('########')`. Confirmed via probe (all-digit
phones pass, `x`-extension phones fail) and **3 consecutive green full-suite
runs** (318 passing, 0 failing).

## Lesson

Factory data must satisfy the app's own validation rules. A factory emitting
format-invalid values turns into a Faker-position-dependent Heisenbug that
"passes alone, fails in suite". When a test passes in isolation but fails in the
full run, suspect leaked/global state (config, Faker sequence), not the test's
own logic.
