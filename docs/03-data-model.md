# Graduate Studies System — Data Model

## Complete Model List (17 Models)

| Model | Table | Notes |
|---|---|---|
| `User` | `users` | Filament user; dual role system; soft deletes |
| `Student` | `students` | Main academic record; GPA; warnings; soft deletes |
| `Application` | `applications` | Pre-student intake form; tracking code |
| `Semester` | `semesters` | Academic period; one active at a time |
| `Course` | `courses` | Course definition; soft deletes |
| `Specialization` | `specializations` | Academic track |
| `CourseOffering` | `course_offerings` | Concrete semester section with faculty |
| `StudentEnrollment` | `student_enrollments` | Rich pivot — tracks enrollment status |
| `Grade` | `grades` | Coursework + final; auto-computed total/letter/points |
| `TransferCourse` | `transfer_courses` | معادلة — previously-earned credits |
| `CourseSurvey` | `course_surveys` | Student's course wishlist for a semester |
| `AddDropRequest` | `add_drop_requests` | Add or drop request during the window |
| `SuspensionRequest` | `suspension_requests` | Semester suspension request |
| `ThesisRegistration` | `thesis_registrations` | Thesis title + supervisor + proposal |
| `DiscussionRequest` | `discussion_requests` | 4-phase مناقشة pipeline |
| `ClearanceRequest` | `clearance_requests` | End-of-studies clearance |
| `AuditLog` | `audit_logs` | Immutable change trail |

