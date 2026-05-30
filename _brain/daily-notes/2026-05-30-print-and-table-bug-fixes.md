---
date: 2026-05-30
tags: [daily, prints, filament, bug-fix, exam-list, graduates, graduation-certificate, thesis]
related:
  - "[[2026-05-19-print-template-and-fonts]]"
  - "[[../project-context/print-system-guide]]"
  - "[[../bugs-and-fixes/2026-05-30-mpdf-logo-red-x]]"
---

# 2026-05-30 — Four print/table bug fixes

Quick-fix session against four user-reported issues. All clarified up-front (data was ambiguous) before touching code. Suite green (165 passed), Pint clean.

## 1. Exam-list print now uses mPDF like the rest

`/admin/exam-list-page` was the last surface still using a **client-side `window.print()` popup** (`printExamList()` JS that opened a new window with hand-built HTML and browser chrome). Converted it to the same server-side mPDF pattern as student/course prints.

- `PrintController::examList(Specialization $specialization)` — admin-only, queries `Application` rows with status `InitiallyApproved` for that spec, ordered by `exam_date`.
- Route `print.exam-list` → `/admin/exam-list/{specialization}/print`.
- New `resources/views/prints/exam_list.blade.php` — mPDF font stack (noto-naskh + markazi for digits), college header from `Setting`, meta (التخصص / موعد الامتحان / عدد الطلاب / التاريخ), م + الاسم الرباعي table, signature footer.
- `exam-list.blade.php`: print button now `onclick="window.open(route('print.exam-list', $activeSpec), '_blank')"`; the whole `@script printExamList` block deleted.
- Regression test: `tests/Feature/ExamListPrintTest.php` (admin → 200 + `application/pdf`; faculty → 403).

## 2. Graduates table — graduated students sink to bottom

`/admin/graduates` only ever lists `DiscussionStatus::Completed` requests, so "finished" = the **student** already marked `StudentStatus::Graduated` (certificate received). The table is a work-queue: not-yet-graduated stay on top.

`GraduateResource::table` — replaced `->defaultSort('completed_at','desc')` with a **closure** default-sort (Filament v5 `defaultSort` accepts `string|Closure`, and a returned `Builder` is used directly — see `CanSortRecords::getDefaultSort`). Orders by a correlated subquery flag (`students.status = graduated ? 1 : 0` asc), then `completed_at` desc. Proven via a rolled-back tinker txn: flipping one student to `active` floats it to the top.

## 3. Graduation certificate "Total Score" → average out of 100

`prints/graduation_certificate.blade.php` "Total Score" cell was the raw **sum** of every course score (each /100), e.g. 1003 for 11 courses. Changed to the **average** of numeric course scores → reads on a 0–100 scale (verified: 91.2 instead of 1003). Header text left as "Total Score" (official template wording).

## 4. Thesis ministry print button persistence — already fixed, verified

`ViewThesisRegistration::printForMinistryAction` was **already fixed in the uncommitted working tree** (visibility changed from `status === Pending` to `in_array($status, [Pending, Printed])`, action no longer re-stamps on reprint, redirects to the PDF). Logic verified.

> ⚠️ **Caveat for live testing:** all 8 seeded `ThesisRegistration`s are status `approved`, where the print button is *intentionally* hidden (terminal state). To see the persistence fix, a record must be `Pending` or `Printed`. Offered to flip one to `Pending` on request.

## Files touched

```
M app/Filament/Resources/Graduates/GraduateResource.php
M app/Http/Controllers/PrintController.php            (examList + imports)
M resources/views/prints/graduation_certificate.blade.php
M resources/views/filament/pages/exam-list.blade.php  (button + script removal)
M routes/web.php                                       (print.exam-list)
A resources/views/prints/exam_list.blade.php
A tests/Feature/ExamListPrintTest.php

# follow-ups (always-visible thesis button + logo)
M app/Filament/Resources/ThesisRegistrations/Pages/ViewThesisRegistration.php   (drop ->visible)
M app/Filament/Resources/ThesisRegistrations/ThesisRegistrationResource.php     (drop ->visible, guard stamp, redirect to PDF)
M resources/views/prints/thesis_ministry.blade.php                              (logo img + base64, header 42/16/42)
A public/images/university-logo.png                                             (320×299 RGB, flattened)
```
(`ViewThesisRegistration.php` visibility-on-Printed change pre-existed in the working tree; this session removed the gate entirely.)
Note: `pint --dirty` also reformatted 14 pre-existing dirty `app/Policies/*.php` files (cosmetic import-ordering only — unrelated to this task).

## Verification

- `vendor/bin/pest --compact` → **165 passed (345 assertions)**.
- tinker: examList PDF (60KB) + gradCert PDF (133KB) generate; graduates ordering split proven; cert avg = 91.2.

## 5. Thesis ministry print — real logo instead of black box

The "black box" in `thesis_ministry.blade.php` headers was a literal `&#9632;` (■) placeholder at 28pt, not a broken image. Replaced both header spots with the university logo:
- Asset committed at `public/images/university-logo.png`, embedded as **base64** in the template's `@php` block (`is_file` guard → `data:image/png;base64,...`) — `$logoData` is read at render time, so updating the file needs no view recompile.
- `<img style="width: 22mm">` to fit the 15%-width center header cell.

**Gotcha — red X in the browser PDF, but logo fine in CLI.** The original logo was a **1577×1475 RGBA** PNG (~9MB decoded in GD). mPDF rendered it fine under `php artisan tinker`, but on the running `php artisan serve` it degraded to mPDF's red-X "image failed" box (a baked-in mPDF marker, not a browser glitch). Root cause: that oversized alpha PNG fails decoding under the HTTP request's tighter resource envelope. **Fix:** downscaled to **320×299 and flattened onto white (RGB, no alpha)** via GD — visually identical at 22mm, 29KB vs 116KB. Verified **live in Chrome** (chrome-devtools MCP, logged in as admin, opened the real print URL): blue logo on both pages.

**Centering.** The header is a 3-column table; the original asymmetric widths (`ar 50% / logo 15% / en 35%`) put the logo cell left of page-center. Equalized to `42% / 16% / 42%` (+ `vertical-align: middle`) → logo dead-center on both pages.

Full post-mortem: [[../bugs-and-fixes/2026-05-30-mpdf-logo-red-x]]. Lesson: for mPDF, pre-size + flatten images to roughly their print dimensions; never embed multi-megapixel alpha PNGs.

> If the logo should be swappable from the dashboard later, promote it to a `Setting` key like the college-name text.

## Other always-visible thesis print fix

Per follow-up: removed the `->visible()` status gate on the `print_for_ministry` action on BOTH surfaces (`ViewThesisRegistration` + `ThesisRegistrationResource` row action) so the button is never hidden. The row action used to unconditionally re-stamp `Printed` on every click — guarded it to stamp only when `Pending`, then redirect to the PDF (matching the view page). Suite still 165 green.

## Loose ends

- Issue 4 live test needs a Pending/Printed thesis (see caveat).
- Dead leftovers in `exam-list.blade.php` (`data-print-name`, `data-spec`, `print-hidden`) are now inert — left in place to avoid restyling risk.
