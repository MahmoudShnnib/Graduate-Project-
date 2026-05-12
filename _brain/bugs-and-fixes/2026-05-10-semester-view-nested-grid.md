---
date: 2026-05-10
tags: [bug, filament, infolist, layout]
related: [[../daily-notes/2026-05-10-multi-phase-fixes]]
---

# `/admin/semesters/{id}` view: section squeezed to half page width

## Symptoms

On the semester view page, the "بيانات الفصل الدراسي" section rendered correctly *internally* (3-column inner layout) but the section as a whole only occupied half the row width — the other half was an empty black box. The publish-grades / edit buttons in the header were unrelated.

## Root cause

Filament v5's `ViewRecord` schema renders its top-level components inside a default multi-column outer grid. A `Section` without `->columnSpanFull()` only occupies 1 column of that outer grid, leaving the rest of the row empty.

Initial misdiagnosis: I assumed a nested `Grid::make(3)` inside `Section::columns(3)` was the cause, removed the inner `Grid`. That cleanup was still correct (avoid nesting grids when the parent already has `columns`), but it didn't fix the half-width rendering. The real fix was adding `->columnSpanFull()` on the outer `Section`.

## Fix

1. Removed redundant inner `Grid::make(3)` — `Section::columns(3)` handles layout itself (cleanup, not the bug fix).
2. Added `->columnSpanFull()` on the outer `Section` — makes the section span the full width of the page schema's outer grid.

## Regression guards

**Convention 1 (page-level):** every top-level `Section` in a `ViewRecord` infolist must call `->columnSpanFull()` unless you intentionally want it to share a row with a sibling section. This matches the pattern used everywhere else in the codebase (e.g. `StudentResource` infolist).

**Convention 2 (nested layout):** when a `Section` already has `->columns(N)`, do not wrap its children in `Grid::make(N)`. Use the Section's own columns. Nesting both creates a 1/N-width strip.

## Files touched

- `app/Filament/Resources/Semesters/SemesterResource.php` (added `->columnSpanFull()`; removed inner `Grid` + `Grid` import)
