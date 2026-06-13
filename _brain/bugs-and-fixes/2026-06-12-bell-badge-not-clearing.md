---
date: 2026-06-12
tags: [bell, badge, alpine, livewire, localStorage]
related: [[../daily-notes/2026-06-12-notification-log-viewer-and-activity-bell]]
---

# Bug: Bell Badge Not Clearing After Viewing Notifications

## Symptoms

Admin opens the activity bell dropdown, reads the entries, closes it — amber badge number remains on the bell icon. Persists across page refreshes and SPA navigation.

## Failed Approaches

### 1. Alpine `seen: false` boolean
Resets to `false` on every Livewire re-render or page load. No persistence.

### 2. `$persist(0).as('admin_bell_seen')`
Unreliable timing: `$persist` evaluates before Livewire hydrates, so `latestId` was 0 at init — saved 0 to storage, badge reappeared.

### 3. Session + `wire:click="markAsSeen"` + `$wire.showBadge`
Two problems:
- `render()` in Livewire 4 always runs after an action method — it overwrote `$this->showBadge = false` set in `markAsSeen()` with a recomputed value. (Should still be correct via session, but timing was fragile.)
- `markAsSeen` was only triggered on button **click**. Closing the dropdown by clicking *outside* (via `@click.outside`) never called it — badge stayed.

## Root Cause

The "mark as seen" logic was tied to the bell button click, not to the dropdown becoming visible. Closing via click-outside silently skipped it.

## Fix

Use `$watch('open')` in Alpine `x-init` to call `markSeen()` the moment the dropdown **opens**, regardless of how it was triggered:

```js
x-init="$watch('open', val => { if (val) markSeen() })"
```

`markSeen()` stores `latestId` (read from `$wire.latestId`, a reactive Livewire public property) into `localStorage`. `showBadge` is computed as `latestId > lastSeenId` — pure Alpine, no Livewire roundtrip needed.

**Files:** `resources/views/livewire/admin-activity-bell.blade.php`, `app/Livewire/AdminActivityBell.php`

## Key Lessons

- **`@click.outside` does not trigger `@click` or `wire:click` on the button.** Any "seen" logic on the button only fires on direct clicks.
- **`render()` always runs after Livewire action methods** — don't rely on action-set state surviving into the rendered output.
- **`$wire.method()` in Alpine `@click` can scope incorrectly** inside render-hook-embedded child Livewire components. `wire:click="method"` on the DOM element is more reliable, but the cleanest solution is to avoid Livewire roundtrips for pure client-side state.
