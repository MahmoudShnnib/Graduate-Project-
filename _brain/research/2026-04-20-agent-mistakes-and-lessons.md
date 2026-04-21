---
date: 2026-04-20
tags: [meta, anti-patterns, lessons, retrospective, filament, livewire, testing]
related: [[../project-context/gotchas]], [[../daily-notes/2026-04-20-task-session-multi-fix]], [[../../CLAUDE]]
---

# Agent mistakes & lessons — 2026-04-20 session

> Retrospective of wrong turns I took during the multi-task session. Each entry = what I did wrong, why it was wrong, how to recognize the trap, and the correct default. **Read this before starting any Filament / Livewire / testing work on this repo.**

## Mistake 1 — Conflating "UI didn't change" with "code is broken"

**What happened:** After T11 attempt 1 (getEloquentQuery override), user reported the applications table "still the same". I immediately assumed my code was wrong and rewrote via `modifyQueryUsing`. Turned out the code was correct — user's expectation was a 3-tier sort (pending / initially_approved / done) but I'd only implemented 2-tier.

**Why wrong:** I skipped the verification step. The SQL in tinker already proved the sort worked. User's intent had shifted from their original wording ("completed at bottom") once they saw the result.

**Rule going forward:**
- When user says "still the same" or "didn't work", **reproduce first, don't rewrite.** Run the actual query, inspect the actual output, compare against user expectation.
- Ask "does the output match the expected ordering?" before assuming the mechanism is broken.
- If SQL is correct but UI disagrees: browser cache, OPcache, or user expectation — in that order.

## Mistake 2 — Using `Filament\Schemas\Components\Actions` inside an infolist

**What happened:** For T1 (attachment popup), I added `Actions::make([Action::make(...)])` as a peer in an infolist `Section`. It rendered nothing — no error, just silently absent from the DOM.

**Why wrong:** `Filament\Schemas\Components\Actions` is a form-context component. Infolists don't render it. Filament has **three distinct "actions" concepts** that look interchangeable:

| Where | How | Use for |
|---|---|---|
| Form schema | `Filament\Schemas\Components\Actions::make([...])` | Buttons attached to a form field group |
| Infolist entry | `$entry->hintAction(Action::make(...))` | Clickable action tied to a view-mode field |
| Page header | `getHeaderActions(): array` returning `[Action::make(...)]` | Top-of-page toolbar buttons |

**Rule going forward:**
- Before using `Action`-related components in an infolist, check `vendor/filament/infolists/src/Components/`. If the component isn't under that namespace (or its `Concerns/`), it probably doesn't work in an infolist.
- For a click-to-modal on an infolist field, start with `->hintAction(Action::make(...)->modalContent(...))`.

## Mistake 3 — Assuming `wire:model` updates server state immediately

**What happened:** For T5 (courses-survey popup), the "فتح المقررات المختارة" button stayed disabled even after the user checked a course. My `->disabled(fn () => empty($this->selectedCourses))` was evaluating against stale server state.

**Why wrong:** Livewire `wire:model` (no modifier) is **deferred** by default — the client buffers the change and only sends it to the server on the next action. So the server still thought `selectedCourses` was empty.

**Rule going forward:**
- If an action's `->disabled(...)`, `->visible(...)`, `->hidden(...)` depends on a Livewire public property driven by a custom Blade input, **use `wire:model.live`** (or `.lazy` / `.blur`).
- Filament's own form/table components handle this internally. This bites only on custom Blade pages (like `CoursesSurveyResults`) that expose their own checkboxes/inputs.

## Mistake 4 — Using a Pest plugin that isn't installed

**What happened:** For T2's regression test, I used `use function Pest\Livewire\livewire;` → `livewire(EditStudent::class, ...)`. It errored: "Call to undefined function Pest\Livewire\livewire()".

**Why wrong:** The `pestphp/pest-plugin-livewire` package wasn't in `composer.json`. I assumed it was installed because the codebase uses Livewire.

**Rule going forward:**
- Before using any Pest helper function, `grep composer.json` for the matching package.
- **Safe default for Livewire testing in this repo:** `use Livewire\Livewire;` → `Livewire::test(Component::class, props)`. Always available.

