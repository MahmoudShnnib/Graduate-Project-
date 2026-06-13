---
date: 2026-06-12
tags: [daily, prints, mpdf, fonts, deployment, git]
related:
  - "[[../project-context/print-system-guide]]"
  - "[[../project-context/deployment-checklist]]"
  - "[[2026-05-19-print-template-and-fonts]]"
---

# 2026-06-12 — Moved mPDF print fonts into version control

> The Arabic print fonts used to live in the git-ignored `storage/app/print-fonts/`, so every fresh
> clone / server deploy had an empty font folder and every print route threw "font not found" until
> someone copied the TTFs by hand. Fixed by committing the fonts to a tracked location.

## Problem

`storage/app/.gitignore` is `*` (excepting `private/`, `public/`, `.gitignore`), so the entire
`storage/app/print-fonts/` folder — and all the TTFs in it — was untracked. Confirmed with
`git ls-files storage/app/print-fonts/` → 0 files. The committed fonts in `public/fonts/Noto_Kufi_Arabic/`
are **web/UI** fonts (and Noto Kufi actually *fails* in mPDF), unrelated to the print stack.

## What we did

1. **Created `resources/fonts/print/`** and committed the 7 fonts the print system actually
   registers — Noto Naskh R+B, Markazi (R), Amiri R+B+I+BI. (`dejavusans` ships bundled with mPDF;
   the `test/` candidates and `TIMES*`/`Liberation*` are unused and left out.)
2. **Updated both controllers'** `fontDir` from `storage_path('app/print-fonts')` to
   `resource_path('fonts/print')`:
   - `app/Http/Controllers/PrintController.php`
   - `app/Http/Controllers/PrintCourseStudentsController.php` (found via project-wide grep — it had
     the same line and would otherwise have broken)
3. **Verified** by rendering a real PDF exercising all three families (Noto Naskh body + Markazi
   number + Amiri) — valid `%PDF` output, all fonts resolved from the new path. Pint passed.
4. **Updated docs:**
   - `print-system-guide.md` — fixed the `fontDir` code sample, added a location-change callout, and
     updated the "Testing a new font" recipe to point at `resources/fonts/print/`.
   - `deployment-checklist.md` — added a "fonts in VCS, no manual copy" bullet + an Arabic-print
     smoke-test step.

## Why `resources/fonts/print/` (not un-ignoring storage)

`storage/` is Laravel's runtime-writable state dir (often wiped/regenerated) — committing assets there
is unconventional. `resources/` is the home for app-shipped source assets (mirrors the existing
`public/fonts/` for web fonts). One small `fontDir` edit vs. `.gitignore` exceptions.

## Result

A clean `git clone` (Mac or server) now has the print fonts with zero manual steps. Deploy only needs
the writable `vendor/mpdf/mpdf/tmp/` dir (unchanged).

## Loose ends

- The old `storage/app/print-fonts/` copies still exist locally (git-ignored, no longer read). Left in
  place because `test/` there holds rejected font candidates for future evaluation. Harmless.
- **Not committed** (per no-auto-commit rule). Staged set when ready: `resources/fonts/print/*.ttf`,
  the two controllers, and the two `_brain/` doc updates.
