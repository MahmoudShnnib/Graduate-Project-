---
date: 2026-04-27
tags: [bug, grades, transfer-courses, portal]
related: [[2026-04-27-quick-fixes]], [[2026-04-20-transfer-courses-not-persisted-on-edit]]
---

# Transfer Courses Not Showing on Grades Page

**Status:** Fixed

**Symptoms:** Students with transfer courses (مواد المعادلة) saw nothing on `/portal/grades-page` — only regular enrolled course grades appeared.

**Root cause:** `GradesPage::getGrades()` only queried `StudentEnrollment` records. The `TransferCourse` model is stored in a separate `transfer_courses` table with its own relationship on `Student`, and was never loaded or rendered on the grades page.

**Fix:**
- Added `GradesPage::getTransferCourses()` — queries `TransferCourse::where('student_id', ...)->with('course')`
- Added a separate "مواد المعادلة" section in the Blade template with matching table styling (same columns minus semester)
- Section only renders when transfer courses exist; unaffected by semester filter (transfer courses are not semester-scoped)

**Regression guard:** Visual — no automated test added (display-only change). Verified with students 5, 10, 16 who have transfer course data.
