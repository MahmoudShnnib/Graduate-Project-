---
tags: [project-context, prints, mpdf, arabic, rtl, fonts, templates, durable]
related:
  - "[[../decisions/2026-05-19-print-template-fonts]]"
  - "[[../bugs-and-fixes/2026-05-19-mpdf-arabic-font-incompatibilities]]"
  - "[[../decisions/2026-05-17-settings-dashboard-widget]]"
  - "[[../daily-notes/2026-05-17-settings-widget-and-print-plan]]"
  - "[[gotchas]]"
---

# Print System — durable reference

> The Arabic ministry / faculty print system. Use this as the starting point for every new print template. Codifies the font stack, controller pattern, blade structure, RTL traps, and verification flow that took a full session to converge on for the first template (`student_letter`).

## The high-level flow

```
Filament Action / Button
        │
        ▼
GET /admin/{resource}/{id}/print-{name}
        │  (web.php route → middleware: auth)
        ▼
PrintController::{name}()
  ├─ authorizeAdminOnly()        ← role check (super_admin / secretary; some let faculty)
  ├─ $student->load(...)          ← eager-load every relation the blade needs
  ├─ view('prints.X', [...])->render()
  ├─ makeMpdf()                   ← single helper; same font stack everywhere
  ├─ $pdf->WriteHTML($html)
  └─ return Response w/ Content-Disposition: inline
```

Settings (`college_name`, `office_name`, `director_name`, `dean_name`, …) are read at the top of each blade via `\App\Models\Setting::get('key')`. They are editable from the dashboard SettingsWidget (Step 1 done — see [[../decisions/2026-05-17-settings-dashboard-widget]]).

## The font stack (do not deviate without a test)

Registered in `PrintController::makeMpdf()`:

| Slot | Font | Variants | Used for |
|---|---|---|---|
| Primary | `noto-naskh` | R + B | Default body — every Arabic paragraph, heading, label |
| Numeric | `markazi` | R only | Numbers only — enrollment number, year digits |
| Substitution | `dejavusans` | bundled | Auto-fill Latin/ASCII glyphs Noto Naskh lacks |
| Fallback (registered, rarely used) | `amiri` | R + B + I + BI | Available for templates that want italic/slanted Arabic |

Mandatory config flags:

```php
'directionality'   => 'rtl',
'autoScriptToLang' => true,
'autoLangToFont'   => false,     // critical — keep our default_font
'useSubstitutions' => true,
'backupSubsFont'   => ['dejavusans'],
'default_font'     => 'noto-naskh',
'default_font_size'=> 16,
'fontDir'          => array_merge($fontDirs, [storage_path('app/print-fonts')]),
```

Why each font, and which fonts were rejected (Aref Ruqaa, Noto Kufi, Scheherazade) — [[../decisions/2026-05-19-print-template-fonts]].

## Blade template skeleton

Use this as the starting point for any new ministry-style letter. Every locked pattern below is here for a reason — see [[../bugs-and-fixes/2026-05-19-mpdf-arabic-font-incompatibilities]] for the bug that motivated it.

```blade
@php
    /** @var \App\Models\Student $student */
    $collegeName  = \App\Models\Setting::get('college_name');
    $officeName   = \App\Models\Setting::get('office_name');
    $directorName = \App\Models\Setting::get('director_name');
    // ...other Setting::get() lookups...

    // Wrap any digits you want in Markazi:
    $regSemester     = $student->registration_semester?->name;
    $regSemesterHtml = $regSemester
        ? preg_replace('/(\d+)/', '<span style="font-family:markazi;font-size:22pt;font-weight:normal;">$1</span>', e($regSemester))
        : null;
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<style>
    body {
        font-family: 'noto-naskh', serif;
        font-size: 16pt;
        line-height: 1.08;
        color: #000;
        direction: rtl;
        text-align: center;
    }
    p { margin: 0 0 8pt 0; text-align: center; }

    /* Title — big centered headline. Top margin = where on the page the letter starts */
    .title {
        font-size: 20pt;
        font-weight: bold;
        margin: 44mm 0 16pt 0;
    }

    /* Markazi spans — for ANY numeric run */
    .markazi-num { font-family: 'markazi'; font-weight: normal; font-size: 22pt; }

    /* Signature — bottom-left of page, floated. Narrow width so RTL right-align
       doesn't drift to page center. nowrap on the inner divs so long lines
       extend leftward instead of wrapping. */
    .signature {
        margin-top: 40pt;
        float: left;
        clear: both;
        width: 55mm;
        text-align: right;
        direction: rtl;
        font-size: 12pt;
        line-height: 1.6;
        overflow: visible;
    }
    .signature > div {
        direction: rtl;
        text-align: right;
        white-space: nowrap;
    }

    /* CC / footer block — flows below the signature, right side.
       Dashes use DejaVu so the hyphen glyph is normal ASCII -. */
    .cc-block {
        clear: both;
        margin-top: 18pt;
        text-align: right;
        direction: rtl;
        font-size: 10pt;
    }
    .cc-divider {
        font-family: 'dejavusans', sans-serif;
        letter-spacing: 0;
        font-size: 10pt;
        line-height: 1;
        margin: 0 0 4pt 0;
    }
</style>
</head>
<body>

<p class="title">إلى من يهــمه الأمــر</p>

{{-- body paragraphs --}}

<div class="signature">
    <div dir="rtl">{{ $directorName }}</div>
    <div dir="rtl">مدير {{ $officeName }} بالكلية</div>
</div>

<div class="cc-block">
    <div class="cc-divider">--------------------</div>
    <table style="border-collapse:collapse;direction:rtl;">
        <tr>
            <td style="white-space:nowrap;padding-left:8pt;">صورة الى:</td>
            <td>- ملف الطالب<br>- صادر القسم</td>
        </tr>
    </table>
</div>

</body>
</html>
```

