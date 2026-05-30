---
date: 2026-05-17
tags: [bug, filament, livewire, query, regression]
related:
  - "[[../project-context/gotchas]]"
  - "[[../research/2026-04-20-agent-mistakes-and-lessons]]"
  - "[[../daily-notes/2026-05-16-wave2-h-and-m-batch]]"
---

# Filament `modifyQueryUsing(fn (Builder $q) => ...)` produces null-model query → tab pages crash

## Symptom

Hitting `/admin/thesis-registrations?tab=pending` (and the same on `/admin/discussion-requests` and `/admin/suspension-requests` when applying their filters) returned HTTP 500:

```
Call to a member function newQueryWithoutRelationships() on null
  at vendor/laravel/framework/src/Illuminate/Database/Eloquent/Builder.php:355
  via vendor/filament/tables/src/Concerns/HasFilters.php:188
```

The crash happens during `getAllTableSummaryQuery()` (called from the summary footer in `vendor/filament/tables/resources/views/index.blade.php:48`). The footer renders unconditionally on every list page, so the page is fully broken — not just the dropdown filter.

## Root cause

The tab definitions used `$q` as the lambda parameter name:

```php
->modifyQueryUsing(fn (Builder $q) => $q->whereIn('status', $pendingStates))
```

Filament v5's `evaluate()` resolves closure parameters **by name first** against the data array. `Tab::modifyQuery()` passes `['query' => $query]`. With the param named `$q`, the name doesn't match. Filament's fallback for a typed `Builder` parameter is to resolve a **fresh empty Builder from the container** — which has `$model = null` and no FROM clause.

So the lambda runs on that junk Builder. `applyQueryScopes` then replaces the cloned-correct query with the lambda's return value. The result: a Builder with `model = null` and SQL like `select * where "status" in (?, ?)` — no FROM clause, no `whereIn('id', $latestIds)`, no model. Later, the SelectFilter's `$query->where(Closure)` in `HasFilters.php:188` triggers `$this->model->newQueryWithoutRelationships()` → null pointer.

## Reproduction & diagnosis

Tinker-instrumenting the table pipeline (skipping the rendering entirely):

```php
$instance = new ListThesisRegistrations(); $instance->activeTab = 'pending';
$table = (new \ReflectionClass($instance))->getMethod('makeTable')->invoke($instance);
$queryVal = (new \ReflectionClass($table))->getProperty('query')->getValue($table);

$q = $table->evaluate($queryVal);    // model=ThesisRegistration, SQL has FROM ✓
$clone = $q->clone();                // still good ✓
$scoped = $table->applyQueryScopes($clone);
// → model=NULL, SQL='select * where "status" in (?, ?)' ✗
```

`applyQueryScopes` is where the model evaporates. Inside it, our tab's `modifyQueryUsing` lambda runs — and returns a Builder distinct from the one it received. Once I saw the SQL had no FROM clause, the param-binding mismatch fell out.

## Fix

Renamed `$q` → `$query` in every `modifyQueryUsing` lambda across the three list pages added by M-6:

```
app/Filament/Resources/ThesisRegistrations/Pages/ListThesisRegistrations.php
app/Filament/Resources/DiscussionRequests/Pages/ListDiscussionRequests.php
app/Filament/Resources/SuspensionRequests/Pages/ListSuspensionRequests.php
```

No other instances of the `fn (Builder $q)` pattern exist in `app/` (verified with grep).

## Verified

- `Livewire::test(ListThesisRegistrations::class, ['activeTab' => 'pending'])` no longer throws (was throwing before).
- Same for `approved`/`rejected` tabs on all three resources.
- Final SQL post-fix: `select * from "thesis_registrations" where "id" in (?, ?, ?, ?, ?, ?) and "status" in (?, ?)` with the model correctly set.
- `php -d memory_limit=512M artisan test --compact` → **163 passed (342 assertions)**. No regressions.

## Why tests didn't catch the M-6 regression

M-6's verification was `php artisan test --compact` (no HTTP-render assertion) + visual browser walk listed as "pending" in the session handoff. Tests that just call `Livewire::test(...)` without forcing the full render (i.e. no `->call('$refresh')` or `->assertSee('...')`) skip the summary footer entirely. The bug shipped, the tab page 500'd on first user click.

## Brain updates

- New gotcha: `_brain/project-context/gotchas.md` → "Filament `modifyQueryUsing` lambda parameter MUST be named `$query`" with code examples.
- New retrospective: `_brain/research/2026-04-20-agent-mistakes-and-lessons.md` → Mistake 12 + checklist item 11.
- New cross-session memory: `feedback-filament-query-lambda-param-name` (in this run's auto-memory).

## Rule going forward

In every Filament evaluate-driven callback (Tab `modifyQueryUsing`, table-level `modifyQueryUsing`, `->relationship(fn (Builder $query) ...)`, `->action(fn ($record) ...)` etc.), **match the parameter name to Filament's data key**: `$query`, `$record`, `$state`, `$get`, `$set`, `$livewire`, `$context`, `$operation`. Don't shorten — name mismatches silently bind to container-resolved instances with null models, and the crash is far from the cause.
