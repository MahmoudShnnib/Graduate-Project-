# The Admin Panel Explained

The admin panel is at `/admin` and is used by super admins, secretaries, and faculty. Faculty see a very limited version — just their own courses.

---

## Dashboard

Shows 4 widgets:
- **Stats** — total students (active vs all), pending applications, open offerings
- **Applications over time** — chart
- **Students per specialization** — chart
- **Settings widget** — editable system settings (college name, office name, director name, dean name, Arabic + English variants). These get printed on PDF documents. Settings are saved in a `settings` DB table and read via `Setting::get('key')`.

---

## Applications (`/admin/applications`)

Lists all applications sorted in 3 tiers: pending on top, initially_approved middle, accepted/rejected bottom.

**What the view page shows:** all applicant data, the uploaded bachelor certificate with a preview, and a photo. Actions in the header:

- **Set exam date** — picker modal
- **Initially approve** — preliminary acceptance step
- **Approve** — runs `ApproveApplication::handle()` which:
  1. Creates a `User` (password comes from what the applicant set on the form)
  2. Creates a `Student` with `status=active`
  3. Generates enrollment number via `EnrollmentNumberService`
  4. Syncs both `users.role = 'student'` and the Spatie role
  5. Sends welcome notification (email + WhatsApp if configured)
- **Reject** — stores rejection; no User/Student created; applicant can reapply later

**Duplicate protection:** only one active application per person at a time. Once accepted, can never reapply. After rejection, reapplication is allowed.

---

## Students (`/admin/students`)

List page with filters for status, specialization, transfer flag, plus search by name or enrollment number.

**View page** follows the Figma view-page design pattern:
- Hero card at top: name, enrollment number, specialization, GPA, status badge, registration semester
- Infolist sections below: personal info, academic info, transfer courses (if any), activity timeline (last 20 events from all request types combined)

**Header actions:**
- Change status — modal with dropdown and consequence description that updates live as you pick a target status
- Manage transfer courses (معادلة مواد) — slide-over with a Repeater for add/edit/delete
- View grades
- Print options (student letter, provisional grades)

**Edit page** — edits student profile. Specialization change is allowed but doesn't cascade old enrollments. Transfer courses slide-over is also accessible here.

---

## Grades (`/admin/grades`)

Shown per course offering. Faculty see only their own offerings; admin/secretary see all.

The grade table shows every enrolled student with their grade entry. Faculty fill in `coursework_grade` and `final_grade`. The system immediately auto-computes total/letter/points.

Grade status flow: `Draft → PendingApproval → Approved`.

Two publish actions on the offering's view page:
- **Publish midterm** — sets `midterm_published_at` on all approved grades → students see coursework only
- **Publish final grades** — sets `published_at` → GPA recalculates, warnings fire for any F grades

