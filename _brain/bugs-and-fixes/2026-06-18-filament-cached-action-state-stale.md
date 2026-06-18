---
date: 2026-06-18
tags: [bug, fix, filament, actions, reactivity, grades]
related: [[../project-context/gotchas]]
---

# Filament header-action button state stale after a same-page action (needs reload)

## Symptom
On `/admin/grades/{offering}/grades` (`ViewCourseGrades`), after the admin clicks **اعتماد**
on a pending grade, the table row correctly updates to "معتمد" but the **نشر الدرجات النهائية**
button stays **disabled** — the admin must reload the page before it enables. Same class of
issue on `ViewExceptionalStudent`: after **استدعاء** the summon button should hide but stays
visible until reload.

## Root cause
Filament caches a page's header/record actions on first build. The publish button used a
**pre-computed scalar**: `->disabled($readyFinal === 0)`, where `$readyFinal` was counted once
at page load inside `getHeaderActions()`. After a table record action, Livewire re-renders and
the table refreshes (record actions auto-refresh the table) — but the cached header action
reuses the baked scalar, so `disabled` never re-evaluates. `ViewExceptionalStudent`'s summon
button had `->visible($visible && $request && ! $alreadySummoned)` with scalars, and its action
refreshes the record in place (no redirect), so the same staleness applied.

## Fix
Pass **closures** for persistently-rendered props so Filament re-evaluates them on every render
(it runs closures even on cached actions):
- `ViewCourseGrades`: `->disabled(fn () => $this->readyToPublishCount() === 0)` +
  `->tooltip(fn () => ...)` + `->modalDescription(fn () => $this->publishModalDescription())`,
  with the counts moved into small private methods.
- `ViewExceptionalStudent`: `->visible(fn () => $this->hasActionableRequest())` and the summon
  button `->visible(fn () => $this->hasActionableRequest() && ! $this->isAlreadySummoned())`,
  reading live off `$this->record`.

## Notes
- `modalDescription`/`form` are evaluated at action **mount** (which re-runs `getHeaderActions()`),
  so scalars there are usually fine — only continuously-rendered props (`disabled`/`visible`/
  `tooltip`/`label`/`badge`) bite. Actions that `redirect()` are immune.
- Browser-verified: approving a grade now enables the publish button live (no reload). Full
  suite 393 green. New durable gotcha added to `project-context/gotchas.md`.
- The grades **list** at `/admin/grades` updates on navigation (server re-queries) — that is
  `wire:navigate` bfcache behavior, not this action-cache bug.

## Follow-up — `wire:navigate` back-button shows stale page (same day)

A related but distinct staleness: pressing the **browser Back** button to return to
`/admin/grades/{offering}/grades` showed the cached snapshot — the hero "X / Y درجة مدخلة"
counts and the table status (e.g. "بانتظار الاعتماد" vs "معتمد") did not reflect grades
changed since the last visit, until a hard refresh. Cause: Livewire `wire:navigate`'s
back/forward cache restores the DOM without revalidating (forward link-clicks DO re-render).

Because this is panel-wide (every list + view page), the fix is a **single global render hook**
rather than per-page: `resources/views/filament/navigate-refresh.blade.php`, registered via
`PanelsRenderHook::BODY_END` in both `AdminPanelProvider` and `StudentPanelProvider`. On
`popstate` (browser back/forward) it refreshes the app's own page Livewire component(s) on the
next `livewire:navigated`; an install guard keeps listeners singular and forward link-clicks
pay nothing. Browser-verified on the grades **view** (status → معتمد) AND the grades **list**
(offering count columns `1 0 1 0` → `1 0 0 1`) after publishing a grade and pressing Back.
See the gotcha "`wire:navigate` back/forward cache shows stale page data".
