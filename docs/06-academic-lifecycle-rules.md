# Graduate Studies System — Academic Lifecycle & Business Rules

This document covers all the domain rules that govern academic progression: enrollment constraints, grading, warnings, dismissal, thesis eligibility, the discussion pipeline, and clearance.

---

## Enrollment Rules

### 4-Enrollment Cap

A student may not have more than **4 active enrollments in a single semester** at any time. This is enforced by `EnrollStudentInOffering` at the action layer:

```
if active enrollments (status=Enrolled) for this student in this semester ≥ 4
  → throw EnrollmentCapReachedException
```

The check is scoped to "would this call add a NEW active enrollment" — re-enrolling a dropped student in the same offering is allowed (idempotent check).

### Specialization Match

A student may only enroll in courses that belong to their specialization. The `course_specialization` pivot determines valid courses for each specialization. Attempting to enroll in an unlinked course throws `CourseOutsideSpecializationException`.

### Closed Offering Guard

`CourseOffering::is_open = false` blocks all new enrollments. Throws `CourseOfferingClosedException`. The UI also hides enroll buttons when the offering is closed.

### Suspended Student Guard

An active approved `SuspensionRequest` for the current semester blocks enrollment. Throws `StudentSuspendedInSemesterException`. This is a defense-in-depth check — the primary gate is `Student::status = OnHold` which is set when the suspension is approved.

### Active Student Guard

Only students with `status = Active` can be enrolled. `StudentStatus::Active` is the sole allowed state.

### Enrollment is Idempotent

`EnrollStudentInOffering` uses `updateOrCreate` on the `(student_id, course_offering_id)` unique pair. Re-running the action when the student is already enrolled is a no-op (no duplicate row, no exception thrown if already active).

---

## Add/Drop Rules

### Window

The add/drop window is semester-scoped: `Semester::add_drop_start` to `Semester::add_drop_end`. `Semester::isAddDropOpen()` checks if today falls within this range.

### Per-Semester Limits

- Maximum **3 drop requests** per student per semester
- Maximum **3 add requests** per student per semester

### Add Approval Enforcement

When an admin approves an add request, `EnrollStudentInOffering` runs with all its guards (cap, specialization, closed offering, suspended). The request can be rejected if any guard fails.

### Drop Approval

When an admin approves a drop request, `DropStudentFromOffering` runs → sets `StudentEnrollment.status = Dropped`. The grade row (if any) persists but is no longer active.

---

## Grade System Rules

### Grade Entry Sequence

1. Faculty enters `coursework_grade` and `final_grade` — `status = Draft`
2. Faculty submits for review — `status = PendingApproval`
3. Admin/secretary approves — `status = Approved`

### Auto-Computation

When both component grades are non-null, `Grade::booted()` auto-computes on every save:
- `total = coursework_grade + final_grade`
- `letter` and `points` via the hardcoded scale

**Hardcoded grading scale:**

| Total range | Letter | Points |
|---|---|---|
| ≥ 85 | A | 4.0 |
| ≥ 75 | B+ | 3.5 |
| ≥ 70 | B | 3.0 |
| ≥ 65 | C+ | 2.5 |
| ≥ 60 | C | 2.0 |
| ≥ 55 | D+ | 1.5 |
| ≥ 50 | D | 1.0 |
| < 50 | F | 0.0 |

The `coursework_max`/`final_max` on `CourseOffering` control the **input max** in forms, but letter thresholds use the absolute total regardless of distribution.

### Grade Publishing

Approval and publishing are separate:
- **Approved** — admin confirms grade is correct (required before publishing)
- **Midterm published** (`midterm_published_at` set) — students see coursework grade only
- **Final published** (`published_at` set) — students see full grade; GPA recalculates; warnings fire

Publishing can be done per course offering (individual) or per semester (bulk).

Edit-after-publish revert rules:
- Edit coursework → clears BOTH publish timestamps
- Edit final only → clears only `published_at`

### GPA Calculation

`GradeCalculationService::recalculateStudentGPA(Student $student)`:
1. Skips non-Active students (GPA frozen)
2. Collects published grades from active enrollments grouped by course (latest grade wins — supports retakes)
3. Adds all TransferCourse entries
4. Computes: `Σ(points × credits) / Σ(credits)`, stored as decimal(3,2)

