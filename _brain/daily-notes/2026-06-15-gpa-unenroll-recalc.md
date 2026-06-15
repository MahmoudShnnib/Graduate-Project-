---
date: 2026-06-15
tags: [gpa, enrollment, add-drop, testing, browser-test]
related: [[../bugs-and-fixes/2026-06-15-unenroll-does-not-recalc-gpa]]
---

# Session: GPA not recalculated when a student is removed from a course

## What was done

- Investigated how GPA works end-to-end (see bug note for the mechanism).
- Found + fixed a bug: removing a student from a course didn't recalc the stored
  GPA. Fixed at 3 admin surfaces (enroll-page single + bulk unenroll, add/drop
  approve-drop, survey-view unenroll). See
  [[../bugs-and-fixes/2026-06-15-unenroll-does-not-recalc-gpa]].
- Wrote `tests/Feature/UnenrollRecalculatesGpaTest.php` (4 tests, green) driving
  the real Filament actions via Livewire.
- Added `DevDataSeeder::gpaUnenrollScenario()` — additive, idempotent fixture
  (student #demo, 3 open graded courses, GPA 3.00, 1 pending drop request).
- Verified **live in Chrome**: 3.00 → (enroll unenroll) 2.50 → (add/drop approve)
  1.00. Scenario reset to clean afterwards.

## Decisions

- **Option A** (recalc at call sites, not inside `DropStudentFromOffering`) to
  avoid double-recalc with `CourseOfferingObserver`.
- **Keep credit-hour weighting** in the GPA for now (user call) — no change to
  `GradeCalculationService`.

## Part 2 — suspension "flushes" the current semester from the GPA

Follow-on from the same conversation. Decision +  build:

- Decision: [[../decisions/2026-06-15-flush-current-semester-on-suspend]]. On
  `OnHold`/`Inactive` the student's **current active-semester** courses are
  withdrawn and the GPA is **force-recalculated** (current courses leave it,
  prior completed work stays). Graduated/Transferred/Dismissed unchanged
  (withdraw-all + frozen GPA).
- Code: `GradeCalculationService::recalculateStudentGPA($student, $force=false)`;
  `StudentObserver` branch + `flushCurrentSemester()`; updated the OnHold/Inactive
  consequence text in `EditStudent::getStatusChangeWarning`.
- Hit + fixed an infinite-recursion gotcha (save inside own `updated` event):
  [[../bugs-and-fixes/2026-06-15-recursion-saving-model-in-own-updated-event]].
- Tests: new `SuspensionFlushesCurrentSemesterTest` (6) + updated
  `StudentStatusGateTest` (split frozen-vs-flush). All green.
- Verified live in Chrome via `DevDataSeeder::suspensionFlushScenario()`
  (student #16): suspend → GPA 3.33 → 4.00, two current courses منسحب, prior A
  kept. Demo data reset to clean afterwards.

## Loose ends / next steps

- ~~`DevDataSeeder` opens the active semester's add/drop window to now ±7d~~ —
  reverted on user request: window restored to the original Sept 2025 dates and
  the seeder line that opened it was removed (so re-seeding no longer reopens it).
- ~~Pre-existing `StudentPortalTest` failures (302 vs 403)~~ — fixed along with 4
  other pre-existing red tests; see
  [[../bugs-and-fixes/2026-06-15-pre-existing-test-failures-cleanup]]. Full suite
  now **318 passing, 0 failing** (3 consecutive clean runs). Also added a retake
  GPA unit test (was an untested core path).
- Nothing committed (per no-auto-commit rule). Changed app files:
  EnrollStudents, AddDropRequestResource, StudentSurveyView, StudentObserver,
  GradeCalculationService, EditStudent (+ DevDataSeeder, 3 test files, 3 _brain
  notes).
