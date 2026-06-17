---
date: 2026-06-17
tags: [go-live, filament, print, mpdf, suspension, settings, ux, faculty]
related: [[2026-06-16-go-live-batch-fixes]], [[2026-06-16-single-final-grade-publish]]
---

# Eight-item user fix batch

Branch `chore/go-live-high-medium-fixes`. **Nothing committed** (per no-auto-commit).
Full suite green: **389 passed (960 assertions)**. Pint clean.
All 8 items verified in the browser on the local app (127.0.0.1:8000) after running
`DevDataSeeder` to populate dummy academic data.

## Items

1. **App #22 shows no document — NOT a bug (no code change).** `ApplicationInfolist`
   renders `bachelor_certificate` correctly when present; all 22 apps have it `NULL`
   because they're factory-seeded (the factory never attaches a file). Real `/apply`
   submissions store + display it. User chose "leave as-is".

2. **Custom survey confirmation modal.** Replaced all native `wire:confirm` on
   `StudentSurveyView` with a styled modal matching the existing retake-modal pattern.
   New `getConfirmConfig()` + `requestConfirm()/cancelConfirm()/confirmAction()` on the
   page; one generic modal block in `student-survey-view.blade.php` (success/danger
   variants). Covers enroll/unenroll/reject single + bulk enroll/reject.

3. **Student-courses print → mPDF + settings signature.** New
   `prints/student_courses.blade.php` (noto-naskh/markazi), `PrintController::studentCourses()`,
   route `print.student-courses` (auth via `authorizeAdminOrOwningStudent`). Button now
   opens the route; removed the old `window.print()` JS + data-attrs. Signature line
   `مدير {office_name} بالكلية` + `director_name` sourced from `Setting` (الإعدادات العامة).
   Title kept short ("كشف المقررات المسجلة") to avoid the double-فصل artifact.

4. **Print template tweaks.**
   - التاريخ now auto-fills today's print date (`now()->format('Y/m/d')`) in
     `exam_attendance.blade.php` and `course_results.blade.php`.
   - Removed the التوقيت row + `$timeSlot` from `course-students.blade.php`.

5. **Faculty can print course-results.** The route already authorized course faculty
   (`authorizeAdminOrCourseFaculty`); added the missing "طباعة نتائج المادة" header
   action on `ViewMyCourse`. Verified a faculty user can generate the PDF.

6. **User-edit layout gap.** `UserForm` top fields were loose/full-width, pushing
   البيانات الأكاديمية down with a dead quadrant beside it. Wrapped account fields in a
   new "بيانات الحساب" `Section` and set all three sections `columnSpanFull()` so they
   stack full-width with no side gaps.

7. **Suspended student still showed dropped current-semester grades.** See decision
   [[../decisions/2026-06-17-suspended-student-grade-visibility]] + bug
   [[../bugs-and-fixes/2026-06-17-suspended-student-grade-visibility]]. `GradesPage` (كشف الدرجات) +
   `CourseTreePage` (المقررات العامة) now hide current-semester non-Enrolled enrollments
   **only when the student status is OnHold/Inactive** (whose GPA recalcs without the
   flushed term). Graduated stays intact (GPA frozen with the term). Realigned the
   pre-existing `StudentStatusGateTest` "keeps approved grades visible after cascade"
   test (it asserted the opposite for OnHold) to use Graduated; added 3 new tests in
   `SuspensionFlushesCurrentSemesterTest`.

8. **Removed grade-sheet upload.** Deleted the `upload_paper_sheet` action + `FileUpload`
   import (`ViewMyCourse`), the ورقة الدرجات blade block, the "أوراق درجات معلقة" widget
   stat (`FacultyStatsOverviewWidget`), `Grade::$fillable` entry, and added a migration
   dropping `course_offerings.paper_sheet_path` + `grades.paper_sheet_path`.

## Follow-ups (same day)
- **exam_attendance**: removed the `@for ($j=0;$j<3;$j++)` loop that printed 3 blank
  trailing rows (the "empty rows" the user reported). Now only real student rows render.
- **exam_results** (`/admin/exam-results/print`): added `director_name` from `Setting` to
  the footer signature (`{director}` / `مدير {office}`) — previously only showed office name.
  Both blade-only; ExamListPrintTest still green (5 passed).

## Verification
All 10 items (8 + 2 follow-ups) **browser-verified** on 127.0.0.1:8000, including #7:
logged in as a suspended (OnHold) student `gpa.test@dev.ly` with a prior passed course
(PRIORPASS) + a flushed current course (CURRFLUSH). كشف الدرجات shows only PRIORPASS;
the tree shows CURRFLUSH as "لم يُدرس بعد" (no grade) and PRIORPASS as مجتاز/B+, counters
correct (مجتازة 1, قيد الدراسة 0). Full suite 389 green.

## Notes / loose ends
- Dev DB now has DevDataSeeder dummy data + a test faculty user
  (`verify.faculty@example.ly` / `12345678`) assigned to offering #1, and one CourseSurvey
  for student #13. All dummy, refreshable.
- The earlier-flagged `notifyGradePublished` breakage (notification overhaul) resolved
  itself — both callers now use `notifyGradesPublished($student)`; suite green.
- Uncommitted. User to review + commit.