## Mistake 5 — Not anticipating the Vite manifest breaking after `optimize:clear`

**What happened:** During T11 debugging I ran `php artisan optimize:clear` to flush caches. Later, a grades feature test started failing with "Unable to locate file in Vite manifest: resources/css/filament/admin/theme.css".

**Why wrong:** `optimize:clear` touches Filament's internal plugin cache, which can desync from the Vite manifest. Filament's base layout directly uses `@vite(...)` for admin theme CSS, which needs `public/build/manifest.json` to be current.

**Rule going forward:**
- After **any** cache-clear during Filament work (`optimize:clear`, `view:clear` + custom pages), if feature tests that hit admin URLs fail with Vite errors → **run `npm run build`** before re-running tests.
- In CI or test setup scripts, ensure `npm run build` precedes `php artisan test`.

## Mistake 6 — Filament table `defaultSort` conflicts with upstream orderBy clauses

**What happened:** T11 attempt 1 added `orderByRaw(...)` via `getEloquentQuery`, but `->defaultSort('created_at', 'desc')` in the table config appended its own `orderBy` that interacted unpredictably with mine.

**Why wrong:** I assumed orderBy clauses in Filament stack cleanly (first one wins as primary). They do — but only if nothing calls `->reorder()` or overrides in between. Filament's internal `applySortingToTableQuery` runs complex logic that can reorder based on user interaction.

**Rule going forward:**
- For a must-survive-user-sort grouping, put the sort **inside** `->modifyQueryUsing()` and call `$query->reorder()` first. That guarantees yours is the only orderBy in the final query.
- Don't mix `->defaultSort(...)` with a query-level orderBy in `getEloquentQuery` — pick one. Preferred for tables: `modifyQueryUsing + reorder`.

## Mistake 7 — Fixing create-side logic without mirroring to edit-side

**What happened (historical pattern, not mine this session, but I should watch for it):** The `CreateStudent` page handled `transfer_courses` correctly (fill + save hooks). The `EditStudent` page was a bare `EditRecord` that silently dropped the form field. Admin users edited transfer courses, saved, and nothing persisted.

**Rule going forward:**
- **Whenever a Filament Create*Page does custom data handling** (handleRecordCreation, afterCreate, mutateFormDataBefore*), verify the matching Edit*Page does the same. Missing `handleRecordUpdate` is a silent bug.
- **Whenever a Filament form has a hidden field storing related-model data**, both fill hook (mutateFormDataBeforeFill) **and** save hook (handleRecordUpdate / afterSave) must exist. Hidden fields are the most common vector for this bug class.
- Audit candidate: grep for `Hidden::make` and for `$schemaSet(` — each instance needs matching lifecycle hooks on both create and edit pages.

## Mistake 9 — Calling a nonexistent method that looks plausible

**What happened:** `SuspensionRequestResource` and `ThesisRegistrationResource` were calling `$notifier->notifyStudent($student, 'key', 'message')` — a method that doesn't exist on `NotificationService`. It had been wrong **before my session** too, so I inherited the pattern and kept using it in my T13 rewrite. The error only surfaced when the user loaded the admin page (so Filament could spot the route target's error).

**Why wrong:** I reused the existing (broken) call shape instead of checking `NotificationService` for a matching method. The class has dedicated methods per domain event — `notifySuspensionDecision($record)` and `notifyThesisDecision($record)` — which I didn't look for.

**Why the bug was invisible for so long:** the Livewire action wraps its closure in a try/catch that logs but continues. The DB update to `status = Approved` ran first, student felt "it worked", but the notification never sent. Classic silent-failure-downstream-of-success pattern.

**Rule going forward:**
- Before reusing a call shape from existing code, **verify the method exists** — don't assume. `grep -n "public function notify" app/Services/NotificationService.php` is 2 seconds.
- When adding a new action closure that calls a service, prefer dedicated domain methods (`notifySuspensionDecision($record)`) over generic ones — typed arguments catch shape mistakes at refactor time.
- **If you inherit an obviously-wrong call**, fix it in place, don't propagate. My T13 rewrite should have caught and fixed this, not preserved it.
- When an admin user reports "it still worked but there was an error", that's ALWAYS a silent-downstream-failure pattern. The first thing to check is post-commit side effects (notifications, audit logs, observers).

