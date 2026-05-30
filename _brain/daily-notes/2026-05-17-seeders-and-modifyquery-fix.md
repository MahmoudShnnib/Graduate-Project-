---
date: 2026-05-17
tags: [daily, seeders, demo-data, filament, bug-fix, regression]
related:
  - "[[2026-05-17-faculty-supervisor-identity-fields]]"
  - "[[../bugs-and-fixes/2026-05-17-filament-modifyquery-null-model]]"
  - "[[2026-05-16-wave2-h-and-m-batch]]"
  - "[[../project-context/gotchas]]"
  - "[[../research/2026-04-20-agent-mistakes-and-lessons]]"
---

# 2026-05-17 — Print-surface seeders + M-6 regression fix

Two strands this afternoon, both downstream of yesterday's faculty/supervisor work.

## 1. Demo-data seeders for every print surface

Goal: every Print button + PDF download in the admin panel should have data on a fresh `migrate:fresh --seed`, organized into scenarios. Touched seeders only — courses left alone per user direction.

### What was built

| File | Change |
|---|---|
| `database/seeders/AdminUserSeeder.php` | Added 3 more faculty (د. سالم المقرحي, د. فاطمة الزروق, د. خالد السويحلي) with full identity fields. All 4 faculty + ppsan + new students share password `12345678` per user request. `admin123`/`secretary123` unchanged. |
| `database/seeders/StudentSeeder.php` | Replaced old 3 dummy students with 13 scenario students: 4 passed (GPA 3.5), 3 failed (GPA 0.0), 3 thesis-eligible (GPA 4.0), 3 graduated (GPA 4.0). Enrollment numbers follow the `YY{specCode}{seasonFlag}{seq}` pattern. |
| `database/seeders/AcademicDataSeeder.php` (new) | The big one. Creates 4 `CourseOffering`s (one per faculty), enrolls all 13 students in all 4 offerings, generates **approved** grades (manually setting `total/letter/points` because `DatabaseSeeder` uses `WithoutModelEvents`, which suppresses `Grade::saving()`), creates 6 approved `ThesisRegistration`s with internal faculty supervisors, and 3 `DiscussionRequest`s with `status=Completed` → those students appear under `/admin/graduates`. |
| `database/seeders/DatabaseSeeder.php` | Added `AcademicDataSeeder::class` at end of chain. |

### Why grades had to be computed manually

`DatabaseSeeder` uses `WithoutModelEvents`. `Grade::booted()` registers a `saving` event that auto-computes `total/letter/points`. Events are disabled → the hook never fires. So the seeder hardcodes:

- passed: cw=32, final=50, total=82, letter=B+, points=3.5
- failed: cw=10, final=20, total=30, letter=F, points=0.0
- thesis/graduated: cw=36, final=54, total=90, letter=A, points=4.0

GPAs are also set manually on the `Student` row (`GradeObserver` would normally compute them).

### Test data verification (post-seed)

```sql
SELECT enrollment_no, gpa, COUNT(grades), thesis_status, discussion_status
FROM students JOIN ... ORDER BY enrollment_no
```

All 13 students have 4 enrollments + 4 grades each. Thesis-eligible + graduated have `thesis_status=approved`. Graduated 3 also have `discussion_status=completed`. ✓

## 2. M-6 regression — `?tab=pending` 500s on thesis/discussion/suspension

Discovered while building seeders: navigating to `/admin/thesis-registrations?tab=pending` returned HTTP 500 with `Call to a member function newQueryWithoutRelationships() on null` at `Eloquent\Builder.php:355`.

Root cause: M-6 (2026-05-16) added status tabs whose `modifyQueryUsing` lambdas used `$q` as the parameter name:

```php
// BROKEN
->modifyQueryUsing(fn (Builder $q) => $q->whereIn('status', $pendingStates))
```

Filament's `evaluate()` resolves closure params by **name first** against `['query' => $query]`. With `$q`, the name doesn't match; Filament falls back to resolving a typed `Builder` from the container → **fresh empty Builder, `model = null`**. The lambda mutates the garbage, returns it, `applyQueryScopes` replaces the cloned-correct query with this. Later `$query->where(Closure)` in `HasFilters.php:188` crashes on null.

The fix is one-character per line in 3 files: `$q` → `$query`.

Full bug write-up in [[../bugs-and-fixes/2026-05-17-filament-modifyquery-null-model]].

### Why tests didn't catch it

M-6's verification was `php artisan test --compact` (163 passed) plus a "browser verification pending" note in the session handoff. The bug only surfaces when Filament renders the **summary footer** of the table (via `getAllTableSummaryQuery()` at `vendor/filament/tables/resources/views/index.blade.php:48`). `Livewire::test(...)` without `->call('$refresh')` or `->assertSee()` skips full rendering. So a green suite shipped a 500-broken page.

### Brain updates (so this stays caught next time)

- **`_brain/project-context/gotchas.md`** — new section "Filament `modifyQueryUsing` lambda parameter MUST be named `$query`" with the BROKEN/CORRECT code examples.
- **`_brain/research/2026-04-20-agent-mistakes-and-lessons.md`** — added Mistake 12 with the full diagnostic trail (the tinker instrumentation that found it: `evaluate → clone → applyQueryScopes` showed the model going null at the third step). Added checklist item 11.
- **`_brain/bugs-and-fixes/2026-05-17-filament-modifyquery-null-model.md`** — the dedicated bug post-mortem.
- **Cross-session memory `feedback-filament-query-lambda-param-name`** — table of every Filament data-key name and its expected lambda param name. Indexed in `MEMORY.md`.

## Tests

`php -d memory_limit=512M artisan test --compact` → **163 passed (342 assertions)**. Same as M-6's baseline — no regressions added or removed by this fix.

## Files touched this session

```
Modified:
  database/seeders/AdminUserSeeder.php
  database/seeders/StudentSeeder.php
  database/seeders/DatabaseSeeder.php
  app/Filament/Resources/ThesisRegistrations/Pages/ListThesisRegistrations.php
  app/Filament/Resources/DiscussionRequests/Pages/ListDiscussionRequests.php
  app/Filament/Resources/SuspensionRequests/Pages/ListSuspensionRequests.php
  _brain/project-context/gotchas.md
  _brain/research/2026-04-20-agent-mistakes-and-lessons.md

New:
  database/seeders/AcademicDataSeeder.php
  _brain/bugs-and-fixes/2026-05-17-filament-modifyquery-null-model.md
  _brain/daily-notes/2026-05-17-seeders-and-modifyquery-fix.md (this file)
  ~/.claude/.../memory/feedback_filament_query_lambda_param_name.md
```

## Test credentials (refreshed)

| Role | Email | Password |
|---|---|---|
| super-admin | `admin@college.edu` | `admin123` |
| secretary | `secretary@college.edu` | `secretary123` |
| All 4 faculty | `cs.faculty@example.ly` / `salem.miqrahi@faculty.ly` / `fatima.zarruq@faculty.ly` / `khalid.suwihli@faculty.ly` | **`12345678`** |
| All 13 students + ppsan | any of the seeded student emails / `ppsan@pp.com` | **`12345678`** |

## Next steps

- Browser-verify all 4 print surfaces work with the new seed data: graduates print, thesis "طباعة للوزارة", course-offering roster print, transcript+clearance PDF downloads.
- C-2 audit finding (Filament form-field `id`/`name` console warning) still pending from Wave 2 handoff. Not touched today.
