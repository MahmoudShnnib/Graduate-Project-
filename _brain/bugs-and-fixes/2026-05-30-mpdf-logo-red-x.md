---
date: 2026-05-30
tags: [bug, mpdf, prints, thesis, images, logo]
related:
  - "[[../project-context/gotchas#mpdf--arabic-print-quirks]]"
  - "[[../project-context/print-system-guide]]"
  - "[[../daily-notes/2026-05-30-print-and-table-bug-fixes]]"
  - "[[2026-05-19-mpdf-arabic-font-incompatibilities]]"
---

# Thesis ministry print: logo rendered as black box, then red X

## Symptoms

On `/admin/thesis-registrations/{id}/print-ministry-form` the university logo slot showed, in sequence:

1. A **black box** (before any logo was wired in).
2. After wiring the logo: a **red X in a small box** — but *only in the browser / on the running server*. The same PDF generated via `php artisan tinker` showed the logo perfectly.
3. After the logo rendered: it was **off-center** horizontally.

## Root causes

**(1) Black box** — the header cell contained a literal HTML entity `&#9632;` (U+25A0 ■) at 28pt as a logo placeholder, in `resources/views/prints/thesis_ministry.blade.php` (two header spots, one per page). Not a broken image — a hardcoded square.

**(2) Red X** — the red-X box is **mPDF's own "image could not be processed" marker** baked into the PDF bytes (poppler/pdf.js/pdfium never draw such a thing, so its presence proves mPDF failed at *generation* time). The logo was a **1577×1475 RGBA PNG** (~9 MB decoded in GD). It decoded fine under `tinker` but failed on the running `php artisan serve` HTTP request — the oversized alpha image exceeds what mPDF/GD can process within the request's resource envelope, and mPDF degrades to the red-X box instead of throwing. Confirmed the *code* was correct (CLI render at 128M produced the logo); the failure was environment/size-bound.

**(3) Off-center** — the 3-column header table used asymmetric widths `ar 50% / logo 15% / en 35%`, so the logo cell's center sat at ~57.5% of the content width.

## Fixes

1. Replaced both `&#9632;` placeholders with `<img src="{{ $logoData }}" style="width: 22mm;">`.
2. Asset stored at `public/images/university-logo.png`; embedded as **base64** in the blade `@php` block (`is_file($logoPath)` guard → `data:image/png;base64,...`). `$logoData` is read at render time, so swapping the file needs no `view:clear`.
3. **Downscaled + flattened** the logo with GD: `imagecopyresampled` to 320×299 onto a white-filled truecolor canvas → RGB, no alpha, 29 KB (was 116 KB). Visually identical at 22mm; removes the decode failure.
4. Equalized the header columns to `42% / 16% / 42%` so the logo lands on page center. Added `vertical-align: middle` to `.header-logo`.

## Regression guard

- No automated test (binary-PDF image assertion is impractical), but verified two ways: rasterized the generated PDF with `pdftoppm` (logo present, centered, both pages) **and live in Chrome** via chrome-devtools MCP (logged in as admin, opened the real print URL).
- Durable lesson promoted to [[../project-context/gotchas#mpdf--arabic-print-quirks]]: never embed multi-megapixel / alpha PNGs in mPDF — pre-size and flatten first.
