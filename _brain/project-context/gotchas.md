---
date: 2026-04-17
tags: [context, gotchas, pitfalls, warnings]
related: [[project-overview]], [[architecture]], [[../../CLAUDE]]
---

# Gotchas

> Non-obvious behavior and things that will bite you. Read before editing — especially before touching enrollment, grades, or auth.

## Hybrid role system

`users.role` is a **plain string column** (`super_admin`, `secretary`, `faculty`, `student`) **and** Spatie Permission's `HasRoles` trait adds roles via a pivot table. The codebase checks **both**, inconsistently:

- `User::canAccessPanel` for admin panel: `$this->role === 'super_admin' || $this->hasRole(Utils::getSuperAdminName())` — either is sufficient
- `Gate::before` in `AppServiceProvider` checks **only** `$user->hasRole('super_admin')` — the Spatie role, not the `role` column. A user with `role='super_admin'` but no Spatie role will **not** bypass gates.
- `MyCourseResource::canAccess` checks `$user->role === 'faculty'` — the column only
- `EnsureStudentRole` middleware checks `$user->role === 'student'` — the column only

**Why it matters:** tests and seeders set both; human-edited users may have only one. Creating an admin user programmatically without assigning the Spatie role can silently disable super-admin gate bypass.

## `super_admin` bypasses all policies

`Gate::before(fn ($user, $ability) => $user->hasRole('super_admin') ? true : null)`.

Any `Gate::allows(...)` / `->can(...)` check returns `true` for super admins regardless of policy logic. Don't rely on policies to enforce business invariants against super admins — they will pass through. Use explicit `if ($user->hasRole(...))` checks in business logic if you need to block a super admin.

## `StudentEnrollment` is never deleted — only status-flipped

Dropping, withdrawing, or cascading from student suspension all **update** the `status` field. The row persists. This has consequences:

- `unique(['student_id', 'course_offering_id'])` means **re-enrolling** a dropped student reuses the same row (`updateOrCreate` in `EnrollStudentInOffering`). There's no enrollment history of attempts.
- Any query that counts or lists enrollments **must** filter by status. The codebase's rule is: always use `activeEnrollments`, `activeCourseOfferings`, `scopeActive()`, or `wherePivot('status', Enrolled)`. Calling `Student::enrollments()` or `CourseOffering::enrollments()` unfiltered is a bug.
- `Grade` has FK `cascadeOnDelete` on `enrollment_id`, but since enrollments aren't deleted, grades persist through drop→re-enroll cycles. Old grades remain linked to the (reused) enrollment row.

## Soft-delete cascade is observer-driven, not FK-driven

The `student_enrollments.student_id` FK uses `ON DELETE CASCADE`. But **soft deletes don't trigger DB cascades** — they just set `deleted_at`. So `StudentObserver::deleting` explicitly withdraws active enrollments before the soft-delete completes.

**If you remove or misconfigure `StudentObserver`**, soft-deleting a student leaves their enrollments active (and their course rosters show them as present). Don't refactor observers without verifying the test `'soft-deleting a student cascades to drop their active enrollments'` still passes.

## Grade auto-calculates `total`/`letter`/`points` on save

`Grade::booted` has a `saving` hook. If both `coursework_grade` and `final_grade` are non-null, it **overwrites** `total`, `letter`, `points` using `Grade::convertToLetter()`. So:

- You cannot manually override a student's letter grade while both component grades are set.
- The grading scale is **hardcoded** in `convertToLetter()`: 85=A, 75=B+, 70=B, 65=C+, 60=C, 55=D+, 50=D, else F. Not DB-configurable.
- `CourseOffering.coursework_max` and `final_max` control the **input maxvalues** in the grade form, but the letter thresholds ignore them. If you change a course's distribution from 40/60 to 30/70, students' letter grades still use the same absolute thresholds on the summed total.
- `TransferCourse` has **the same auto-compute logic** in its own `booted()`.

## `Semester::isActive` is a singleton assumption

Many pages call `Semester::where('is_active', true)->first()` with no guard against multiple active semesters. If an admin accidentally sets `is_active=true` on two semesters, `first()` silently returns one — usually the lowest `id` — which may not be the one currently in use. There's no DB constraint preventing multiple active semesters.

## Course survey menu vs course offerings

Two course↔semester relationships exist:

- `semester_course` pivot — "these courses are shown to students in the survey for this semester"
- `course_offerings` table — "these concrete sections exist with a faculty assignment"

They are **not automatically in sync.** Adding a course to `semester_course` doesn't create an offering. Creating an offering doesn't add it to the survey list. `SemesterResource` has explicit admin actions (`open_survey`, `edit_survey_courses`) for the pivot; `CourseOfferingResource` manages offerings. Don't assume a course in the survey can actually be enrolled in.

## Enrollment number generation is racy

`EnrollmentNumberService::generate`:

1. Builds prefix `{YY}{specializationCode}{sessionFlag}`
2. `SELECT MAX(enrollment_no) WHERE enrollment_no LIKE '{prefix}%'` (including soft-deleted students)
3. Increments and saves

No transaction lock, no upsert on a counter table. Under concurrent application approvals, two students can read the same MAX and both try `seq = MAX + 1` — the DB unique constraint will reject the second save, surfacing as an unhandled exception in `ApproveApplication`. In practice rare (applications are approved one at a time in the UI), but don't call this service from concurrent workers.

## `WATCHED_FIELDS` in `AuditObserver` is static + per-class

