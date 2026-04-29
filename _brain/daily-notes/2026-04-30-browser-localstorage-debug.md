---
date: 2026-04-30
tags: [daily, browser, debug, localStorage, filament, zen]
related: [[../bugs-and-fixes/2026-04-30-zen-localstorage-corruption]]
---

# Session: Browser localStorage Debugging

## Problem

After the Apr 28 session, the admin panel lost all interactivity:
- Sidebar navigation groups were collapsed and **unclickable**
- Dark mode toggle didn't work
- Resources were accessible via direct URL but hidden in collapsed groups

## Root Cause

The disk filled to 100% during the Apr 28 session. Laravel logged repeated `No space left on device` errors when trying to compile Blade views. This also corrupted Zen browser's (Firefox-based) localStorage database — `NS_ERROR_FAILURE` on every `localStorage` access.

Filament's `app.js` reads `collapsedGroups` and `theme` from localStorage on page load. With localStorage broken:
- `this.collapsedGroups.includes is not a function` — sidebar groups can't expand
- `loadDarkMode` fails — theme switcher breaks
- Alpine.js initialization crashes — all interactivity lost

## Investigation Steps

1. Checked Laravel logs → found `No space left on device` errors from Apr 28
2. Cleared all server caches (`view:clear`, `config:clear`, `route:clear`, `cache:clear`)
3. Re-published Filament assets (`filament:assets`)
4. Rebuilt Vite assets (`npm run build`)
5. None of the above fixed it — problem was client-side
6. Browser console revealed `NS_ERROR_FAILURE` and `collapsedGroups.includes is not a function`
7. `localStorage.clear()` also threw `NS_ERROR_FAILURE` — storage completely inaccessible
8. Tried different port (8001) — same issue (system-wide, not per-origin)
9. Confirmed `dom.storage.enabled = true` in `about:config`
10. Fix: renamed `~/.zen/.../storage/` folder → Zen recreated clean storage on restart

## Fix

Freed disk space (~10 GB from `~/.cache/`), then renamed Zen browser's corrupted storage directory:
```bash
mv "~/.zen/x93pjrxj.Default (release)/storage" "~/.zen/x93pjrxj.Default (release)/storage.bak"
```

## Lessons

- Disk-full conditions can corrupt browser storage, not just server-side files
- `NS_ERROR_FAILURE` in Firefox/Zen = localStorage is inaccessible at the engine level
- Server-side cache clearing won't fix client-side storage corruption
- Filament depends on localStorage for sidebar state and theme — if it's broken, the entire UI loses interactivity
