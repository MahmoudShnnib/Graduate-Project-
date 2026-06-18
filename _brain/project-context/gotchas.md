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

## WhatsApp `NotificationLog` status is reconciled, not optimistic (2026-06-18)

Qonvo's **send** endpoint (`POST /whatsapp/messages/send`) only **queues** the message: it returns `2xx` with `data.status = "pending"` even for sends that ultimately **fail** (e.g. self-send, or a downstream WhatsApp error — Qonvo's dashboard then shows "Failed: Failed to send message through WhatsApp service"). So a `2xx` is NOT proof of delivery.

The fix: `WhatsAppChannel` now logs the message as **`pending`** (mapped from Qonvo's response via `WhatsAppService::mapStatus()`) and stores `notification_logs.provider_message_id` (Qonvo's id). The **real** status is fetched later from `GET /whatsapp/messages/{id}` (returns `status` + `error_message`) by `WhatsAppService::reconcilePendingLogs()`, run via:
- the `whatsapp:reconcile` command (scheduled every 5 min in `routes/console.php` — needs `schedule:work`/cron), and
- a **"تحديث حالات الإرسال"** header button on `/admin/notification-logs` (fallback, no scheduler needed).

Statuses: `pending` (قيد الإرسال, queued at Qonvo) → reconciles to `sent` or `failed`. An exception during send (Qonvo unreachable) still logs `failed` immediately. So `status='failed'` is now trustworthy; `status='pending'` means "not yet reconciled". See [[../bugs-and-fixes/2026-06-18-whatsapp-log-status-reconciliation]].

(Email is still optimistic — `notify()` not throwing means "handed to the mail transport", not delivered.)

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

## Vite manifest needed for Filament tests

Tests that hit Filament-rendered pages (like `$this->get('/admin/grades')->assertOk()`) fail with "Unable to locate file in Vite manifest: resources/css/filament/admin/theme.css" when `public/build/manifest.json` is missing/stale. Filament's base layout renders `@vite('resources/css/filament/admin/theme.css')` directly.

**Fix:** `npm run build` generates the manifest. Note: the test runs against the built assets, not the dev server, so `npm run dev` alone doesn't solve this for CI or local test runs.

Symptoms can appear after `php artisan optimize:clear` or clearing the Vite build dir. If a test suddenly starts failing on a Vite manifest error after a cache clear, rebuild with `npm run build` and retry.

## `Heroicon::*` enum case names must be verified against the vendor source

Filament v5's `Filament\Support\Icons\Heroicon` enum names diverge from heroicons.com's display names in ways that are easy to misremember. Real cases that DON'T exist: `IdentificationBadge`, `HeartFilled`, `IdentificationSolid`. Real cases that DO exist: `Identification`, `Heart`, `Star`, `Sparkles`, `MapPin`, `Flag`, `Calendar`, `BuildingLibrary`. The outlined variants are prefixed `Outlined*` (e.g. `OutlinedIdentification`).

A wrong case name **does not fail the test suite** — Filament resolves icons at request render time, not boot time. Pages that aren't covered by a Livewire/HTTP test will crash for the user with `Undefined constant Filament\Support\Icons\Heroicon::*` and CI stays green.

**Convention:** before typing any `Heroicon::*` (or `Filament\Support\Enums\*`, or any vendor enum case) from memory, grep the vendor source:

```bash
grep -i "case .*identif" vendor/filament/support/src/Icons/Heroicon.php
```

