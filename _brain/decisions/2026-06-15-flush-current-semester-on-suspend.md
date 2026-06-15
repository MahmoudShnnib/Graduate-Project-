---
date: 2026-06-15
tags: [decision, gpa, suspension, enrollment, observer]
related: [[../bugs-and-fixes/2026-06-15-unenroll-does-not-recalc-gpa]]
---

# Suspension / Inactive "flushes" the current semester from the GPA

## Context

When a student is set to قيده موقوف (`OnHold`) or منقطع (`Inactive`),
`StudentObserver::cascadeWithdraw` withdrew **every** enrollment the student
had (all semesters — `activeCourseOfferings()` has no semester filter) and the
GPA was **frozen** (`recalculateStudentGPA` early-returns for non-active
students). The freeze was load-bearing: without it the GPA would read 0.00 from
zero remaining active enrollments. Net effect: a suspended student's GPA kept
its old value, still counting courses that were now withdrawn — and the user's
intent (current-semester courses should be removed from the GPA) was not met.

## Decision

For `OnHold` and `Inactive` only:

1. Withdraw **only the current active-semester** enrollments. Prior semesters'
   enrollments are left untouched (still `Enrolled`, still counted).
2. **Force a GPA recalculation** right after, bypassing the non-active freeze.
   The withdrawn current-semester courses fall out; previously-completed graded
   courses remain. A first-semester student with no prior work → 0.00 (accepted).
3. Withdrawn courses are **permanently flushed** — reactivation does not restore
   them, and they never count toward the GPA again. Re-studying the course later
   is a fresh, separate enrollment (existing "latest attempt only" retake logic
   in `GradeCalculationService` counts the new one).

Unchanged: `Graduated`, `Transferred`, `Dismissed`, and hard-delete keep the
existing withdraw-all + frozen-GPA behavior, so graduates keep their final GPA.

## Alternatives considered

1. **Recalc on suspend with the existing withdraw-all cascade** — rejected: it
   withdraws the entire history, so every suspended student would read 0.00.
2. **Keep grades counted on withdrawn enrollments** (change the core GPA query)
   — rejected: touches the system-wide `activeEnrollments`-only invariant.
3. **Leave the freeze as-is** — rejected: doesn't meet the requirement that the
   current semester leaves the GPA.

## Implementation

- `GradeCalculationService::recalculateStudentGPA(Student $student, bool $force = false)`
  — `force` skips the non-active guard. Only the suspend/inactive path forces;
  all other callers keep the freeze (protects graduates from stray recalcs).
- `StudentObserver::updated` — branch: `OnHold`/`Inactive` → new
  `flushCurrentSemester()`; other non-active statuses and `deleting` → existing
  `cascadeWithdraw()`.
- `flushCurrentSemester()` withdraws only `Semester::active()` enrollments, then
  force-recalcs.

## Consequences

- Suspended/inactive students' GPA now reflects only completed prior work.
- The "flushed" courses behave exactly like any other withdrawn course in the
  GPA + retake logic — no new special case in the calculator.
- Reactivation (`→ Active`) still does not recalc (returns early); the GPA is
  already correct from the flush, and the withdrawn courses stay out.
