---
date: 2026-06-18
tags: [decision, filament, livewire, spa, wire-navigate, reactivity]
related:
  - "[[../bugs-and-fixes/2026-06-18-filament-cached-action-state-stale]]"
  - "[[../project-context/gotchas]]"
  - "[[../daily-notes/2026-06-18-grades-reactivity-and-suspension-revisit]]"
---

# One global render hook for wire:navigate back/forward staleness (not per-page)

## Context

Both panels run `->spa()`, so every link uses Livewire `wire:navigate`. Its back/forward
history cache restores a page's DOM snapshot WITHOUT revalidating — so server-rendered counts,
statuses and table rows go stale after the browser Back button (reported on the grades pages:
"i need to refresh for the numbers in the table to update"). Forward link-clicks already
revalidate; only history restores are stale. This is **framework-wide** — it affects every
list and view page, not just grades.

## Decision

Fix it once, globally, instead of patching pages individually:
`resources/views/filament/navigate-refresh.blade.php`, registered via
`PanelsRenderHook::BODY_END` in **both** `AdminPanelProvider` and `StudentPanelProvider`.

The script:
- installs listeners once (guarded by `window.__cetNavRefreshInstalled` — the hook re-renders
  on every page, the listeners must not stack),
- sets a flag on `popstate` (browser back/forward only),
- on the next `livewire:navigated`, refreshes the app's own page component(s):
  `Livewire.all().filter(c => c.name.startsWith('App\\')).forEach(c => c.$wire.$refresh())`
  — skipping Filament's chrome (topbar/sidebar/notifications) which manage themselves.

## Alternatives considered

1. **Per-page `@script` refresher** (first attempt, on `ViewCourseGrades`) — works, but every
   page with stale-prone data needs its own copy, and default Filament list pages have no blade
   to put it in. Removed once the global hook landed.
2. **Refresh on every `livewire:navigated`** (forward too) — simpler but adds a wasted
   round-trip on normal forward navigation (which already revalidates). Rejected; gated on
   `popstate` instead.
3. **Disable wire:navigate / SPA** — loses the SPA speed everywhere. Rejected.

## Consequences

- Every list + view page in both panels is fresh after browser Back/Forward; forward
  navigation pays nothing.
- One extra round-trip per history restore (acceptable — happens only on Back/Forward).
- If a future page must NOT auto-refresh on back (e.g. an in-progress unsaved form that should
  survive a back-restore), it would need to opt out — none exist today.
- Browser-verified on the grades view (status → معتمد) and the grades list (count columns
  `1 0 1 0` → `1 0 0 1`). See [[../bugs-and-fixes/2026-06-18-filament-cached-action-state-stale]].
