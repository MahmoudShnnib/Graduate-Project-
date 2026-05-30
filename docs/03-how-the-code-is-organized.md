# How the Code is Organized

The codebase follows a clear separation: **UI shells → Actions → Models → Observers → Services**. Controllers and Filament pages are thin. Business logic lives in Actions and Services. Side effects (cascades, GPA recalculation, audit logging) are driven by Eloquent Observers.

---

## The Main Folders

**`app/Actions/`** — each file does exactly one business operation. Most are `__invoke`-able and wrap multi-row writes in `DB::transaction`. Examples:
- `ApproveApplication.php` — creates User + Student + enrollment number + notification in one transaction
- `CreateApplication.php` — public form data → Application row + tracking code
- `Enrollment/EnrollStudentInOffering.php` — idempotent upsert of a StudentEnrollment + marks matching CourseSurvey as processed
- `Enrollment/DropStudentFromOffering.php` — sets enrollment status to Dropped or Withdrawn

**`app/Services/`** — stateless/reusable logic or external integrations. Called from Actions, not from pages directly:
- `GradeCalculationService` — GPA math
- `EnrollmentNumberService` — generates the enrollment number format
- `NotificationService` — central dispatcher for all notifications
- `AuditService` — writes AuditLog rows
- `PdfService` — DomPDF-based PDF generation (transcripts, forms)
- `WhatsAppService` — Qonvo HTTP client with Libyan phone normalization
- `TrackingCodeService` — generates APP-YYYY-NNNN codes

**`app/Observers/`** — Eloquent event listeners registered once in `AppServiceProvider::boot()`. They run automatically on model writes:
- `StudentObserver` — when status changes to non-Active, withdraws all active enrollments
- `GradeObserver` — when `published_at` is set, triggers GPA recalculation; when F grade is approved, triggers warning evaluation
- `CourseOfferingObserver` — when `is_open` flips true→false, cascades all enrollments to Withdrawn
- `AuditObserver` — on any update to watched models, writes an AuditLog row if watched fields changed
- `SemesterObserver` — when a semester is activated, checks all Inactive students for consecutive dismissal

**`app/Exceptions/`** — typed domain exceptions thrown by Actions:
- `EnrollmentCapReachedException` — student already has 4 enrollments this semester
- `CourseOutsideSpecializationException` — course not in student's specialization
- `StudentSuspendedInSemesterException` — student has an approved suspension
- `CourseOfferingClosedException` — offering is closed
- `StudentNotActiveException` — student status is not Active

Every enrollment caller catches all 4 exceptions and shows Arabic Filament notifications.

**`app/Filament/Resources/`** — 14 admin Resources (Applications, Students, Grades, CourseOfferings, Semesters, Courses, Specializations, AddDropRequests, SuspensionRequests, ThesisRegistrations, DiscussionRequests, AuditLogs, Users, Graduates). Each has a folder with the Resource class + a `Pages/` subfolder.

**`app/Filament/Pages/`** — custom admin pages that aren't tied to a single model's CRUD (ExamList, CoursesSurveyResults, ReportsPage, StudentSurveyView, StudentAddDropView, ExceptionalOpportunitiesPage).

**`app/Filament/Student/Pages/`** — 9 hand-rolled Livewire pages for the student portal (Dashboard, StudentCourses, CourseTree, CourseSurvey, AddDrop, Grades, SuspensionRequest, ThesisRegistration, Clearance).

**`app/Filament/Widgets/`** — 4 dashboard widgets (StatsOverview, ApplicationsOverTime, StudentsPerSpecialization, SettingsWidget).

**`app/Http/Controllers/`** — only 3 controllers:
- `RegistrationController` — the big public form with ~130 lines of inline Arabic validation
- `ApplicationTrackingController` — tracking code + national ID lookup
- `PrintController` — mPDF-based ministry print templates

**`app/Enums/`** — backed string enums, each with an Arabic `label()` method. All status values live here.

**`app/Rules/`** — custom Livewire validation rules: `ArabicOnly` and `MinArabicWords`.

**`app/Notifications/`** — 9 notification classes + `HasWhatsAppChannel` trait that decides whether to add WhatsApp to `via()`.

**`app/Providers/AppServiceProvider.php`** — critical file. Registers `Gate::before` for super_admin bypass AND registers all observers.

