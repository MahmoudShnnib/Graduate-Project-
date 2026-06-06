---
date: 2026-06-03
tags: [decision, performance, n+1, eager-loading, filament, eloquent, cache]
related:
  - "[[../daily-notes/2026-06-03-pre-deploy-cleanup-and-n1-plan]]"
  - "[[../project-context/gotchas]]"
---

# N+1 fix decisions: full-column eager loads + static Setting cache

When implementing the 12 N+1 fixes planned earlier on 2026-06-03, two parts of the
plan were changed because the planned approach would have shipped bugs. Recording
the reasoning so neither is "re-optimized" back into a bug later.

## Decision 1 — Eager-load full relation columns, NOT `with(['rel:id,name'])`

**Context.** The plan specified column-restricted eager loads (e.g.
`->with(['user:id,name', 'specialization:id,name'])`) on Filament Resource
`getEloquentQuery()` methods.

**Problem.** `getEloquentQuery()` is the single query source for the **whole
Resource** — list, view, edit, and row-action record resolution. Filament's
`Resource::resolveRecordRouteBinding()` resolves the view/edit record through it.
An eager load with restricted columns hydrates the relation with **only** those
columns; later attribute access on an unselected column returns `null` (it does
NOT lazy-reload, because the relation is already loaded). Concretely the plan
would have:

- nulled `user.email` / `user.phone` on the **Student view page** infolist;
- nulled `semester.is_active` / `add_drop_*` in **AddDropRequest** approve/reject
  `visible()` (`$semester?->is_active && $semester?->isAddDropOpen()`) → the
  approve button silently never renders;
- nulled `user.email`/`phone` for notifications dispatched from row actions.

**Decision.** Use **full-column** eager loads (`->with(['user', 'specialization'])`).

**Why it costs nothing.** Column restriction does not change the query *count* —
it's still one query per relation. It only narrows the SELECT list. Since these
relations are reused on view pages / in row actions, the micro-optimization buys
nothing and introduces fragility. Confirmed post-fix: Students list 32→3 queries
AND the view page still shows `user.email`.

**Consequence / rule.** On a Filament Resource `getEloquentQuery()`, prefer full
eager loads. Only restrict columns if you are certain the relation is used
**nowhere** but a single list column (rare — and not worth the risk).

## Decision 2 — `Setting::get()` uses static memoization, not `cache()->remember()`

**Context.** The plan cached the settings table via
`cache()->remember('settings.all', 3600, ...)` + `booted()` invalidation.

**Problem.** This app runs `CACHE_STORE=database`. With the database cache store,
`cache()->remember()` itself issues a **cache-table** query on every call.
Measured: 6 `Setting::get()` calls → **8 queries** — *worse* than the original 6
(one settings query per get).

**Decision.** Pure per-request static memoization:

```php
private static ?array $cached = null;

public static function get(string $key, ?string $default = null): ?string
{
    self::$cached ??= static::query()->pluck('value', 'key')->toArray();
    return self::$cached[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
}

protected static function booted(): void
{
    static::saved(fn () => self::$cached = null);
    static::deleted(fn () => self::$cached = null);
}
```

**Consequences.**
- 6 gets → **1 query**, driver-agnostic, zero cache-table overhead. Verified.
- No cross-request persistence — but 1 tiny query per request is negligible, and
  PHP-FPM resets statics per request so values are always fresh.
- In-request invalidation via `booted()` (save/delete clears the static). Verified
  a save is reflected by the next get.
- ⚠️ Under **Octane** (not used here) the static would persist across requests;
  the `booted()` clear covers writes, but a manual `Setting::query()->update()`
  bypassing models would not. Revisit if Octane is ever adopted.

**Alternative considered.** Switch `CACHE_STORE` to `array`/`file`/`redis` so the
cache facade becomes cheap. Rejected — changing the app-wide cache driver to serve
one model's lookups is the wrong lever, and pre-deployment is the wrong time.
