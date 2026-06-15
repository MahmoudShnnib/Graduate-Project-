---
date: 2026-06-15
tags: [pdf, mpdf, dompdf, cleanup, filament, graduates, clearance]
related: [[../project-context/project-overview]], [[../project-context/architecture]], [[../project-context/print-system-guide]]
---

# DomPDF system removal + Graduate clearance print button

## Context
PDF generation had drifted into **two engines**: the legacy DomPDF stack (`PdfService` →
`resources/views/pdfs/*`) and the newer **mPDF** print system (`PrintController` →
`resources/views/prints/*`). DomPDF was effectively redundant — clearance existed in both,
two of its methods were dead, and there was no longer any reason to keep transcript on DomPDF.
User decision: remove DomPDF entirely, mPDF becomes the sole engine.

## What was done

### 1. Removed the entire DomPDF system
- Deleted `app/Services/PdfService.php` (only caller was `ReportsPage`).
- Deleted `app/Filament/Pages/ReportsPage.php` + `resources/views/filament/pages/reports.blade.php`
  ("التقارير والنماذج" page — its only functions were the DomPDF transcript + a duplicate
  DomPDF clearance). **Transcript feature dropped entirely** (no mPDF equivalent existed; user accepted).
- Deleted the 4 orphaned views: `resources/views/pdfs/{transcript,clearance_form,suspension_form,thesis_form}.blade.php`
  and removed the now-empty `pdfs/` dir.
- `composer remove barryvdh/laravel-dompdf` — needed `--ignore-platform-req=ext-gd`
  (composer's resolver false-negatives on gd in this CLI; gd *is* loaded — and it was **mpdf**,
  the kept package, that declares the `ext-gd` requirement, not dompdf). mpdf left intact.

### 2. Test fixes (functionality deleted with approval)
- `tests/Feature/FacultyResourceVisibilityTest.php` — dropped the two `ReportsPage::canAccess()` assertions.
- `tests/Feature/ProductionHardeningTest.php` — removed the M8 block (2 tests for
  `ReportsPage::getStudentsProperty()` picker) + the now-unused `ReportsPage`/`Student` imports. M9 kept.

### 3. New feature — "طباعة نموذج الاستماحة" button on the Graduates **list** page
User wanted it as a **header button** on `/admin/graduates` (the list), **not** a per-row table action
and **not** inside the per-graduate view page.

Key realization: `prints/clearance_form` is a **blank template** (line 2: "every field filled in by hand,
no student data is rendered") — it only reads `Setting::get('college_name'/'office_name')`. So a single
student-independent button is exactly right; the existing per-student route only used `{student}` for
auth + filename.

- New route `print.clearance-blank` → `GET /admin/clearance-form/print` (auth middleware).
- New `PrintController::clearanceFormBlank()` — `authorizeAdminOnly()`, renders the same blank
  `prints/clearance_form` view, streams inline (`clearance-form.pdf`). The per-student
  `clearanceForm(Student)` route is untouched (still used by the student portal).
- `ListGraduates` now defines `getHeaderActions()` with a gray printer `Action` linking to the new route, opening in a new tab.
- Reverted the earlier `ViewGraduate::printClearanceAction()` attempt.

Test added to `ProductionHardeningTest`: admin gets `assertOk()` (confirms the blank PDF renders),
student is redirected to `/portal/login` (403 → logout per bootstrap/app.php).

### 4. Docs sweep
- `CLAUDE.md`: tech-stack integration line dompdf→mpdf; removed `ReportsPage`/`PdfService`/`pdfs/`
  from folder structure; updated `prints/` listing + support-flows blurb.
- `_brain/project-context/architecture.md` + `project-overview.md`: replaced DomPDF/`PdfService`
  descriptions with the mPDF stream-inline reality.
- Left dated daily-notes/decisions/research/audit snapshots untouched (historical).

## Verification
- `vendor/bin/pint --dirty` → pass.
- Full suite: **316 passed (781 assertions)**.

## ⚠️ Heads-up for the parallel go-live-audit session
`go-live-readiness-audit.md` lists `ReportsPage.php:50-51` as a LIKE case-sensitivity fix target
(and uses "transcript" as a temp-dir example). **`ReportsPage` no longer exists** — that finding is
resolved-by-deletion. I left that file untouched to avoid clobbering the in-flight B1/B2 work.

## Loose ends
- mPDF is now the single PDF engine (9 documents). No transcript document exists anymore — revisit
  if an official transcript is ever needed (would be a new `prints/transcript` + PrintController method + route).

---

## Follow-up (evening) — clearance PDF layout polish

Visual tuning of `resources/views/prints/clearance_form.blade.php` only. No controller change.

1. **"Shrunk in the center" fix.** All print templates share `makeMpdf()` with 25.4mm (1in) page
   margins; borderless templates put text at that 25.4mm line. The clearance form draws its `.frame`
   border *at* 25.4mm and then adds inner padding (`.frame` 2.5mm + `.frame-inner` 9mm ≈ 11.5mm/side),
   so its text started ~37mm in — visibly narrower than every other doc. Fix: `@page { margin: 14mm; }`
   scoped to this blade. The border now sits out at 14mm and, with the frame padding, the **text** lands
   at ~25.4mm — matching the other PDFs. Wider content area = less overflow risk, not more.
2. **Bottom gap.** `min-height` on `.frame-inner` did **nothing** — mPDF ignores `min-height` on block
   elements (confirmed by trial; use a fixed-height spacer instead). Solution: a `.notes-spacer`
   `<div>` (fixed `height`) placed right before the `.notes` block to push the ملاحظة section down.
   Final value **22mm** (started at 45mm, user wanted half).

### Gotchas worth remembering
- **mPDF ignores `min-height`** on block elements — to reserve vertical space, use a fixed-`height`
  spacer div, not `min-height`.
- **Per-document margins** can be set with CSS `@page { margin: … }` inside the blade, overriding the
  shared `makeMpdf()` constructor margins — no controller change, no impact on other templates.
