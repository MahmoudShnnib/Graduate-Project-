---
date: 2026-04-27
tags: [daily, warnings, dismissal, exceptional-requests, status, observer, service]
related: [[../decisions/2026-04-27-warning-system-and-dismissal]], [[../decisions/2026-04-23-student-status-restructure]]
---

# Task Session: Warnings Rework, Dismissal, Exceptional Requests

## Task 1: Warning System Rework + Dismissed Status

**Problem:** `warnings_count` was a manual integer with no automated logic. No warning records, no observer triggers, no escalation to dismissal.

### What was built

**New data model:**
- `student_warnings` table — tracks individual warnings per student, per type (course_failure / consecutive_inactive), with course_id, semester_id, is_active, resolved_at, has_exceptional_chance
- `dismissed_at`, `dismissal_type`, `dismissal_course_id` columns on students

**New enum:** `WarningType` (course_failure, consecutive_inactive) with Arabic labels
**New model:** `StudentWarning` with relationships to Student, Course, Semester
**New service:** `WarningService` — core logic:
- `evaluateGradeApproval()` — called from GradeObserver on grade approval
- `processInactiveWarning()` — called from StudentObserver + SemesterObserver
- `clearInactiveWarnings()` — called when student leaves Inactive status
- `syncWarningsCount()` — updates denormalized count on Student

**New status:** `StudentStatus::Dismissed = 'dismissed'` (مفصول) with danger color

**Updated observers:**
- `GradeObserver` — handles both `created` and `updated` events (for direct-Approved grades AND status transitions)
- `StudentObserver` — processes inactive warnings on status change, clears on de-inactivation
- `SemesterObserver` (new) — processes all Inactive students when a semester is activated

**Type 1 flow (course failure):**
1. Grade approved with F → warning created for (student, course)
2. Same course passed later → warning resolved
3. Same course failed again → DISMISSED

**Type 2 flow (consecutive inactive):**
1. Student set to Inactive → warning created for current semester
2. New semester activated while still Inactive → 2nd warning → DISMISSED
3. Student becomes Active → all inactive warnings cleared

### Files created (8)
- `app/Enums/WarningType.php`
- `app/Models/StudentWarning.php`
- `app/Services/WarningService.php`
- `app/Observers/SemesterObserver.php`
- `tests/Feature/WarningSystemTest.php` (8 specs)
- 2 migrations
- `_brain/decisions/2026-04-27-warning-system-and-dismissal.md`

### Files updated (14)
- `StudentStatus.php` — added Dismissed case
- `Student.php` — new fillable, casts, relationships
- `GradeObserver.php` — warning evaluation on grade approval
- `StudentObserver.php` — inactive warning logic
- `AppServiceProvider.php` — registered SemesterObserver
- `status-block-banner.blade.php` — dismissed banner
- `StatsOverviewWidget.php` — dismissed count
- `EditStudent.php` — dismissed warning text
- `AddDropRequestResource.php` — dismissed color
- `StudentResource.php` — warnings_count read-only
- `StudentFactory.php` — dismissed() state
- `StudentStatusGateTest.php` — Dismissed in all datasets

---

## Task 2: Exceptional Opportunities + Requests

**Problem:** Dismissed students need a way to request reinstatement with conditional acceptance logic.

### What was built

**New data model:**
- `exceptional_requests` table — student_id, reason, status (pending/accepted/rejected/summoned), decided_by, decided_at, summon_message, summon_sent_at, can_reapply
- `has_exceptional_chance` boolean on `student_warnings` — marks warnings created after exceptional acceptance

**New enum:** `ExceptionalRequestStatus` (pending, accepted, rejected, summoned) with labels + colors
**New model:** `ExceptionalRequest`

**Admin page:** `/admin/exceptional-opportunities` — "فرص استثنائية"
- Lists all dismissed students with dismissal reason + date
- Shows latest exceptional request status per student
- 3 action buttons per pending/summoned request:
  - **قبول** — reactivates student, creates `has_exceptional_chance` warning for course failure dismissals
  - **رفض** — student stays dismissed
  - **استدعاء** — sends a message to student dashboard (once per request)

**Student portal page:** `/portal/exceptional-request-page` — "طلب استثنائي"
- Only visible to dismissed students (shouldRegisterNavigation checks status)
- Form to submit a request with reason
- Shows pending/summoned/rejected status
- Shows summon message if summoned
- Shows permanent ban message if `can_reapply = false`
- Request history section

**Dashboard summon banner:**
- Urgent danger-styled banner at top of student dashboard
- Shows when admin has summoned the student via exceptional request
- Displays the admin's message + timestamp

### Acceptance logic
**Course failure dismissal → accept:**
1. Resolve all old warnings for the course
2. Create new warning with `has_exceptional_chance = true`
3. Set student to Active, clear dismissal fields
4. If student fails the same course AGAIN → dismissed + `can_reapply = false` (permanent ban)

**Consecutive inactive dismissal → accept:**
1. Clear all inactive warnings (fresh start, 0 warnings)
2. Set student to Active, clear dismissal fields

### Files created (8)
- `app/Enums/ExceptionalRequestStatus.php`
- `app/Models/ExceptionalRequest.php`
- `app/Filament/Pages/ExceptionalOpportunitiesPage.php`
- `app/Filament/Student/Pages/ExceptionalRequestPage.php`
- `resources/views/filament/pages/exceptional-opportunities.blade.php`
- `resources/views/filament/student/pages/exceptional-request.blade.php`
- `tests/Feature/ExceptionalRequestTest.php` (6 specs)
- 2 migrations

### Files updated (4)
- `Student.php` — exceptionalRequests relationship
- `StudentDashboard.php` — getActiveSummon() method
- `dashboard.blade.php` — summon banner
- `WarningService.php` — has_exceptional_chance ban logic

---

## Session Stats
- **Tests:** 137 passed (277 assertions) — up from 117
- **New test specs:** 14 (8 warning + 6 exceptional)
- **New migrations:** 4
- **New models:** 2 (StudentWarning, ExceptionalRequest)
- **New enums:** 2 (WarningType, ExceptionalRequestStatus)
- **New services:** 1 (WarningService)
- **New observers:** 1 (SemesterObserver)
- **New admin pages:** 1 (ExceptionalOpportunitiesPage)
- **New student pages:** 1 (ExceptionalRequestPage)
- **Pint clean**
