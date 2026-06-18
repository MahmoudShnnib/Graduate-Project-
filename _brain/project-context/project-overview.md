---
date: 2026-04-17
tags: [context, overview, domain, architecture]
related: [[architecture]], [[gotchas]], [[../../CLAUDE]]
---

# Project Overview

> Deep dive into what this system does, how its pieces fit together, and where the bodies are buried. Read this alongside [[architecture]] (diagrams + component roles) and [[gotchas]] (landmines).

## What this project does

A **graduate-studies management system** for a Libyan university faculty, built as a graduation project. The core value proposition is automating the academic-lifecycle paperwork that admin secretaries currently do by hand: intake of applications, admissions tracking, enrollment, add/drop, grade entry + approval, GPA calculation, thesis supervision registration, suspension requests, and end-of-studies clearance.

Five actor types, five user experiences (three of them share the `/admin` panel but see very different things):

| Actor | Role (`users.role`) | Entry point | Powers |
|---|---|---|---|
| **Applicant** (متقدم) | — (not yet a user) | `/apply` (public) | Submit an application, track it via `/apply/track`. Promoted to Student on approval. |
| **Office Head** (رئيس مكتب الدراسات العليا) | `super_admin` | `/admin` (Filament) | Everything — all permissions + `Gate::before` bypass; manages users/roles/audit log on top of all academic operations |
| **Administrative Assistant** (مساعد إداري) | `manager_assistant` | `/admin` (Filament) | Day-to-day operator: applications, students, courses, semesters/offerings, enrollment rostering, grade approval, add/drop/suspension/thesis requests. Reads everything **except** admin-only models (`Role`, `User`, `AuditLog`, `NotificationLog`). |
| **Faculty Member** (عضو هيئة التدريس) | `faculty` | `/admin` → "موادي" only | Sees only their own course offerings (`faculty_id == auth()->id()`) and enters grades for them. Holds just `ViewAny:CourseOffering` + `View:CourseOffering`; everything else is hidden. |
| **Student** (الطالب) | `student` | `/portal` (Filament, RTL Arabic) | See their tree, surveys, grades, submit add/drop/suspension/thesis/clearance requests |

> **Note on role naming:** the Spatie/Shield role for the assistant is `manager_assistant` (the `users.role` column may carry the legacy value `secretary`, which `canAccessPanel` and `repairUserRoles` still accept and map across). Earlier docs called this actor "secretary."

