---
date: 2026-05-19
tags: [bug, prints, mpdf, fonts, arabic, gpos, otl]
related:
  - "[[../decisions/2026-05-19-print-template-fonts]]"
  - "[[../project-context/print-system-guide]]"
  - "[[../daily-notes/2026-05-19-print-template-and-fonts]]"
---

# mPDF Arabic-font incompatibilities + glyph-substitution traps

A grab-bag of every error/quirk hit while picking fonts for the Step 4 print templates. Each is paired with the symptom, the root cause, and the fix (or "skip this font, it's incompatible").

## 1. Latin punctuation rendering as boxes (`☐ / ☐ - ☐`)

**Symptom.** Student-letter PDF rendered Arabic text fine but every `/`, `-`, `–` came through as a tofu box. Numbers were also boxed when set in Noto Naskh.

**Cause.** Noto Naskh Arabic's TTF ships only Arabic glyphs and a few Arabic-Indic digits — no Latin codepoints. mPDF falls back to its placeholder box if no font in the active stack covers the codepoint.

**Fix.** Two mPDF flags in `PrintController::makeMpdf()`:

```php
'useSubstitutions' => true,
'backupSubsFont' => ['dejavusans'],
```

`dejavusans` is bundled with mPDF and covers full ASCII + Latin-1 + Latin Extended. mPDF now silently switches to DejaVu for any codepoint the primary font lacks. The Arabic shaping for `noto-naskh` runs is unaffected because substitution is per-codepoint.

**Regression guard.** If new punctuation appears in a future template and shows as a box, the first thing to check is whether `useSubstitutions` is still true on that `Mpdf` instance.

## 2. mPDF errors / refuses to load specific fonts

These all failed during the font shootout. **Do not try to add any of them back without first re-testing in an isolated route.** They're listed here so future-me doesn't repeat the experiment.

| Font | Exact error | Class / file |
|---|---|---|
| Aref Ruqaa (Bold + Regular) | `Undefined array key 57653` | `Mpdf\Otl::GPOSGetAnchor()` |
| Noto Kufi Arabic | `Undefined array key 0` | `Mpdf\TTFontFile::getTTFGlyph()` |
| Scheherazade New 4.500 | `GPOS Lookup Type 5 Format 3 is not supported` | `Mpdf\Otl::GPOSLookup()` |
| Scheherazade 2.100 (2015) | `MarkGlyphSets are not supported` | `Mpdf\TTFontFile::extractInfo()` |

**Cause.** mPDF implements a subset of the OpenType GPOS/GSUB spec. These fonts use lookup formats or glyph-mark constructs that mPDF's renderer doesn't handle. The error throws *during* `WriteHTML()`, not during font registration, so a single render with a bad font crashes the whole request.

**Fix.** Use one of the verified-working fonts only ([[../decisions/2026-05-19-print-template-fonts]]). For experimentation, isolate each candidate behind its own route and its own `Mpdf` instance so a crash on one font doesn't poison the others — that was the pattern used here in `PrintController::fontTestSingle()` (since deleted).

## 3. Dashed border renders as spaced `_ _ _ _` not standard `--------`

**Symptom.** `<div style="border-top: 1px dashed #000;"></div>` in the CC footer rendered as wide-spaced underscores, not a normal dashed line.

**Cause.** mPDF's dashed-border renderer uses a fixed dash/gap ratio that, at 1pt thickness, draws each dash as a stub with large gaps. Combined with the surrounding Arabic font (which has unusual punctuation glyphs), the visual result was a row of disconnected marks.

**Fix.** Drop the CSS border. Use a literal text run of twenty hyphens in DejaVu Sans:

```html
<div class="cc-divider">--------------------</div>
```

```css
.cc-divider {
    font-family: 'dejavusans', sans-serif;
    letter-spacing: 0;
    /* no border-top */
}
```

DejaVu's hyphen glyph (`U+002D`) is a normal ASCII hyphen and the run renders as a clean `---...` line.

## 4. Synthetic bold on a variable font with no `B` variant looks inked

**Symptom.** Enrollment number set in Markazi Text + `font-weight: bold` rendered as heavily stroked digits — looked smeared, not bold.

**Cause.** Markazi is registered with only the `R` variant. When CSS asks for bold, mPDF synthesises by drawing the regular glyph twice with a stroke offset. For variable Arabic fonts with already-thick strokes, this produces inky artefacts rather than a proper bold weight.

**Fix.** Use `font-weight: normal` on Markazi spans and instead increase the font-size. Final values in `student_letter.blade.php`:

```css
.enrollment-no { font-family: 'markazi'; font-weight: normal; font-size: 22pt; }
```

And inline for the semester-year digits (preg_replace in the `@php` block):

```php
preg_replace('/(\d+)/', '<span style="font-family:markazi;font-size:22pt;font-weight:normal;">$1</span>', e($regSemester))
```

The size bump compensates for the missing weight. If a future template needs Markazi at multiple sizes with bold differentiation, register a Markazi Bold variant in `fontdata` rather than relying on synthetic bold.

## 5. `position: fixed` + RTL — text-align gotcha

**Symptom.** Signature block was placed at `left: 18mm; top: 205mm` (bottom-left of page). With `text-align: right` inside an 85mm-wide box, the Arabic ended at x≈103mm — right before page center — so the signature *visually* read as centered even though the block was technically on the left.

**Cause.** Page center is at ~108mm on Letter. An 85mm-wide left-anchored box with right-aligned content puts the text right edge essentially at center.

**Fix.** Narrow the box. Final width is 55mm with `white-space: nowrap` on the inner divs so longer lines extend leftward past the block bound instead of wrapping:

```css
.signature {
    float: left;
    width: 55mm;
    text-align: right;
    direction: rtl;
}
.signature > div { white-space: nowrap; }
```

Note: user ultimately preferred `float: left` over `position: fixed` because it kept the block in document flow (easier to nudge vertically by adding empty paragraphs above), so the final template uses floats — see the file directly for the locked positions.

## 6. Variable-font registration only takes `R` slot

**Symptom.** Tried to register Markazi as `R + B` both pointing at `MarkaziText-VF.ttf` (it's a variable font with weight axis). The bold variant was ignored — `font-weight: bold` rendered identically to regular.

**Cause.** mPDF reads static font tables, not variation axes. It doesn't apply variable-font axis values; it just loads the default static instance from the VF file. Registering the same file under `B` is a no-op.

**Fix.** Either (a) ship a separate static-bold TTF and register it in `B`, or (b) use a single weight only. Current template uses option (b) — see Fix #4.

## Combined "do not touch" list

When working on the next print templates:

1. Keep `useSubstitutions => true` and `backupSubsFont => ['dejavusans']` on every `Mpdf` instance.
2. Don't add a new Arabic font to `fontdata` without running it through an isolated test route first (see [[../project-context/print-system-guide#testing-a-new-font]]).
3. For dashes / decorative lines, use literal characters in DejaVu, not CSS borders.
4. Don't `font-weight: bold` a font that has no `B` registered.
5. For RTL positioned blocks, narrow the box so right-aligned text doesn't drift to the page centerline.
