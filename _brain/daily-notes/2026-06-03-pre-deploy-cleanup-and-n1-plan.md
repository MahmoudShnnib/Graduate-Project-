---
date: 2026-06-03
tags: [daily, performance, n+1, eager-loading, deployment, cleanup, filament, eloquent]
related:
  - "[[../project-context/print-system-guide]]"
  - "[[../project-context/gotchas]]"
---

# 2026-06-03 — Pre-deploy cleanup + N+1 audit plan

Two things happened this session:

1. **Deployment cleanup** — removed all test/dummy data surfaces and seeder files before going live.
2. **N+1 audit** — identified and measured every N+1 query problem in the app. Fixes are fully planned but NOT yet implemented. Pick up here next session.

---

## Part 1 — What was already done today (complete ✅)

### Deleted files
- `database/seeders/ApplicationSeeder.php`
- `database/seeders/StudentSeeder.php`
- `database/seeders/AcademicDataSeeder.php`
- `database/seeders/DummyStudentSeeder.php`
- `app/Support/SampleApplicantData.php`

### Edited files
- `database/seeders/DatabaseSeeder.php` — removed the 3 dummy seeder calls; now only runs: `RolesAndPermissionsSeeder`, `SettingsSeeder`, `SpecializationSeeder`, `SemesterSeeder`, `CourseSeeder`, `AdminUserSeeder`
- `routes/web.php` — removed `/apply/sample` route
- `app/Http/Controllers/RegistrationController.php` — removed `sample()` method + `SampleApplicantData` import + `JsonResponse` import
- `app/Filament/Resources/Students/Pages/CreateStudent.php` — removed `fillSampleData` header action + `SampleApplicantData` import
- `resources/views/registration/create.blade.php` — removed amber "توليد بيانات تجريبية" button (lines 14–26) + JS block (lines 344–396)
- `resources/views/filament/pages/exam-list.blade.php` — removed dead `data-spec`, `data-exam`, `data-print-name`, `print-hidden` attributes
- `phpunit.xml` — added `<ini name="memory_limit" value="256M"/>` to the existing `<php>` block so `php artisan test --compact` works without a memory flag

### Deployment platform
Azure for Students was **dropped** as the hosting target. Platform TBD — ask user before giving deployment advice next session.

### Test result after cleanup
`php artisan test --compact` → **165 passed (345 assertions)** ✅

---

## Part 2 — N+1 Fix Plan (NOT YET IMPLEMENTED)

### How these were measured
Used `DB::enableQueryLog()` + `DB::getQueryLog()` in `php artisan tinker` (same data Laravel Debugbar uses). Numbers are real, not theoretical.

### Confirmed query counts

| Page | Queries now | Queries after fix | Rows |
|---|---|---|---|
| Students table | **32** | 4 | 15 |
| CourseOfferings table | **17** | 5 | 5 |
| MyCourseResource table | **22** | 2 | 5 |
| AddDropRequests table | **17** | 7 | 3 |
| Graduates table | **14** | 5 | 4 |

---

### Fix 1 — StudentResource `getEloquentQuery()`
**File:** `app/Filament/Resources/Students/StudentResource.php`

Currently has NO `getEloquentQuery()` override. Table columns `TextColumn::make('user.name')` and `TextColumn::make('specialization.name')` fire 2 queries per row.

**Add this method** (before `form()` or `table()`):

```php
use Illuminate\Database\Eloquent\Builder;

public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->with(['user:id,name', 'specialization:id,name']);
}
```

> ⚠️ The `:id,name` column selection is intentional — `id` is the FK Laravel needs to stitch the relation. Never omit it when limiting columns.

---

### Fix 2 — CourseOfferingResource `getEloquentQuery()`
**File:** `app/Filament/Resources/CourseOfferings/CourseOfferingResource.php`

No `getEloquentQuery()` override. Table columns reference `course.name`, `semester.name`, `faculty.name`.

**Add:**

```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->with(['course:id,name,code', 'semester:id,name', 'faculty:id,name']);
}
```

> Note: `MyCourseResource` (faculty view) is a **separate file** from `CourseOfferingResource` (admin view). MyCourseResource already has a `getEloquentQuery()` with `->with(['course', 'semester'])`. Don't confuse the two files.

---

### Fix 3 — MyCourseResource `grades_status` column (WORST one — 4 counts per row)
**File:** `app/Filament/Resources/Faculty/MyCourseResource.php`

