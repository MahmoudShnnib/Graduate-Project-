# Models and Database

There are 17 Eloquent models. Here's what each one is and what matters about it.

---

## User
Table: `users`. Fields: `id, name, email, password, role (string), is_active, phone, deleted_at`.

The `role` column is a plain string: `super_admin`, `secretary`, `faculty`, or `student`. The system ALSO uses Spatie roles in a pivot table — both must stay in sync. `is_active = false` prevents login. Soft deletes. Uses `Notifiable` trait.

`canAccessPanel(Panel $panel)` routes the user to the right Filament panel based on role:
- super_admin / secretary / faculty → `/admin`
- student (and is_active=true) → `/portal`

---

## Student
Table: `students`. Fields: `id, user_id, enrollment_no (unique), specialization_id, registration_semester_id, is_transfer, status (enum), gpa (decimal 3,2), warnings_count, dismissed_at, dismissal_type, dismissal_course_id, deleted_at`.

This is the main academic record. The `status` field drives everything — what the student can do, whether they can enroll, whether their GPA is calculated.

**Status enum values:**
- `active` (مستمر) — full access, can enroll and submit requests
- `on_hold` (قيده موقوف) — view only, set when a suspension is approved
- `inactive` (منقطع) — view only, admin-set or auto-set at semester end
- `transferred` (منتقل) — view only
- `graduated` (متخرج) — view only
- `dismissed` (مفصول) — view only, set after 2nd course failure or 2nd consecutive inactive semester

Any status change away from `active` triggers `StudentObserver` which withdraws all active enrollments.

Key methods on the model:
- `canPerformActions()` — returns `true` only if `status === Active`
- `isEligibleForThesis()` — all general courses done + all specialized courses done + ≥2 electives done + GPA ≥ 3.0
- `activeEnrollments()` — relation filtered to `status = enrolled`
- `activeCourseOfferings()` — same, through the pivot
- `isSuspendedInSemester(Semester)` — defense-in-depth check

---

## Application
Table: `applications`. Fields: personal data (names in Arabic and English, national ID, phone, email, birth_date, gender, marital_status, city, address), `specialization_id, is_transfer, study_type, nationality, delegation_* fields, bachelor_certificate (file path), exam_date, exam_result, status, tracking_code, approved_by, approved_at`.

Status enum: `pending`, `initially_approved`, `accepted`, `rejected`.

The password is hashed at intake so when the application is approved, the new User is created with the password they already set. Uses `Notifiable` trait (for welcome notifications). Tracking code format: `APP-{YEAR}-NNNN`.

---

## Semester
Table: `semesters`. Fields: `id, name, season, year, is_active, survey_open, add_drop_start, add_drop_end, start_date, end_date`.

One active semester at a time (no DB constraint, just convention). `isAddDropOpen()` checks if today is between `add_drop_start` and `add_drop_end`. Has two relationships with courses:
1. `belongsToMany Course` through `semester_course` pivot — these are courses shown in the student survey
2. `hasMany CourseOffering` — the actual scheduled sections

---

## Course
Table: `courses`. Fields: `id, name_ar, name_en, code, credits`. Soft deletes.

`belongsToMany Specialization` through `course_specialization` pivot, which has a `type` column: `general`, `specialized`, or `elective`. A single course can belong to multiple specializations with different types in each.

---

## Specialization
Table: `specializations`. Fields: `id, name, code`. Students belong to one specialization. Courses and offerings are filtered by specialization.

---

## CourseOffering
Table: `course_offerings`. Fields: `id, course_id, semester_id, faculty_id, is_open, coursework_max (default 40), final_max (default 60)`.

`coursework_max + final_max` must equal 100. When `is_open` flips from true to false, `CourseOfferingObserver` cascades all active enrollments to `Withdrawn` and recalculates GPA per student.

---

## StudentEnrollment
Table: `student_enrollments`. Fields: `id, student_id, course_offering_id, status (enum), enrolled_at`.

Status enum: `enrolled`, `dropped`, `withdrawn`.

**This is the most important structural decision in the whole system: enrollment rows are NEVER deleted.** Only the status is updated. Re-enrolling a dropped student reuses the same row via `updateOrCreate`. The unique constraint is on `(student_id, course_offering_id)`.

