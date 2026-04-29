---
date: 2026-04-25
tags: [retake, survey, gpa, enrollment]
related: [[2026-04-25-filters-tree-logs-offerings-print]]
---

# Course Retake System

## Context
Students need to retake courses to improve low grades and raise their GPA toward the 3.0 thesis threshold.

## Decision
Retake requests are modeled as `CourseSurvey` records with `is_retake = true`, reusing the existing survey infrastructure rather than a separate model.

### Rules
- **Eligibility:** Any course the student passed with grade below A
- **GPA:** Latest grade per course is used; old grade stays in enrollment history
- **Cap:** Retakes count toward the 4-enrollment-per-semester cap
- **Repeats:** A student can retake the same course multiple times
- **No per-semester retake limit**

### Admin approval flow
Retake requests are separated from regular survey requests on the EnrollStudents page. Admin must explicitly select retake students and confirm through a two-step process (select → confirmation dialog). This prevents accidental re-enrollment.

## Alternatives Considered
- **Separate `RetakeRequest` model** — more explicit but duplicates survey infrastructure
- **Flag on `StudentEnrollment`** — doesn't capture the request/approval flow

## Consequences
- `GradeCalculationService` groups by course_id, takes latest grade — affects all GPA calculations going forward
- Survey queries must filter `is_retake` to separate regular from retake requests
- The "below A" threshold is intentionally easy to change later (single method in `CourseSurveyPage`)
