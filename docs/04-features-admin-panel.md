# Graduate Studies System — Admin Panel Features

The admin panel is a **Filament v5** application at `/admin`. It is shared by three roles: **super admin**, **secretary**, and **faculty**. Each role has a different view of the navigation and different permissions enforced by Filament Shield.

---

## Admin Panel Navigation Structure

```
الرئيسية (Dashboard)
  ├─ Stats overview widget
  ├─ Applications over time chart
  ├─ Students per specialization chart
  └─ Settings widget (college name, dean, director, etc.)

إدارة الطلاب
  ├─ الطلبات (Applications)
  ├─ الطلاب (Students)
  ├─ التسجيلات (Student Enrollments) — read-only aggregate
  └─ الدرجات (Grades)

الإعدادات الأكاديمية
  ├─ الفصول الدراسية (Semesters)
  ├─ المقررات الدراسية (Courses)
  ├─ التخصصات (Specializations)
  └─ المقررات المفتوحة (Course Offerings)

هيئة التدريس
  └─ موادي (My Courses — faculty only)

الطلبات والإجراءات
  ├─ طلبات الإضافة والحذف (Add/Drop Requests)
  ├─ طلبات التوقف (Suspension Requests)
  ├─ تسجيلات الرسائل (Thesis Registrations)
  └─ طلبات المناقشة (Discussion Requests)

أخرى
  ├─ المستخدمون (Users)
  ├─ سجلات التدقيق (Audit Logs)
  └─ الخريجون (Graduates) — read-only

Custom Pages (no navigation registration):
  ├─ /admin/courses-survey-results
  ├─ /admin/reports
  ├─ /admin/exam-list
  ├─ /admin/exceptional-opportunities-page
  ├─ /admin/student-survey/{student}/{semester}
  └─ /admin/student-add-drop/{student}/{semester}
```

---

## 1. Dashboard

**URL:** `/admin`

Four widgets on the dashboard:
1. **StatsOverviewWidget** — total students (active/all), pending applications count, open course offerings count
2. **ApplicationsOverTimeWidget** — chart of applications per month
3. **StudentsPerSpecializationWidget** — bar/pie chart by specialization
4. **SettingsWidget** — editable settings for system-wide values (college name, office name, director name, dean name, Arabic name, English name). Settings stored in `settings` DB table; read at print time via `Setting::get('key')`.

---

## 2. Applications Resource (`/admin/applications`)

**Who:** super admin, secretary

### List page
- 3-tier sort: pending on top → initially_approved → accepted/rejected at bottom; `created_at desc` within each tier
- Filters: status, specialization, exam date
- Per-row actions: view detail, approve, reject, set exam date

### View page (detail)
- Full applicant information in infolist: personal data, academic background, national ID, phone, uploaded bachelor certificate (with preview modal)
- Actions in header:
  - **Set exam date** — calendar picker modal
  - **Initially approve** — preliminary acceptance
  - **Approve** — triggers `ApproveApplication::handle()`:
    1. Creates `User` with hashed password from intake
    2. Creates `Student` with `status=Active`
    3. Generates enrollment number via `EnrollmentNumberService`
    4. Assigns Spatie role + sets `users.role`
    5. Sends welcome notification (email + optional WhatsApp)
  - **Reject** — stores rejection; allows reapplication

### Duplicate application rules
- Only 1 active application allowed at a time (blocked if pending or initially_approved)
- Permanently blocked if already accepted (student exists)
- Allowed after rejection (previous kept as historical record)

---

## 3. Students Resource (`/admin/students`)

**Who:** super admin, secretary

### List page
- Columns: enrollment number, full name, specialization, semester, transfer flag, status badge, GPA
- Filters: status (6 values), specialization, transfer flag
- Search: by name, enrollment number
- Export functionality

### View page (detail — Figma view-page pattern)
**Hero card** at top: name, enrollment number, specialization, GPA, status badge, registration semester

