---
date: 2026-04-17
tags: [context, architecture, diagram, components]
related: [[project-overview]], [[gotchas]], [[../../CLAUDE]]
---

# Architecture

> How the pieces connect and communicate. Pairs with [[project-overview]] (domain detail) and [[gotchas]] (pitfalls).

## System map

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                            HTTP ENTRY POINTS                                    │
├─────────────────────────┬─────────────────────────┬─────────────────────────────┤
│ Public (web guard)      │ Admin Panel (Filament)  │ Student Panel (Filament)    │
│ /apply                  │ /admin                  │ /portal                     │
│ /apply/track            │ /admin/login            │ /portal/login               │
│ /  → redirects to admin │ super_admin/secretary/  │ role=student AND is_active  │
│                         │ faculty                 │                             │
└────────┬────────────────┴──────────┬──────────────┴──────────┬──────────────────┘
         │                           │                         │
         ▼                           ▼                         ▼
┌──────────────────┐  ┌─────────────────────────────┐  ┌───────────────────────┐
│ Controllers      │  │ AdminPanelProvider          │  │ StudentPanelProvider  │
│ Registration,    │  │  • Discovers 14 Resources   │  │  • Discovers 9 Pages  │
│ ApplicationTrack,│  │  • Discovers 6 Pages        │  │  • Arabic nav groups  │
│ PrintCourseStud  │  │  • 4 dashboard Widgets      │  │  • EnsureStudentRole  │
│ (thin)           │  │  • FilamentShieldPlugin     │  │    middleware         │
└──────┬───────────┘  └──────────────┬──────────────┘  └──────────┬────────────┘
       │                             │                            │
       │   ┌─────────────────────────┴────────────────────────────┘
       │   │
       ▼   ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                          BUSINESS ORCHESTRATION                              │
