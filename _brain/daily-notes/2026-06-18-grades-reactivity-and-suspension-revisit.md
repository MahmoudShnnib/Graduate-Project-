---
date: 2026-06-18
tags: [filament, grades, suspension, gpa, reactivity, course-survey]
related:
  - "[[2026-06-17-eight-fix-batch]]"
  - "[[../bugs-and-fixes/2026-06-18-filament-cached-action-state-stale]]"
  - "[[../decisions/2026-06-17-suspended-student-grade-visibility]]"
---

# Grades-page reactivity + suspension visibility revisit

Branch `chore/go-live-high-medium-fixes`. **Uncommitted.** Full suite **393 green**.
All changes browser-verified on the local app.

## Work

1. **Course-survey "unavailable" → "قيد المراجعة".** Submitted survey choices showed
   "المادة غير متاحة هذا الفصل" before offerings opened. `CourseSurveyPage::getEnrollmentStatuses()`
   dropped the `'unavailable'` status; a choice is now `enrolled` / `rejected` / `pending`
   (pending including no-offering-yet). Blade fallback → قيد المراجعة. +3 tests.
   See [[../bugs-and-fixes/2026-06-18-survey-choice-shows-unavailable-not-pending]].

2. **Suspension grade-visibility — corrected to `freezesGpa()`.** My 2026-06-17 fix gated
   hiding on `OnHold/Inactive` only, which missed a student **reactivated to مستمر** via
   تغيير حالة الطالب — the flushed course's grade reappeared. Added
   `StudentStatus::freezesGpa()` (Graduated/Transferred/Dismissed = true). Rule: live-record
   students (Active/OnHold/Inactive) show **Enrolled-only** (GPA = activeEnrollments), frozen
   students show the full snapshot. Updated `GradesPage` + `CourseTreePage`, dropped the old
   semester/OnHold logic. +1 reactivation test. Verified via `flefel.akram@example.org`
   (Active, a Withdrawn published-C+ course) → course shows "لم يُدرس بعد", transcript empty.
   See revised [[../decisions/2026-06-17-suspended-student-grade-visibility]].

3. **Filament cached-action staleness (the "needs refresh" bug).** `ViewCourseGrades`
   نشر الدرجات النهائية button used `->disabled($scalar)` baked at page load, so it stayed
   disabled after approving a grade in the table until reload. Fixed with closures
   (`->disabled(fn () => ...)`, tooltip, modalDescription) + count helper methods.
   Checked other resources: `ViewExceptionalStudent`'s summon `->visible($scalar)` was the same
   class (summon refreshes in place, no redirect) — converted to live closures. `ViewSemester`
   was fine (no gated persistent prop; modalDescription is mount-fresh). Browser-verified the
   publish button now enables live. New durable gotcha + bug note
   [[../bugs-and-fixes/2026-06-18-filament-cached-action-state-stale]].

4. **`wire:navigate` back-button staleness (panel-wide).** Pressing browser Back showed a
   cached snapshot — counts/statuses stale until hard refresh. Since it affects *every*
   Filament list + view page, fixed with **one global render hook**
   (`resources/views/filament/navigate-refresh.blade.php` via `PanelsRenderHook::BODY_END` in
   both panel providers): on `popstate` it refreshes the app's page component(s); forward
   link-clicks pay nothing. Browser-verified on the grades **view** (status → معتمد) and the
   grades **list** (count columns `1 0 1 0` → `1 0 0 1`) after a server-side change + Back.
   Distinct from #3 (cached action props vs SPA history cache). Same bug note + gotcha.

## Dev-data left behind (all dummy, refreshable)
- `gpa.test@dev.ly` reactivated to Active with extra PRIORPASS/CURRFLUSH courses + a survey;
  active semester `survey_open=false`.
- offering 9 (Advanced Mathematics) enrollment made active, grade approved (unpublished).
- offering 12 (Fault Tolerant Systems): student #1 enrolled, grade entered + **published**
  (toggled while reproducing the wire:navigate back-cache bug).
- `verify.faculty@example.ly` assigned to offerings 1 + 12.

## Next
- User to review + commit. Optionally reset the dev test rows.