Editing a published grade resets the timestamps (so students don't see a half-edited grade).

---

## Semesters (`/admin/semesters`)

Create a semester, activate it (one at a time), set the survey open/closed, set add/drop window dates.

The RelationManager on the semester view page manages CourseOfferings directly.

Survey course management: a separate action on the semester lets admin choose which courses from the catalog appear in students' surveys. Adding to this list does NOT create course offerings — that's a separate step.

When a new semester is activated, `SemesterObserver` fires and checks all currently-Inactive students for consecutive dismissal (2nd consecutive inactive = dismissed).

---

## Course Offerings (`/admin/course-offerings`)

Each offering links a Course to a Semester to a faculty User. `is_open` controls whether enrollments are possible. `coursework_max + final_max` must sum to 100 (defaults to 40/60).

**Enroll students page** (`/admin/course-offerings/{id}/enroll`):
Two ways to add students:
- "إضافة طالب" — search for any active student
- "طلبوا المادة" — shows students who requested this course in their survey

Both routes enforce: 4-enrollment cap, specialization match, offering must be open, student must be Active.

**Closing an offering:** confirmation modal warns that all students will be withdrawn. Confirming flips `is_open=false` which triggers `CourseOfferingObserver` to cascade all active enrollments to `Withdrawn` and recalculate GPA.

---

## Course Survey Results (`/admin/courses-survey-results`)

Custom admin page (not a Resource). Shows two sections:

**Demand table** — per course: total survey requests + breakdown by preference priority (1st choice, 2nd choice, etc.). Lets admin see which courses are most wanted.

**Students table** — one row per student. Clicking a row goes to `/admin/student-survey/{student}/{semester}` where admin can approve or reject each individual survey request. Approving runs `EnrollStudentInOffering`. Rejecting sets `rejected_at`.

The checkbox selection on the demand table feeds a "فتح المقررات المختارة" button that opens a modal to bulk-create CourseOffering rows with faculty assignment.

---

## Add/Drop Requests (`/admin/add-drop-requests`)

Grouped by student — one row per student shows their name, pending count, and a "مكتمل" / "قيد المراجعة" status. Clicking a row drills into that student's individual requests.

The drill-in page (`/admin/student-add-drop/{student}/{semester}`) shows a progress banner and a table of requests. Approve/reject each one. Approving an add runs `EnrollStudentInOffering` with all guards. Approving a drop runs `DropStudentFromOffering`.

---

## Suspension Requests (`/admin/suspension-requests`)

List and view pages. View page shows the student info, which semester they want to suspend for, the reason, and the uploaded form PDF.

Approve → student status becomes `on_hold`, all active enrollments withdrawn (via StudentObserver).
Reject → student notified, can resubmit.

If admin later sets the student back to `Active` via the student's Change Status action, the code automatically reverts the approved suspension to `Rejected` so it doesn't conflict.

---

## Thesis Registrations (`/admin/thesis-registrations`)

Tabs: pending / printed / approved / rejected.

Admin workflow for each thesis:
1. Click "طباعة للوزارة" → `status = printed` → `printed_at` set. Accept/reject buttons only appear AFTER this step.
2. Accept → re-validates eligibility → `status = approved`, student notified.
3. Reject → `status = rejected`, reason stored, student can resubmit.

External supervisors are managed via a slide-over Repeater on this page.

---

## Discussion Requests (`/admin/discussion-requests`)

Shows only the latest request per student. Admin goes through 4 phases:

| Student's current status | What admin can do |
|---|---|
| pending | Accept (set date) or Reject (with reason) |
| scheduled | Mark "تمت المناقشة" |
| discussed | Accept / Accept with Edits / Reject |
| accepted_with_edits | Mark "تم التعديلات" |
| discussion_approved | Mark "تم التجليد والتسليم" → completed |

When status reaches `completed`, the student's clearance page unlocks.

---

## Users (`/admin/users`)

Super admin creates/edits/deactivates admin users. Role change automatically syncs both `users.role` and the Spatie role. `is_active=false` prevents login.

---

## Audit Logs (`/admin/audit-logs`)

Read-only. Shows every change to watched fields on Grade, Application, Student, StudentEnrollment, ThesisRegistration models. Each row has old values and new values (JSON), plus who changed it and from what IP. Arabic status labels are resolved from enum `label()` methods in the view.

---

## Exceptional Opportunities (`/admin/exceptional-opportunities-page`)

For dismissed students. Shows two tables: students who submitted exceptional requests + dismissed students who haven't submitted anything yet.

Click a student → `/admin/exceptional-opportunities-page/{student}` — admin can:
- **Accept** — student returns to Active. For course-failure dismissal: new warning created with `has_exceptional_chance=true`. For consecutive-inactive: all inactive warnings cleared.
- **Reject** — student stays dismissed, can resubmit.
- **Summon** — sends a message telling the student to come in. Stores `summon_message` and `summon_sent_at`.

If a student who got an exceptional chance fails the same course again → permanently dismissed (`can_reapply=false`). They can never submit another exceptional request.