---

## Course Retakes

Any course passed with grade below A (B+, B, C+, C, D+, D) is eligible for retake. F grades trigger warnings (see below) and are also retakeable.

Rules:
- Retake requests use the survey infrastructure: `CourseSurvey.is_retake = true`
- Retakes count toward the 4-enrollment-per-semester cap
- No per-semester limit on how many courses can be retaken
- GPA uses the **latest grade** per course (old grade stays in enrollment history)
- A student can retake the same course multiple times

---

## Warning System

Two independent warning types, each escalating to dismissal on second occurrence.

### Type 1: Course Failure Warning

**Trigger:** Admin approves a grade with `letter = F`

| Event | Action |
|---|---|
| 1st F on course X | Create `course_failure` warning (course_id=X, is_active=true); warnings_count++ |
| Student passes course X later | Warning resolved (is_active=false, resolved_at set); warnings_count-- |
| 2nd F on course X (while warning active) | Student **dismissed** |

After dismissal for course failure:
- `Student.dismissed_at` set
- `Student.dismissal_type = 'course_failure'`
- `Student.dismissal_course_id = X`
- `StudentObserver` cascades all active enrollments to `Withdrawn`

### Type 2: Consecutive Inactive Warning

**Trigger:** Admin sets student `status = Inactive` (منقطع)

| Event | Action |
|---|---|
| 1st consecutive inactive semester | Create `consecutive_inactive` warning; warnings_count++ |
| Student becomes Active again | All inactive warnings cleared (warnings_count reset) |
| 2nd consecutive inactive semester | Student **dismissed** |
| Normal semester between inactives | Warning count resets — must be consecutive |

The "consecutive" check runs when `SemesterObserver` fires on semester activation — all currently-Inactive students are evaluated.

### Un-approving a Grade (Reversal)

If an admin un-approves an F grade (sets status back to Draft/PendingApproval):
- Warning is resolved (`is_active=false`, `resolved_at` set)
- `warnings_count` decremented
- If the F was the trigger for dismissal → dismissal reversed (`dismissed_at` cleared, status returns to Active)

---

## Dismissal & Exceptional Requests

### Dismissal State

`StudentStatus::Dismissed` (مفصول) — same behavior as all non-Active statuses:
- `canPerformActions()` returns false
- `StudentObserver` cascades all active enrollments to `Withdrawn`
- GPA frozen

### Exceptional Request (طلب فرصة استثنائية)

Dismissed students can submit an exceptional request:
- Submit reason text
- Admin can: **Accept** / **Reject** / **Summon** (send a message requiring the student to visit)

**Accept for course-failure dismissal:**
- Student returns to `Active`
- New warning created with `has_exceptional_chance = true`
- Old warnings for that course resolved
- If student fails the same course again → **permanently dismissed** (`can_reapply = false`)

**Accept for consecutive-inactive dismissal:**
- Student returns to `Active`
- ALL inactive warnings cleared (fresh start, 0 warnings)

**Permanent ban:**
`can_reapply = false` on the student → exceptional request form is permanently blocked with a "permanent ban" message.

---

## Student Status Flow Diagram

```
Application ──approved──► Active (مستمر)
                               │
                    ┌──────────┼──────────┐
                    │          │          │
              (suspension    (admin     (admin
               approved)     sets)      sets)
                    │          │          │
                    ▼          ▼          ▼
                 OnHold    Inactive  Transferred
               (قيده       (منقطع)   (منتقل)
               موقوف)
                    │
                    │  (2nd consecutive
                    │   inactive semester)
                    ▼
                Dismissed (مفصول)
                    │
                    │  (exceptional
                    │   request accepted)
                    ▼
                 Active (again, with warning)

Active ──(thesis + discussion + clearance)──► Graduated (متخرج)
```

Any status → Active transition triggered by admin manually also runs `rescindActiveSemesterSuspensionIfOverridden` to clean up dangling approved suspensions.

---

## Thesis Eligibility Rules

`Student::isEligibleForThesis()` returns `true` only when ALL four conditions are met:

1. **All general courses completed** — every course linked to the student's specialization with `type = general` in `course_specialization` must have an approved grade with letter ≠ F, OR a passing `TransferCourse` entry
2. **All specialized courses completed** — same logic with `type = specialized`
3. **At least 2 elective courses completed** — any 2 courses with `type = elective` from the specialization
4. **GPA ≥ 3.0**

