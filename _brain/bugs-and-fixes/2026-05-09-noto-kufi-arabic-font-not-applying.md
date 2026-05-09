---
date: 2026-05-09
tags: [bug, fix, font, filament, css, tailwind]
related:
  - "[[../decisions/2026-05-09-local-font-provider-for-filament]]"
  - "[[../daily-notes/2026-05-09-font-fix]]"
---

# Bug: Noto Kufi Arabic Font Not Applying

## Symptoms

After adding `@font-face` + `@theme { --font-sans: 'Noto Kufi Arabic', ... }` to all three CSS files (`app.css`, `admin/theme.css`, `student/theme.css`) and running `npm run build`, the font had no visible effect — the UI still rendered in Inter.

## Root Cause

Filament v5 manages fonts through its own PHP-level system, completely separate from Tailwind's `--font-sans` variable.

The base layout (`vendor/filament/filament/resources/views/components/layout/base.blade.php`) injects an **inline `<style>`** block into every page:

```html
<style>
  :root {
    --font-family: 'Inter Variable';   ← set from filament()->getFontFamily()
    ...
  }
</style>
```

Filament's dist CSS then maps `--default-font-family: var(--font-family)` and applies it to `body`. This entire chain uses `--font-family`, not Tailwind's `--font-sans`.

Overriding `--font-sans` in `@theme` only affects **Tailwind utility classes** (e.g. `.font-sans`). It does not touch Filament's `--font-family` variable, which is always set by `->font()` on the panel config (defaulting to `'Inter Variable'` from a locally bundled woff2).

> [!note] Additional detail
> `filament()->renderStyles()` also loads `/fonts/filament/filament/inter/index.css` which preloads the Inter woff2. This was confirmed via tinker before the fix.

## Fix

Used Filament's official `LocalFontProvider` API in both panel providers:

```php
use Filament\FontProviders\LocalFontProvider;

->font('Noto Kufi Arabic', url: asset('css/noto-kufi-arabic.css'), provider: LocalFontProvider::class)
```

Created `public/css/noto-kufi-arabic.css`:
```css
@font-face {
    font-family: 'Noto Kufi Arabic';
    src: url('/fonts/Noto_Kufi_Arabic/NotoKufiArabic-VariableFont_wght.ttf') format('truetype');
    font-weight: 100 900;
    font-style: normal;
    font-display: swap;
}
```

This causes Filament to:
1. Set `--font-family: 'Noto Kufi Arabic'` in the inline `:root` style
2. Load the local font CSS via a `<link>` tag (no CDN dependency)
3. Stop preloading Inter

Confirmed via tinker: `$panel->getFontFamily()` → `'Noto Kufi Arabic'`.

## Files Changed

| File | Change |
|---|---|
| `app/Providers/Filament/AdminPanelProvider.php` | Added `->font()` with `LocalFontProvider` |
| `app/Providers/Filament/StudentPanelProvider.php` | Added `->font()` with `LocalFontProvider` |
| `public/css/noto-kufi-arabic.css` | Created — `@font-face` declaration |

## Regression Guard

The `@font-face` and `--font-sans` override in the Tailwind theme CSS files (`resources/css/filament/*/theme.css`) are still present and provide coverage for Tailwind utility classes. The `LocalFontProvider` handles the Filament-layer font.

No cache clear is needed at runtime — the inline style is rendered per-request by PHP.
