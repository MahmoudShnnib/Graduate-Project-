# Graduate Studies System — Architecture & Design Patterns

## High-Level Architecture

```
┌──────────────────────────────────────────────────────────────────────────┐
│                          HTTP ENTRY POINTS                               │
├────────────────┬──────────────────────────┬──────────────────────────────┤
│ Public         │ Admin Panel (Filament)   │ Student Panel (Filament)     │
│ /apply         │ /admin                  │ /portal                      │
│ /apply/track   │ super_admin / secretary │ role=student AND is_active   │
│                │ / faculty               │                              │
└───────┬────────┴──────────┬──────────────┴──────────┬───────────────────┘
        │                   │                          │
        ▼                   ▼                          ▼
┌───────────────┐  ┌──────────────────────┐  ┌───────────────────────────┐
│ Controllers   │  │ AdminPanelProvider   │  │ StudentPanelProvider      │
│ Registration  │  │  14 Resources        │  │  9 Pages (hand-rolled     │
│ AppTracking   │  │  6 Pages             │  │  Livewire)                │
│ PrintCtrl     │  │  4 Widgets           │  │  EnsureStudentRole        │
└──────┬────────┘  │  FilamentShieldPlugin│  │  middleware               │
       │           └──────────┬───────────┘  └───────────┬───────────────┘
       │                      │                           │
       └──────────────────────┴───────────────────────────┘
                              │
                              ▼
        ┌─────────────────────────────────────────────────────┐
        │               BUSINESS ORCHESTRATION                │
        │                                                     │
        │  Actions/                    Services/              │
        │  (single op, __invoke,       (reusable computation  │
        │   DB::transaction)           or integration)        │
        │                                                     │
        │  ApproveApplication          GradeCalculationSvc    │
        │  CreateApplication           EnrollmentNumberSvc    │
        │  EnrollStudentInOffering     NotificationService    │
        │  DropStudentFromOffering     AuditService           │
        │                             PdfService             │
        │                             WhatsAppService        │
        └─────────────────────┬───────────────────────────────┘
                              │
                              ▼
        ┌─────────────────────────────────────────────────────┐
        │                   DATA LAYER                        │
        │  17 Eloquent models, 38 DB migrations               │
        │  MySQL — FK cascades, unique constraints, soft del  │
        └─────────────────────┬───────────────────────────────┘
                              │
        ┌─────────────────────┴───────────────────────────────┐
        │               OBSERVER LAYER (reactive)             │
        │  StudentObserver, GradeObserver, AuditObserver      │
        │  CourseOfferingObserver, SemesterObserver           │
        └─────────────────────────────────────────────────────┘
```

The controlling principle is: **thin UI → Action → Model write → Observer side-effect → Service for reusable logic**. Controllers and Filament pages are shells. Domain logic lives in Actions and Services. Cross-cutting invariants are enforced by Observers.

---

## Folder Structure

```
app/
├── Actions/           Single-responsibility, often __invoke-able, wraps multi-row writes in DB::transaction
│   ├── ApproveApplication.php
│   ├── CreateApplication.php
│   └── Enrollment/
│       ├── EnrollStudentInOffering.php   (idempotent upsert + flips CourseSurvey.is_processed)
│       └── DropStudentFromOffering.php   (sets enrollment status to Dropped/Withdrawn)
├── Channels/          WhatsAppChannel + WhatsAppMessage DTO
├── Enums/             Backed string enums with Arabic label() methods
├── Exceptions/        Typed domain exceptions (EnrollmentCapReachedException, etc.)
├── Filament/
│   ├── Pages/         Admin-panel custom pages (ReportsPage, ExamListPage, …)
│   ├── Resources/     14 admin Resources (Students, Applications, CourseOfferings, Grades, …)
│   ├── Student/Pages/ 9 student-portal Livewire pages
│   └── Widgets/       Dashboard widgets (stats, applications over time, per-specialization)
├── Http/Controllers/  RegistrationController, ApplicationTrackingController, PrintController
├── Models/            17 Eloquent models
├── Notifications/     9 notification classes + HasWhatsAppChannel trait
├── Observers/         StudentObserver, GradeObserver, AuditObserver, CourseOfferingObserver, SemesterObserver
├── Policies/          14 policies — one per resource
├── Providers/
│   ├── AppServiceProvider.php   Gate::before bypass + observer registrations
│   └── Filament/{Admin,Student}PanelProvider.php
├── Rules/             ArabicOnly, MinArabicWords (custom validation rules)
└── Services/          AuditService, EnrollmentNumberService, GradeCalculationService,
                       NotificationService, PdfService, TrackingCodeService, WhatsAppService
```