Because of this, every query touching enrollments MUST filter by status. The codebase uses named relations: `Student::activeEnrollments()` (status=enrolled), `CourseOffering::activeEnrollments()` (same). Calling the unfiltered `enrollments()` relation is a bug.

Has `hasOne Grade` (with `cascadeOnDelete` on `enrollment_id`). The grade persists through drop→re-enroll cycles.

---

## Grade
Table: `grades`. Fields: `id, enrollment_id, coursework_grade, final_grade, total (auto), letter (auto), points (auto), status (enum), approved_by, approved_at, midterm_published_at, published_at`.

Status enum: `draft`, `pending_approval`, `approved`.

**Auto-computation:** `Grade::booted()` has a `saving` hook. When both `coursework_grade` and `final_grade` are non-null, it overwrites `total/letter/points` automatically using this hardcoded scale:

| Total | Letter | Points |
|---|---|---|
| ≥85 | A | 4.0 |
| ≥75 | B+ | 3.5 |
| ≥70 | B | 3.0 |
| ≥65 | C+ | 2.5 |
| ≥60 | C | 2.0 |
| ≥55 | D+ | 1.5 |
| ≥50 | D | 1.0 |
| <50 | F | 0.0 |

**Publishing vs approval are separate:**
- `approved` means admin confirmed it's correct
- `midterm_published_at` set → students see coursework grade only
- `published_at` set → students see full grade, GPA recalculates, warnings fire

Editing a grade after publishing clears the timestamps (coursework edit clears both; final edit clears only `published_at`).

---

## TransferCourse (معادلة مواد)
Table: `transfer_courses`. Fields: `id, student_id, course_id, institution_name, coursework_grade, final_grade, total (auto), letter (auto), points (auto)`.

Same auto-compute logic as Grade in its own `booted()`. Included in GPA calculation alongside regular enrollments.

---

## CourseSurvey
Table: `course_surveys`. Fields: `id, student_id, course_id, semester_id, priority, is_retake, is_processed, rejected_at`.

`is_processed = true` when the enrollment is created (accepted) or explicitly rejected. `is_retake = true` for courses the student wants to repeat (grade below A). No upper cap on how many courses a student can select in one survey.

---

## AddDropRequest
Table: `add_drop_requests`. Fields: `id, student_id, course_offering_id, semester_id, type (add/drop), status (pending/approved/rejected), notes`.

Max 3 adds and 3 drops per student per semester.

---

## SuspensionRequest
Table: `suspension_requests`. Fields: `id, student_id, semester_id, reason, file_path, status, decided_at, decision_notes`.

Status: `pending`, `approved`, `rejected`. Approval flips `Student.status = on_hold`.

---

## ThesisRegistration
Table: `thesis_registrations`. Fields: `id, student_id, title_ar, title_en, faculty_supervisor_id, external_supervisor_id, is_external_supervisor, proposal_file, status, printed_at, approved_at, approved_by, rejection_reason`.

Status: `pending`, `printed`, `approved`, `rejected`. Admin must print before accept/reject buttons appear.

---

## DiscussionRequest
Table: `discussion_requests`. Fields: `id, student_id, thesis_registration_id, status, rejection_reason, discussion_date, decided_by`.

Status enum (8 values): `pending`, `rejected`, `scheduled`, `discussed`, `discussion_rejected`, `accepted_with_edits`, `discussion_approved`, `completed`.

Clearance only unlocks when status = `completed`.

---

## ClearanceRequest
Table: `clearance_requests`. Fields: `id, student_id (unique), file_path, status (pending/issued), issued_at, issued_by`.

One per student. Student can only submit after discussion is `completed`.

---

## AuditLog
Table: `audit_logs`. Fields: `id, user_id, action, model_type, model_id, old_values (JSON), new_values (JSON), ip_address, created_at`. No `updated_at` (timestamps=false). Immutable.

---

## Key Pivot Tables

**`semester_course`** — links which courses are available in the student survey for a semester. Separate from offerings.

**`course_specialization`** — links courses to specializations with a `type` (general/specialized/elective). A course can be in multiple specializations with different types.

---

## GPA Formula

`GradeCalculationService::recalculateStudentGPA(Student)`:
1. Skips non-Active students
2. Gets published grades from active enrollments, grouped by course — takes the LATEST grade per course (supports retakes)
3. Adds all TransferCourse entries
4. Computes: `sum(points × credits) / sum(credits)` → saved to `Student.gpa`