The existing `getEloquentQuery()` already has `->with(['course', 'semester'])`. The `enrollments_count` column already uses Filament's `.counts('activeEnrollments')` which gives `$record->activeEnrollments_count` on each row. BUT the `grades_status` column ignores this and fires 3 more `->count()` queries per row.

**Step A — add 3 `withCount` aliases to existing `getEloquentQuery()`:**

```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->where('faculty_id', auth()->id())
        ->with(['course', 'semester'])
        ->withCount([
            'activeEnrollments as grade_approved_count' => fn ($q) => $q->whereHas('grade', fn ($g) => $g->where('status', GradeStatus::Approved)),
            'activeEnrollments as grade_pending_count'  => fn ($q) => $q->whereHas('grade', fn ($g) => $g->where('status', GradeStatus::PendingApproval)),
            'activeEnrollments as grade_draft_count'    => fn ($q) => $q->whereHas('grade', fn ($g) => $g->where('status', GradeStatus::Draft)),
        ]);
}
```

**Step B — rewrite `getStateUsing()` in the `grades_status` column to use the pre-loaded counts:**

```php
->getStateUsing(function (CourseOffering $record): string {
    $total    = $record->activeEnrollments_count; // from Filament's ->counts(), 0 queries
    $approved = $record->grade_approved_count;    // from withCount above, 0 queries
    $pending  = $record->grade_pending_count;
    $draft    = $record->grade_draft_count;

    if ($total === 0) return 'لا يوجد طلاب';
    if ($approved === $total) return 'معتمدة بالكامل';
    if ($pending > 0) return 'بانتظار الاعتماد';
    if ($draft > 0) return 'مسودة';
    return 'لم تُدخل بعد';
})
```

---

### Fix 4 — AddDropRequestResource `getEloquentQuery()`
**File:** `app/Filament/Resources/AddDropRequests/AddDropRequestResource.php`

No `getEloquentQuery()`. Table has `student.user.name`, `student.status`, `courseOffering.course.name`, `courseOffering.semester.name` — all unloaded.

**Add:**

```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->with([
            'student:id,status,user_id',
            'student.user:id,name',
            'courseOffering:id,course_id,semester_id',
            'courseOffering.course:id,name',
            'courseOffering.semester:id,name',
        ]);
}
```

---

### Fix 5 — GraduateResource `getEloquentQuery()`
**File:** `app/Filament/Resources/Graduates/GraduateResource.php`

Already has `getEloquentQuery()` with `whereIn($latestIds)` + `where(status, Completed)`. Just append `->with([...])` to the chain.

**Change:**
```php
return parent::getEloquentQuery()
    ->whereIn('id', $latestIds)
    ->where('status', DiscussionStatus::Completed);
```
**To:**
```php
return parent::getEloquentQuery()
    ->whereIn('id', $latestIds)
    ->where('status', DiscussionStatus::Completed)
    ->with([
        'student:id,enrollment_no,gpa,status,specialization_id,user_id',
        'student.user:id,name',
        'student.specialization:id,name',
        'thesisRegistration:id,student_id,title',
    ]);
```

---

### Fix 6 — ApplicationResource `getEloquentQuery()`
**File:** `app/Filament/Resources/Applications/ApplicationResource.php`

No `getEloquentQuery()`. Table has `TextColumn::make('specialization.name')`.

**Add:**

```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->with(['specialization:id,name']);
}
```

---

### Fix 7 — ThesisRegistrationResource `getEloquentQuery()`
**File:** `app/Filament/Resources/ThesisRegistrations/ThesisRegistrationResource.php`

Already has `getEloquentQuery()` with `whereIn($latestIds)`. Append `->with([...])`.

**Change:**
```php
return parent::getEloquentQuery()->whereIn('id', $latestIds);
```
**To:**
```php
return parent::getEloquentQuery()
    ->whereIn('id', $latestIds)
    ->with([
        'student:id,enrollment_no,gpa,user_id',
        'student.user:id,name',
    ]);
```

> Check the infolist columns when reading the file — may also need `supervisor` and `facultySupervisor` loaded if those relations are displayed.

---

### Fix 8 — DiscussionRequestResource `getEloquentQuery()`
**File:** `app/Filament/Resources/DiscussionRequests/DiscussionRequestResource.php`

Already has `getEloquentQuery()` with `whereIn` + `where(status != Completed)`. Append `->with([...])`.

