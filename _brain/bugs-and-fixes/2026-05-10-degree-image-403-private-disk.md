---
date: 2026-05-10
tags: [bug, storage, filament, file-upload]
related: [[../daily-notes/2026-05-10-multi-phase-fixes]]
---

# Student degree files (and faculty grade sheets) returned 403 when admin tried to view them

## Symptoms

- Clicking the degree image / PDF in the admin student view (`/admin/students/{id}`) → 403 forbidden on `/storage/students/degrees/{ulid}.pdf`.
- Same broken behavior would have hit faculty grade-sheet uploads.

## Root cause

Three issues stacked:

1. **`public/storage` symlink was never created** on this machine. `php artisan storage:link` had not been run since the project was cloned. Every URL pointing at `/storage/<path>` returned 404/403 because the symlink target did not exist.
2. **`FILESYSTEM_DISK=local` in `.env`** means the *default* disk is the private `storage/app/private/` location, not the publicly-served `storage/app/public/`.
3. **Two `FileUpload` components did not call `->disk('public')`**, so they inherited the default `local` disk and wrote files to a non-web-accessible location — while the display code (`ImageEntry::make('degree_image')->disk('public')`, `asset('storage/...')`) was assuming the public disk. The two offenders were:
   - `app/Filament/Resources/Students/StudentResource.php` — `degree_image`
   - `app/Filament/Resources/Faculty/Pages/ViewMyCourse.php` — `paper_sheet_path`

Every other upload in the codebase already targets `public` explicitly (`PdfService`, `RegistrationController`, `ThesisRegistrationPage` — all use `disk('public')` or `store(..., 'public')`).

## Fix

1. Ran `php artisan storage:link` — created the symlink, unblocked all 10+ `asset('storage/...')` URL builders in the codebase.
2. Added `->disk('public')` to the two offending `FileUpload` components.
3. Migrated 6 existing degree files + 2 grade sheets from `storage/app/private/...` → `storage/app/public/...` so already-uploaded documents keep working.

## Regression guard

**Convention to enforce on every new `FileUpload`:** always include `->disk('public')` (or explicitly choose `local` if the file must be private). The default disk is private — silent fallthrough is the trap.

If a future upload should be truly private (e.g. legal documents), use `->disk('local')` and serve via `Storage::disk('local')->url($path)` (signed) or a custom controller — never `asset('storage/...')`.

## Files touched

- `app/Filament/Resources/Students/StudentResource.php` (line 511)
- `app/Filament/Resources/Faculty/Pages/ViewMyCourse.php` (line 106)
- `public/storage` (new symlink)
- `storage/app/public/students/degrees/*` (6 migrated files)
- `storage/app/public/grades/sheets/*` (2 migrated files)

## Follow-up: thumbnail rendered broken even after disk fix

After the symlink + disk fix, **the modal preview worked but the thumbnail still showed a broken image icon**. Root cause: Filament's `ImageEntry` builds its `src` via `Storage::disk('public')->url($path)`, which always uses the disk's configured `url` (= `APP_URL/storage`). `APP_URL=http://localhost` in `.env`, so the thumbnail src was `http://localhost/storage/...` — broken when the user is browsing on `127.0.0.1:8000` (`php artisan serve`) or `graduate-project.test` (Herd). Meanwhile the modal preview used `asset('storage/...')` which IS request-aware in an HTTP context — so the modal worked but the inline thumbnail didn't.

**Final approach (after user feedback):**

Two-entry pattern (one for images, one for PDFs):

- **Images** → `ImageEntry::make('degree_image')` with `->getStateUsing(fn ($record) => asset('storage/'.$record->degree_image))`. Filament's `ImageEntry` accepts a full URL state and uses it as-is, bypassing the broken `Storage::url()` host lookup entirely. `hintAction` = `عرض بالحجم الكامل` → modal preview via `filament.components.image-preview`.
- **PDFs** → `TextEntry::make('degree_image')` with `->state('فتح الملف')` + `->icon(Heroicon::ArrowTopRightOnSquare)` + `->color('info')` + `->url(asset(...))` + `->openUrlInNewTab()`. No thumbnail, no embedded viewer — just an "open" button that pops the PDF in a new browser tab.

The `filament.components.image-preview` blade is image-only.

**Files touched (follow-up):**
- `resources/views/filament/components/image-preview.blade.php` (image-only, no PDF branch)
- `app/Filament/Resources/Students/StudentResource.php` (`ImageEntry` for images + `TextEntry` button for PDFs)

**Iterations that didn't stick (for future me):**
- Tried single `ViewEntry` rendering an `<object data="...pdf" type="application/pdf">` for the PDF thumbnail. Rejected: browsers showed the full PDF viewer chrome (toolbar), not just the first page.
- Tried a server-side rasterized JPEG thumbnail via the `magick` CLI (Imagick PHP ext not installed but Ghostscript + ImageMagick CLI are present). Cancelled before completion: user decided no PDF thumbnail at all, just an open button.

## Conventions to remember

1. Every new `FileUpload` must explicitly call `->disk('public')`. The env default is `local`/private and silent fallthrough is the trap.
2. When `APP_URL` doesn't match the live request host (multi-environment dev: Herd + `php artisan serve`), avoid `Storage::disk('public')->url()` and Filament's default `ImageEntry` URL builder for asset display. Either (a) pass `asset('storage/...')` via `->getStateUsing()` so Filament uses it as-is, or (b) render the asset yourself in a custom `ViewEntry` blade using `asset()`. `asset()` IS request-host-aware inside an HTTP request.
3. Filament `hintAction` on a custom `ViewEntry` does not display the same way as on built-in entries — prefer `ImageEntry`/`TextEntry` (which do render `hintAction` reliably) and use `ViewEntry` only for content that built-in entries cannot express.
