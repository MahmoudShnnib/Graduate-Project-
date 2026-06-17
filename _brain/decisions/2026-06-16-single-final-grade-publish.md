---
date: 2026-06-16
tags: [decision, grades, workflow, gpa]
related: [[2026-06-16-go-live-batch-fixes]]
---

# Grades: single final-publish step (coursework + final entered together)

## Context
The grades system had a **two-phase publish**: admins could "نشر درجات أعمال الفصل"
(publish coursework / `midterm_published_at`) so students saw coursework-only, then later
"نشر الدرجات النهائية" (publish finals / `published_at`) which made the full grade visible and
recalculated GPA. Faculty could enter coursework alone and add the final later. The user wants
this simplified.

## Decision
- **Faculty** enter coursework (أعمال الفصل) **and** final (الامتحان النهائي). A coursework-only
  **draft** is allowed, but it **cannot be submitted for approval** until the final is entered
  (`submit_grades` only promotes drafts with both marks; incomplete ones are skipped with a notice).
- **Admins** have a **single** publish action: "نشر الدرجات النهائية". The coursework/midterm
  publish action was removed everywhere (per-course + semester-wide).
- A grade becomes **visible to the student AND affects GPA only** when `published_at` is set.
  (GPA already only recalculated on `published_at` — see GradeObserver — so that half was correct.)

## Implementation
- Dropped the `midterm_published_at` column (migration) + removed from `Grade` model.
- `GradeObserver::updating` now clears `published_at` when either mark changes.
- Removed `publish_midterms` from `ViewCourseGrades` + `ViewSemester`; final publish no longer
  writes a midterm timestamp; removed the "منتصف فقط" publish badge.
- `ViewMyCourse`: `enter_grade` editable only when no-grade/Draft; `submit_grades` gated on
  completeness; reworded the final-field helper.
- Student visibility (`GradesPage`, `grades`/`student-courses` blades): `published_at` only;
  removed midterm-only display.
- Deleted `CourseworkGradePublishedNotification` + `NotificationService::notifyCourseworkPublished`.
- `DevDataSeeder` cleaned; `GradeCourseworkFlowTest` rewritten.

## Consequences
- Historical audit labels `grades_midterm_published` / `midterm_published_at` are intentionally
  **kept** in `AuditService` + `AuditLogResource` so old log rows still render readably; no new
  writes occur.
- The status workflow (Draft → PendingApproval → Approved) and the separate "approve" step are
  unchanged — only the publish half was simplified.