**Change:**
```php
return parent::getEloquentQuery()
    ->whereIn('id', $latestIds)
    ->where('status', '!=', DiscussionStatus::Completed);
```
**To:**
```php
return parent::getEloquentQuery()
    ->whereIn('id', $latestIds)
    ->where('status', '!=', DiscussionStatus::Completed)
    ->with([
        'student:id,enrollment_no,user_id',
        'student.user:id,name',
        'thesisRegistration:id,student_id,title',
    ]);
```

---

### Fix 9 — SuspensionRequestResource `getEloquentQuery()`
**File:** `app/Filament/Resources/SuspensionRequests/SuspensionRequestResource.php`

No `getEloquentQuery()`. Table has `student.user.name`, `student.enrollment_no`, `semester.name`. Infolist also shows `student.specialization.name` and `decidedBy.name`.

**Add:**

```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->with([
            'student:id,enrollment_no,specialization_id,user_id',
            'student.user:id,name',
            'student.specialization:id,name',
            'semester:id,name',
            'decidedBy:id,name',
        ]);
}
```

---

### Fix 10 — Setting::get() — cache the whole table, invalidate in booted()
**File:** `app/Models/Setting.php`

Currently: every `Setting::get('key')` fires one `SELECT`. Graduation PDF calls it 6 times = 6 queries.

**Replace `get()` method and add `booted()`:**

```php
public static function get(string $key, ?string $default = null): ?string
{
    $all = cache()->remember('settings.all', 3600, function () {
        return static::query()->pluck('value', 'key')->toArray();
    });

    return $all[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
}

protected static function booted(): void
{
    static::saved(fn () => cache()->forget('settings.all'));
    static::deleted(fn () => cache()->forget('settings.all'));
}
```

> `booted()` fires on every save/delete regardless of WHERE the change comes from (Filament, seeder, tinker, API). This is the correct invalidation point — not the controller.
> `allKeyed()` method also reads from DB directly — check if it should also use cache. Probably fine as-is since it's only used in the settings widget form.

---

### Fix 11 — getActiveSemester() memoization (20+ hits per request)
**Files with this pattern:**
- `app/Filament/Student/Pages/AddDropPage.php` (8+ calls, confirmed 20+ total across portal)
- `app/Filament/Student/Pages/CourseTreePage.php` (has its own `getActiveSemester()`)
- Check other Student Pages for the same pattern

**For each file, change:**

```php
public function getActiveSemester(): ?Semester
{
    return Semester::where('is_active', true)->first();
}
```

**To:**

```php
private ?Semester $resolvedSemester = null;

public function getActiveSemester(): ?Semester
{
    return $this->resolvedSemester ??= Semester::where('is_active', true)->first();
}
```

> `??=` means: if `$resolvedSemester` is null, run the query and assign it; otherwise return the already-stored value. Zero extra queries on subsequent calls within the same Livewire lifecycle.

---

### Fix 12 — StudentSurveyView CourseOffering loop queries
**File:** `app/Filament/Pages/StudentSurveyView.php`

Lines 108, 117, 160, 261, 314, 385, 405, 430 all fire `CourseOffering::where('course_id', ...)->first()` — some inside loops, some repeated per-method-call.

**The pattern to apply wherever a loop queries by course_id:**

```php
// Before the loop — load ALL offerings for this semester keyed by course_id
$offeringsByCourseId = CourseOffering::where('semester_id', $this->semester->id)
    ->get()
    ->keyBy('course_id');

// Inside the loop — zero queries
$offering = $offeringsByCourseId->get($survey->course_id);
```

> Read the full file carefully before editing — there are 8 call sites across different methods. Some may need the collection passed in, others can build it as a cached property.

---

## Implementation order for next session

Do them in this order to make testing easier:

1. Fix 10 (Setting cache) — standalone, easy to test with `php artisan tinker`
2. Fixes 1, 2, 4, 6, 9 (add new `getEloquentQuery()`) — isolated, no existing method to conflict with
3. Fixes 5, 7, 8 (append `->with()` to existing `getEloquentQuery()`) — read current state first
4. Fix 3 (MyCourseResource `withCount` + `getStateUsing` rewrite) — most complex, do last
5. Fix 11 (memoize `getActiveSemester()`) — scan ALL student pages for the pattern first
6. Fix 12 (StudentSurveyView loop) — read the whole file before touching anything

