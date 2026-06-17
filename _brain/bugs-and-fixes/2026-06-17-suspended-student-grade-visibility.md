---
date: 2026-06-17
tags: [bug, suspension, grades, gpa, student-portal]
related: [[2026-06-17-eight-fix-batch]]
---

# Suspended student still saw dropped current-semester grades

## Symptom
A student whose account was suspended (قيده موقوف / `OnHold`) still saw the grades of
the current-semester courses that the suspension had dropped — in both the
المقررات العامة section of `/portal/course-tree-page` and the كشف الدرجات of
`/portal/grades-page`.

## Root cause
On suspension, `StudentObserver` flushes the current term: enrollments → `Withdrawn`
AND excluded from the GPA. But:
- `GradesPage::getGrades()` had **no enrollment-status filter** — any enrollment with a
  published grade showed, including the flushed (Withdrawn) one whose coursework grade
  had been published before the suspension.
- `CourseTreePage::getEnrollmentMap()` computed `$isPassed` purely from the published
  grade, ignoring enrollment status, so the flushed course showed as "passed".

## Why a naive fix breaks graduation
The tree deliberately walks ALL enrollments (not just active) so a **graduated** student
keeps their transcript — graduation withdraws every enrollment but freezes the GPA *with*
them. So "hide all non-Enrolled" would wrongly wipe a graduate's current-term course
(GPA counts it, transcript wouldn't). The distinguishing factor is **student status**:
OnHold/Inactive recalc the GPA *without* the flushed term; Graduated freezes *with* it.

## Fix
Hide a current-semester non-`Enrolled` enrollment **only when** the student's status is
`OnHold` or `Inactive`:
- `CourseTreePage`: `$isFlushedCurrent = $isSuspended && $isCurrentSemester && status !== Enrolled;`
  gate added to `$isPassed`.
- `GradesPage`: `excludeSuspendedCurrent()` applies `status = Enrolled OR semester != active`
  to both `getGrades()` and `getSemesters()`, but only when the student is OnHold/Inactive.
  Memoized active-semester id.

## Regression guard
- 3 new tests in `SuspensionFlushesCurrentSemesterTest`: transcript hides flushed course,
  tree excludes flushed course, graduated keeps full transcript.
- Realigned the pre-existing `StudentStatusGateTest` "keeps approved grades visible after
  cascade" test — it asserted the OnHold course stayed visible (the old intended
  behaviour, now reversed by user request). Switched it to `Graduated`, which legitimately
  keeps grades visible.
