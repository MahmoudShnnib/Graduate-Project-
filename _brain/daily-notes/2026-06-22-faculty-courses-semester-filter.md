---
date: 2026-06-22
tags: [filament, faculty, course-offerings, filter, daily]
---

# 2026-06-22 — Faculty "موادي" list: semester filter (default = current)

## Done

The faculty courses resource (`MyCourseResource`, نav "موادي") listed offerings from
all semesters mixed together. Added a `SelectFilter` on `semester_id` defaulting to
the active semester, matching the existing pattern in `ApplicationResource` /
`CourseOfferingResource`. Faculty now see the current semester by default and can
switch to a previous one (or clear to see all).

- `app/Filament/Resources/Faculty/MyCourseResource.php` — added `SelectFilter` +
  imports (`Semester`, `SelectFilter`); default `Semester::activeId()`.
- `tests/Feature/MyCoursesSemesterFilterTest.php` — 3 tests (default=current, switch
  to previous, clear=all). Green. Pint clean.

## Next steps

- Browser-verify as a faculty user at /admin/my-courses. Commit when ready (no auto-commit).
