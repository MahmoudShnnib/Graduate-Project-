---
date: 2026-04-21
tags: [bug, suspension, domain-invariant, portal]
related: [[../daily-notes/2026-04-21-task-session-p2-multi-fix]], [[../decisions/2026-04-20-survey-rejection-via-timestamp]]
---

# Bug: students could re-submit suspension requests for an already-approved semester

> Source task: T5 of the 2026-04-21 session.

## Symptoms

On `/portal/suspension-request-page`, a student who had already been approved for a semester-scoped suspension could:

1. Open the form again (the only guard was `hasPendingRequest()` — an approved request doesn't count as pending).
2. Select the same semester from the dropdown.
3. Submit a second request — which would be saved as `Pending`, requiring an admin to reject/approve it again.

This created noise in the admin review queue and let students generate duplicate approved suspensions (observer-cascading withdrawal twice, though idempotently).

## Root cause

`SuspensionRequestPage::submit()` ([app/Filament/Student/Pages/SuspensionRequestPage.php](../../app/Filament/Student/Pages/SuspensionRequestPage.php)) had only one pre-guard: `hasPendingRequest()`, which returned true only if any request's `status === Pending`. Approved and Rejected requests were invisible to this guard.

Related: `Student::isSuspendedInSemester()` already answers the correct question (is this student approved-suspended for a given semester), but the suspension page never called it.

## Fix

Two-level guard + UI affordance:

1. **Backend hard-block** in `submit()`:
   ```php
   if ($this->isSelectedSemesterAlreadyApproved()) {
       Notification::make()
           ->title('لا يمكن تقديم طلب انقطاع جديد لهذا الفصل')
           ->body('تمت الموافقة مسبقاً على طلب انقطاع لهذا الفصل الدراسي.')
           ->danger()
           ->send();
       return;
   }
   ```
   This runs after `hasPendingRequest()` and the `validate()` call. An approved suspension for the currently-selected semester surfaces as an Arabic danger notification; no new row is created.

2. **UI affordances** in [suspension-request.blade.php](../../resources/views/filament/student/pages/suspension-request.blade.php):
   - The dropdown `<option>` for any already-approved semester is marked `@disabled(...)` with the label suffixed "`(تمت الموافقة مسبقاً)`".
   - When the student somehow selects an approved semester (direct state manipulation), a red banner renders below the dropdown explaining the lockout.
   - The `<textarea>` and submit `<x-filament::button>` both disable when the selected semester is approved (double-guard against state-races).
   - Dropdown's `wire:model` changed from deferred to `.live` so the banner + disabled state update as soon as the selection changes (same pattern as [courses-survey-results.blade.php:54](../../resources/views/filament/pages/courses-survey-results.blade.php)).

## Why this isn't an Action guard

The suspension submit path is **student-only**, single-caller (`SuspensionRequestPage::submit`). The "Action-guard + Observer-cascade" two-chokepoint pattern ([[../project-context/gotchas#"Action guard + Observer cascade" — two chokepoint pattern for state-gated invariants]]) is for invariants with multiple mutation paths — add/drop, enroll, etc. There's no SuspensionRequestResource action that creates new submissions; only the portal page does. So a page-level guard is sufficient.

If later work adds admin-side ability to file a suspension request on behalf of a student, promote this into an Action with `SuspensionAlreadyApprovedException` (symmetric to the existing `StudentSuspendedInSemesterException`).

## Regression tests

[tests/Feature/SuspensionRequestResubmitGuardTest.php](../../tests/Feature/SuspensionRequestResubmitGuardTest.php) — 4 specs:

1. Blocks a second submission when a prior Approved one exists for the same semester (asserts only 1 row exists).
2. Allows submission for a different semester when another is approved — the per-semester scope is respected.
3. Allows re-submission after prior **Rejected** — rejection doesn't block new attempts (matches Q5.2).
4. `isSelectedSemesterAlreadyApproved()` helper returns true when selected = approved semester.

## Consequences

- No schema change — the guard uses existing `status` + `semester_id` columns on `suspension_requests`.
- No migration, no observer, no typed exception.
- The Student model's existing `isSuspendedInSemester(Semester)` helper remains the canonical answer for "should this block survey/add-drop/enrollment"; the new `SuspensionRequestPage::isSelectedSemesterAlreadyApproved()` is a UI-scoped helper that answers the narrower "would a new request for the currently-selected semester violate uniqueness?"
