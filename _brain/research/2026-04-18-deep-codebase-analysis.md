---
date: 2026-04-18
tags: [research, architecture, audit, enrollment, grades, security]
related: [[../project-context/project-overview]], [[../project-context/architecture]], [[../project-context/gotchas]], [[../bugs-and-fixes/2026-04-18-double-gpa-recalc-and-audit-churn]], [[../daily-notes/2026-04-18-deep-codebase-analysis]]
---

# Deep Codebase Analysis — 2026-04-18

> End-to-end systematic scan of the Laravel 12 + Filament v5 graduate-studies system. Complements (does not replace) [[../project-context/project-overview|project-overview]] and [[../project-context/architecture|architecture]]. Focus here: **prioritized issue list with file:line anchors** and verified findings.

## Scope of this pass

- Branch: `omar-admin-bbg` (with 30+ uncommitted modifications from the Apr 16 Phase 4–5 refactor).
- Did not re-derive what's already in [[../project-context/project-overview]] or [[../project-context/architecture]] — this note captures **new findings, verified claims, and a prioritized issue backlog** only.
- Three claims flagged as unverified in the initial analysis were all checked against source files. See [[#Verified findings]].

## Structure scan summary (delta vs existing context notes)

No structural surprises beyond what `project-overview` documents. Counts:

| Category | Count | Notes |
|---|---|---|
| Models | 17 | As documented |
| Filament Resources (admin) | 14 | + 1 faculty-scoped `MyCourseResource` |
| Student portal pages | 9 | All under `app/Filament/Student/Pages/` |
| Admin custom pages | 6 | `FacultyGradeEntry` was deleted Apr 16 |
| Widgets | 4 | Applications-over-time, status, stats, per-spec |
| Actions | 4 | `ApproveApplication`, `CreateApplication`, `Enrollment/Enroll…`, `Enrollment/Drop…` (+ unused `BaseAction` stub) |
| Services | 7 | Audit, EnrollmentNumber, GradeCalculation, Notification, Pdf, TrackingCode, WhatsApp |
| Observers | 3 | Grade, Student, Audit — all registered in [AppServiceProvider](../../app/Providers/AppServiceProvider.php) |
| Notifications | 9 | + `Concerns/HasWhatsAppChannel` trait |
| Policies | 14 | One per resource, Shield-generated |
| Enums | 9 | All Arabic `label()` methods |
| Migrations | 38 | Core landed 2026-03-09; incremental through 2026-04-16 |
| Factories | 11 | Standard + `StudentEnrollmentFactory`, `GradeFactory` |
| Seeders | 8 | `DatabaseSeeder` chains Roles → Spec → Semester → Course → Admin → Application → Student |

## Verified findings

Three items were explicitly re-checked this pass.

### ✅ Double GPA recalc in `ViewCourseGrades` — CONFIRMED

[ViewCourseGrades.php:109-121](../../app/Filament/Resources/Grades/Pages/ViewCourseGrades.php#L109-L121): the `approve` action calls `$record->update([...'status' => Approved])` which fires `GradeObserver::updated` → `GradeCalculationService::recalculateStudentGPA()` — **then immediately also calls** `$calculator->recalculateStudentGPA($student)` directly on L117.

Same double-invocation in:
- `change_status` action at [L140-157](../../app/Filament/Resources/Grades/Pages/ViewCourseGrades.php#L140-L157)
- `approve_all` bulk action at [L204-225](../../app/Filament/Resources/Grades/Pages/ViewCourseGrades.php#L204-L225)

`notifyGradePublished` is correctly only in the page (no observer duplicates it).

### ✅ `Student.gpa` IS in audit watchlist — CONFIRMED

[AuditObserver.php:18](../../app/Observers/AuditObserver.php#L18):
```php
Student::class => ['gpa', 'warnings_count', 'status', 'enrollment_no'],
```

Compounded with the double recalc above: every grade approval writes the gpa twice, and AuditObserver logs each write. Net effect per approval: **2 `Student_updated` audit rows with identical gpa values**.

### ❌ `EnsureStudentRole` registration — I was wrong, it IS registered

[StudentPanelProvider.php:53-56](../../app/Providers/Filament/StudentPanelProvider.php#L53-L56):
```php
->authMiddleware([
    Authenticate::class,
    EnsureStudentRole::class,
]);
```

Portal is correctly gated. Original Issue 13 is retracted.

## Connection map (data flow by feature)

See [[../project-context/architecture]] for full diagrams. Deltas worth noting:

- **Grade approval path:** `ViewCourseGrades` → `$record->update(status=Approved)` → `GradeObserver::updated` → `GradeCalculationService::recalculateStudentGPA` → `$student->save()` → `AuditObserver::updated(Student)`. The page then redundantly repeats `recalculateStudentGPA` + dispatches `GradePublishedNotification`.
- **Staff enrollment path:** `EnrollStudents` (Filament page) → `EnrollStudentInOffering` action → `StudentEnrollment::updateOrCreate` (idempotent) → marks matching `CourseSurvey.is_processed=true`. No authorization call on the page action.
- **Approval path:** `ApproveApplication` → DB txn wrapping User + Student creation + `EnrollmentNumberService::generate`. Notification outside txn (robust-first).

## Prioritized issue backlog

Issues are tagged P0 (security/correctness blockers), P1 (performance/bloat), P2 (code-quality / data-integrity polish). Each has a specific file anchor.

### P0 — Security / correctness

| # | Issue | Anchor | Fix sketch |
|---|---|---|---|
| P0-1 | No `$this->authorize(...)` calls on `EnrollStudents` page actions (survey_students, enroll_manual, unenroll). Relies entirely on Filament Shield — business-logic invariants aren't enforced at the action boundary. | [EnrollStudents.php](../../app/Filament/Resources/CourseOfferings/Pages/EnrollStudents.php) | Add `$this->authorize('create', StudentEnrollment::class)` at the top of each action closure. |
| P0-2 | Faculty can edit grades on course offerings they don't own. `ViewMyCourse` has no check that `$record->faculty_id === auth()->id()` — Shield may let any `faculty` role user reach the URL. | [ViewMyCourse.php](../../app/Filament/Resources/Faculty/Pages/ViewMyCourse.php) | In `mount()`: `abort_unless($this->record->faculty_id === auth()->id() \|\| auth()->user()->hasRole('super_admin'), 403);` |
| P0-3 | Public `/apply/track` has **no rate limit**. Tracking codes + national IDs can be enumerated. | [routes/web.php](../../routes/web.php) | `Route::post('/apply/track', …)->middleware('throttle:5,1');` |

### P1 — Performance / bloat

| # | Issue | Anchor | Fix sketch |
|---|---|---|---|
| P1-4 | `Semester::where('is_active', true)->first()` is called from 5+ student-portal pages every render. No caching. | [AddDropPage](../../app/Filament/Student/Pages/AddDropPage.php), [StudentCoursesPage](../../app/Filament/Student/Pages/StudentCoursesPage.php), [GradesPage](../../app/Filament/Student/Pages/GradesPage.php), [CourseSurveyPage](../../app/Filament/Student/Pages/CourseSurveyPage.php), [CourseTreePage](../../app/Filament/Student/Pages/CourseTreePage.php) | Singleton/cached lookup in `AppServiceProvider::boot()`, invalidate on `SemesterResource` save. |
| P1-5 | N+1 in `StudentObserver::cascadeWithdraw` — `activeCourseOfferings()->get()` loads all rows even when we'd be fine with `cursor()` or `exists()`. | [StudentObserver.php](../../app/Observers/StudentObserver.php) | Use `->cursor()` in the foreach. |
| P1-6 | Audit log churn on GPA updates (see [[../bugs-and-fixes/2026-04-18-double-gpa-recalc-and-audit-churn]]). | [AuditObserver.php:18](../../app/Observers/AuditObserver.php#L18) + [ViewCourseGrades.php](../../app/Filament/Resources/Grades/Pages/ViewCourseGrades.php) | Drop `'gpa'` from watchlist + remove redundant service call from page. |
| P1-7 | Notifications dispatch **synchronously** — already documented in [[../project-context/gotchas#Notifications run synchronously (no queue)]]. Worth revisiting before scale. | [NotificationService.php](../../app/Services/NotificationService.php) | `implements ShouldQueue` on notification classes OR move dispatch to a queued job. |

### P2 — Code quality / data integrity

| # | Issue | Anchor | Fix sketch |
|---|---|---|---|
| P2-8 | No Form Request classes. `RegistrationController@store` has ~100 lines of inline validation (custom rules, date math, phone format). | [RegistrationController.php](../../app/Http/Controllers/RegistrationController.php) | Extract to `app/Http/Requests/StoreApplicationRequest.php` (the project has zero Form Requests today — this would set the first precedent, worth a [[../decisions/README|decision note]]). |
| P2-9 | Grade→letter conversion duplicated across `Grade::convertToLetter()` and `TransferCourse::booted()` — same match statement in two places (also flagged in [[../project-context/gotchas#Grade auto-calculates total/letter/points on save]]). | [Grade.php](../../app/Models/Grade.php), [TransferCourse.php](../../app/Models/TransferCourse.php) | Extract `CalculatesLetterGrade` trait or move to an enum helper. |
| P2-10 | Enrollment-query shape duplicated across 4 portal pages — no repository. | [StudentCoursesPage](../../app/Filament/Student/Pages/StudentCoursesPage.php), [GradesPage](../../app/Filament/Student/Pages/GradesPage.php), [AddDropPage](../../app/Filament/Student/Pages/AddDropPage.php), [CourseSurveyPage](../../app/Filament/Student/Pages/CourseSurveyPage.php) | `StudentEnrollmentRepository::activeInSemester(Student, Semester)`. |
| P2-11 | `EnrollmentNumberService` race condition — already in gotchas, worth a guard. | [EnrollmentNumberService.php:25-29](../../app/Services/EnrollmentNumberService.php) | `lockForUpdate` inside the approve txn, or add DB-level unique constraint + retry. |
| P2-12 | Add/Drop `MAX_ADDS = 3` / `MAX_DROPS = 3` constants defined but not enforced at submit time. | [AddDropPage.php](../../app/Filament/Student/Pages/AddDropPage.php) | Validate count before `AddDropRequest::create`. |
| P2-13 | `TransferCourse.course_id` not constrained to student's specialization. | [TransferCourse.php](../../app/Models/TransferCourse.php) | Validate in form or observer: course must be in `$student->specialization->courses`. |

## Strengths (to preserve on refactors)

- **Observer-driven invariants** — GPA recalc, enrollment cascade, audit logging all centralized. Any refactor that bypasses observers (e.g. `saveQuietly`) must either invoke the service directly or be paired with an explicit test.
- **Rich pivot `StudentEnrollment` + `active*` convention** — the Apr 16 refactor made this enforceable. Future pages must keep using `active*` relations.
- **Transactional multi-row writes** in `ApproveApplication` and `EnrollStudentInOffering`.
- **Config-driven WhatsApp** (absent `QONVO_API_TOKEN` silently disables).
- **Explicit audit watchlist** (allow-list, not implicit).

## Weaknesses (summary)

1. **No Form Request classes.** Validation lives inline in `RegistrationController` — untestable in isolation.
2. **Missing direct authorization** on Filament page actions (P0-1, P0-2).
3. **Uncached active-semester lookup** hits DB on every portal page.
4. **Duplicated grade-letter logic** (Grade vs TransferCourse).
5. **`super_admin` gate bypass** can hide missing policy methods.
6. **Synchronous WhatsApp** calls block HTTP requests.

## Recommended next sprint (in order)

1. Apply the two-file fix for [[../bugs-and-fixes/2026-04-18-double-gpa-recalc-and-audit-churn|double GPA recalc + audit churn]] — ~15 min, highest value.
2. P0-1, P0-2, P0-3 — security gates and throttling. ~2 hours total.
3. Cache active semester (P1-4) — ~1 hour, measurable perf win on portal.
4. Form Request for application intake (P2-8) — requires [[../decisions/README|decision]] on project convention first.
5. Extract `CalculatesLetterGrade` trait (P2-9).

## Things still unverified

- Does `super_admin` bypass mask any policy methods that would otherwise fail? (Would require reading all 14 policies.)
- Is `EnrollmentNumberService` ever called from a queue worker? (If yes, P2-11 escalates to P1.)
- Do any remaining `Student::enrollments()` calls exist in non-presentation code (jobs, services)? Phase 4 swept presentation only.

Capture these in [[../daily-notes/2026-04-18-deep-codebase-analysis#Loose ends|today's daily note]] as follow-up tasks.