After EACH fix:
- Run `vendor/bin/pint --dirty --format agent`
- Run `php artisan test --compact` (165 tests must stay green)
- Verify query count dropped with tinker

---

## How to tell Claude to pick this up

At the start of the next session, say:

> **"read brain and continue the n+1 fixes from 2026-06-03"**

Claude will read CLAUDE.md → scan recent daily notes → find this file → know exactly where to start.

---

## Loose ends / other deployment checklist items

- Production `.env` values still need to be set (APP_DEBUG=false, APP_ENV=production, APP_URL=real domain, DB connection to real DB, etc.)
- Deployment platform not yet decided (Azure was dropped — ask user)
- Docker files (`Dockerfile`, `docker/`, `.dockerignore`) are committed to `fixes-and-date` branch but not merged to `main` yet
- `fixes-and-date` branch PR was opened on 2026-05-30 — check if it was merged before starting next session with `git status` and `git log`

---

## Part 3 — N+1 fixes IMPLEMENTED (2026-06-03 evening) ✅

All 12 fixes from Part 2 are now implemented. **165 tests green, Pint clean.** Verified query counts with `DB::enableQueryLog()` via boost tinker.

### Two important deviations from the plan (the plan would have shipped bugs)

**1. Full-column eager loads instead of `:id,name` restriction.**
`getEloquentQuery()` feeds **view/edit pages and row-action logic**, not just the list table (Filament's `resolveRecordRouteBinding` uses it). The plan's `with(['user:id,name'])` would have:
- nulled `user.email` / `user.phone` on the **Student view page** infolist;
- nulled `semester.is_active` / `add_drop_*` in **AddDropRequest** approve `visible()` → button never renders;
- broken notifications that read `user.email`/`phone` in row actions.

Column-restriction gives the **same query count** as full eager loading (1 query/relation either way) — it only narrows columns. Since the relations are reused downstream, full eager loads are the correct, safe choice. Confirmed: Student view page `user.email` still present after the fix. See [[../decisions/2026-06-03-n1-eager-loading-deviations]].

**2. `Setting` uses static (per-request) memoization, NOT `cache()->remember()`.**
`CACHE_STORE=database` → `cache()->remember()` fires a **cache-table** query per call. The plan's cache version measured **8 queries for 6 gets — worse than the original 6.** A pure static `private static ?array $cached` guarantees **6 gets → 1 query** regardless of driver, with zero cache-table overhead. `booted()` saved/deleted clears the static for in-request invalidation (verified). See [[../decisions/2026-06-03-n1-eager-loading-deviations]] and [[../project-context/gotchas#Setting cache must be static, not the cache facade (CACHE_STORE=database)]].

### Verified query reductions
| Surface | Before | After |
|---|---|---|
| Students list (15 rows) | 32 | **3** |
| MyCourseResource (1 row, grade counts now constant subqueries) | 22 | **3** |
| Setting::get ×6 (e.g. PDF) | 6 | **1** |

### Fix 3 detail (MyCourseResource)
Used explicit `withCount` aliases — including `activeEnrollments as enrollments_count` so the existing column name resolves directly (dropped its `->counts()`), plus `grade_approved_count` / `grade_pending_count` / `grade_draft_count`. `getStateUsing` now reads those aliases (0 per-row queries). The grade counts are constant subqueries on the main query, so the column stays flat as enrollments grow.

### Fix 11 / 12
- Fix 11: memoized `getActiveSemester()` in all **6** student pages via `private ?Semester $resolvedSemester ??= ...` (private → not serialized by Livewire → correct per-request scope).
- Fix 12: `StudentSurveyView::buildSurveyEntry()` now reads from two memoized keyed collections (`offeringsByCourseId`, `activeEnrollmentsByOfferingId`) instead of querying per survey entry; `enrollSelected()` loop reuses the offerings collection. (No CourseSurvey data in DB to measure live, but the per-entry queries are structurally eliminated.)

### Files touched (15)
`app/Models/Setting.php`; `StudentResource`, `CourseOfferingResource`, `AddDropRequestResource`, `ApplicationResource`, `SuspensionRequestResource`, `GraduateResource`, `ThesisRegistrationResource`, `DiscussionRequestResource`, `Faculty/MyCourseResource`; `Student/Pages/{StudentCoursesPage,CourseTreePage,StudentDashboard,AddDropPage,CourseSurveyPage,SuspensionRequestPage}`; `Pages/StudentSurveyView`.
