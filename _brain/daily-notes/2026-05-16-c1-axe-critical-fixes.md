---
date: 2026-05-16
tags: [daily, a11y, axe, ux-audit-fix, c-1, vendor-publish, filament]
related:
  - "[[2026-05-15-ux-audit-admin]]"
  - "[[../research/audit-2026-05-15/audit|audit report]]"
  - "[[../research/audit-2026-05-15/fix-plan]]"
  - "[[../project-context/design-conventions]]"
---

# 2026-05-16 — C-1 fix: 8 axe Critical violations resolved

## What was done

Audit finding **C-1** closed: all 8 axe Critical violations across `/admin`, `/admin/audit-logs`, `/admin/students/{id}`, and `/admin/reports-page` now have proper accessible names. Fixes split between two app-level Blade views and two Filament-vendor view overrides (published into `resources/views/vendor/`).

## Violation → fix table

| Violation | Surface | Source | Fix |
|---|---|---|---|
| `select-name` on chart-period filter | `/admin` (dashboard) | Filament vendor `chart-widget.blade.php` | Vendor override: added `aria-label="تصفية الفترة"` to `<x-filament::input.select>` |
| `button-name` ×2 on section-collapse | `/admin/students/{id}` | Filament vendor `section/index.blade.php` | Vendor override: added `aria-label="طي أو فتح القسم"` to `<x-filament::icon-button>` |
| `aria-allowed-attr` ×1 | `/admin/students/{id}` | Filament vendor `section/index.blade.php` (line 118 had `x-bind:aria-expanded` on a `<div>`) | Vendor override: moved `x-bind:aria-expanded` from `.fi-section-content-ctn` `<div>` onto the icon button (where ARIA spec allows it) |
| `button-name` ×2 on audit-log paginator | `/admin/audit-logs` | App-level `list-audit-logs.blade.php:321, 332` | Added `aria-label="الصفحة التالية"` / `"الصفحة السابقة"` on the icon-only buttons |
| `select-name` on audit-log per-page | `/admin/audit-logs` | App-level `list-audit-logs.blade.php:346` | Converted the visual `<span>` label into a proper `<label for="audit-log-per-page">` + added matching `id` to the select |
| `select-name` on Reports Page student picker | `/admin/reports-page` | App-level `reports.blade.php:5-6` | Same pattern — `<label for="reports-student-picker">` + matching `id` on the select |

## Files touched

| File | Status | Change |
|---|---|---|
| `resources/views/vendor/filament/components/section/index.blade.php` | **new** (vendor override) | Published from `vendor/filament/support/resources/views/components/section/index.blade.php`, then edited: added `aria-label` and `x-bind:aria-expanded` on the icon-button; removed `x-bind:aria-expanded` from the content-div |
| `resources/views/vendor/filament-widgets/chart-widget.blade.php` | **new** (vendor override) | Published from `vendor/filament/widgets/resources/views/chart-widget.blade.php`, then edited: added `aria-label="تصفية الفترة"` on the filter `<x-filament::input.select>` |
| `resources/views/filament/pages/reports.blade.php` | modified | `<label>` + `id` linkage on student picker |
| `resources/views/filament/resources/audit-logs/pages/list-audit-logs.blade.php` | modified | 2 `aria-label`s on pagination buttons + `<label>` + `id` linkage on per-page select |

## Design conventions honored

- [[../project-context/design-conventions]] **§1 Arabic-first** — all new `aria-label`s authored in Arabic (`الصفحة التالية`, `الصفحة السابقة`, `طي أو فتح القسم`, `تصفية الفترة`).
- [[../project-context/design-conventions]] **§9 toggle icon buttons** — "Always ship `aria-expanded`, `aria-label`, `title` for accessibility." Now upheld for Filament's own collapsible section buttons too, not just our custom ones.
- **Server-side, not JS** — fix matches how the rest of the codebase handles a11y (see `degree-document.blade.php:25` for the existing `aria-label="عرض الوثيقة العلمية بالحجم الكامل"` precedent). No JS shims added; nothing depends on script execution.

## Why vendor publish (not a JS shim)

User instruction: "just keep the same design principle". Three options were on the table — JS shim, vendor publish, or skip vendor-sourced violations. The codebase's existing a11y pattern is server-side `aria-*` attributes in Blade. The vendor-publish path keeps the fix in that paradigm, fully resolves all 5 vendor-sourced violations (a JS shim couldn't fully fix `aria-allowed-attr` because Alpine continuously re-binds attributes), and adds no runtime dependency.

## Vendor override management

Two files now live under `resources/views/vendor/`:

```
resources/views/vendor/filament/components/section/index.blade.php
resources/views/vendor/filament-widgets/chart-widget.blade.php
```

Both have a top-of-block `{{-- Published from Filament v5: ... See _brain/daily-notes/2026-05-16-c1-axe-critical-fixes.md --}}` comment marking the modifications so future maintainers know where the divergence lives.

**On Filament upgrades:** the next time `composer update` bumps Filament, these two overrides may stop matching upstream. Procedure: diff the upstream version against ours, re-apply the aria-* edits on top of the new upstream copy. The edits are tiny (3 lines on section, 1 line on chart-widget) so the re-merge is cheap.

**Initial publish overshot:** `php artisan vendor:publish --tag=filament-views` copied 38 unrelated files. Trimmed down to exactly 1 file (`section/index.blade.php`). Same for `filament-widgets-views` — trimmed to 1 file (`chart-widget.blade.php`). The repo only owns what it actually modified.

## Verification

- `php artisan view:clear` → compiled views cleared.
- `php artisan optimize:clear` → clean.
- `php -d memory_limit=2048M artisan test --compact` → **158 passed (337 assertions)**. (Test suite's memory budget is tight in this env — needed 2GB this run.)
- Browser walk pending — user should re-run axe-core on the four affected routes:
  1. `/admin` (dashboard) — `select-name` count should drop by 1 (the chart filter)
  2. `/admin/audit-logs` — `button-name` should drop by 2; `select-name` by 1
  3. `/admin/students/{id}` — `button-name` drops by 2; `aria-allowed-attr` by 1
  4. `/admin/reports-page` — `select-name` by 1
  - Verdict block in `audit.md` would now show `axe Critical: 0` for these 4 routes.

## Audit progress

- ✓ Wave 1 still pending (brand + cheap a11y — `APP_NAME`, focus ring, Vite font, sidebar group labels) — not in user's current queue.
- ✓ Wave 2 (C-3 RBAC) still pending — highest-impact remaining finding.
- ✓ Wave 3 (H-3 + H-6) — closed in [[2026-05-15-h3-application-view-actions]] + [[2026-05-15-h6-disable-when-no-exam-date]].
- ✓ **C-1 — closed this session.**
- Still pending in user's current queue: H-1/H-2 (light-mode contrast), M-18 (faculty widgets).

## Next session anchors

- **H-1 / H-2** — light-mode contrast on view-page hero meta + GPA chip. User wants light mode darkened; dark mode untouched. Files: `resources/views/filament/resources/students/pages/view-student.blade.php` `@push('styles')` block (and the same scoped styles in 8 other figma view pages — promote to `resources/css/filament/admin/theme.css` once the value is locked).
- **M-18** — faculty dashboard widgets. User explicitly: NOT a "survey results" widget. Candidates: "my courses this semester", "grade sheets I haven't uploaded", "students enrolled in my offerings". File: `app/Providers/Filament/AdminPanelProvider.php` widget registration + new widget classes under `app/Filament/Widgets/Faculty/`.
- **Filament upgrade note** — when bumping Filament, re-check the 2 vendor overrides.
