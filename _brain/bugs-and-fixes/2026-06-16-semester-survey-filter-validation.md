---
date: 2026-06-16
tags: [bug, filament, validation, semester, survey]
related: [[2026-06-16-go-live-batch-fixes]]
---

# Semester survey: specialization filter invalidated already-selected courses

## Symptom
On `/admin/semesters` "فتح/تعديل الاستطلاع", selecting courses, then choosing a
"تصفية حسب التخصص" produced **"المقررات المختار غير صالح"** on submit — even when the
selected course belonged to a different specialization than the filter.

## Root cause
The `course_ids` `CheckboxList` built its `->options()` from `Course` filtered by the
`->live()` `specialization_filter`. Filament validates a `CheckboxList` value against its
**current** options. When the filter narrowed the options, any already-selected course from a
different specialization dropped out of the list → validation rejected it.

## Fix
`SemesterResource::surveyCourseOptions($filter, $selectedIds)` (extracted, testable): when a
filter is set, narrow by specialization **OR** keep any `course_ids` already selected
(`->orWhereIn('id', $selectedIds)`); when no filter, return all active courses. Filter is now
a find-helper only; selections are never invalidated.

Gotcha hit on the way: the union must be guarded by `when($filter)` — putting the
`orWhereIn('id', [])` in the unfiltered path made an empty selection return zero rows.

## Regression guard
`tests/Feature/SemesterSurveyCourseFilterTest.php` — selected course survives a foreign-spec
filter; filter still narrows when nothing is selected; inactive courses excluded.
