---
date: 2026-06-22
tags: [bug, filament, semester, survey, ux]
related: [[2026-06-16-survey-filter-invalidates-selected-courses]]
---

# Survey-courses modal: out-of-filter selected courses looked like they belonged to the filtered specialization

## Symptoms

On `/admin/semesters`, the **"تعديل المقررات المتاحة للاستطلاع"** action (and the identical "فتح الاستطلاع" action) has a specialization filter + a course checkbox list. Selecting courses with the filter on "جميع التخصصات", then switching the filter to another specialization, left the previously-checked courses in the list rendered **identically** to courses that genuinely belong to that specialization. Users read them as "courses opened *in* this specialization" — misleading.

## Root cause

`SemesterResource::surveyCourseOptions()` deliberately keeps any already-selected course visible via `orWhereIn('id', $selectedIds)` (from the earlier fix — see [[2026-06-16-survey-filter-invalidates-selected-courses]]; without it Filament rejects a checked-but-filtered-out course as `المقررات المختار غير صالح`). But every option used the same label format, so a kept-out-of-filter course was visually indistinguishable from a real member of the filtered specialization.

## Fix

`app/Filament/Resources/Semesters/SemesterResource.php` → `surveyCourseOptions()`. When a specialization filter is active, eager-load `specializations:id` and append ` (محدّد — من تخصص آخر)` to the label of any course that does **not** belong to the filtered specialization. The `orWhereIn` validation fix is preserved — courses stay in the list and keep their checked state.

## Regression guard

`tests/Feature/SemesterSurveyCourseFilterTest.php` — two new tests: (1) a kept-but-out-of-filter selected course gets the marker while the in-filter course does not; (2) no marker when filter is null. 5 tests / 8 assertions green.
