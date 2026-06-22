---
date: 2026-06-22
tags: [filament, semester, survey, ux, bug]
related: [[2026-06-22-survey-filter-misleading-labels]]
---

# 2026-06-22 — Survey modal: mark out-of-filter selected courses

## Done

- Fixed misleading UI in the semester survey-courses modals ("فتح الاستطلاع" / "تعديل المقررات المتاحة للاستطلاع"). Courses kept visible only because they were already selected now show ` (محدّد — من تخصص آخر)` when they don't belong to the active specialization filter, so they aren't mistaken for that specialization's own courses.
- Single edit: `SemesterResource::surveyCourseOptions()`. Preserves the prior `orWhereIn` validation fix.
- Added 2 tests to `tests/Feature/SemesterSurveyCourseFilterTest.php` (now 5 passing). Pint clean.

## Bug log

- [[2026-06-22-survey-filter-misleading-labels]] (bugs-and-fixes)

## Next steps

- User to browser-verify at `/admin/semesters`, then commit (no auto-commit per house rule).
