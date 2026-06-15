---
date: 2026-06-15
tags: [bug, audit, batch, filament, bulk-actions]
related:
  - "[[../daily-notes/2026-06-15-go-live-fixes-implementation]]"
---

# Audit "same operation" group leaked across multiple students in bulk actions

## Symptoms
On `/admin/audit-logs/{id}`, the **"إجراءات أخرى ضمن نفس العملية"** section (and the
collapsed list row "+N إجراء ضمن نفس العملية") bundled the audit rows of **many different
students** into one operation — e.g. one `exam_passed` row for student A showed siblings for
students B and C too (20 mixed rows in the reported case). Expected: at most **one** subject's
fan-out per operation.

## Root cause
`AuditService::$batchId` is one UUID memoized per unit-of-work. It resets only at work
boundaries: `ResetAuditBatch` middleware (per HTTP request), `JobProcessing`, `CommandStarting`
(see `AppServiceProvider`). But several **bulk admin actions iterate many subjects inside a
single request**, so every selected subject's fan-out (application edit → user create → student
create → exam result, all via `AuditObserver`) inherited the same `batch_id` and was glued into
one fake "operation."

## Fix
Start a fresh batch **per loop iteration** so each subject's fan-out is its own operation —
`AuditService::resetBatch()` as the first statement inside each per-subject bulk loop. Full
codebase sweep (every `foreach`/`->each` that mutates audited models) found **8 sites** that
process *independent* subjects in one request:

- `app/Filament/Pages/ExamListPage.php` — `markPassed()`, `markFailed()`
- `app/Filament/Resources/Applications/ApplicationResource.php` — `bulkInitialApprove`
- `app/Filament/Resources/Grades/Pages/ViewCourseGrades.php` — `approve_all` bulk action
- `app/Filament/Resources/CourseOfferings/Pages/EnrollStudents.php` — 3 bulk-enroll loops
  (`survey_students`, `retake_requests`, `enroll_manual`) + `unenroll_selected` bulk action

### Deliberately NOT split (one decision + cascade, not N independent decisions)
- **Grade publishing** (`ViewCourseGrades` + `ViewSemester` `publish_midterms`/`publish_finals`):
  one summary `grades_published`/`grades_midterm_published` row + one `Student_updated` (GPA recalc
  via `recalculateStudentGPA` → `$student->save()`) per student. This is a single decision with a
  causal fan-out — grouping is correct; splitting would orphan the summary row.
- **CoursesSurveyResults** bulk-creates N `CourseOffering`s from survey results — non-person
  records, one "open these courses" decision.
- **DeleteBulkAction** (Applications/Users/Specializations/Courses/Semesters/Roles): vendor action;
  bulk delete groups N `*_deleted` rows. Left as-is pending a product decision (a single
  delete-all click is arguably one operation).

### Verified single-subject (no change needed)
`StudentSurveyView` (one student, many courses), `CreateStudent`/`EditStudent` transfer-course
loops, `ListThesisRegistrations` supervisors, student-portal `AddDrop`/`CourseSurvey`.

## Why this is the right layer
The fan-out crosses model types (Application + User + Student), so siblings can't be filtered by
`model_id` on the view side — they're only correlated by `batch_id`. Scoping the batch at write
time is the only place the "one subject = one operation" invariant can be expressed.

## Regression guard
`tests/Feature/AuditBatchPerSubjectTest.php` — drives `ExamListPage::callAction('markPassed')`,
`markFailed`, and `EnrollStudents::callAction('enroll_manual')` on 2 subjects each, asserting the
per-subject rows carry **distinct** `batch_id`s. RED before the fix, GREEN after. Full suite: 334
passed. (NB: mount `EnrollStudents` with the model — `['record' => $offering]` — not the key; the
`$record` property is typed `CourseOffering`.)

The existing `AuditLogCollapseTest` "reads honestly" test still uses a shared batch with two
subjects — that remains valid: it tests the collapse **query** given a shared batch, not the bulk
action behaviour. Real bulk actions no longer produce such a batch.

## Browser verification (Chrome, 127.0.0.1:8000)
Seeded an active semester + 2 `InitiallyApproved` applications, bulk-passed both via the Exam
List page (confirmation modal → "قبول نهائي"). DB confirmed two disjoint batches (each holding
exactly one student's 7 rows); the audit detail "same operation" section for student A listed
only student A's rows. 0 console errors.