**`app/Providers/Filament/AdminPanelProvider.php`** — configures the `/admin` panel.

**`app/Providers/Filament/StudentPanelProvider.php`** — configures the `/portal` panel, sets Arabic brand name, adds `EnsureStudentRole` middleware.

**`bootstrap/app.php`** — registers middleware, routes, and the exception handler that redirects unauthenticated `/portal/*` access to `/portal/login`.

---

## The Observer Pattern in Detail

Every important side effect in this system is triggered by an Observer, not by the caller. This means the caller (Filament page, Action) doesn't need to know about downstream effects.

**When a student's status changes to non-Active:**
`StudentObserver::updated` fires → calls `DropStudentFromOffering` on every active enrollment → status becomes `Withdrawn`. The GPA freeze also happens here because `GradeCalculationService` skips non-Active students.

**When a grade's `published_at` is set:**
`GradeObserver::updated` fires → calls `GradeCalculationService::recalculateStudentGPA` → updates `Student.gpa` → which fires `AuditObserver::updated` (gpa is in WATCHED_FIELDS) → writes AuditLog.

**When a course offering is closed (`is_open` true→false):**
`CourseOfferingObserver::updated` fires → cascades all active enrollments to Withdrawn → recalculates GPA for each affected student.

**When any watched field changes on Grade/Application/Student/StudentEnrollment/ThesisRegistration:**
`AuditObserver::updated` checks `WATCHED_FIELDS` (a static per-model allow-list) → if a watched field changed, writes AuditLog.

---

## Filament Patterns Used Throughout

**Every Filament select is `->searchable()->preload()`** — plain native selects break RTL styling in Filament.

**`modifyQueryUsing(fn (Builder $query) => ...)` — the parameter MUST be named `$query`**, not `$q` or anything else. Filament v5 resolves closure parameters by name, and using the wrong name causes it to inject a fresh empty Builder that crashes.

**Actions on infolist entries use `->hintAction()`**, not `Filament\Schemas\Components\Actions`. The `Actions` component only renders inside forms, not infolists.

**Confirmation modals on the Save button don't work** in Filament's EditRecord pages. If a save-like flow needs a confirmation, build a separate `Action` that opens a modal — don't try to add `requiresConfirmation()` to the Save button.

**Widget actions need `<x-filament-actions::modals />`** in their own Blade template. The page-level modal slot doesn't serve widgets.

---

## Route Structure

```
/ → redirects to /admin/dashboard

Public:
  GET  /apply              → RegistrationController@create
  POST /apply              → RegistrationController@store
  GET  /apply/track        → ApplicationTrackingController@show
  GET  /apply/sample       → RegistrationController@sampleData (rate-limited)

Admin panel (Filament):
  /admin/*                 → Filament admin panel

Student portal (Filament):
  /portal/*                → Filament student panel

Print routes (auth required):
  GET /admin/students/{id}/print-student-letter
  GET /admin/students/{id}/print-provisional-grades
  GET /admin/students/{id}/print-certificate
  GET /admin/course-offerings/{id}/print-students
  GET /admin/course-offerings/{id}/print-exam-attendance
  GET /admin/course-offerings/{id}/print-course-results
```

---

## How Permissions Work

`bezhansalleh/filament-shield` auto-generates Spatie permissions per Resource: `ViewAny:Student`, `Create:CourseOffering`, `Update:Grade`, etc. The `RolesAndPermissionsSeeder` assigns these to roles.

`Gate::before` in `AppServiceProvider` gives `super_admin` a `true` bypass on every policy check.

Faculty's visible Resources are narrowed by `shouldRegisterNavigation()` and `canAccess()` on the Resource class, not just by Shield permissions.

`EnsureStudentRole` middleware on all `/portal/*` routes checks `$user->role === 'student'`. If not, redirects to `/portal/login`.

---

## Hybrid Role System (Important)

`users.role` (string column) AND Spatie roles (pivot table) are BOTH used across the codebase — inconsistently. Some code checks the column, some checks Spatie. Both must stay in sync. Whenever a User is created or updated programmatically, the code calls `$user->syncRoles([$user->role])` right after. If only one source is set, some checks pass and others fail in unexpected ways.