│                                                                              │
│   ┌──────────────────┐        ┌──────────────────────────────────────────┐   │
│   │ Actions/*        │───────▶│ Services/*                               │   │
│   │ (single op,      │        │ (reusable logic: audit, gpa calc,        │   │
│   │  __invoke)       │        │  enrollment#, pdf, tracking#, whatsapp,  │   │
│   │                  │        │  notifications)                          │   │
│   └────────┬─────────┘        └─────────┬────────────────────────────────┘   │
│            │                            │                                    │
│            │                            ▼                                    │
│            │           ┌────────────────────────────┐                        │
│            │           │ External: Qonvo WhatsApp   │                        │
│            │           │          mPDF              │                        │
│            │           │          Mail (SMTP)       │                        │
│            │           └────────────────────────────┘                        │
│            ▼                                                                 │
└──────────────────────────────────────────────────────────────────────────────┘
             │
             ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                                 DATA LAYER                                   │
│                                                                              │
│   ┌───────────┐   ┌────────────────┐   ┌──────────┐   ┌─────────────────┐    │
│   │ User      │──▶│ Student        │──▶│ Enroll-  │──▶│ Grade           │    │
│   │ (role,    │   │ (gpa, status,  │   │ ment     │   │ (coursework+    │    │
│   │  panel    │   │  SoftDeletes)  │   │ (status: │   │  final → auto   │    │
│   │  access)  │   │                │   │ enrolled/│   │  total/letter/  │    │
│   └───────────┘   └───────┬────────┘   │ dropped/ │   │  points)        │    │
│         │                 │            │ withdrwn)│   └─────────────────┘    │
│         ▼                 ▼            └────┬─────┘                          │
│   ┌───────────┐   ┌────────────────┐        │                                │
│   │Application│   │ CourseSurvey   │        │                                │
│   │ (tracking,│   │ (priority 1-4, │        ▼                                │
│   │  status,  │   │  is_processed) │   ┌──────────────┐                      │
│   │  password)│   └────────────────┘   │ Course       │                      │
│   └───────────┘                        │ Offering     │                      │
│                                        │ (faculty_id, │                      │
│   ┌───────────┐   ┌────────────────┐   │  is_open,    │                      │
│   │ Semester  │──▶│ semester_course│──▶│  coursework_ │                      │
│   │ (active,  │   │ (pivot for     │   │  max/        │                      │
│   │  add_drop │   │  survey menu)  │   │  final_max)  │                      │
│   │  window,  │   └────────────────┘   └──────┬───────┘                      │
│   │  survey_  │                               │                              │
│   │  open)    │          ┌────────────────────┘                              │
│   └───────────┘          ▼                                                   │
│                    ┌────────────┐     ┌──────────────────┐                   │
│                    │ Course     │────▶│ course_speciali- │                   │
│                    │ (SoftDel,  │     │ zation (pivot +  │                   │
│                    │  credits)  │     │  type: general/  │                   │
│                    └────────────┘     │  specialized/    │                   │
│                                       │  elective)       │                   │
│                                       └──────────────────┘                   │
│                                                                              │
│   AddDropRequest  SuspensionRequest  ThesisRegistration  ClearanceRequest    │
│   TransferCourse  AuditLog  NotificationLog  Specialization                  │
└──────────────────────────────────────────────────────────────────────────────┘
             ▲
             │ watches writes
             │
┌──────────────────────────────────────────────────────────────────────────────┐
│                          OBSERVER LAYER (reactive)                           │
│                                                                              │
│   StudentObserver        GradeObserver         AuditObserver                 │
│   ──────────────         ─────────────         ─────────────                 │
│   • on status→!Active,   • on status→Approved, • on any updated() for        │
│     cascade drop           recompute GPA via     Grade/Application/Student/  │
│     all active             GradeCalculation     StudentEnrollment/Thesis:    │
│     enrollments to         Service              check WATCHED_FIELDS,        │
│     Withdrawn                                   write AuditLog row           │
│   • on deleting(),                                                           │
│     same cascade                                                             │
└──────────────────────────────────────────────────────────────────────────────┘
```

## Major components

### Panels (Filament)

**Admin panel** (`/admin`, default panel, id `admin`)
- Provider: `App\Providers\Filament\AdminPanelProvider`
- Auto-discovers Resources in `app/Filament/Resources/`, Pages in `app/Filament/Pages/`, Widgets in `app/Filament/Widgets/`
- Ships with `FilamentShieldPlugin` which creates per-Resource permissions (`view_student`, `create_course_offering`, etc.)
- Access controlled by `User::canAccessPanel`: super_admin (via Spatie role OR `role` column), secretary, faculty
- Faculty's visible Resources are further narrowed by role-scoped `shouldRegisterNavigation()` / `canAccess()` checks (see `MyCourseResource`, `GradeResource`)

**Student panel** (`/portal`, id `student`)
- Provider: `App\Providers\Filament\StudentPanelProvider`
- Arabic brand: `بوابة الطالب` — nav groups: `الأكاديمي` (Academic), `الطلبات` (Requests)
- Adds `EnsureStudentRole` to auth middleware — double-enforces that only `role === 'student'` can reach any page here
- Pages are plain `Filament\Pages\Page` subclasses with Livewire public properties, Blade views under `resources/views/filament/student/pages/`

**Shared auth guard.** Both panels use the `web` guard against the `users` table. Separation is purely by role, not by authentication store.

### Business orchestration

**Controllers** — only three, all thin:
- `RegistrationController` — public application intake (fat validation, thin controller)
- `ApplicationTrackingController` — tracking code lookup
- `PrintCourseStudentsController` — eager-loads + renders a printable roster Blade

**Actions** (`app/Actions/*`) — orchestrate a single business op, usually invokable:
- `CreateApplication(array $data): Application` — from public form
- `ApproveApplication::handle(Application): Application` — DB-transaction, creates User+Student, generates enrollment number, notifies
- `Enrollment/EnrollStudentInOffering::__invoke(Student, CourseOffering): StudentEnrollment` — upsert + mark survey processed
- `Enrollment/DropStudentFromOffering::__invoke(Student, CourseOffering, EnrollmentStatus = Dropped): bool`

**Services** (`app/Services/*`) — reusable, stateless (or near-stateless):
- `AuditService`, `EnrollmentNumberService`, `GradeCalculationService`, `NotificationService`, `TrackingCodeService`, `WhatsAppService` (the DomPDF-based `PdfService` was removed 2026-06-15; all PDFs now go through `PrintController` + mPDF)

### Observer layer (the reactive glue)

Registered once in `AppServiceProvider::boot()`. They run inside Eloquent's event lifecycle, so they fire on both explicit writes and cascaded writes (including observer-invoked updates).

| Observer | Trigger | Effect |
|---|---|---|
| `StudentObserver::updated` | Student `status` changes to anything other than `Active` | Calls `DropStudentFromOffering` on every active enrollment, setting status to `Withdrawn` |
| `StudentObserver::deleting` | Student soft-delete | Same cascade (needed because FK `ON DELETE CASCADE` doesn't fire for soft deletes) |
| `GradeObserver::updated` | Grade `status` changes to `Approved` and the enrollment is active | Calls `GradeCalculationService::recalculateStudentGPA` |
| `CourseOfferingObserver::updated` | `is_open` transitions `true → false` (added 2026-04-21, T18) | Calls `DropStudentFromOffering` on every active enrollment with `Withdrawn` status, then recalculates GPA per affected student. No-op on `false → true` — reopening does not re-activate withdrawn students. |
| `AuditObserver::updated` | Any update on Grade, Application, Student, StudentEnrollment, ThesisRegistration | If any `WATCHED_FIELDS` changed, writes an `AuditLog` row with old/new/user/ip |

### Data layer

See the map above for relationships. Key structural choices:

- **`StudentEnrollment` is a rich pivot**, not a junction table. It sits between `students` and `course_offerings`, carrying `status` (`enrolled/dropped/withdrawn`). Belongs-to-many from Student goes through this table with `->withPivot('status')`, and queries must always filter status — hence the `active*` relations.
- **Two course↔semester relationships**:
  - `semester_course` pivot — which courses are **available** for the student survey in a semester
  - `course_offerings` — the **concrete scheduled sections** (course + semester + faculty + is_open + grade distribution)
- **Course↔Specialization is m2m with a pivot type** (`general/specialized/elective`) via `course_specialization`. Courses can belong to multiple specializations with different roles in each.
- **Soft deletes** are on `User`, `Student`, `Course` only. `CourseOffering`, `StudentEnrollment`, `Grade` are hard-deleted — but in practice `StudentEnrollment` is never deleted; its status is updated.
- **FK cascades at the DB level** are set for: `user_id` on students (cascade), `student_id` on enrollments (cascade), `course_offering_id` on enrollments (cascade), `enrollment_id` on grades (cascade), `approved_by` on grades (null on delete).

## How they communicate

### Request lifecycle (admin approves an application)

```
Filament Resource (ApplicationResource page action)
  │  [button click → Livewire action]
  ▼
ApproveApplication::handle(Application)
  │  [DB::transaction]
  ├─► Application::update(status = Accepted)        ──────► AuditObserver writes AuditLog
  ├─► User::create(...)                             
  ├─► Student::create(...)                          ──────► AuditObserver writes AuditLog
  └─► EnrollmentNumberService::generate(student, semester, isTransfer)
            │
            ▼
          Student::save(with enrollment_no)         ──────► AuditObserver writes AuditLog
  [transaction commits]
  │
  ▼
NotificationService::notifyWelcomeStudent(student)
  │
  ├─► notifiable->notify(WelcomeStudentNotification)
  │        │
  │        ├─► mail channel → SMTP
  │        └─► WhatsAppChannel (if HasWhatsAppChannel trait + config set)
  │                    │
  │                    └─► WhatsAppService::sendText → Qonvo HTTP POST
  │
  └─► NotificationLog::create({student, channel, event, status, sent_at})
```

### Request lifecycle (grade approval → GPA recalc)

```
Filament GradeResource (admin sets status=Approved)
  │
  ▼
Grade::update(status = Approved)
  │
  ▼  Eloquent updated event
  ├─► AuditObserver::updated       (Grade is in WATCHED_FIELDS)
  │       └─► AuditService::log → AuditLog row
  │
  └─► GradeObserver::updated
          └─► if status transitioned to Approved AND enrollment active:
               GradeCalculationService::recalculateStudentGPA(student)
                    │
                    ▼
                  sums credit-weighted points (active enrollments + transfer courses)
                    │
                    ▼
                  Student::save(gpa = new_value)
                    │
                    ▼  Eloquent updated event
                    └─► AuditObserver::updated (Student gpa in WATCHED_FIELDS)
                           └─► AuditLog row
```

### Student portal page → data

Student-portal pages (`AddDropPage`, `CourseSurveyPage`, `GradesPage`, etc.) follow the same shape:

1. Page declares Livewire public properties (`selections`, `selectedDropEnrollments`, …).
2. Getter methods query by `auth()->user()->student` + `Semester::where('is_active', true)->first()`.
3. Action methods (`submit()`, `toggleCourse()`, `submitRequests()`) validate business rules (window open, max limits, ownership), write models, emit `Filament\Notifications\Notification` flash messages.

No Livewire events cross pages; each page is self-contained.

### Notifications

A single `NotificationService` concentrates every notification call. Models that receive notifications are `Notifiable` (`User`, `Application`). The `HasWhatsAppChannel` trait on each notification class decides `via()` based on the notifiable's `phone` and `config('services.qonvo.api_token')` — emit `['mail']` always, plus `[WhatsAppChannel::class]` conditionally.

### PDF generation

`PrintController` (and `PrintCourseStudentsController`) renders a Blade view (`resources/views/prints/*`) to HTML, feeds it to a configured `Mpdf\Mpdf` instance, and streams the result inline as the HTTP response (`$pdf->Output('', 'S')`) — nothing is persisted to disk. All synchronous, routed via the `print.*` named routes. (The earlier DomPDF-based `PdfService`, which wrote files to `storage/app/public/pdfs/`, was removed 2026-06-15.)

### Audit trail

`AuditObserver::WATCHED_FIELDS` is the source of truth for what is recorded. Everything else is invisible to the audit log. The log captures: `user_id` (from `auth()->id()`), `action` (class_basename + '_updated'), `model_type` / `model_id`, `old_values`, `new_values`, `ip_address`, and `created_at`. `AuditLog` has **no `updated_at`** (`public $timestamps = false`).