**Pivot / supporting tables:** `semester_course`, `course_specialization`, `notification_logs`, `student_warnings`, `settings`, `permissions`, `roles`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`

---

## Model Detail

### User

```
id, name, email (unique), password, role (string: super_admin/secretary/faculty/student),
is_active (bool, default true), phone, deleted_at (SoftDeletes)
```

- `canAccessPanel(Panel $panel)` — routes to admin or student panel based on role + is_active
- `hasOne Student` — via FK `user_id` on `students`
- `hasOne Application`
- Implements `FilamentUser`, uses `HasRoles` (Spatie), `Notifiable`

### Student

```
id, user_id (FK→users, cascade), enrollment_no (unique), specialization_id (FK),
registration_semester_id (FK→semesters), is_transfer (bool),
status (StudentStatus enum), gpa (decimal 3,2, default 0.00),
warnings_count (int, default 0),
dismissed_at (nullable timestamp), dismissal_type (nullable string),
dismissal_course_id (nullable FK→courses),
deleted_at (SoftDeletes)
```

**Key relations:**
- `belongsTo User`
- `belongsTo Specialization`
- `hasMany StudentEnrollment` (all, unfiltered)
- `activeEnrollments()` — filters `status = Enrolled`
- `activeCourseOfferings()` — via `activeEnrollments`
- `hasMany Grade` (through enrollments)
- `hasMany TransferCourse`
- `hasMany CourseSurvey`
- `hasMany ThesisRegistration`
- `hasMany DiscussionRequest`
- `hasOne ClearanceRequest`

**Domain methods:**
- `isEligibleForThesis(): bool` — all general + all specialized + ≥2 elective courses (approved grade ≠ F or passing transfer) + GPA ≥ 3.0
- `getThesisEligibilityBreakdown(): array` — per-type progress for UI
- `getCompletedCourseIds(): Collection`
- `isSuspendedInSemester(Semester $semester): bool` — defense-in-depth
- `canPerformActions(): bool` — `return $this->status === StudentStatus::Active`

### StudentStatus Enum

| Case | Value | Arabic | Behavior |
|---|---|---|---|
| `Active` | `active` | مستمر | Full access |
| `OnHold` | `on_hold` | قيده موقوف | View-only; set when suspension approved |
| `Inactive` | `inactive` | منقطع | View-only; auto-set if zero enrollments end of semester |
| `Transferred` | `transferred` | منتقل | View-only |
| `Graduated` | `graduated` | متخرج | View-only |
| `Dismissed` | `dismissed` | مفصول | View-only; set on 2nd course failure or 2nd consecutive inactive semester |

Any status other than `Active` cascades active enrollments to `Withdrawn` via `StudentObserver`.

### Application

```
id, tracking_code (unique, format APP-YYYY-NNNN),
first_name_ar, last_name_ar, full_name_ar, full_name_en,
national_id (12 digits), birth_date, phone (Libyan 09XXXXXXXXX),
email (unique), password (hashed at intake),
gender, marital_status, city, address,
specialization_id (FK), is_transfer (bool),
study_type, nationality, delegation_* fields,
bachelor_certificate (file path),
exam_date (nullable), exam_result (nullable),
status (ApplicationStatus enum),
approved_by (nullable FK→users), approved_at (nullable)
```

- `Notifiable` — routes WhatsApp via `phone` field
- Status enum: `pending`, `initially_approved`, `accepted`, `rejected`
- Password is hashed at intake so approval doesn't need another step

### Semester

```
id, name (e.g. "خريف 2025"), season (Season enum: spring/fall/summer),
year (int), is_active (bool), survey_open (bool),
add_drop_start (nullable date), add_drop_end (nullable date),
start_date (nullable date), end_date (nullable date)
```

- `isAddDropOpen(): bool` — checks today is between `add_drop_start` and `add_drop_end`
- `belongsToMany Course` through `semester_course` (survey menu)
- `hasMany CourseOffering` (concrete sections)
- **One active semester assumed** — no DB constraint prevents two active semesters

### Course

```
id, name_ar, name_en, code, credits (int), deleted_at (SoftDeletes)
```

- `belongsToMany Specialization` through `course_specialization` pivot (with `type`: `general`/`specialized`/`elective`)
- A course can belong to multiple specializations with different types in each

### CourseOffering

```
id, course_id (FK), semester_id (FK), faculty_id (FK→users),
is_open (bool, default true),
coursework_max (int, default 40), final_max (int, default 60)
```

- `coursework_max + final_max` must equal 100 (validated)
- `is_open=false` cascades all active enrollments to `Withdrawn` via `CourseOfferingObserver`
- `hasMany StudentEnrollment`
- `activeEnrollments()` — filters `status = Enrolled`

### StudentEnrollment

```
id, student_id (FK, cascade on delete), course_offering_id (FK, cascade on delete),
status (EnrollmentStatus enum), enrolled_at
```

- Status enum: `enrolled`, `dropped`, `withdrawn`
- **Never deleted** — only status-updated
- `unique(['student_id', 'course_offering_id'])` — re-enrolling reuses the same row via `updateOrCreate`
- `scopeActive()` — filters `status = Enrolled`
- `hasOne Grade` (FK `enrollment_id`, cascade on delete)

### Grade

```
id, enrollment_id (FK→student_enrollments, cascade),
coursework_grade (nullable decimal), final_grade (nullable decimal),
total (auto-computed), letter (auto-computed), points (auto-computed),
status (GradeStatus enum), approved_by (nullable FK), approved_at (nullable),
midterm_published_at (nullable), published_at (nullable)
```

- `booted()` saving hook: if both components present → auto-computes `total/letter/points`
- GradeStatus enum: `draft`, `pending_approval`, `approved`
- `convertToLetter(float $total): string` — static method with hardcoded thresholds
- `midterm_published_at` — students see coursework_grade only
- `published_at` — students see full results; triggers GPA recalc

### TransferCourse (معادلة)

```
id, student_id (FK), course_id (FK), institution_name,
coursework_grade, final_grade, total (auto), letter (auto), points (auto)
```

Same auto-compute logic as Grade in `booted()`. Included in GPA calculation alongside regular enrollments.

### CourseSurvey

```
id, student_id (FK), course_id (FK), semester_id (FK),
priority (int, ordering), is_retake (bool, default false),
is_processed (bool, default false), rejected_at (nullable)
```

- `is_processed = true` when enrollment is created or explicitly rejected
- `is_retake = true` for courses the student wants to repeat (grade below A)
- No upper cap on selections per semester (removed 2026-04-20)

### AddDropRequest

```
id, student_id (FK), course_offering_id (FK), semester_id (FK),
type (AddDropType enum: add/drop), status (pending/approved/rejected), notes
```

### SuspensionRequest

```
id, student_id (FK), semester_id (FK), reason (text), file_path (nullable),
status (SuspensionStatus enum), decided_at (nullable), decision_notes (nullable)
```

- SuspensionStatus: `pending`, `approved`, `rejected`
- Approval flips `Student.status = OnHold`

### ThesisRegistration

```
id, student_id (FK), title_ar, title_en,
faculty_supervisor_id (nullable FK→users),
external_supervisor_id (nullable FK→supervisors), is_external_supervisor (bool),
proposal_file (file path), status (ThesisStatus enum),
printed_at (nullable), approved_at (nullable), approved_by (nullable),
rejection_reason (nullable)
```

- ThesisStatus: `pending`, `printed`, `approved`, `rejected`
- Admin must print before accept/reject buttons appear
- Only students with `isEligibleForThesis() = true` can submit

### DiscussionRequest

```
id, student_id (FK), thesis_registration_id (FK),
status (DiscussionStatus enum), rejection_reason (nullable),
discussion_date (nullable),
decided_by (nullable FK→users)
```

- DiscussionStatus: `pending`, `rejected`, `scheduled`, `discussed`, `discussion_rejected`, `accepted_with_edits`, `discussion_approved`, `completed`
- Admin sees all 8 statuses; student sees simplified 4 labels
- Clearance only unlocks when `status = completed`

### ClearanceRequest

```
id, student_id (FK, unique), file_path (nullable),
status (pending/issued), issued_at (nullable), issued_by (nullable FK)
```

### AuditLog

```
id, user_id (nullable FK), action, model_type, model_id,
old_values (JSON), new_values (JSON), ip_address, created_at
```

No `updated_at` (`$timestamps = false`). Immutable once written.

---

## Key Foreign Key Cascades

| FK | Behavior |
|---|---|
| `students.user_id` → `users.id` | CASCADE DELETE |
| `student_enrollments.student_id` → `students.id` | CASCADE DELETE |
| `student_enrollments.course_offering_id` → `course_offerings.id` | CASCADE DELETE |
| `grades.enrollment_id` → `student_enrollments.id` | CASCADE DELETE |
| `grades.approved_by` → `users.id` | SET NULL on delete |

Note: `User`, `Student`, and `Course` use **soft deletes**. DB cascade doesn't fire on soft delete — `StudentObserver::deleting` manually cascades enrollment withdrawal.

---

## Pivot Tables

### `semester_course`
Links semesters to courses available for the student survey. Separate from `course_offerings`.
Fields: `semester_id`, `course_id`

### `course_specialization`
Links courses to specializations with a type.
Fields: `course_id`, `specialization_id`, `type` (`general`/`specialized`/`elective`)
A course can appear multiple times with different types per specialization.

---

## Enum Reference

### All Backed Enums (every enum has Arabic `label()` method)

**`StudentStatus`** — `active`, `on_hold`, `inactive`, `transferred`, `graduated`, `dismissed`

**`GradeStatus`** — `draft`, `pending_approval`, `approved`

**`EnrollmentStatus`** — `enrolled`, `dropped`, `withdrawn`

**`ApplicationStatus`** — `pending`, `initially_approved`, `accepted`, `rejected`

**`SuspensionStatus`** — `pending`, `approved`, `rejected`

**`ThesisStatus`** — `pending`, `printed`, `approved`, `rejected`

**`DiscussionStatus`** — `pending`, `rejected`, `scheduled`, `discussed`, `discussion_rejected`, `accepted_with_edits`, `discussion_approved`, `completed`

**`AddDropType`** — `add`, `drop`

**`Season`** — `spring`, `fall`, `summer`

**`CourseType`** — `general`, `specialized`, `elective`

**`NotificationChannel`** — `mail`, `whatsapp`

---

## GPA Calculation Logic

`GradeCalculationService::recalculateStudentGPA(Student $student)`:

1. Skips students whose status is not `Active` (frozen GPA for non-active)
2. Fetches all active enrollments that have `published_at` set (grades visible to students)
3. Groups by `course_id`, takes the **latest grade** per course (supports retakes)
4. Includes all `TransferCourse` rows for the same student
5. Computes credit-weighted average: `Σ(points × credits) / Σ(credits)`
6. Saves via `Student::updateQuietly` to avoid observer loops

---

## Warning System Data Model

`student_warnings` table:
```
id, student_id (FK), type (course_failure / consecutive_inactive),
course_id (nullable FK), semester_id (FK), is_active (bool),
resolved_at (nullable), created_at, updated_at
```

- `warnings_count` on `students` is a denormalized count of `WHERE is_active = true` — kept in sync by `WarningService::syncWarningsCount()`
- `dismissed_at`, `dismissal_type`, `dismissal_course_id` on `students` record permanent dismissal context
- Warning records are never deleted — only resolved (`is_active=false`) for audit trail
