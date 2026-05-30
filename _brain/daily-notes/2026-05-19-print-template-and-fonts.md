---
date: 2026-05-19
tags: [daily, prints, mpdf, fonts, arabic, rtl, student-letter, step-4]
related:
  - "[[../decisions/2026-05-19-print-template-fonts]]"
  - "[[../bugs-and-fixes/2026-05-19-mpdf-arabic-font-incompatibilities]]"
  - "[[../project-context/print-system-guide]]"
  - "[[2026-05-17-settings-widget-and-print-plan]]"
---

# 2026-05-19 — Font selection + first ministry print template (`تعريف طالب`)

> Step 4 of the print overhaul is partly done: the **student-letter** template (`prints/student_letter.blade.php`) is locked. Font stack and reusable patterns are documented for the rest of the cohort. Pick up next session at the remaining Step 4 templates (provisional grades content, attendance sheet, course result) using [[../project-context/print-system-guide]] as the starting point.

## What we did today

### 1. Font shootout (then removed)

Goal: find an mPDF-compatible Arabic font that looks closer to the ministry Word docs than DomPDF's default. Tested seven candidates in **isolated per-font routes** so a crash on one wouldn't poison the others.

Working: Noto Naskh Arabic, Markazi Text VF, El Messiri VF, Amiri.
Failing (mPDF errors): Aref Ruqaa, Noto Kufi Arabic, Scheherazade (all versions tried).

Full error table → [[../bugs-and-fixes/2026-05-19-mpdf-arabic-font-incompatibilities]] §2.

After the user picked, the entire test harness was deleted:
- `PrintController::fontTest()` and `fontTestSingle()` methods removed.
- `/admin/font-test/{font}` routes removed.
- `resources/views/prints/font-test*.blade.php` views deleted.
- Test fonts kept in `storage/app/print-fonts/test/` for possible re-evaluation, **not** loaded by `makeMpdf()`.

### 2. Font stack locked

Three fonts registered in `PrintController::makeMpdf()`:

- **Noto Naskh Arabic** (R + B) — default for everything Arabic.
- **Markazi Text VF** (R only) — for numbers (enrollment no., year digits).
- **DejaVu Sans** (mPDF bundled) — substitution fallback for ASCII chars Noto lacks.

Plus Amiri (R + B + I + BI) is registered but unused by the current template.

Critical mPDF flags: `useSubstitutions => true`, `backupSubsFont => ['dejavusans']`, `autoLangToFont => false`.

Decision note → [[../decisions/2026-05-19-print-template-fonts]].

### 3. Student-letter template built

`resources/views/prints/student_letter.blade.php` is the first ministry-style PDF. It matches the layout of the Word doc `تعريف طالب 2.docx`:

- Centered title `إلى من يهــمه الأمــر` at ~50mm from top.
- Right-aligned greeting `بعد التحية ،،،،` italic.
- Centered body paragraphs: faculty/office line → student name → registration paragraph → closing.
- Enrollment number and semester-year digits are wrapped in `<span style="font-family:markazi;...">` via `preg_replace` so they get the larger numeric font.
- Salam line.
- **Signature** (`<div class="signature">`) — floated left, narrow width (55mm), right-aligned RTL text, `white-space: nowrap` so longer lines overflow leftward instead of wrapping.
- **CC block** (`<div class="cc-block">`) — flows under the signature, right-aligned. Dashes line is a literal `--------------------` in DejaVu (not a CSS border). Two-cell table with "صورة الى:" right cell + items left cell.

User locked the final positioning manually after a few iterations — don't reposition without explicit ask.

### 4. Numeric styling tuning

Iterated on the number sizing with the user:

- Started at inherited 16pt (too small) →
- 18pt (still small) →
- 20pt → 22pt (perfect) →
- User asked to reduce the boldness → realised Markazi has no `B` variant so `font-weight: bold` was producing synthetic stroke-doubling → set to `font-weight: normal` → final.

Final values, both at `.enrollment-no` and in the inline span produced by `preg_replace`:

