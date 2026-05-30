---
date: 2026-05-19
tags: [decision, prints, fonts, mpdf, arabic, rtl, typography]
related:
  - "[[../bugs-and-fixes/2026-05-19-mpdf-arabic-font-incompatibilities]]"
  - "[[../project-context/print-system-guide]]"
  - "[[../daily-notes/2026-05-19-print-template-and-fonts]]"
  - "[[../daily-notes/2026-05-17-settings-widget-and-print-plan]]"
---

# Print-template font stack — Noto Naskh + Markazi + DejaVu fallback

## Context

Step 4 of the print-system overhaul (see [[../daily-notes/2026-05-17-settings-widget-and-print-plan]]) ships four NEW print templates that must match an Arabic ministry / faculty look. The first one — `تعريف طالب 2.docx` → `print.student-letter` — drove the font-stack decision. We need:

- An Arabic font that renders RTL with proper letter-shaping (OpenType GSUB/GPOS) for paragraphs of running text.
- Latin-friendly digits for enrollment numbers and years (the ministry doc uses a different style for numerals).
- Glyph coverage for plain ASCII punctuation (`/`, `-`, `–`) which appear inline between Arabic runs.

## Decision

Three-font stack registered in `PrintController::makeMpdf()`:

| Font | Variants registered | Used for | Why |
|---|---|---|---|
| **Noto Naskh Arabic** | R + B | Default body, headings, all Arabic running text | Best mPDF-compatible Arabic Naskh font on free license. Bold variant available. OpenType layout (`useOTL => 0xFF`) supported. |
| **Markazi Text VF** | R only | Numbers only (`.enrollment-no` + inline preg_replace span for semester digits) | Variable font, regular variant renders cleanly. Numeric glyphs look closer to the ministry doc's numbering style than Noto's. **No bold registered** — see "Synthetic bold trap" below. |
| **DejaVu Sans** | bundled with mPDF | Substitution fallback (`backupSubsFont => ['dejavusans']`) | Provides ASCII punctuation/Latin glyphs that Noto Naskh Arabic doesn't ship. Also fixes the dashed-line in the CC footer (`--------------------` set in DejaVu so the hyphen glyph is the standard one, not the spaced look Noto produces). |

Config flags that make the stack work:

```php
'autoScriptToLang' => true,
'autoLangToFont' => false,
'useSubstitutions' => true,
'backupSubsFont' => ['dejavusans'],
'default_font' => 'noto-naskh',
'default_font_size' => 16,
```

`autoLangToFont => false` matters — leaving it `true` made mPDF override our `default_font` based on the `lang="ar"` attribute and pick its own fallback. With it off, our `default_font` wins.

## Alternatives considered (and rejected)

| Font tried | Outcome |
|---|---|
| **Aref Ruqaa** | mPDF error `Undefined array key 57653` in `Otl.php`. Glyph index out of range. Font is incompatible. |
| **Noto Kufi Arabic** | mPDF error `Undefined array key 0` in `TTFontFile`. Font table parsing failure. |
| **Scheherazade New 4.500** | mPDF error `GPOS Lookup Type 5 Format 3 is not supported`. |
| **Scheherazade 2.100 (2015)** | mPDF error `MarkGlyphSets are not supported`. All Scheherazade versions fail on mPDF. |
| **Amiri** | Works (kept in `fontdata` as backup) but the slanted/italic variant looks cramped at body size and the regular weight is too "calligraphic" for a ministry letter. |
| **El Messiri VF** | Works in mPDF but the letter widths are inconsistent at print scale — looked off in long paragraphs. |

The test infrastructure that produced these results (a `fontTest()` controller with one route per font, fault-isolated so a crash on font N didn't poison fonts N+1) was added then **removed** in this session — see [[../daily-notes/2026-05-19-print-template-and-fonts]].

## Consequences

- All four remaining print templates (Step 4 cohort: حضور وغياب, درجات مؤقت, نتيجة مقرر; Step 7 thesis letter; Step 8 graduation certificate) should reuse the same `makeMpdf()` instance and the same font stack — see [[../project-context/print-system-guide]] for the reusable pattern.
- Any new Arabic font added in the future must be lab-tested through an isolated route first; the failure modes (`Undefined array key`, `Lookup Type X not supported`) only surface at render time.
- Markazi has no bold variant registered. If a template needs bold digits, **don't** write `font-weight: bold` on a Markazi span — mPDF will synthesise the bold via stroke and the result looks heavy/inked. Either register `B` with a true bold file, or use the natural weight (current student-letter does the latter at 22pt).
- DejaVu substitution silently fills missing glyphs. This is great for `/` and `-`, but means a typo in a font name (e.g. `markaz` instead of `markazi`) will fall through to DejaVu without warning. Always grep for typos when text suddenly looks "wrong but not broken".

## Where the font files live

```
storage/app/print-fonts/
├── amiri-regular.ttf
├── amiri-bold.ttf
├── amiri-slanted.ttf
├── amiri-boldslanted.ttf
├── NotoNaskhArabic-Regular.ttf
├── NotoNaskhArabic-Bold.ttf
├── MarkaziText-VF.ttf
└── test/             ← experimental fonts kept here for future testing (not loaded)
```

The `fontDir` config merges this directory with mPDF's bundled font dir, so DejaVu remains available even though it's not in our folder.
