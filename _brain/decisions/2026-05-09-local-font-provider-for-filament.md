---
date: 2026-05-09
tags: [decision, font, filament, css, tailwind, typography]
related:
  - "[[../bugs-and-fixes/2026-05-09-noto-kufi-arabic-font-not-applying]]"
  - "[[../daily-notes/2026-05-09-font-fix]]"
---

# Decision: Use LocalFontProvider for System-Wide Font in Filament

## Context

The project needed to apply a custom Arabic variable font (Noto Kufi Arabic) across all panels. The font TTF was downloaded to `public/fonts/Noto_Kufi_Arabic/`.

First attempt: added `@font-face` + `@theme { --font-sans: ... }` to Tailwind theme CSS files. This had no effect because Filament's font system is independent of Tailwind's `--font-sans` variable.

## Decision

Use Filament's `LocalFontProvider` in both panel providers:

```php
->font('Noto Kufi Arabic', url: asset('css/noto-kufi-arabic.css'), provider: LocalFontProvider::class)
```

A dedicated `public/css/noto-kufi-arabic.css` holds the `@font-face` declaration. The TTF stays at `public/fonts/Noto_Kufi_Arabic/NotoKufiArabic-VariableFont_wght.ttf`.

## Why Not CSS-Only

Filament v5 injects `--font-family: '<name>'` as an **inline style** on `:root` on every page, derived from `filament()->getFontFamily()`. The dist CSS maps `--default-font-family: var(--font-family)`. No amount of Tailwind `@theme` overriding can override an inline style.

The only correct hook is Filament's own `->font()` API on the panel config.

## Why LocalFontProvider Over BunnyFontProvider / GoogleFontProvider

- Font is self-hosted → no external CDN dependency, no GDPR concerns
- Works offline / in LAN environments (the app is a university intranet system)
- Avoids the Bunny Fonts `index.css` that previously loaded Inter

## Consequences

- Filament panels (admin + student) now load `Noto Kufi Arabic` from `public/css/noto-kufi-arabic.css`
- Inter Variable is no longer preloaded or rendered
- Public pages (`/apply`, `/apply/track`) use `app.css` with the Tailwind `@theme` override — they are not affected by this Filament config and continue to work via `--font-sans`
- If the font name or file path changes, update **both** the panel providers and `public/css/noto-kufi-arabic.css`

## Future: Adding Other Fonts

To add a mono or serif font to panels, use:
```php
->monoFont('...', url: asset('css/mono-font.css'), provider: LocalFontProvider::class)
->serifFont('...', url: asset('css/serif-font.css'), provider: LocalFontProvider::class)
```