```css
font-family: 'markazi'; font-weight: normal; font-size: 22pt;
```

### 5. Box-character fix

Initial render had every `/`, `-`, `–` showing as a tofu box because Noto Naskh Arabic has no Latin glyphs. Enabled `useSubstitutions => true` + `backupSubsFont => ['dejavusans']` in `makeMpdf()` — fixed.

### 6. Dashed-line fix

CSS dashed border in the CC footer was rendering as ugly spaced underscores. Replaced with literal `--------------------` in DejaVu Sans. Looks correct now.

### 7. Signature/CC positioning iterations

Several rounds:

1. First attempt — table-based footer, content flowed too high on the page.
2. `position: fixed` blocks anchored at absolute mm coordinates. Looked right horizontally but `text-align: left` made the Arabic look LTR-aligned.
3. Switched to `text-align: right` — but at 85mm width the right edge of the text drifted to page-center, looked centered.
4. Narrowed box to 55mm — fixed the centering illusion.
5. User edited the file themselves to switch from `position: fixed` to `float: left` (keeps the block in document flow, easier to nudge with empty paragraphs).
6. Dashes line moved to sit right below the signature's last line.

Final positioning is in the file; **don't touch unless asked.**

## Files touched today

- `app/Http/Controllers/PrintController.php` — added/removed font-test methods; final state has only `studentLetter()`, `provisionalGrades()`, and `makeMpdf()` with the three-font stack.
- `routes/web.php` — added/removed font-test routes; final routes are `print.student-letter` and `print.provisional-grades` plus `course-offering.print-students`.
- `resources/views/prints/student_letter.blade.php` — rewritten end-to-end (multiple iterations).
- `storage/app/print-fonts/MarkaziText-VF.ttf` — copied from `test/` to the loaded directory.
- `storage/app/print-fonts/test/*` — kept (not loaded).
- Removed: `resources/views/prints/font-test.blade.php`, `font-test-section.blade.php`.

## Decisions made

- [[../decisions/2026-05-19-print-template-fonts]] — three-font stack and config flags.
- Markazi runs use natural weight (no synthetic bold), compensated with larger size.
- Footer/dashes use literal characters in DejaVu, never CSS borders.
- For RTL signature blocks: narrow width + `text-align: right` + `white-space: nowrap`.

## Bugs documented

- [[../bugs-and-fixes/2026-05-19-mpdf-arabic-font-incompatibilities]] — every error hit today, with cause + fix.

## Loose ends / next session

Step 4 has four templates total. **One done** (student letter). Remaining:

1. **`كشف درجات مؤقت`** (Provisional Grades) — `prints/student_provisional_grades.blade.php` scaffold already exists from earlier work, but needs the Word-doc layout match (font stack, header, table styling, signature/CC block reusing the patterns from this session).
2. **`كشف الحضور والغياب`** (Attendance Sheet) — new. Per-course-offering print listing enrolled students. Wired into both `/admin/course-offerings/{id}/enroll` and `/admin/my-courses/{id}`.
3. **`نتيجة مقرر دراسي`** (Course Result) — new. Per-course-offering print listing grades. Same two surfaces as attendance.

After Step 4, continue with Steps 5–10 (see status table in [[../project-context/print-system-guide#step-status-print-system-overhaul]]).

**Starting point for the next template:** copy the skeleton from [[../project-context/print-system-guide#blade-template-skeleton]]. The font stack, RTL traps, and positioning patterns are already solved — just adapt the body content.

## Test credentials (unchanged)

| Role | Email | Password |
|---|---|---|
| super-admin | `admin@college.edu` | `admin123` |
| secretary | `secretary@college.edu` | `secretary123` |
| faculty | `cs.faculty@example.ly` | `12345678` |
| student | `mohammed.zawai@example.com` | `12345678` |

## URLs touched

- `https://graduate-project.test/admin/students/{id}/print-student-letter` — works, locked.
- `https://graduate-project.test/admin/students/{id}/print-provisional-grades` — exists, needs Word-doc layout pass.