## RTL traps to avoid

These are the ones that cost the most time on the first template:

1. **Page center is the enemy of "left-side" blocks.** Letter page is 215.9mm wide; center ≈ 108mm. A box positioned at `left: 18mm` with width 85mm and `text-align: right` puts the text right edge at 103mm — visually centered. Narrow the box (e.g. 55mm width) so right-aligned text stays well left of center.
2. **`text-align: left` inside `dir="rtl"` looks unnatural.** Arabic blocks read better right-aligned even when the *block itself* is on the page's left. Use `direction: rtl; text-align: right` and control horizontal page position via the block's own `left` / `float` instead.
3. **`<td>` order in RTL tables is reversed visually.** First `<td>` = physical right cell, second `<td>` = physical left cell. The CC block exploits this: label cell goes first.
4. **Mixed Arabic/Latin in one span.** Use `e()` (HTML-escape user content) and `{!! !!}` only for the digit-wrapping preg_replace.
5. **Avoid CSS dashed/dotted borders.** mPDF's renderer makes them look like spaced underscores. Always use a literal text run in DejaVu.
6. **Numbers + bold = ink smear.** Markazi has no `B` registered, so any `font-weight: bold` on a Markazi span produces synthetic stroke-doubling. Bump size instead.

Full bug list — [[../bugs-and-fixes/2026-05-19-mpdf-arabic-font-incompatibilities]].

## Adding a new template (the recipe)

1. **Controller method** in `app/Http/Controllers/PrintController.php`:
   ```php
   public function thingName(SomeModel $record): Response
   {
       $this->authorizeAdminOnly(); // or a wider check if faculty needs access
       $record->load(['relation.one', 'relation.two']);
       $html = view('prints.thing_name', ['record' => $record])->render();
       $pdf  = $this->makeMpdf();
       $pdf->WriteHTML($html);
       return response($pdf->Output('', 'S'), 200, [
           'Content-Type'        => 'application/pdf',
           'Content-Disposition' => 'inline; filename="thing-'.$record->id.'.pdf"',
       ]);
   }
   ```

2. **Route** in `routes/web.php`:
   ```php
   Route::get('/admin/{some-models}/{record}/print-thing-name', [PrintController::class, 'thingName'])
       ->middleware(['auth'])->name('print.thing-name');
   ```

3. **Blade** at `resources/views/prints/thing_name.blade.php` — start from the skeleton above. Copy `student_letter.blade.php` for any letter-style print; copy `student_provisional_grades.blade.php` for a table/list print.

4. **Wire to UI.** Either (a) Filament header `Action` on a Resource page, or (b) a button in a Blade overlay. The pattern is `redirect()->route('print.thing-name', $record)` for direct, or a `Radio`-modal picker when a surface has ≥ 2 print options (see Step 6 in [[../daily-notes/2026-05-17-settings-widget-and-print-plan]]).

5. **Verify.**
   - `vendor/bin/pint --dirty --format agent`.
   - Open the print URL in the browser. mPDF inlines errors at the top — *don't* return early on a stray PHP warning, they show up as text above the PDF.
   - For tables / data prints, test with one record that has all fields and one with empty/null fields (the `?->` chains should cover it).

## Testing a new font

If you ever need to evaluate a candidate font:

1. Drop the TTF into `storage/app/print-fonts/test/`.
2. Temporarily register it in `makeMpdf()` under a temp key, e.g. `'tmp-font' => ['R' => 'CandidateFont.ttf', 'useOTL' => 0xFF, 'useKashida' => 75]`.
3. Add a temporary route like `Route::get('/admin/font-test/{font}', [PrintController::class, 'fontTestSingle'])` that wraps **one font per Mpdf instance** (so a crash on one doesn't poison others).
4. Hit the URL in the browser. Either it renders a clean sample of Arabic + digits, or mPDF throws — note the error class and bail.
5. **Remove the temp route + method + test font from `fontdata`** before committing. The font shootout this session shipped, then removed, this exact scaffolding — don't leave it in `main`.

## Step status (Print System Overhaul)

From the original 10-step plan in [[../daily-notes/2026-05-17-settings-widget-and-print-plan]]:

| Step | What | Status |
|---|---|---|
| 1 | Settings dashboard widget | ✅ Done (2026-05-17) |
| 2 | Add `Setting::get('dean_name')` field | ⏳ TODO |
| 3 | `ApplicationObserver::approved` captures `semester_id` | ⏳ TODO |
| 4 | New ministry templates (student letter, provisional grades, attendance, course result) | 🟡 **In progress** — student letter DONE today; provisional grades scaffold exists, needs Word-doc match; attendance + course result TODO |
| 5 | `القسم` derivation logic on course prints | ⏳ TODO |
| 6 | Multi-print picker (Filament Action modal with Radio) | ⏳ TODO |
| 7 | Thesis ministry print + status flip on first click | ⏳ TODO |
| 8 | Update graduation certificate against new Word doc | ⏳ TODO |
| 9 | Drop hero `طباعة` button on student view page | ⏳ TODO |
| 10 | Delete unused `pdfs/thesis_form.blade.php` | ⏳ TODO |

Continue at Step 4's remaining templates next session — they all share this guide's skeleton.