"Completed" means: approved `Grade` with `letter ≠ F` (any passing letter from D to A), OR a `TransferCourse` entry with non-F letter.

The student portal shows a per-type breakdown card showing progress toward each condition.

---

## Thesis Registration Process (3-Step Admin)

```
Student submits ThesisRegistration (status=pending)
  │
  ▼
Admin: "طباعة للوزارة" → status=printed, printed_at set
  │  (accept/reject buttons only appear AFTER printing)
  │
  ▼
Admin: Accept → re-validates eligibility → status=approved
  OR
Admin: Reject (with reason) → status=rejected → student can resubmit
```

Only the latest registration is "live" — history is preserved but admin only sees pending/printed rows as actionable.

---

## Discussion (مناقشة) Pipeline — 4 Phases

Triggered by: `ThesisRegistration.status = approved`

```
Phase 1 — Initial Review
  Student requests discussion → DiscussionRequest created (status=pending)
  Admin: Accept (set date) → status=scheduled
  Admin: Reject (reason) → status=rejected → student can resubmit

Phase 2 — The Discussion
  Discussion date is advisory (no system enforcement)
  Admin: Mark "تمت المناقشة" → status=discussed

Phase 3 — Result
  Admin: Accept → status=discussion_approved
  Admin: Accept with Edits → status=accepted_with_edits
  Admin: Reject (reason) → status=discussion_rejected → student can resubmit
  
  [If accepted_with_edits:]
  Admin: Mark "تم التعديلات" → status=discussion_approved

Phase 4 — Binding & Submission
  Admin: Mark "تم التجليد والتسليم" → status=completed, completed_at set
  → CLEARANCE UNLOCKS
```

Admin sees all 8 status labels. Student sees simplified:
- pending/scheduled/discussed/accepted_with_edits → "قيد المراجعة" (with phase-specific subtext)
- rejected/discussion_rejected → "مرفوض" + reason
- discussion_approved → "تمت الموافقة النهائية"
- completed → "مكتمل"

---

## Clearance Gate

`ClearanceRequest` submission is gated by `isDiscussionCompleted()`:
```php
public function isDiscussionCompleted(): bool
{
    return $this->discussionRequests()
        ->where('status', DiscussionStatus::Completed)
        ->exists();
}
```

Before discussion completion → portal shows blocked banner: "لا يمكن تقديم طلب الإخلاء إلا بعد إتمام المناقشة والتجليد".

After admin issues clearance (`ClearanceRequest.status = issued`):
- Transcript PDF generated via `PdfService`
- Clearance certificate PDF generated
- `notifyClearanceIssued` notification fires
- Student can download both from the portal

---

## Semester Management Rules

### Single Active Semester

The system assumes exactly one active semester at a time. `Semester::where('is_active', true)->first()` is the canonical "current term" lookup. There is no DB constraint preventing two active semesters — admin must ensure only one is active.

### Survey → Offering Independence

Adding a course to the `semester_course` pivot (survey menu) does NOT create a `CourseOffering`. The admin must explicitly create offerings. A course can be in the survey but have no offering (students express demand; admin may or may not create an offering based on demand).

### Semester Archive

When admin activates a new semester:
1. `SemesterObserver::updated` fires
2. All students with `status = Inactive` are checked for consecutive semesters
3. Any with a prior-semester inactive warning → dismissed (2nd consecutive)
4. Survey and add/drop windows are semester-scoped

---

## Enrollment Number Generation

`EnrollmentNumberService::generate(Student $student, Semester $semester, bool $isTransfer)`:

1. Builds prefix: `{YY}{SpecCode}{SessionFlag}` where SessionFlag distinguishes regular vs transfer
2. Queries `SELECT MAX(enrollment_no) WHERE enrollment_no LIKE '{prefix}%'` (including soft-deleted students via `withTrashed`)
3. Increments seq and saves to `Student.enrollment_no`

**Race condition note:** Under concurrent approvals, two students can read the same MAX and both try seq = MAX+1. The DB unique constraint rejects the second. In practice approvals happen one at a time in the UI, so this is rare.
