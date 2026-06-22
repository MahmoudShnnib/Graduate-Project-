---
date: 2026-06-22
tags: [bug, gpa, transfer-courses, survey, retake]
related: [[2026-06-22-transfer-courses-are-passed-courses]]
---

# Transfer (معادلة) courses double-counted in GPA + passed-count, and showed in survey

## Symptoms

For a transfer student, an equivalence course (e.g. "Advanced Electronics"):
- still appeared in the pre-enrollment **course survey** as available to study;
- if studied in-system, was counted **alongside** the new grade → GPA inflated/"fake";
- inflated the **المقررات المجتازة** count on the dashboard and grades page (counted
  the transfer record AND the regular pass).

## Root cause

Three places summed the regular and transfer tables independently with no dedup:

- `GradeCalculationService::recalculateStudentGPA()` — one loop over the latest
  regular grade per course, a separate loop over **all** transfer courses; a course
  present in both was counted twice (and its credits double-added).
- `Student::completedCoursesCount()` — returned `enrollmentCount + transferCount`
  (the sibling `getCompletedCourseIds()` already deduped, but this method didn't).
- `CourseSurveyPage::getAvailableCourses()` — never excluded passed courses, so
  transfer (and regular passed) courses kept showing in the survey.

## Fix

Treat a transfer course as a passed course; see decision
[[2026-06-22-transfer-courses-are-passed-courses]]. Files:

- `app/Services/GradeCalculationService.php` — skip a transfer course when
  `$latestPerCourse->has($transferCourse->course_id)` (latest in-system grade wins).
- `app/Models/Student.php` — `completedCoursesCount()` → `count($this->getCompletedCourseIds())`.
- `app/Filament/Student/Pages/CourseSurveyPage.php` — `getAvailableCourses()` adds
  `whereNotIn('id', $student->getCompletedCourseIds())`; `getRetakeableCourses()`
  unions below-A transfer course ids so transfers are retakeable.

## Regression guard

- `tests/Unit/GradeCalculationServiceTest.php` — transfer+in-system counts once with
  in-system grade winning (incl. lower retake); transfer-only still counts.
- `tests/Unit/StudentEligibilityTest.php` — `completedCoursesCount` dedups.
- `tests/Feature/TransferCourseSurveyTest.php` — survey excludes passed (transfer +
  regular); below-A transfer is retakeable, A transfer is not.

Full suite: 474 passed.