---

## Key Architectural Patterns

### 1. Observer-Driven Cascades

Business invariants (GPA recalculation, enrollment withdrawal, audit trail) are triggered by Eloquent observers registered in `AppServiceProvider::boot()`. They fire on Eloquent lifecycle events — callers don't need to know about side effects.

| Observer | Trigger | Effect |
|---|---|---|
| `StudentObserver::updated` | Student `status` → non-Active | Calls `DropStudentFromOffering` on every active enrollment → `Withdrawn` |
| `StudentObserver::deleting` | Student soft-deleted | Same cascade (needed because FK `ON DELETE CASCADE` doesn't fire on soft deletes) |
| `GradeObserver::updated` | Grade `published_at` set | Calls `GradeCalculationService::recalculateStudentGPA` |
| `GradeObserver::updated` | Grade `status → Approved` + enrollment active | Also triggers `WarningService::evaluateGradeApproval` for F-grade warnings |
| `CourseOfferingObserver::updated` | `is_open` flips `true → false` | Cascades all active enrollments to `Withdrawn`, recalculates GPA per student |
| `AuditObserver::updated` | Any update on Grade/Application/Student/StudentEnrollment/ThesisRegistration | Checks `WATCHED_FIELDS` allow-list; writes `AuditLog` row |
| `SemesterObserver::updated` | `is_active` set to `true` | Processes all `Inactive` students for consecutive-semester dismissal check |

### 2. Status-as-Pivot (Rich Pivot Table)

`StudentEnrollment` is a rich pivot between `students` and `course_offerings`. It carries:
- `status`: `enrolled` / `dropped` / `withdrawn`
- Enrollment history is **never deleted** — only status-updated

**Critical rule:** Every query that reads enrollments MUST filter by status. The codebase enforces this via named relations:
- `Student::activeEnrollments()` — filters `status = Enrolled`
- `Student::activeCourseOfferings()` — same via pivot
- `CourseOffering::activeEnrollments()` — same

Calling unfiltered `Student::enrollments()` anywhere is a bug.

### 3. Two Course↔Semester Relationships (Not Interchangeable)

```
semester_course (pivot)
  → "these courses are shown to students in the survey"
  → managed via SemesterResource's survey actions

course_offerings (table)
  → "these concrete scheduled sections exist with faculty assignment"
  → managed via CourseOfferingResource
```

Adding a course to `semester_course` does NOT create an offering. Creating an offering does NOT add it to the survey. They are managed separately.

### 4. Typed Exceptions for Domain Invariants

Every Action-layer guard throws a typed exception:

| Exception | When thrown |
|---|---|
| `EnrollmentCapReachedException` | Student already has 4 active enrollments |
| `CourseOutsideSpecializationException` | Course not in student's specialization |
| `StudentSuspendedInSemesterException` | Student has approved suspension for active semester |
| `CourseOfferingClosedException` | Offering is not open |
| `StudentNotActiveException` | Student status is not Active |

All enrollment call sites catch all exceptions and display Arabic Filament notifications.

### 5. Grade Auto-Calculation

`Grade::booted()` has a `saving` hook. When both `coursework_grade` and `final_grade` are non-null, it **automatically overwrites** `total`, `letter`, and `points`:

```
total  = coursework_grade + final_grade
letter = Grade::convertToLetter(total)
  → 85+ = A (4.0)  75+ = B+ (3.5)  70+ = B (3.0)  65+ = C+ (2.5)
  → 60+ = C (2.0)  55+ = D+ (1.5)  50+ = D (1.0)  else = F (0.0)
points = from the letter scale
```

`TransferCourse` uses the same auto-compute logic in its own `booted()`. The grading thresholds are **hardcoded** and not DB-configurable.

### 6. Dual Filament Panels, Single User Table

Both panels use the `web` auth guard against the `users` table. Separation is purely by role:
- `User::canAccessPanel(Panel $panel)` routes `super_admin`, `secretary`, `faculty` → admin panel; `student` + `is_active` → student panel
- `EnsureStudentRole` middleware on the student panel double-enforces `role === 'student'`

### 7. Grade Publishing vs. Grade Approval

These are **two separate concerns**:

- **Approval** (`GradeStatus::Approved`) — quality gate; admin confirms the grade is correct
- **Publishing** — visibility gate; controls when students see grades
  - `midterm_published_at` — students see `coursework_grade` only
  - `published_at` — students see full grades; GPA recalculates; warnings fire

When grades are edited after publishing:
- Edit `coursework_grade` → clears BOTH timestamps
- Edit `final_grade` → clears only `published_at` (midterm stays visible)

---

## Request Lifecycle Examples

### Application Approval

```
Filament ApplicationResource (button click)
  ↓
ApproveApplication::handle(Application)   [DB::transaction]
  ├─ Application::update(status=Accepted) ──→ AuditObserver writes AuditLog
  ├─ User::create(...)
  ├─ Student::create(...)               ──→ AuditObserver writes AuditLog
  └─ EnrollmentNumberService::generate(student, semester, isTransfer)
              ↓
          Student::save(enrollment_no)  ──→ AuditObserver writes AuditLog
  [transaction commits]
  ↓
NotificationService::notifyWelcomeStudent(student)
  ├─ mail channel → SMTP
  └─ WhatsAppChannel (if QONVO_API_TOKEN set) → Qonvo HTTP POST
```

### Grade Published → GPA Recalculated

```
Admin sets published_at on a Grade
  ↓
Grade::save()
  ↓ Eloquent updated event
  ├─ AuditObserver::updated (Grade in WATCHED_FIELDS) → AuditLog row
  └─ GradeObserver::updated
          ↓ if published_at just set AND enrollment is active
          GradeCalculationService::recalculateStudentGPA(student)
            ↓
          credit-weighted sum over (active enrollments with published_at set) + transfer courses
            ↓
          Student::save(gpa = new_value)
            ↓ Eloquent updated event
            └─ AuditObserver::updated (Student.gpa in WATCHED_FIELDS) → AuditLog row
```

---

## Notification Architecture

All notifications are dispatched through `NotificationService`, which concentrates every notification call. Models receiving notifications implement `Notifiable` (`User`, `Application`).

The `HasWhatsAppChannel` trait on each notification class decides `via()`:
- Always includes `['mail']`
- Adds `[WhatsAppChannel::class]` if `$notifiable->phone` is set AND `config('services.qonvo.api_token')` is non-null

**Important:** All notifications run **synchronously** — no `ShouldQueue` exists. A slow Qonvo API can delay the HTTP response.

**`NotificationLog` status is optimistic** — `status='sent'` means "attempted", not "delivered".

---

## Audit Trail

`AuditObserver::WATCHED_FIELDS` is an explicit per-model allow-list. Fields not in the list are invisible to audit logging. The log captures:
- `user_id` (from `auth()->id()`)
- `action` (class_basename + '_updated')
- `model_type` / `model_id`
- `old_values` / `new_values` (JSON)
- `ip_address`
- `created_at` (no `updated_at` — `$timestamps = false`)

---

## Authorization Model

```php
// AppServiceProvider::boot()
Gate::before(fn ($user, $ability) => $user->hasRole('super_admin') ? true : null);
// ↑ Super admin bypasses ALL policies
```

- Policies: one per resource, auto-discovered via `AuthServiceProvider`
- Shield-generated permissions: `ViewAny:Student`, `Create:CourseOffering`, etc.
- Role checks in code: both `$user->role === '...'` (column) AND `$user->hasRole(...)` (Spatie) are used inconsistently — both must be kept in sync

**Hybrid role sync rule:** whenever a `User` is created or updated programmatically, call `$user->syncRoles([$user->role])` immediately. Applied in `ApproveApplication`, `CreateStudent`, `CreateUser`, `EditUser`, and `RolesAndPermissionsSeeder::repairUserRoles`.

---

## PDF & Print System

Two PDF stacks are used:

1. **DomPDF (`barryvdh/laravel-dompdf`)** — older stack used for transcripts, clearance, suspension, and thesis forms. `PdfService` generates these to `storage/app/public/pdfs/`, returns relative path. Synchronous.

2. **mPDF** — newer stack used for ministry-style Arabic print templates (student letters, provisional grades, exam attendance, course results). Font stack: Noto Naskh (Arabic body), Markazi (numbers), DejaVu Sans (ASCII/Latin substitution). Returned as inline PDF responses via `PrintController`.

All print routes are under `/admin/{resource}/{id}/print-{name}`, protected by `auth` middleware.