The UI is **entirely in Arabic** (right-to-left). English appears only in raw identifiers (emails, enrollment numbers, the applicant's English name — required for the bachelor certificate).

## How the main pieces fit together

The lifecycle flows roughly top-to-bottom through these layers:

1. **Public intake** — `RegistrationController` (inline validation w/ Arabic rules) → `CreateApplication` action → `Application` row + tracking code.
2. **Admin review** — admin sees applications in the Filament [[#Application flow|Applications resource]], enters exam results, and triggers `ApproveApplication`. That action (in a DB transaction) promotes the applicant: creates a `User`, creates a `Student`, generates an `enrollment_no` via `EnrollmentNumberService`, and dispatches a welcome notification via `NotificationService`.
3. **Semester setup** — admin creates a `Semester` (one active at a time), attaches courses via the `semester_course` pivot (what's available for student survey), then creates concrete `CourseOffering` rows (course + semester + faculty). Course offerings have a configurable `coursework_max` / `final_max` grade distribution (default 40/60).
4. **Pre-enrollment survey** — students visit `CourseSurveyPage` and rank any number of courses from their specialization as an ordered wishlist. No cap on survey selections (the old `MAX_SELECTIONS = 4` constant was removed 2026-04-20). The admin decides which to actually enroll; the hard 4-course enrollment cap lives at the enrollment-action layer (`EnrollStudentInOffering::MAX_ENROLLMENTS_PER_SEMESTER`).
5. **Enrollment** — admin/secretary uses `EnrollStudents` page on a CourseOffering to manually roster students. The `EnrollStudentInOffering` action upserts a `StudentEnrollment` with `status = Enrolled` and flips the matching `CourseSurvey.is_processed = true`.
6. **Add/drop** — during the semester's `add_drop_start..add_drop_end` window, students can request up to 3 drops + 3 adds from `AddDropPage`. Submissions become `AddDropRequest` rows for admin approval; approval dispatches enrollment actions.
7. **Grade entry** — faculty sees their own courses via `MyCourseResource` (scoped by `faculty_id == auth()->id()`), enters coursework + final grades in `ViewMyCourse`. The `Grade` model's `booted()` saving hook auto-computes `total`, `letter` (A/B+/B/C+/C/D+/D/F), and `points` (4.0/3.5/…/0.0). Status starts as `Draft`, moves to `PendingApproval`.
8. **Grade approval** — admin/secretary reviews in the Grades resource and sets `status = Approved`. The `GradeObserver::updated` hook catches the status change and calls `GradeCalculationService::recalculateStudentGPA` which updates `Student::gpa` (weighted by course credits, including transfer courses).
9. **Student-side reads** — student portal pages read through the status-filtered relations (`activeEnrollments`, `activeCourseOfferings`), always limited to approved grades for transcripts.
10. **Support flows** — thesis registration (eligible when all general + specialized courses + 2 electives completed with `gpa >= 3.0`), **discussion request** (4-phase pipeline: review → scheduled → discussed → result → binding/submission), suspension requests, clearance requests (with mPDF clearance form — gated by discussion completion, not just thesis approval), and notifications via email + optional WhatsApp.

## Data flow / architecture

See [[architecture]] for the diagram. In brief:

```
public form ─► Action ─► Model ─► (Observer) ─► Service ─► Notification
                                         │
                                         └─► AuditLog
```

The pattern is **thin controllers/pages → Action → Model write → Observer side-effect → Service for reusable logic**. Observers are the glue that makes domain invariants self-healing: dropping a student from a course, suspending a student, approving a grade — each triggers downstream recalculation without the caller having to remember to do it.

## Key files and what they do

**Providers / bootstrapping**
- `bootstrap/app.php` — routes + 403-to-portal-login redirect
- `bootstrap/providers.php` — registers `AppServiceProvider`, `AdminPanelProvider`, `StudentPanelProvider`
- `app/Providers/AppServiceProvider.php` — the critical wiring: `Gate::before` for super_admin, observer registrations (Grade, Student, plus AuditObserver on 5 models)

**Models (17 total, relationships worth knowing)**
- `User` — implements `FilamentUser`; `canAccessPanel` routes based on `role` + `is_active`; hasOne `Student`; hasOne `Application`
- `Student` — `SoftDeletes`; hasMany `enrollments` (all) + `activeEnrollments` (filtered); belongsToMany `courseOfferings` through pivot; hasMany `thesisRegistrations` + `discussionRequests`; hasOne `clearanceRequest`; `completedCoursesCount()` + `isEligibleForThesis()` domain methods
- `CourseOffering` — belongs to Course + Semester + `faculty` (User); has `activeEnrollments`; stores `coursework_max`/`final_max` for grade distribution
- `StudentEnrollment` — rich pivot with `status` enum; `scopeActive()` Builder scope; has one `Grade` (FK `enrollment_id` with `cascadeOnDelete`)
- `Grade` — `booted()` saving hook auto-fills `total`/`letter`/`points`; static `convertToLetter()` holds the grading scale
- `Semester` — `isAddDropOpen()` helper; belongsToMany `courses` through `semester_course` pivot (for survey) + hasMany `courseOfferings` (for enrollment)
- `Application` — `Notifiable`; routes WhatsApp via `phone`; stores password hashed at intake so approval doesn't need a second step

**Actions (orchestration)**
- `Actions/ApproveApplication.php` — Application → User + Student in one transaction, then enrollment number + welcome notification
- `Actions/CreateApplication.php` — Public form data → Application + tracking code
- `Actions/Enrollment/EnrollStudentInOffering.php` — `__invoke`, idempotent upsert, flips matching survey
- `Actions/Enrollment/DropStudentFromOffering.php` — `__invoke`, idempotent, returns bool
- `Actions/BaseAction.php` — stub class, not currently used (see [[gotchas#Unused scaffolding]])

**Services (reusable logic)**
- `EnrollmentNumberService::generate` — builds `{YY}{specCode}{sessionFlag}{seq}` prefix-scoped sequence; uses `Student::withTrashed()` so soft-deletes don't cause number collisions
- `GradeCalculationService::recalculateStudentGPA` — credit-weighted GPA from approved grades + transfer courses; skips suspended students
- `NotificationService` — thin dispatcher that calls `notifiable->notify()`, catches errors, logs to `NotificationLog` per-channel; WhatsApp channel only triggers if `config('services.qonvo.api_token')` is set
- `AuditService::log` — writes `AuditLog` with user_id, IP, old/new values
- PDF generation — handled by `PrintController` + `PrintCourseStudentsController` via **mPDF**, streamed inline (not persisted). Documents: clearance, graduation certificate, ministry/exam/grade forms, course rosters. (The earlier DomPDF `PdfService` — transcript/clearance/suspension/thesis written to `storage/app/public/pdfs/` — was removed 2026-06-15.)
- `TrackingCodeService::generate` — `APP-{YEAR}-0001`-style monotonic code
- `WhatsAppService` — Qonvo HTTP client with Libyan phone normalization to `+218...`

**Observers**
- `StudentObserver` — injected `DropStudentFromOffering`; on `updated` (if status changed to non-active) or `deleting`, cascades all active enrollments to `Withdrawn`
- `GradeObserver` — injected `GradeCalculationService`; on `updated` if `status → Approved` and the enrollment is still active, triggers GPA recalc
- `AuditObserver` — per-class watched-field allow-list, writes `AuditLog` for each change

**Filament**
- **Survey review:** as of 2026-04-20 T10, `CoursesSurveyResults` is the single page for survey review — it renders both the course-demand aggregation and the per-student list in one view. The old `StudentSurveysList` was retired.
- `AdminPanelProvider` — default panel, discovers all Resources/Pages/Widgets, wires `FilamentShieldPlugin`
- `StudentPanelProvider` — separate panel at `/portal`, Arabic brand name `بوابة الطالب`, navigation groups `الأكاديمي` + `الطلبات`, adds `EnsureStudentRole` to auth middleware
- `Filament/Resources/Faculty/MyCourseResource.php` — example of role-scoped Resource (faculty see only their courses)
- `Filament/Student/Pages/*` — 9 hand-rolled Livewire pages for the student portal (not using Filament form builder — they use public properties + Blade views)

**Public HTTP**
- `RegistrationController` — the big one: ~130 lines of inline validation rules with Arabic messages, Libyan phone format check, national-ID 12-digit check, Arabic name (4 words minimum) + English name (regex), age range 22–60, conditional delegation fields, file upload
- `ApplicationTrackingController` — tracking code + national ID lookup
- `PrintCourseStudentsController` — eager-loads active enrollments + student user + specialization, sorts by specialization→name, renders `prints/course-students.blade.php`

## Dependencies worth noting

- **`bezhansalleh/filament-shield`** — auto-generates Spatie permissions per Filament Resource. `User::canAccessPanel` calls `Utils::getSuperAdminName()` + `Utils::getPanelUserRoleName()`. Shield-generated permissions are referenced in `RolesAndPermissionsSeeder`.
- **`spatie/laravel-permission`** — works alongside the `users.role` column (not instead of). Both are checked; see [[gotchas#Hybrid role system]].
- **`mpdf/mpdf`** — sole PDF engine. All documents render from `resources/views/prints/*` through `PrintController`/`PrintCourseStudentsController` and stream inline. (`barryvdh/laravel-dompdf` + its `PdfService` were removed 2026-06-15 — see [[../daily-notes/2026-06-15-dompdf-removal-and-graduate-clearance|removal note]].)
- **Qonvo WhatsApp API** (`qonvo.ly`) — third-party Libyan WhatsApp gateway. If creds aren't in `.env`, the system silently falls back to email-only. No queue/retry layer.
- **No queue driver configured for actual use** — `composer run dev` launches `queue:listen`, but **no `ShouldQueue` implementation exists**. All notifications and PDFs run synchronously.

## Things that look incomplete or need attention

- **`BaseAction` is a stub.** `can()` is the only method and it uses `Gate::allow(...)` which isn't how Laravel's Gate API works (allow is static-called as `Gate::allows`, and it returns bool, not Response). No action extends this class. Likely dead or aspirational scaffolding.
- **Form Requests never created.** The guidelines say "always create Form Request classes for validation." `RegistrationController::store` has 130 lines of inline validation instead. Other controllers are thin.
- **No `ShouldQueue` on any notification.** Email + WhatsApp dispatch blocks the request. With the Qonvo API potentially latent or down, grade approval / application approval / student enrollment may experience request timeouts.
- **`AuditObserver::WATCHED_FIELDS` is static** — adding a new watched column requires editing the class. There's no "track everything" fallback.
- **`EnrollmentNumberService` does not validate uniqueness before `save()`.** It relies on the `enrollment_no` unique index + sequential max logic. Under concurrent approval, a race could produce two students with the same prefix; the DB would reject the second but the service would throw an unhandled exception.
- **Student portal pages use `auth()->user()->student` without null guards** in several methods (e.g., `CourseSurveyPage::mount`). If a non-student user somehow reaches these pages (middleware misconfiguration), it would crash.
- **`AppServiceProvider` registers `AuditObserver` on 5 models but not via the observer's `WATCHED_FIELDS` keys** — it's maintained manually. Mismatches (e.g. forgot to register `Student` but added it to the array) would silently fail.
- **`NotificationService::dispatch` marks WhatsApp as `'sent'` merely if the call *didn't throw*.** Qonvo 4xx responses would be reflected in logs via `Log::warning`, but the channel is still recorded as "sent" in `NotificationLog`. See [[gotchas#Notification status is optimistic]].
- **Uncommitted work on branch.** `git status` shows 30+ modified files + 5 deletions related to an enrollment-filtering refactor (see memory S87/S88). Before starting new work, check what that branch is doing.
- **`welcome.blade.php`** is the stock Laravel page, unused — `/` redirects to the admin dashboard.
- **Laravel Pail** is in dev deps but only surfaces via `composer run dev`. No log viewer on the admin panel.

## Next-session anchors

- The enrollment-filtering refactor (`omar-admin-bbg` branch) consolidates all enrollment queries through `active*` relations — see [[../daily-notes/README|daily notes]] and prior session memory S83–S88.
- `_brain/project-context/architecture.md` — component map.
- `_brain/project-context/gotchas.md` — traps to avoid before editing.
