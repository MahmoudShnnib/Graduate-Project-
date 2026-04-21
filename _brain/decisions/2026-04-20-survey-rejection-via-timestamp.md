---
date: 2026-04-20
tags: [decision, schema, course-survey, admin-ux]
related: [[../daily-notes/2026-04-20-task-session-multi-fix]], [[../project-context/gotchas]]
---

# Decision: represent survey rejection via `rejected_at` timestamp

> Source task: T7 of the 2026-04-20 session.

## Context

Admins need to explicitly reject a student's surveyed course (distinct from accepting & enrolling). Both outcomes should remove the entry from the "طلبوا المادة" pending list but the student needs to see which happened on their own portal.

## Decision

Add a nullable `rejected_at` timestamp column to `course_surveys`. Both accept and reject set `is_processed=true`; only reject sets `rejected_at = now()`.

Resulting state triple:

| `is_processed` | `rejected_at` | Meaning |
|---|---|---|
| `false` | `null` | Pending (admin hasn't decided) |
| `true`  | `null` | Accepted — admin enrolled the student |
| `true`  | `<ts>` | Rejected — admin said no |

## Alternatives considered

**A. Replace `is_processed` boolean with a `status` enum (`pending/accepted/rejected`).** Cleaner semantics long-term. Rejected because every call site in the codebase that currently checks `where('is_processed', false)` would need to migrate — including [CourseSurveyPage](../../app/Filament/Student/Pages/CourseSurveyPage.php), [EnrollStudents](../../app/Filament/Resources/CourseOfferings/Pages/EnrollStudents.php), [CourseOfferingResource](../../app/Filament/Resources/CourseOfferings/CourseOfferingResource.php), [EnrollStudentInOffering action](../../app/Actions/Enrollment/EnrollStudentInOffering.php), tests, etc. Bigger blast radius for little immediate gain.

**B. Infer rejection from "`is_processed=true` + no active enrollment for this course".** Rejected because it's fragile: a student who was enrolled, then later dropped mid-semester, would look identical to a rejection. The two domain events deserve distinct state.

**C. Add `rejected_at` nullable timestamp.** **Chosen.** Small migration, zero touch to existing queries, free audit info (when was it rejected, by whom can be added later if needed).

## Consequences

### Positive
- No breaking change to existing `where('is_processed', false)` queries.
- Includes an audit timestamp for free.
- Can be extended to `rejected_by` / `rejection_reason` later without another schema rewrite.
- Student-side display is a clean single predicate (`rejected_at !== null`).

### Negative
- `is_processed=true` is now semantically ambiguous between "accepted" and "rejected". Callers that need to discriminate must check both columns.
- If later work does unify into a `status` enum, we'll have two legacy columns to reconcile. Acceptable cost.

## Implementation

- Migration: [2026_04_20_215117_add_rejected_at_to_course_surveys_table.php](../../database/migrations/2026_04_20_215117_add_rejected_at_to_course_surveys_table.php)
- Model change: [CourseSurvey.php](../../app/Models/CourseSurvey.php) — `rejected_at` added to `$fillable` + `datetime` cast; `is_processed` also got a `boolean` cast for good measure.
- Admin action: [StudentSurveyView::rejectCourse](../../app/Filament/Pages/StudentSurveyView.php).
- Student display: [CourseSurveyPage::getEnrollmentStatuses](../../app/Filament/Student/Pages/CourseSurveyPage.php) now returns `'rejected'` as a fourth possible state.

## Follow-up candidates

- If admins later need "who rejected this and when did they see the student's side", add `rejected_by` FK + `rejection_reason` text column.
- If UX calls for "un-reject" (admin made a mistake), add a reset action that clears `is_processed=false` + `rejected_at=null`. Currently a re-call to `rejectCourse` just updates the timestamp; there's no inverse.
