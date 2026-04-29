---
date: 2026-04-30
tags: [bug, browser, localStorage, zen, filament, disk-full]
related: [[../daily-notes/2026-04-30-browser-localstorage-debug]]
---

# Zen Browser localStorage Corruption (Disk-Full)

## Symptoms

- Admin panel sidebar groups collapsed and unclickable
- Dark mode toggle non-functional
- All Filament Alpine.js interactivity broken
- Browser console: `NS_ERROR_FAILURE` on localStorage access, `this.collapsedGroups.includes is not a function`

## Root Cause

Disk filled to 100% during Apr 28 session → corrupted Zen browser's internal SQLite storage database. `localStorage` became completely inaccessible (even `localStorage.clear()` threw `NS_ERROR_FAILURE`). Filament's `app.js` depends on localStorage for sidebar collapsed state and theme preference — when it can't read, Alpine.js crashes on page load.

## Fix

1. Freed disk space (cleared `~/.cache/`, ~10 GB)
2. Closed Zen browser
3. Renamed corrupted storage: `mv "~/.zen/x93pjrxj.Default (release)/storage" "...storage.bak"`
4. Restarted Zen — fresh storage directory created automatically

## Regression Guard

No code changes needed. This is a browser/environment issue, not an application bug. Monitor disk usage — repeated full-disk events risk the same corruption.
