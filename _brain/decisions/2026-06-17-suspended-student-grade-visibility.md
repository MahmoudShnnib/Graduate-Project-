---
date: 2026-06-17
tags: [decision, suspension, grades, transcript, course-tree, student-portal, domain-rule]
related:
  - "[[2026-06-15-flush-current-semester-on-suspend]]"
  - "[[../bugs-and-fixes/2026-06-17-suspended-student-grade-visibility]]"
  - "[[../daily-notes/2026-06-17-eight-fix-batch]]"
---

# Hide flushed current-term courses from a suspended student's transcript + tree

## Context

[[2026-06-15-flush-current-semester-on-suspend]] made `OnHold`/`Inactive` withdraw
only the current-semester enrollments and drop them from the GPA. But the **display**
side wasn't aligned: `GradesPage` (كشف الدرجات) and `CourseTreePage` (المقررات العامة)
still rendered those flushed courses' published grades — the GPA excluded a course the
transcript still showed. Reported by the user for a قيده موقوف student.

The naive fix ("hide every non-`Enrolled` enrollment") breaks **graduation**: a graduated
student has *all* enrollments withdrawn but the GPA is **frozen WITH them**, so their
transcript must stay intact. Status alone (`Withdrawn`) can't distinguish the two cases.

## Decision

Hide a current-semester enrollment from the transcript/tree **only when the student's
status is `OnHold` or `Inactive`** (the two states whose GPA recalcs *without* the flushed
term). Concretely: exclude an enrollment when
`studentSuspended && isCurrentSemester && status !== Enrolled`.

- Prior-semester courses always stay (a graduation cascade withdraws them, but they are
  not "current", so they remain — transcript + GPA stay consistent).
- `Graduated`/`Transferred`/`Dismissed`/`Active` are untouched → no behavior change.

This is the display-layer mirror of the GPA rule: **the set of courses a student sees ==
the set that counts in their GPA**, for every status.

## Alternatives considered

1. **Blanket `status = Enrolled` filter** — rejected: wipes a graduated student's whole
   transcript (all their enrollments are `Withdrawn`).
2. **Filter purely on `semester != current`** — rejected: same graduation problem, and it
   would also hide a legitimately-passed current-term course of an *active* student.
3. **Per-enrollment "flushed" flag on the pivot** — rejected: heavier schema change; the
   status+student-status pair already encodes it unambiguously.

## Implementation

- `CourseTreePage::getEnrollmentMap()` — `$isFlushedCurrent = $isSuspended &&
  $isCurrentSemester && status !== Enrolled` gates `$isPassed`.
- `GradesPage` — `excludeSuspendedCurrent($query, $student)` applies
  `status = Enrolled OR semester != active`, but **only when** the student is
  `OnHold`/`Inactive`; applied to both `getGrades()` and `getSemesters()`. Active-semester
  id is memoized.
- `$isSuspended = in_array($student->status, [OnHold, Inactive], true)`.

## Revision (2026-06-18) — gate on `freezesGpa()`, not `OnHold/Inactive`

The `OnHold/Inactive`-only gate above was **wrong**: when an admin uses تغيير حالة الطالب to
reactivate a suspended student back to `Active` (مستمر), the flushed course stays `Withdrawn`
and out of the GPA — but the student is now `Active`, so the `OnHold/Inactive` gate stopped
hiding it and its grade reappeared in كشف الدرجات + المقررات العامة.

Corrected rule: hide based on whether the GPA is **live vs frozen**, via a new
`StudentStatus::freezesGpa()`:
- **Live** (`Active`/`OnHold`/`Inactive`): GPA = `activeEnrollments()` (Enrolled only) →
  show **Enrolled only** (hides every withdrawn/flushed course, reactivated case included).
- **Frozen** (`Graduated`/`Transferred`/`Dismissed`): GPA snapshot → show the full record.

Implementation: `GradesPage::applyRecordVisibility()` adds `where('status', Enrolled)` unless
`freezesGpa()`; `CourseTreePage` uses `$countsForStudent = $showFullRecord || status === Enrolled`.
New regression test: "keeps flushed current courses hidden after reactivation to مستمر".
Browser-verified with student `flefel.akram@example.org` (Active, a Withdrawn published-C+
course) — the course shows "لم يُدرس بعد" with no grade and the transcript is empty.

## Consequences

- A pre-existing test (`StudentStatusGateTest` "keeps approved grades visible after
  cascade") asserted the **opposite** for `OnHold` — that was the prior intended behavior,
  now deliberately reversed per user request. Realigned it to use `Graduated` (which
  legitimately keeps grades visible). 3 new tests added in
  `SuspensionFlushesCurrentSemesterTest`.
- Browser-verified: suspended `gpa.test@dev.ly` — flushed course hidden in كشف الدرجات,
  shows as "لم يُدرس بعد" (no grade) in the tree; prior course kept as مجتاز/B+.