## Mistake 8 — `Livewire::test(Page::class, ['model' => $id])` fails on typed Filament page props

**What happened:** For T7's regression test, I passed `['student' => $student->id, 'semester' => $semester->id]` to `Livewire::test(StudentSurveyView::class, ...)`. Got `Cannot assign int to property App\Filament\Pages\StudentSurveyView::$student of type ?App\Models\Student`.

**Why wrong:** Filament page public props are typed as the model class (`public ?Student $student = null`). Livewire's mount-param assignment does `$component->{$property} = $value` — no route-model binding happens inside `Livewire::test()`. Passing an int tries to assign it directly to a `?Student`-typed property → TypeError.

**Rule going forward:**
- In `Livewire::test(...)` for a Filament page whose public props are typed as models, **pass model instances, not IDs**:
  ```php
  Livewire::test(StudentSurveyView::class, ['student' => $student, 'semester' => $semester])
  ```
- This only applies to tests. In actual HTTP requests, Filament's route-model binding resolves the ID to the model before `mount()`.

## Mistake 10 — Calling `Resource::table($table)` from a non-Resource Page leaves the query empty

**What happened:** For T12 I built a standalone `Filament\Pages\Page` that reused `AddDropRequestResource::table($table)` to avoid duplicating the approve/reject table definition. Loading the page threw:

> `Table [StudentAddDropView] must have a [query()], [relationship()], or [records()].`

**Why wrong:** A Resource's `table()` decorates a `Table` with columns, filters, and actions — but the **root query** comes from the Resource's own `getEloquentQuery()` via Filament's model-binding plumbing. That plumbing only fires inside a Resource page (`ListRecords`, `ViewRecord`, etc.). In a plain Page, the table has no root query, and Filament refuses to render. **`->modifyQueryUsing()` is a decorator** — it transforms an existing query. It cannot be the source.

**Rule going forward:**
- When reusing a Resource's `table()` from a standalone `Filament\Pages\Page`, **always** add `->query(fn () => Model::query()->where(...))` in the Page's `table()` method. The Resource's table supplies everything else.
- If you find yourself reaching for `modifyQueryUsing` before a `query()` exists, stop — that's the trap. Set the root query first.
- When you see the LogicException `Table must have a [query(), relationship(), or records()]`, the fix is always "provide a root query source" — the 3 options in the error message are literal.

Example fix from T12:

```php
public function table(Table $table): Table
{
    return AddDropRequestResource::table($table)
        ->query(fn () => AddDropRequest::query()
            ->where('student_id', $this->student->id)
            ->whereHas('courseOffering', fn ($q) => $q->where('semester_id', $this->semester->id)));
}
```

## Default checklist before declaring a Filament task "done"

1. Did I run `vendor/bin/pint --dirty --format agent`? → formatting
2. Did I run `php artisan test --compact`? → regression
3. Did I verify the behavior end-to-end (tinker for query, or Livewire::test for UI)? → not just "tests pass"
4. Is the implementation **consistent** with how the rest of the codebase does this? → `searchable()->preload()` on selects, `active*` relations for enrollments, Arabic labels, observer-driven side effects, etc.
5. If I touched a Blade view with custom inputs driving action state — are the inputs `wire:model.live`?
6. If I touched a create-side lifecycle — did I check the edit-side mirror?
7. If I cleared caches during debugging — did I `npm run build` to restore the Vite manifest?
8. Did I write a vault note (daily + bugs/decisions where warranted) before marking complete?

## When in doubt

- **Read the vendor source.** I wasted time guessing at `hintAction` until I read `HasHint.php`. Same for `applySortingToTableQuery`. Filament is well-factored — a 30-second scan of the right trait usually answers structural questions.
- **Don't guess — ask.** The user explicitly said they'd rather answer 10 questions than watch 10 wrong implementations. For this session, the intent-clarification answers at Phase 0 saved multiple wrong directions. When a new ambiguity surfaces mid-task, stop and ask.
