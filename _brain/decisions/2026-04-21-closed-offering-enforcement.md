---
date: 2026-04-21
tags: [decision, enrollment, observer, domain-invariant, course-offering]
related: [[../daily-notes/2026-04-20-task-session-multi-fix]], [[../project-context/gotchas]], [[../project-context/architecture]]
---

# Decision: closed-offering enforcement — action guard + observer cascade

> Source task: T18 of the 2026-04-20/21 session (user-driven follow-up after T10).

## Context

`CourseOffering.is_open` is a boolean flag that previously had no enforcement beyond the student-side add/drop page filter. An admin could:

- **Enroll students into a closed offering** via `/admin/course-offerings/{id}/enroll` — no guard.
- **Close an offering with active enrollments** via the toggle action — no cascade; students stayed "enrolled" in a course that nobody could register for.

Both are domain invariants, not UX nits: GPA calculation, course tree rendering, and grade entry all presume that active enrollments correspond to actively-running course offerings.

## Decision

Enforce `is_open` at **two independent chokepoints**:

1. **Write-path guard** inside `EnrollStudentInOffering::__invoke`:
   - Throws `CourseOfferingClosedException` when `$offering->is_open === false`.
   - First guard in the action (cheapest — property on already-loaded offering; most common reject case).
   - Sits inside the `!$isAlreadyActive` idempotency branch so re-running enroll on a currently-active enrollment doesn't false-fail if the offering was just closed (the observer takes care of that state).
   - Caught at all 4 UI call sites with Arabic notifications; preventively hidden at 2 sites via `->visible(fn () => $this->record->is_open)`.

2. **Transition cascade** via `CourseOfferingObserver::updated`:
   - Fires only when `wasChanged('is_open')` and the original value was `true` and current is `false`. Two-way check avoids false-positives on create events and no-op saves.
   - Loops `activeEnrollments()` and calls `DropStudentFromOffering(student, offering, Withdrawn)` for each.
   - Triggers `GradeCalculationService::recalculateStudentGPA(student)` per affected student (once, deduped by `$affectedStudents[id] = student`).
   - Uses `EnrollmentStatus::Withdrawn` (not `Dropped`) — matches the "forced by system event" convention from T13 (suspension).

## Alternatives considered

**A. Only the write-path guard, no cascade.** Rejected. Closing an offering would leave stale enrollments whose students would still see them on their portal, but they'd fail to drop/grade/re-enroll anywhere downstream. The cascade is the domain-correct behavior.

**B. Only the observer cascade, no action guard.** Rejected. The cascade handles *existing* enrollments; the action guard prevents *new* ones from being created into an already-closed offering. Different concerns.

**C. Use `Dropped` not `Withdrawn`.** Rejected. `Dropped` semantically implies student-initiated drop; `Withdrawn` implies system/policy event (already used by suspension cascade + student-status cascade). Keeps the audit narrative clean.

**D. Re-activate withdrawn enrollments when the offering re-opens.** Rejected. Reopening is an admin signal that the course is available again, not that previously-withdrawn students should be auto-reinstated. Admin must re-enroll them explicitly (which works — `EnrollStudentInOffering::updateOrCreate` flips the existing Withdrawn row back to Enrolled, all guards re-apply).

## Consequences

### Positive
- Parity with the existing "Action + Observer" domain-invariant pattern (T13 suspension used the same split).
- Idempotency-aware: safely re-saving the offering with the same `is_open` value is a no-op.
- Preventive UX: closed offerings don't even render the enroll buttons.
- GPA reflects withdrawn enrollments immediately — no stale data until the next grade approval.
- 5 new specs in [tests/Feature/ClosedOfferingEnforcementTest.php](../../tests/Feature/ClosedOfferingEnforcementTest.php) lock in the behavior including idempotency edge cases.

### Negative
- Closing an offering is now more expensive (O(enrolled) drops + O(affected_students) GPA recalcs). Acceptable — offering closure is rare and admin-initiated.
- The observer runs inside the model's update transaction; a failure during cascade rolls back the `is_open` change too. Appropriate — partial state (offering closed but students still enrolled) is worse than the closure failing entirely.
- One more exception type for callers to handle. The 4 call sites already had 3 catches; adding a 4th is mechanical.

## Implementation

- New exception: [app/Exceptions/CourseOfferingClosedException.php](../../app/Exceptions/CourseOfferingClosedException.php)
- New observer: [app/Observers/CourseOfferingObserver.php](../../app/Observers/CourseOfferingObserver.php)
- Observer registered in [AppServiceProvider::boot](../../app/Providers/AppServiceProvider.php)
- Guard in [EnrollStudentInOffering.php](../../app/Actions/Enrollment/EnrollStudentInOffering.php) (first check)
- Caught in [EnrollStudents.php](../../app/Filament/Resources/CourseOfferings/Pages/EnrollStudents.php), [StudentSurveyView.php](../../app/Filament/Pages/StudentSurveyView.php), [AddDropRequestResource.php](../../app/Filament/Resources/AddDropRequests/AddDropRequestResource.php)
- `->visible()` preventive hide on EnrollStudents's 2 enrollment actions when `!$this->record->is_open`

## Follow-up candidates

- **Audit `is_open` flips.** Not added in this pass. Worth considering adding `CourseOffering::class => ['is_open']` to `AuditObserver::WATCHED_FIELDS` + registering the observer on CourseOffering. Low priority — the StudentEnrollment status change cascade is already audited, which indirectly records the consequence.
- **Notify students when their enrollment is auto-withdrawn.** Currently no notification — the student just sees their enrollment disappear from `/portal/student-courses`. A `notifyEnrollmentWithdrawn` method on NotificationService could dispatch an info message. Requires domain buy-in.
- **Grade-state invariant.** If a student has an Approved grade attached to an enrollment that gets withdrawn, the grade technically still exists but is orphaned from GPA (since GPA filters active only). No code breakage, but semantically odd. Could be a future cleanup — either preserve the grade as "course completed before closure" or purge it.
