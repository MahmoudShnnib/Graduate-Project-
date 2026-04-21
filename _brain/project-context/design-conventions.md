---
date: 2026-04-21
tags: [context, design, conventions, ui, patterns, reference]
related: [[project-overview]], [[architecture]], [[gotchas]], [[../../CLAUDE]]
---

# Design & Code Conventions

> The single source of truth for visual, structural, and code patterns this project uses. Read this before adding any new Filament page, button, banner, or Action. **When in doubt, match a pattern here — don't invent a new one.**

Every entry is grounded in a real file in the codebase so you can open it and see the pattern in context.

---

## 1. Language & locale

- **Arabic-first RTL.** Every user-facing label, button, badge, notification, and validation message is authored in Arabic. English appears only in raw identifiers (emails, enrollment numbers, the applicant's English name, route names, code symbols).
- **Tone:** formal Arabic (فصحى), no colloquialisms. "إلغاء" not "بطّل".
- **Never rely on auto-derived labels** — always explicit `->label('…')` on Filament fields/columns.
- **Enum labels:** every backed enum ships a `label(): string` method returning Arabic. Use it via `$state->label()` in UI.

## 2. Color tokens (Filament's built-ins, reused everywhere)

Filament exposes these 7 semantic colors globally — use them, don't introduce new ones:

| Color | Semantic | Example use |
|---|---|---|
| `primary` | Navigation, brand, CTA | Default button, ثوابت link |
| `success` | Positive outcome, completed | "مقبول", "مكتمل", enrolled |
| `warning` | Pending review, partial | "قيد المراجعة", "جزئي" |
| `danger` | Negative outcome, destructive, blocked | "مرفوض", "موقوف", "مغلق", delete buttons |
| `info` | Informational, transfer, graduated | "منتقل", "متخرج" |
| `gray` | Neutral / unavailable / stub | "غير متاح", `—` placeholders |

**Never use raw Tailwind hex**. If a shade doesn't exist, extend the tokens globally (admin theme.css) rather than hard-code.

## 3. Status badges

**Standard Arabic status labels + color mapping (used project-wide):**

| Arabic | Color | Typical use |
|---|---|---|
| قيد المراجعة | warning | pending / awaiting decision |
| مقبول | success | accepted / approved |
| مرفوض | danger | rejected |
| مكتمل | success | all items processed |
| جزئي | warning | some items processed |
| معلّق | gray | nothing decided yet |
| موقوف | danger | suspended / forced-withdrawal |
| مسجّل | success | actively enrolled |
| مغلق | danger | course offering closed |

Render via `<x-admin.badge color="…">…</x-admin.badge>` or inline Tailwind using `bg-{color}-100 text-{color}-700 dark:bg-{color}-500/20 dark:text-{color}-400`.

## 4. State banners (the "red-card" pattern)

When the student/admin is **blocked from an action** because of an inbound state (suspension, closure, ineligibility), render a prominent banner card — NOT a small inline message.

**Canonical example:** "غير مؤهل حالياً" on [thesis-registration.blade.php:17-55](../../resources/views/filament/student/pages/thesis-registration.blade.php).

**Structure:**
```blade
<div class="flex items-start gap-4 p-4 rounded-xl border bg-{color}-50 border-{color}-200 dark:bg-{color}-500/10 dark:border-{color}-500/30">
    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-{color}-100 dark:bg-{color}-500/20">
        <x-heroicon-o-{icon} class="text-{color}-600 dark:text-{color}-400" style="width:1.25rem;height:1.25rem" />
    </div>
    <div class="flex-1">
        <p class="font-semibold text-{color}-800 dark:text-{color}-300 text-sm">{title}</p>
        <p class="text-sm text-{color}-700 dark:text-{color}-400 mt-0.5">{description}</p>
    </div>
</div>
```

**Used by:**
- Thesis eligibility check (green success / red danger)
- [course-survey.blade.php](../../resources/views/filament/student/pages/course-survey.blade.php) — suspension banner
- [add-drop.blade.php](../../resources/views/filament/student/pages/add-drop.blade.php) — suspension banner
- [student-add-drop-view.blade.php](../../resources/views/filament/pages/student-add-drop-view.blade.php) — progress banner (green/warning/gray)

**Icon choice per color:**
- success → `check-circle` (or `check`)
- warning → `clock` (in-progress) or `information-circle` (advisory)
- danger → `pause-circle` (suspension), `x-circle` (rejection), `x-mark` (blocked)
- gray → `inbox` (empty state)

## 5. Page composition (drill-in detail pattern)

The **StudentSurveyView** / **StudentAddDropView** pattern — used whenever an admin drills into one student's submissions of a type:

```
┌─ Student Info Section ──────────────────┐
│  اسم الطالب · رقم القيد · التخصص · فصل │
└─────────────────────────────────────────┘

┌─ Progress/State Banner (red-card style) ┐
│  Title + counts + status verb           │
└─────────────────────────────────────────┘

┌─ Action Table ──────────────────────────┐
│  Individual rows with approve/reject    │
│  buttons                                 │
└─────────────────────────────────────────┘

← العودة لـ{parent list label}
```

**Reference files:**
- [StudentSurveyView.php](../../app/Filament/Pages/StudentSurveyView.php) + [student-survey-view.blade.php](../../resources/views/filament/pages/student-survey-view.blade.php)
- [StudentAddDropView.php](../../app/Filament/Pages/StudentAddDropView.php) + [student-add-drop-view.blade.php](../../resources/views/filament/pages/student-add-drop-view.blade.php)

**URL convention for drill-ins:** `/admin/{entity}-{operation}/{student_id}/{semester_id}` (e.g. `/admin/student-survey/5/1`, `/admin/student-add-drop/5/1`). Not under the resource — these are cross-cutting views.

## 6. Grouped-list index pattern

When admin-side showing one row per student (aggregating submissions):

**Reference files:**
- [ListAddDropRequests.php](../../app/Filament/Resources/AddDropRequests/Pages/ListAddDropRequests.php) + [list-add-drop-requests.blade.php](../../resources/views/filament/resources/add-drop-requests/pages/list-add-drop-requests.blade.php)
- [CoursesSurveyResults.php](../../app/Filament/Pages/CoursesSurveyResults.php) — students section inside merged page

**Columns:** الطالب · رقم القيد · التخصص · counts · حالة badge · link-icon action

**Clickable row:** wrap the `<tr>` with `onclick="window.location='{detailUrl}'"` + `cursor-pointer`. Keep the explicit link column on the right too so the affordance is obvious.

**"مكتمل" when pending=0:** status is computed, not stored. `withCount([... fn ($q) => $q->where('status', 'pending')])` in the query; template checks `$row->pending_count === 0`.

## 7. Modal popups with forms

**Reference files:**
- [CoursesSurveyResults.php](../../app/Filament/Pages/CoursesSurveyResults.php) — "فتح المقررات المختارة" with a pre-filled Repeater
- [CourseOfferingsRelationManager.php](../../app/Filament/Resources/Semesters/RelationManagers/CourseOfferingsRelationManager.php) — single-course "فتح مادة"
- [StudentResource.php:401-440](../../app/Filament/Resources/Students/StudentResource.php) — "معادلة مواد" slide-over

**Pattern for pre-filled multi-row popup:**
```php
Action::make('...')
    ->modalHeading('…')
    ->modalWidth('3xl')          // 5xl for image previews
    ->modalSubmitActionLabel('فتح')
    ->modalCancelActionLabel('إلغاء')
    ->fillForm(fn (): array => [
        'courses' => collect($this->selectedCourses)
            ->map(fn ($id) => [...])
            ->values()->all(),
    ])
    ->form([
        Repeater::make('courses')
            ->schema([...])
            ->addable(false)
            ->deletable(false)
            ->reorderable(false),
    ])
    ->action(fn (array $data) => ...);
```

**Key flags:** `addable(false)->deletable(false)->reorderable(false)` when the set of items is fixed (driven by a prior selection).

## 8. Infolist actions — use `hintAction`

The trap: `Filament\Schemas\Components\Actions` silently doesn't render in infolists. Three distinct concepts Filament exposes:

| Context | API | Use for |
|---|---|---|
| Form schema | `Filament\Schemas\Components\Actions::make([...])` | Button grouping inside a form |
| **Infolist entry** | `$entry->hintAction(Action::make(...))` | **Clickable action next to a view-mode field** |
| Page header | `getHeaderActions(): array` | Top-of-page toolbar |

**Reference:** [StudentResource.php:195-227](../../app/Filament/Resources/Students/StudentResource.php) — `ImageEntry->hintAction(Action::make('preview_degree_image')->modalContent(...))`. (Note: T1 from 2026-04-20 is still PINNED — the hintAction approach renders but the modal didn't show in user's testing; next-session candidate.)

## 9. Toggle icon buttons (chevron with rotation)

For show/hide sections — use a single chevron that rotates, not two icons swapping:

**Reference:** [courses-survey-results.blade.php](../../resources/views/filament/pages/courses-survey-results.blade.php) — courses-table toggle

```blade
<button type="button"
        wire:click="toggleX"
        aria-expanded="{{ $open ? 'true' : 'false' }}"
        aria-label="{{ $open ? 'إخفاء' : 'إظهار' }}"
        title="{{ $open ? 'إخفاء' : 'إظهار' }}"
        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full
               bg-gray-100 dark:bg-white/5 text-gray-500 dark:text-gray-400
               hover:bg-gray-200 dark:hover:bg-white/10
               hover:text-gray-900 dark:hover:text-white transition">
    <x-heroicon-m-chevron-down
        class="h-4 w-4 transition-transform duration-200 {{ $open ? 'rotate-180' : '' }}" />
</button>
```

- **Single icon + CSS rotation** (not two swapped icons).
- **Circular icon button** (`h-8 w-8 rounded-full`) — never text + icon.
- **Mini heroicon variant** (`m-chevron-down`, not `o-`) — sharper at 16px.
- **Always ship `aria-expanded`, `aria-label`, `title`** for accessibility.
- **Transition on transform** (`duration-200`) feels tactile.

## 10. Persistent UI state

Admin preferences that should survive page reload → `#[\Livewire\Attributes\Session]` on the public property:

**Reference:**
- [ListApplications.php:20-21](../../app/Filament/Resources/Applications/Pages/ListApplications.php) — `scheduledExamDate`
- [CoursesSurveyResults.php](../../app/Filament/Pages/CoursesSurveyResults.php) — `showCoursesTable` toggle

```php
#[\Livewire\Attributes\Session]
public bool $showX = true;
```

One property per preference. Don't conflate.

## 11. Livewire reactivity rule

For any `wire:model` binding that drives an action's `->disabled()` / `->visible()` / `->hidden()` closure:

**Always use `.live`** (or `.lazy` / `.blur`), never plain deferred `wire:model`. The closure evaluates server-side; without `.live` the server doesn't see the change until the next round-trip.

**Reference:** [courses-survey-results.blade.php:54](../../resources/views/filament/pages/courses-survey-results.blade.php) — checkbox `wire:model.live="selectedCourses"` drives the header action's disabled state.

## 12. Relationship Select fields — always `searchable`+`preload`

**Convention:** any `Select::make('x_id')->relationship(...)` uses `->searchable()->preload()`. Matches Filament's themed dropdown and avoids native `<select>` styling quirks (which caused the invisible-specialization-field bug in T14).

**Reference:** [ApplicationResource.php:81](../../app/Filament/Resources/Applications/ApplicationResource.php), [UserForm.php:45](../../app/Filament/Resources/Users/Schemas/UserForm.php), [StudentResource.php:303](../../app/Filament/Resources/Students/StudentResource.php).

Plain native selects (no `searchable`) are fine for small fixed enum options (gender, marital_status) but **never** for relationship queries.

## 13. Back-link pattern

At the bottom of every drill-in / detail page:

```blade
<a href="{{ url('/admin/...') }}"
   class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline">
    <x-heroicon-o-arrow-right style="width:.875rem;height:.875rem" />
    العودة لـ{destination label}
</a>
```

- Arrow icon is `arrow-right` (visually points back in RTL layout).
- Label is `العودة لـ{noun}` — noun matches the destination page title.

## 14. Page routing

- **Resource pages** (`list`, `view`, `edit`, `create`): standard Filament Resource with `getPages()` wiring. URL auto-generated from resource slug.
- **Cross-cutting detail Pages** (drill-ins into entities not scoped by the resource's model): plain `Filament\Pages\Page` with custom `getRoutePath()`. URL convention `/admin/{noun}-{operation}/{student}/{semester}`. Set `$shouldRegisterNavigation = false`.
- **List-style admin pages with no model CRUD**: plain `Filament\Pages\Page` + custom Blade (no Table required).

Reference paths:
- Resource: `/admin/add-drop-requests` → `ListAddDropRequests` inside the resource
- Drill-in: `/admin/student-add-drop/{student}/{semester}` → `StudentAddDropView` standalone Page

---

## Code conventions

### Typed exceptions for domain invariants

Every Action-layer guard throws a **typed exception** named `{Event}Exception`, carrying context in readonly properties.

**Existing in the codebase:**
- `EnrollmentCapReachedException` — student, cap, currentCount
- `CourseOutsideSpecializationException` — student, offering
- `StudentSuspendedInSemesterException` — student, semester
- `CourseOfferingClosedException` — offering

**All 4 enrollment call sites catch all 4** with Arabic Filament notifications. Order: closed → suspended → outOfSpec → cap (declaration order doesn't matter, but the `try` block in [EnrollStudents.php](../../app/Filament/Resources/CourseOfferings/Pages/EnrollStudents.php) demonstrates the convention).

### Action-guard + Observer-cascade for state-gated invariants

See [[gotchas#"Action guard + Observer cascade" — two chokepoint pattern for state-gated invariants]]. Rule of thumb:
- Single gating flag + single mutation path → action-level cascade is enough (T13 suspension).
- Single gating flag + multiple mutation paths → observer for cascade (T18 offering closure).
- Action-level guard required in **both** cases to prevent new violations.

### Observer transition detection

See [[gotchas#Observer true→false transitions need BOTH `wasChanged()` and `getOriginal()` checks]].

```php
if (! $model->wasChanged('field')) return;
if (! (bool) $model->getOriginal('field')) return;  // wasn't in starting state
if ((bool) $model->field) return;                    // ended in wrong state
// proceed with cascade
```

### Idempotency in Actions

Every Action starts with an `isAlreadyActive` / `alreadyProcessed` short-circuit. Example from [EnrollStudentInOffering](../../app/Actions/Enrollment/EnrollStudentInOffering.php):

```php
$isAlreadyActive = StudentEnrollment::where(...)->active()->exists();

if (! $isAlreadyActive) {
    // run ALL guards here
}

// upsert happens unconditionally (updateOrCreate)
```

Benefits: re-running the action on current state is a no-op; guards only apply to state-changing calls; retries are safe.

### `active*` relations for filtered defaults

Any read path that should exclude dropped/withdrawn enrollments uses the `active*` relation — never `enrollments()`.

- `Student::activeEnrollments()` filters `status = Enrolled`
- `Student::activeCourseOfferings()` same via pivot
- `CourseOffering::activeEnrollments()` same

### Action vs Page

- **Action class** (`app/Actions/**`): orchestrates one business op, often invokable, wraps multi-row writes in `DB::transaction`. When it writes to multiple tables or emits side effects, it's an Action.
- **Service** (`app/Services/**`): stateless computation or external integration. Pure(ish), reusable across Actions.
- **Filament Page / Resource Page**: UI shell. Thin — calls Actions/Services.

New code goes into the matching directory. Don't mix concerns.

### Single source of truth for tables

When a standalone Filament Page needs to render the same table that a Resource already defines (with columns/actions/filters), reuse it:

```php
public function table(Table $table): Table
{
    return XxxResource::table($table)
        ->query(fn () => XxxModel::query()->where(...));
}
```

**Critical:** always add `->query(fn () => ...)` when reusing outside the Resource context — the Resource's table doesn't set a root query; it relies on the Resource's `getEloquentQuery()` plumbing that only fires in Resource pages. See [[../research/2026-04-20-agent-mistakes-and-lessons#Mistake 10]].

### Audit trail

`AuditObserver::WATCHED_FIELDS` is an **explicit allow-list**, not "track everything". When adding a new auditable column, update `WATCHED_FIELDS` AND ensure the model is registered via `Model::observe(AuditObserver::class)` in `AppServiceProvider::boot()`.

### Seeder + permission naming

Shield uses `pascal` case + `:` separator — `ViewAny:CourseOffering`, `Create:Student`, `Update:Grade`. Never use snake_case names in queries against `permissions.name`. See [[../bugs-and-fixes/2026-04-20-filament-shield-roles-broken]] for the full bug history.

### Role column + Spatie role must stay in sync

`users.role` (string) and Spatie's role assignment **both** get checked across the codebase. Whenever creating a User programmatically, call `$user->syncRoles([$user->role])` immediately after. Applied in:
- `ApproveApplication::handle`
- `CreateStudent::handleRecordCreation`
- `CreateUser::afterCreate`
- `EditUser::afterSave`
- `RolesAndPermissionsSeeder::repairUserRoles`

---

## Notification conventions

Use dedicated methods, not generic `notifyStudent(...)`:

- `notifySuspensionDecision($request)` — on suspension approve/reject
- `notifyThesisDecision($request)` — on thesis approve/reject
- `notifyAddDropDecision($request)` — on add-drop decide
- `notifyGradePublished($grade)` — on grade approval
- `notifyWelcomeStudent($student)` — on application approval
- `notifyApplicationReceived($application)` — on intake
- `notifyClearanceIssued($clearance)` — on clearance

All pass the model; the service fetches the notifiable + dispatches via `HasWhatsAppChannel`. Pass `$record->fresh()` after `$record->update(...)` to ensure the dispatched notification sees the new state.

---

## File placement cheatsheet

| What you're adding | Where it goes |
|---|---|
| New domain invariant | `app/Exceptions/{Name}Exception.php` + guard in the relevant Action |
| Side-effect cascade on state change | `app/Observers/{Model}Observer.php` + register in `AppServiceProvider` |
| Reusable business op | `app/Actions/{Domain}/{VerbNoun}.php` with `__invoke` |
| Reusable computation / integration | `app/Services/{Name}Service.php` |
| Admin CRUD for a model | `app/Filament/Resources/{Plural}/` folder (Resource + Pages/ + Schemas/) |
| Admin cross-cutting view (not a CRUD) | `app/Filament/Pages/{Name}.php` + matching Blade |
| Student portal page | `app/Filament/Student/Pages/{Name}.php` + matching Blade |
| Arabic RTL Blade partial reused across pages | `resources/views/filament/components/{name}.blade.php` |
| Reusable Blade UI primitive (badge, empty-state) | `resources/views/components/admin/{name}.blade.php` |

---

## Anti-patterns (do NOT do these)

1. **Don't use `<select>` without `searchable()->preload()` for relationship options.** Breaks RTL styling.
2. **Don't use plain `wire:model` on inputs that drive `->disabled()` / `->visible()` closures.** Use `.live`.
3. **Don't place domain invariants in Filament page action closures.** They belong on Actions.
4. **Don't propagate an unreviewed call shape from existing code.** Verify the method exists — `grep -n 'public function ...' app/Services/...` is 2 seconds.
5. **Don't call `Resource::table($table)` from a standalone Page without `->query(fn => ...)`.** Error will be `Table must have a [query(), relationship(), or records()]`.
6. **Don't swap between two icons for toggle states.** Use one icon + CSS rotation.
7. **Don't mix `defaultSort()` with `getEloquentQuery` orderBy for multi-tier sorts.** Use `->modifyQueryUsing` + `$query->reorder()`.
8. **Don't loosen a new Action guard because existing test fixtures don't set up the full domain state.** Fix the fixtures — they were always incomplete.
9. **Don't flip `Student.status` for per-semester suspensions.** Use `SuspensionRequest.semester_id` + `isSuspendedInSemester()` helper.
10. **Don't conflate "UI didn't change" with "code is broken".** Reproduce the SQL / DB state first — `wire:model` deferral, browser cache, or user expectation mismatch account for 80% of these reports.

---

## When this file should be updated

- New user-facing color or icon convention → section 2/4/9
- New structural page layout (drill-in, grouped list, etc.) → section 5/6
- New exception or observer pattern → relevant code section
- Any mistake/anti-pattern you catch yourself avoiding → anti-patterns list
- **Never** duplicate information from gotchas.md — link to it instead.
