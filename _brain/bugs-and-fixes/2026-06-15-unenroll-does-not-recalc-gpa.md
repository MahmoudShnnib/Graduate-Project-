---
date: 2026-06-15
tags: [bug, gpa, enrollment, add-drop, observer]
related: [[2026-04-18-double-gpa-recalc-and-audit-churn]]
---

# Removing a student from a course left a stale GPA

#gpa #enrollment #bug

## Symptom

On `/admin/course-offerings/{id}/enroll`, deleting (`حذف`) a student from a
course did **not** change their stored `students.gpa`. The dropped course was
correctly excluded from future GPA math, but the number on the student view
page stayed at its old value until some *other* trigger recalculated it.

## Root cause

GPA is only ever (re)computed by `GradeCalculationService::recalculateStudentGPA()`.
It reads `activeEnrollments()` (status = `enrolled`), so a dropped course is
excluded — but **dropping doesn't itself call recalc**. `DropStudentFromOffering`
only flips the enrollment status and writes an audit log. Three admin surfaces
called that action without a follow-up recalc:

1. `EnrollStudents` — single `unenroll` + bulk `unenroll_selected`
2. `AddDropRequestResource::table` `approve` action (drop branch) — the surface
   `StudentAddDropView` reuses
3. `StudentSurveyView::unenrollStudent`

(`CourseOfferingObserver` already recalced on offering-close; `StudentObserver`
intentionally does not — GPA must freeze for inactive/graduated students.)

## Fix

Call `recalculateStudentGPA()` right after the drop at each of the three
surfaces (Option A — localised to the call sites, **not** inside
`DropStudentFromOffering`, to avoid double-recalc with `CourseOfferingObserver`
— see [[2026-04-18-double-gpa-recalc-and-audit-churn]]). Bulk unenroll recalcs
once per affected student.

## Regression guard

`tests/Feature/UnenrollRecalculatesGpaTest.php` — drives the **real Filament
UI actions** via Livewire (`callTableAction`/`call`) and asserts GPA 3.00 → 0.00
for all three surfaces.

Also verified live in Chrome with a seeded scenario (`DevDataSeeder`
`gpaUnenrollScenario`: GPA 3.00 → enroll-page unenroll → 2.50 → add/drop
approve → 1.00).

## Out of scope (noted, not fixed)

- 2 pre-existing `StudentPortalTest` failures (faculty/non-student get 302 not
  403 on `/portal`) — unrelated; confirmed they fail with this change stashed.
- Credit-hour weighting in GPA: user decided to **keep** it for now.