One bash call. Catches the mistake before the user does. See [[../research/2026-04-20-agent-mistakes-and-lessons#Mistake 11]] for the 2026-05-17 incident that surfaced this rule.

## Filament has three "Actions" concepts that look similar but aren't interchangeable

1. **`Filament\Schemas\Components\Actions`** — field-like component that groups Action buttons inside a **form**. Does NOT render in infolists.
2. **`Filament\Actions\Action` → `->hintAction($action)`** on an infolist entry — the correct way to attach a clickable action (with `->modalContent(...)`) to a view-mode field. Renders a small button next to the label.
3. **Page-level actions via `getHeaderActions()`** on a page class — for top-of-page toolbar buttons.

Mixing them is a silent no-op — the button is simply absent from the rendered HTML. `hintAction` only works on infolist entries that use the `HasHint` trait (all standard entries do).

## Filament tables: user column-click sort can override `defaultSort`

If you need a sort that must survive user column clicks (e.g. "completed applications always at the bottom"), `->defaultSort(...)` is insufficient — Filament replaces it when the user clicks a sortable column header.

Use `->modifyQueryUsing(function (Builder $query) { $query->reorder()->orderByRaw(...)->orderByDesc(...); })` instead. `->reorder()` drops any previously attached orderBy clauses so yours is the only one. For multi-tier status priority use `CASE WHEN status = 'a' THEN 0 WHEN status = 'b' THEN 1 ELSE 2 END ASC` (ANSI SQL, portable across MySQL/PG/SQLite).

## Filament `modifyQueryUsing` lambda parameter MUST be named `$query`

Filament v5's `evaluate()` resolves closure parameters by **name** first (then by type as fallback). For `modifyQueryUsing(fn (Builder $q) => $q->...)` — using `$q` (or `$builder`, or any other name) — name matching to the data key `'query'` fails. Filament then falls back to building a parameter via the container, which produces a **fresh empty `Builder` with `$model = null`**. The lambda's modifications run on that empty query, the original query is discarded, and downstream `$query->where(Closure)` calls crash with `Call to a member function newQueryWithoutRelationships() on null` at `Eloquent\Builder.php:355` (the closure-where path needs the model to build the sub-query).

**Always use `$query` as the parameter name:**

```php
// BROKEN — $q produces empty Builder with null model
->modifyQueryUsing(fn (Builder $q) => $q->whereIn('status', $states))

// CORRECT
->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $states))
```

Tests don't catch this — the page only crashes when Filament renders `getAllTableSummaryQuery()` (the summary footer), which happens in browser rendering, not in `Livewire::test(...)->mount()` unless you assert on the rendered view. The bug surfaced 2026-05-17 on `/admin/thesis-registrations?tab=pending` after M-6's tab additions used `$q`. Same trap applies to any other Filament evaluate-driven callback (`->relationship(fn (Builder $q) ...)`, `->using(fn (Model $r) ...)` where `$r` should be `$record`, etc.). **Match the parameter name to the standard Filament data key.**

## Filament Action `->disabled()` / `->visible()` needs `wire:model.live`

If an action's `->disabled(fn () => empty($this->selectedX))` or `->visible(...)` depends on a Livewire public property set via a custom Blade `<input wire:model="...">`, plain deferred `wire:model` **won't trigger a re-render** of the action — the button's state is stuck at whatever the property was on the last server roundtrip.

Fix: use `wire:model.live` (immediate sync) or `wire:model.lazy` (sync on blur) instead. Example: `/admin/courses-survey-results`, [courses-survey-results.blade.php:54](../../resources/views/filament/pages/courses-survey-results.blade.php) — the per-row checkbox uses `.live` so the header action's disabled state updates as soon as a course is checked.

Filament's own table bulk actions handle this internally. This gotcha only bites on custom Blade pages that expose their own checkboxes / form fields driving action state.

## Observer true→false transitions need BOTH `wasChanged()` and `getOriginal()` checks

When an observer reacts to a boolean field flipping one direction (e.g. `CourseOffering::is_open` true→false in T18), a single `wasChanged('field')` check is insufficient:

- `wasChanged()` returns true on any update where the field's new value differs from `getOriginal()`, **including** false→true.
- On newly-created records, `wasChanged()` behavior varies by Eloquent event timing — `updated()` event doesn't fire on create, so that's safe, but still check both sides.

Defensive pattern (from `CourseOfferingObserver`):

```php
if (! $offering->wasChanged('is_open')) return;

$previousIsOpen = (bool) $offering->getOriginal('is_open');
$currentIsOpen  = (bool) $offering->is_open;

if (! $previousIsOpen || $currentIsOpen) return;  // only fire on true → false
```

Applies to any observer that watches a directional transition, not just booleans (e.g. status: Pending → Approved vs Approved → anything else).

## "Action guard + Observer cascade" — two chokepoint pattern for state-gated invariants

When a domain invariant depends on a model's current state (e.g. "can't enroll into a closed offering"), split enforcement into two places:

1. **Action-layer guard** — blocks new writes that would violate the invariant from now on. Typed exception, caught by UI callers with human-friendly messages.
2. **Observer cascade** — fixes up existing state when the gating flag flips, so the system moves into a consistent state the moment the flag changes.

Examples in this codebase:
- T13 suspension: `EnrollStudentInOffering` guard (StudentSuspendedInSemesterException) + `SuspensionRequestResource` approve action cascading to Withdrawn (no observer — the action is rare enough to do it in-place).
- T18 closed offering: `EnrollStudentInOffering` guard (CourseOfferingClosedException) + `CourseOfferingObserver::updated` cascade. Observer fits here because `is_open` can flip from many UI paths (main resource, semester RelationManager, bulk actions).

Rule: if the gating flag has one mutation path → handle in that action. If it has multiple mutation paths → observer. Both need the Action-layer guard either way.

## Tightening an Action's preconditions breaks tests that used loose fixtures

When you add a new guard (specialization check, cap check, etc.) inside an Action like `EnrollStudentInOffering`, existing tests that built minimal-but-unrealistic fixtures will start failing. Example from T15: adding the spec guard broke 10 pre-existing tests because `makeEnrolledStudent()` + `CourseOffering::factory()->create()` never attached courses to specializations.

**Before merging a new Action invariant:** run the full suite and budget fixture repair time. The fix is almost always to teach the test to set up the full domain state (attach pivots, assign roles, populate `specialization_id`, etc.) — NOT to weaken the guard.

If you find yourself thinking "maybe I should make the guard optional via a flag" to avoid breaking tests, that's a smell — the domain truth isn't optional.

## Domain invariants belong on Actions, not on UI pages

When a rule applies to *every* path that writes a given model (e.g. "students can't have >4 active enrollments per semester"), enforce it inside the single-responsibility Action class — never in a Filament page's `->action()` closure.

Why: enrollment-write paths in this repo include `EnrollStudents` page (2 actions), `StudentSurveyView` (2 actions), `AddDropRequestResource` approve, programmatic seeders, and tests. Duplicating the guard across all 5+ sites guarantees drift. Typed exceptions give callers a clean surface to catch and translate to Arabic notifications.

Pattern used for the 4-enrollment cap (T9, 2026-04-20):

```php
// Action throws typed exception:
throw new EnrollmentCapReachedException($student, $cap, $currentCount);

// Each caller catches + shows Arabic notification:
try {
    $enroll($student, $offering);
} catch (EnrollmentCapReachedException $e) {
    Notification::make()->title('...')->danger()->send();
    return;
}
```

**Idempotency trap:** an invariant check that counts *all* writes can false-block legitimate re-runs on unchanged data. Always scope the check: "would this call add a NEW row?" not "is the count already at cap?". See `EnrollStudentInOffering::__invoke` — it checks `isAlreadyActive` before counting.

## Filament widget action modals need their own `<x-filament-actions::modals />` slot

When a Filament Widget implements `HasActions` + `InteractsWithActions` and you want a button on the widget to open an action's modal, **the widget Blade must render `<x-filament-actions::modals />`**, otherwise clicking the button silently mounts the action but no modal appears.

The page-level modal slot (rendered by `<x-filament-panels::page>`) only sees actions on the page itself, NOT actions defined on its widgets. Each Livewire component (widgets are Livewire components) renders its own action modals.

Also: prefer **explicit** `wire:click="mountAction('actionName')"` over the magic `{{ $this->actionName }}` render — the magic property render is brittle on widgets and can silently produce a button that doesn't wire properly. Pattern:

```blade
<x-filament-widgets::widget>
    <x-filament::button wire:click="mountAction('editSettings')" icon="heroicon-o-cog-6-tooth" color="gray" size="sm">
        إعدادات النظام
    </x-filament::button>

    <x-filament-actions::modals />
</x-filament-widgets::widget>
```

Surfaces using this pattern in the repo: `app/Filament/Widgets/SettingsWidget.php` + its Blade. See [[../decisions/2026-05-17-settings-dashboard-widget]] for the full settings widget rationale.

## Filament Resource `getEloquentQuery()` eager loads feed view/edit/row-actions too

`getEloquentQuery()` is the **single query source for the whole Resource** — list table, view page, edit page, and row-action record resolution (Filament's `resolveRecordRouteBinding()` resolves the single record through it). So a column-restricted eager load there (`->with(['user:id,name'])`) hydrates that relation with **only** those columns **everywhere**, not just the list.

Accessing an unselected column on an already-loaded relation returns **`null`** — Eloquent does NOT lazy-reload a relation that's already loaded. Real breakages this caused in the 2026-06-03 N+1 work (caught before shipping):

- `user:id,name` → `user.email` / `user.phone` null on the **Student view page** infolist.
- `courseOffering.semester:id,name` → `semester.is_active` / `add_drop_*` null in **AddDropRequest** approve `visible()` → the approve button silently never renders.
- Row-action notifications reading `user.email` get null.

**Rule:** on a Filament Resource `getEloquentQuery()`, eager-load **full columns** (`->with(['user', 'specialization'])`). Column restriction does NOT reduce the query count (still 1 query/relation) — it only narrows the SELECT, so it buys nothing while risking null-on-view bugs. Only restrict columns for a relation you're certain is used nowhere but one list column. See [[../decisions/2026-06-03-n1-eager-loading-deviations]].

## Setting cache must be static, not the cache facade (CACHE_STORE=database)

This app runs `CACHE_STORE=database`. With the database cache store, `cache()->remember()` **itself issues a query against the `cache` table on every call** — so wrapping `Setting::get()` in `cache()->remember()` made it *worse* (measured 8 queries for 6 gets vs the original 6).

`Setting::get()` therefore uses a **per-request static** memo (`private static ?array $cached`) that does exactly one `pluck('value','key')` per request regardless of how many gets (PDF templates call it ~6×). `booted()` saved/deleted clears the static for in-request invalidation.

**Rule:** before reaching for `cache()->remember()` as an N+1 fix, check `config('cache.default')`. On a `database`/`dynamodb` store the cache lookup is itself a query — a static (per-request) memo is the driver-agnostic win. See [[../decisions/2026-06-03-n1-eager-loading-deviations]]. (⚠️ static memo persists across requests under Octane — not used here.)

## Student grade visibility must mirror the GPA, gated on STUDENT STATUS not enrollment status

The course tree (`CourseTreePage`) walks **all** enrollments (not just active) on purpose, so a `Graduated` student — whose cascade withdraws *every* enrollment but whose GPA is **frozen with them** — keeps their transcript. So you **cannot** filter the transcript/tree by `status = Enrolled`: that wipes graduates.

The real distinction is the **student's status**, not the enrollment's:
- `OnHold`/`Inactive` flush the current term AND recalc the GPA *without* it → the flushed current-semester courses must be **hidden** from كشف الدرجات + المقررات العامة.
- `Graduated` (and others) freeze the GPA *with* the term → transcript stays intact.

Rule: hide an enrollment only when `in_array($student->status, [OnHold, Inactive]) && isCurrentSemester && status !== Enrolled`. The visible set must always equal the GPA-counted set. See [[../decisions/2026-06-17-suspended-student-grade-visibility]] (display side) + [[../decisions/2026-06-15-flush-current-semester-on-suspend]] (GPA side). Watch for tests that assert the *old* "grades stay visible after an OnHold cascade" behavior — that was reversed 2026-06-17.

## Testing gotchas

- **`Setting`'s static memo leaks across tests.** `Setting::$cached` is a per-request static; `RefreshDatabase` rolls back rows but **not** statics. A value set in one test (e.g. `Setting::set('apply_otp_required','0')`) bleeds into the next, which then reads stale config. Call `Setting::flushCache()` in `beforeEach` of any test that mutates or depends on settings (done in `WhatsAppOtpVerificationTest`). Same trap applies to any model using static memoization.
- `tests/Pest.php` provides `studentWithRecord()` and `adminUser()` — **always** use these instead of manually creating User+Student+Role
- `adminUser()` creates both the Spatie role AND the `role` column — mirror this if you hand-build admins
- `RefreshDatabase` is applied to both Feature and Unit dirs — Unit tests have DB access (unusual but intentional here because `GradeCalculationServiceTest` needs it)
- `StudentEnrollmentFactory` exists but observers fire during factory `create()` — creating an enrollment whose student is suspended will immediately get it withdrawn. Use `StudentStatus::Active` explicitly if you don't want the cascade.

## mPDF / Arabic print quirks

The ministry print templates have their own constellation of traps. Full reference: [[print-system-guide]] and [[../bugs-and-fixes/2026-05-19-mpdf-arabic-font-incompatibilities]].

- **Noto Naskh has no Latin glyphs.** `/`, `-`, digits all render as boxes unless `useSubstitutions => true` + `backupSubsFont => ['dejavusans']` are set in `makeMpdf()`. Both flags must be present on every Mpdf instance.
- **`autoLangToFont` must stay false.** With `lang="ar"` on the HTML element, leaving it true makes mPDF override `default_font` and pick its own fallback.
- **CSS dashed borders look like spaced underscores.** Use literal `--------------------` in DejaVu Sans for any horizontal rule in a print template.
- **Variable fonts only load the static default instance.** Markazi Text VF has weight axes, but mPDF ignores them. Registering the same VF under `R` and `B` makes both render identical. To get a real bold, either ship a separate static bold TTF or use the natural weight at a larger size.
- **`font-weight: bold` on a font with no `B` registered = synthetic ink-smear.** mPDF doubles-strokes. Don't do it.
- **RTL positioned blocks drift to center.** A left-anchored box (`left: 18mm`) with `text-align: right` puts the right edge of text near page-center on Letter (~108mm). Narrow the width or use `float: left` + `width: ≤ 60mm` so the block doesn't visually appear centered.
- **mPDF Arabic-font compatibility is not free.** Aref Ruqaa, Noto Kufi Arabic, and all Scheherazade versions throw OTL/GPOS errors in mPDF. **Always lab-test a new font in an isolated route before adding it to `fontdata`** — a bad font crashes the whole render. See [[print-system-guide#testing-a-new-font]].
- **Never embed multi-megapixel / alpha PNGs.** A 1577×1475 RGBA logo (~9 MB decoded in GD) rendered fine under `php artisan tinker` but degraded to mPDF's **red-X "image failed" box** on the running `php artisan serve` — a baked-in mPDF marker, *not* a browser glitch (poppler/pdf.js never draw it). The oversized alpha image fails decoding under the HTTP request's tighter resource envelope. **Fix: pre-size the image to roughly its print dimensions and flatten alpha onto white before embedding.** A 320×299 RGB PNG (29 KB) is visually identical at 22mm. Embed as base64 in the blade `@php` block (`$logoData` is read at render time → updating the file needs no `view:clear`). See [[../bugs-and-fixes/2026-05-30-mpdf-logo-red-x]].
- **Logo placeholder was a literal `&#9632;` (■).** The "black box" reports on `thesis_ministry.blade.php` were the U+25A0 placeholder char at 28pt, not a broken image. The only template that had it.
- **Centering an image in a 3-column header table needs equal side columns.** A header of `ar 50% / logo 15% / en 35%` puts the logo cell center at 57.5%, visibly off-center. Equalize the side columns (`42% / 16% / 42%`) so the middle cell lands on the page center.
