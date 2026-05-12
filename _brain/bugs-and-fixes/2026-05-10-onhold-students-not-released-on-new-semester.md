---
date: 2026-05-10
tags: [bug, observer, suspension, student-status]
related: [[../daily-notes/2026-05-10-multi-phase-fixes]]
---

# OnHold students kept their `قيده موقوف` status into the new active semester

## Symptoms

After opening a new semester (marking it `is_active=true`), students whose approved suspension was for the *previous* semester were still showing as `قيده موقوف` (`StudentStatus::OnHold`). They should have been auto-released to `Active` since suspension is per-semester.

## Root cause

`SemesterObserver` already had a pass that auto-reactivates `Inactive` students when a semester becomes active (`resetInactiveStudentsToActive()`), but no equivalent pass for `OnHold`. The `OnHold` flag on `students.status` was set when the suspension was approved (in `SuspensionRequestResource`) and was never cleared automatically — only an admin manually editing the student could change it.

## Fix

Added `releaseOnHoldStudentsForSemester(Semester $activeSemester)` to `SemesterObserver`, called alongside `resetInactiveStudentsToActive()` whenever a semester becomes active (`created` with `is_active=true`, or `updated` with `is_active` flipped `false → true`).

The query releases any `OnHold` student whose approved suspensions do NOT include the newly active semester. Students whose approved suspension DOES cover the new active semester stay `OnHold` (their suspension applies to the current term).

Uses `updateQuietly` to match the existing `resetInactiveStudentsToActive` pattern — skips StudentObserver cascades and avoids bulk audit log churn.

## Coverage added

New file `tests/Feature/SemesterObserverTest.php` — 5 cases:
1. Release OnHold when only old-semester suspension exists.
2. Keep OnHold when approved suspension covers the new active semester.
3. Regression: Inactive students still reset to Active.
4. Release path also works when an existing semester is flipped to active (not only on `created`).
5. Pending (not-yet-approved) suspensions don't block release.

All 5 pass. No existing tests regressed.

## Pre-existing issue surfaced (NOT fixed in this phase)

`SuspensionRequestResubmitGuardTest` has 3 failing tests on `main` / HEAD pre-Phase-5:

- `allows new suspension request for a different semester when another semester has approved` — submission returns 0 created rows.
- `allows re-submission after a prior rejection for the same semester` — looks like the page logic was tightened to block this; test wasn't updated.
- `isSelectedSemesterAlreadyApproved returns true for approved semester` — method renamed/removed but test still references it.

These predate Phase 5. Fix them in a separate session — either align the test with the current (tightened) rule per [[../project-context/project-overview]] ("approved suspension anywhere blocks all future submissions") or restore the page method, depending on intended behavior.

## Files touched

- `app/Observers/SemesterObserver.php`
- `tests/Feature/SemesterObserverTest.php` (new)