**Infolist sections:**
- Personal information: birth date, national ID, phone, email
- Academic information: GPA, warnings count, specialization
- Transfer courses section (if `is_transfer=true`): list of معادلة courses with grades
- Activity timeline: last 20 combined events from CourseSurvey, AddDrop, Suspension, Thesis, Clearance, AuditLog

**Header actions:**
- Change status (modal with confirmation + consequence description per target status)
- Manage transfer courses (slide-over Repeater)
- View grades
- Print student letter (ministry-style PDF)
- Print provisional grades

### Edit page
- Student profile fields + specialization change
- **Transfer courses slide-over:** add/edit/delete معادلة rows per specialization; triggers GPA recalculation

---

## 4. Grades Resource (`/admin/grades`)

**Who:** super admin, secretary, faculty (own courses only)

### View by course offering (`/admin/grades/{offering_id}`)
- Table of all enrolled students with their grade entry rows
- Faculty enters `coursework_grade` and `final_grade`; total/letter/points auto-computed
- Status workflow:
  1. Faculty enters grades → status = `Draft`
  2. Faculty submits → status = `PendingApproval`
  3. Admin/secretary approves → status = `Approved`
- Header actions per offering:
  - **Publish midterm** — sets `midterm_published_at` on all approved grades → students see coursework only
  - **Publish final grades** — sets `published_at` → GPA recalculates + warnings fire

### Grade editing behavior after publish
- Edit `coursework_grade` → clears BOTH `midterm_published_at` AND `published_at`
- Edit `final_grade` → clears only `published_at` (midterm stays visible)

---

## 5. Semesters Resource (`/admin/semesters`)

**Who:** super admin, secretary

### Semester setup flow
1. Create semester (name, season, year, dates, add/drop window)
2. **Activate** — sets `is_active=true`; system assumes one active at a time
3. **Survey actions:**
   - Open survey → sets `survey_open=true`
   - Edit survey courses → manage `semester_course` pivot (which courses students can request)
4. **Course offerings** — managed via RelationManager on the semester view page

### Semester archive
When a new semester is activated, the previous one is archived. `SemesterObserver` fires on activation and processes all `Inactive` students for consecutive-semester dismissal detection.

---

## 6. Course Offerings Resource (`/admin/course-offerings`)

**Who:** super admin, secretary

- Create offering: choose course, active semester, faculty user, `is_open`, `coursework_max/final_max` (must sum to 100)
- Close offering: confirmation modal showing "all students will be withdrawn"; triggers `CourseOfferingObserver` → cascades to `Withdrawn`
- **Enroll students page** (`/admin/course-offerings/{id}/enroll`):
  - "إضافة طالب" — search and select any active student not already enrolled
  - "طلبوا المادة" — students who requested this course in their survey
  - Enforcement at action layer: cap (4 per semester), specialization match, closed offering, suspended student

---

## 7. Course Survey Results (`/admin/courses-survey-results`)

A custom merged admin page (not a Resource):
- **Demand table** — per course: total requests + breakdown by priority (1st, 2nd, 3rd, 4th choice)
- **Students table** — one row per student; click row → drill-in to individual student survey view
- Checkbox-select courses → "فتح المقررات المختارة" modal → bulk create CourseOffering rows with faculty assignment
- Per-student drill-in (`/admin/student-survey/{student}/{semester}`): approve/reject each course request individually; rejecting sets `rejected_at` on the CourseSurvey row

---

## 8. Add/Drop Requests (`/admin/add-drop-requests`)

Grouped view — one row per student:
- Columns: student name, enrollment number, specialization, pending count, status badge (مكتمل / قيد المراجعة)
- Click row → drill-in to student's requests (`/admin/student-add-drop/{student}/{semester}`)

**Drill-in page:**
- Progress banner (how many approved/rejected/pending)
- Table of individual requests with approve/reject actions
- Approve add → runs `EnrollStudentInOffering` (all guards apply)
- Approve drop → runs `DropStudentFromOffering` → sets `StudentEnrollment.status = Dropped`
- Reject → stores rejection; student notified

---

## 9. Suspension Requests (`/admin/suspension-requests`)