Located at `app/Observers/AuditObserver.php`. Fields are an allow-list. If you add a new column to Grade/Student/etc. that should be auditable, you **must** edit this constant. There's no "track all changes" fallback. Columns not in the list are invisible to `AuditLog`.

Also: the observer is registered per-class in `AppServiceProvider::boot()`. If you add a model to `WATCHED_FIELDS` but forget to register it, changes won't be captured — and there's no error.

## Notifications run synchronously (no queue)

Despite `composer run dev` launching `php artisan queue:listen`, **no notification implements `ShouldQueue`**. This means:

- `ApproveApplication` blocks the HTTP request while sending welcome email + WhatsApp
- If Qonvo (WhatsApp provider) is slow, grade approval / enrollment / any notify call can time out
- `NotificationService::dispatch` wraps the notify in try/catch and logs failures, so partial failures won't abort the action — but latency still hurts UX

If you add a new notification that may be slow, you probably want `ShouldQueue`. Just be aware there's no precedent in the codebase and Laravel queue driver config may need validation.

## Notification status is optimistic

`NotificationService::dispatch` marks email as `'sent'` if `notifiable->notify()` doesn't throw. For Laravel's mail channel, that means "handed off to the mail transport" — not "delivered to the recipient."

For WhatsApp, it's even murkier: the service sets `$whatsappStatus = 'sent'` just because `config('services.qonvo.api_token')` exists, **before** the Qonvo call is made. The actual send happens inside `$notifiable->notify($notification)` through the `WhatsAppChannel`, which logs failures to Laravel log but doesn't propagate them back. So a `NotificationLog` row with `status='sent'` for WhatsApp means "we attempted it" — not "it delivered."

Don't use `NotificationLog.status='sent'` as evidence the student saw the message.

## Libyan phone format is baked in

`WhatsAppService::formatPhone` and `RegistrationController::store` both assume **Libyan 10-digit phone numbers starting with 09**. The WhatsApp service converts `09XXXXXXXX` → `+218 9XXXXXXXX`. The registration form rejects anything else.

If you ever need to support other country codes, this logic is duplicated across the validator (controller), the model validation hint text, and the service's conversion code — all will need changes.

## Arabic validation rules are custom

`ArabicOnly` and `MinArabicWords` are in `app/Rules/`. They use Unicode `\p{Arabic}` character classes. A few things to know:

- `ArabicOnly(allowNumbers: true)` also accepts digits, `،,-.` — used for addresses where building numbers appear
- The rules trim the input before matching but don't strip diacritics
- `MinArabicWords` counts words via `preg_split('/\s+/')` — hyphenated compound names count as one word

Mixing English in an Arabic-only field silently fails with the generic "اللغة العربية فقط" message.

## Filament panel access paths

- Admin: `/admin` (panel id `admin`, path `admin`) — also the default panel
- Student: `/portal` (panel id `student`, path `portal`)
- Admin login: `/admin/login`
- Student login: `/portal/login`
- Root `/` redirects to `filament.admin.pages.dashboard` — there's **no public landing page** beyond `/apply`
- Unauthenticated 403 on `/portal/*` redirects to `/portal/login` via the `bootstrap/app.php` exception handler — but only for `portal` paths

## Unused scaffolding / dead-looking code

- `app/Actions/BaseAction.php` — stub with broken `can()` method, no subclasses. Safe to ignore, do not `extends BaseAction` on new actions.
- `resources/views/welcome.blade.php` — stock Laravel, unused because `/` redirects.
- Some Filament Resource stubs (`StudentForm.php`, `StudentsTable.php`) were deleted in the current uncommitted branch — don't re-create them; Filament v5 keeps form/table logic on the Resource class directly.
- `tests/Feature/ExampleTest.php` and `tests/Unit/ExampleTest.php` — Laravel scaffolding, can be removed but verify test suite first.

## Uncommitted work is in flight

`git status` shows 30+ modifications on the `omar-admin-bbg` branch — an enrollment-filtering refactor (memory S83–S88): action classes, observers, status-filtered relations, new `EnrollmentConsistencyTest`. Before starting unrelated work:

- `git diff` first to see what's staged vs working
- Don't rebase, reset, or force-push without checking with the user
- The branch name suggests it's personal — merge via PR, not directly to main

## Filament Shield permissions lag behind code

`FilamentShieldPlugin` auto-discovers permissions, but permissions only exist in the DB after running the Shield generator + `RolesAndPermissionsSeeder`. Creating a new Resource doesn't automatically grant anyone access to it. New Resources require:

1. `php artisan shield:generate --resource={Name}` (or similar)
2. Re-running `RolesAndPermissionsSeeder` or manually assigning permissions

If a new Resource is invisible to an existing admin, that's the first thing to check.

## `config('services.qonvo.base_url')` is hardcoded

In `config/services.php`: `'base_url' => 'https://qonvo.ly/api'` — no env fallback. Staging/local environments with a different Qonvo endpoint will silently hit production. If you need to mock WhatsApp in tests, intercept via `Http::fake()` or never set `QONVO_API_TOKEN` locally.

## Testing gotchas

- `tests/Pest.php` provides `studentWithRecord()` and `adminUser()` — **always** use these instead of manually creating User+Student+Role
- `adminUser()` creates both the Spatie role AND the `role` column — mirror this if you hand-build admins
- `RefreshDatabase` is applied to both Feature and Unit dirs — Unit tests have DB access (unusual but intentional here because `GradeCalculationServiceTest` needs it)
- `StudentEnrollmentFactory` exists but observers fire during factory `create()` — creating an enrollment whose student is suspended will immediately get it withdrawn. Use `StudentStatus::Active` explicitly if you don't want the cascade.
