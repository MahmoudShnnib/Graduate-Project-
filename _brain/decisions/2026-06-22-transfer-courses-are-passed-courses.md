---
date: 2026-06-22
tags: [decision, transfer-courses, gpa, survey, retake, domain-rule]
related: [[2026-06-22-transfer-course-gpa-and-count-double-count]]
---

# Domain rule: a transfer (معادلة) course IS a passed course

## Context

Transfer students (منتقل من كلية أخرى) get equivalence courses entered by staff via
"إدخال مواد المعادلة" (StudentResource → `manage_transfer_courses`), stored as
`TransferCourse` rows referencing a real `course_id`. These were not treated like
in-system passed courses: a transfer course still appeared in the pre-enrollment
course survey as something new to study, and if studied, **both** the transfer
record and the new grade were counted — inflating GPA and the "المقررات المجتازة"
count.

## Decision

A `TransferCourse` is semantically identical to a passed in-system course. The
single source of truth for "what has this student passed" is
`Student::getCompletedCourseIds()` (already deduped by `course_id` across regular
passes + transfers). Every surface derives from that concept:

1. **GPA** (`GradeCalculationService`) — the latest in-system grade per course wins.
   A transfer course is skipped when the same `course_id` has any regular graded
   enrollment (`$latestPerCourse->has(...)`). Latest always supersedes the
   equivalence, even if the in-system grade is *lower*.
2. **Passed count** (`Student::completedCoursesCount()`) — returns
   `count(getCompletedCourseIds())`; no more `enrollmentCount + transferCount`.
3. **Survey** (`CourseSurveyPage::getAvailableCourses()`) — excludes every passed
   course (`whereNotIn('id', getCompletedCourseIds())`), transfer or regular.
4. **Retake** (`CourseSurveyPage::getRetakeableCourses()`) — a transfer course with
   letter below A is retakeable via طلب إعادة مادة, mirroring the rule for regular
   passes. Once retaken and graded, rule #1 makes the new grade win.

## Alternatives considered

- **Add a DB unique constraint on (student_id, course_id) across the two tables** —
  rejected: a transfer course and a later in-system retake are legitimately two
  rows for the same course; the rule is "latest wins", not "forbid duplicates".
- **Only hide transfer courses from the survey** — rejected in favour of hiding all
  passed courses, so a passed course is consistently re-studied only via retake.

## Consequences

- `CourseTreePage` already gave regular passes precedence over transfers — unchanged
  and now consistent with GPA.
- The grades page and printed transcript still show transfers in a separate "معادلة"
  section (historical record); that display is intentional and untouched.
- Tests: `GradeCalculationServiceTest` (+2), `StudentEligibilityTest` (+1),
  new `TransferCourseSurveyTest` (4). Full suite 474 green.