List and view pages for `SuspensionRequest` rows:
- View page shows: student info, semester requested, reason, uploaded suspension form PDF
- **Approve action:**
  - Sets `SuspensionRequest.status = approved`
  - Flips `Student.status = OnHold`
  - `StudentObserver` cascades all active enrollments to `Withdrawn`
  - GPA frozen (service skips non-Active students)
- **Reject action:** stores rejection reason; student can resubmit
- When admin later sets student back to Active, `EditStudent::rescindActiveSemesterSuspensionIfOverridden` flips the approved suspension to `Rejected` automatically

---

## 10. Thesis Registrations (`/admin/thesis-registrations`)

Tabs: pending / printed / approved / rejected

**View page** shows all thesis registration data:
- Student info, supervisor (internal faculty or external), Arabic + English titles, proposal PDF download
- **Print for ministry** button → `status = printed` → `printed_at` set
- After printing, **Approve / Reject** buttons appear
- Approve re-validates eligibility; approved → `status = approved`, student notified
- Reject → stores reason; student notified; student can resubmit

---

## 11. Discussion Requests (`/admin/discussion-requests`)

Shows latest request per student (`MAX(id) GROUP BY student_id`).

**4-phase admin workflow:**

| Current status | Available admin actions |
|---|---|
| `pending` | Accept (set discussion date) / Reject (with reason) |
| `scheduled` | Mark "تمت المناقشة" |
| `discussed` | Accept / Accept with Edits / Reject (with reason) |
| `accepted_with_edits` | Mark "تم التعديلات" → becomes `discussion_approved` |
| `discussion_approved` | Mark "تم التجليد والتسليم" → `completed` |
| `completed` | Read-only; clearance unlocks for student |

---

## 12. Users Resource (`/admin/users`)

**Who:** super admin only (secretary has limited view)

- Create/edit/deactivate admin users (super admin, secretary, faculty)
- Role assignment keeps `users.role` and Spatie role in sync
- `is_active=false` prevents panel login

---

## 13. Audit Logs (`/admin/audit-logs`)

Read-only list and view pages for `AuditLog`:
- Columns: when, who, what changed, which model
- View page shows: old values → new values (JSON diff with Arabic status labels via `tryFrom()->label()`)
- Useful for tracking application status changes, grade approvals, student status changes

---

## 14. Graduates (`/admin/graduates`)

Read-only view of students with `status = Graduated`. Shows their full academic record. No actions.

---

## 15. Reports Page (`/admin/reports`)

Custom page with downloadable reports:
- Applications report (by date range, status)
- Students report (by specialization, status)
- Grades report (by offering, semester)

---

## 16. Exam List Page (`/admin/exam-list`)

Custom page showing scheduled exam dates across all applications. Used for printing exam schedules.

---

## 17. Exceptional Opportunities (`/admin/exceptional-opportunities-page`)

For managing dismissed students:
- **Main page** — two tables: pending exceptional requests + dismissed students without a request
- Click row → `/admin/exceptional-opportunities-page/{student}` (separate URL for browser history)
- **Per-student detail:**
  - Accept → flips student to Active, creates new warning with `has_exceptional_chance=true`
  - Reject → student stays dismissed, can resubmit
  - Summon → sets `summon_message` + `summon_sent_at`; once-per-request enforcement

---

## Print Endpoints

All print routes are under `/admin/...` protected by `auth` middleware.

| Document | URL Pattern | Format |
|---|---|---|
| Student letter (خطاب للطالب) | `/admin/students/{id}/print-student-letter` | mPDF (inline) |
| Provisional grades (درجات مبدئية) | `/admin/students/{id}/print-provisional-grades` | mPDF (inline) |
| Course student roster (قائمة الطلاب) | `/admin/course-offerings/{id}/print-students` | Blade → browser print |
| Exam attendance sheet | `/admin/course-offerings/{id}/print-exam-attendance` | mPDF (inline) |
| Course results sheet | `/admin/course-offerings/{id}/print-course-results` | mPDF (inline) |
| Graduation certificate | `/admin/students/{id}/print-certificate` | mPDF (inline) |
