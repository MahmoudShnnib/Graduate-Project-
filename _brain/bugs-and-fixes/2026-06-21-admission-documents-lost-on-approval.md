---
date: 2026-06-21
tags: [bug, applications, students, documents, approval, migration]
related:
  - "[[../daily-notes/2026-06-21-student-form-docs-and-validation]]"
---

# Admission documents lost when an application is approved

## Symptom
After accepting an application, the new student's uploaded documents (bachelor certificate,
remedial statement) disappeared — not shown on `/admin/students/{id}` (e.g. #16).

## Root cause
Data-model gap, category (a) "never copied":
- `applications` table **has** `bachelor_certificate` + `remedial_statement` (file paths).
- `students` table had **neither** column — only `degree_image` (post-graduation).
- `ApproveApplication` copied biographical fields but not the documents (couldn't — no columns).
- The student view (`StudentResource::infolist` + `view-student.blade.php`) only rendered
  `degree_image`. The application *infolist* showed the docs fine — that masked the loss.

## Fix
- Migration `2026_06_21_042030_add_admission_documents_to_students_table`: adds the two nullable
  string columns, then **backfills** existing students from their originating application
  (`applications.user_id` → `students.user_id`). The stored value is the same path string, so
  student + application reference the same physical file — no copy.
- `Student::$fillable` += `bachelor_certificate`, `remedial_statement`.
- `ApproveApplication::handle` copies both into `Student::create`.
- `CreateStudent::handleRecordCreation` passes both from the form data.
- `StudentResource::form` — new **مستندات القبول** FileUpload section (bachelor required on
  create, remedial optional). So manually-created students can hold these too.
- `StudentResource::infolist` — new **مستندات القبول** TextEntry section linking to
  `asset('storage/'.$path)`, mirroring `ApplicationInfolist`.

## Regression guard
`ApproveApplicationTest` → "copies the admission documents to the new student on approval".
Backfill verified on dev DB: student #16's `bachelor_certificate` now equals its application's.
