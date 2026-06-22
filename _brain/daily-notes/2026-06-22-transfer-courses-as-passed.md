---
date: 2026-06-22
tags: [transfer-courses, gpa, survey, retake, daily]
related: [[2026-06-22-transfer-courses-are-passed-courses]] [[2026-06-22-transfer-course-gpa-and-count-double-count]]
---

# 2026-06-22 — Transfer (معادلة) courses now treated as passed courses

## Done

Made `TransferCourse` equivalences behave exactly like in-system passed courses,
fixing fake GPA, double-counted المقررات المجتازة, and transfer courses appearing
in the survey. Four edits + tests, full suite 474 green. Not committed (per
no-auto-commit rule).

- `GradeCalculationService` — transfer skipped when course has a regular grade (latest wins).
- `Student::completedCoursesCount()` — now `count(getCompletedCourseIds())` (deduped).
- `CourseSurveyPage::getAvailableCourses()` — excludes all passed courses.
- `CourseSurveyPage::getRetakeableCourses()` — below-A transfers are retakeable.

## Decisions

- [[2026-06-22-transfer-courses-are-passed-courses]] — domain rule + alternatives.

## Bugs fixed

- [[2026-06-22-transfer-course-gpa-and-count-double-count]]

## Also today (earlier)

- [[2026-06-22-survey-filter-misleading-labels]] — out-of-filter selected courses in
  the semester survey-courses modal now tagged.

## Next steps

- Browser-verify with a transfer student at /portal/course-survey, /portal/grades-page,
  /portal/student-dashboard. Then commit when ready.
