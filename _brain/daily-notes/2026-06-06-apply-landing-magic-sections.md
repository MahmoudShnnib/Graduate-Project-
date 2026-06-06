---
date: 2026-06-06
tags: [daily, apply, landing, alpine, tailwind, animation, settings, contact]
related:
  - "[[2026-06-04-apply-page-revamp]]"
  - "[[2026-06-06-semester-scoped-applications]]"
---

# 2026-06-06 — "Magic" redesign of the /apply landing sections

User wanted the three landing sections (`#departments`, `#news`, `#contact`) to feel premium/inspiring ("full magic"). All view-layer + a Setting/widget tweak. `/apply` and `/apply/track` both return 200; dashboard tests still 6/6.

## What changed

**`resources/views/registration/create.blade.php`** (Play CDN + Alpine — no build step):
- Added a scoped `<style>` (keyframes: aurora drift, shimmer, float, spin, draw, pulse) + utility classes (`.mg-aurora/.mg-card/.mg-glass/.mg-spot/.mg-glow/.mg-gtext/.mg-float/.mg-ring/.mg-line`) and a `<script>` registering `Alpine.data('magicCard')` (cursor-spotlight via `--mx/--my`, 3D tilt via `rotateX/Y`, `copy()` → toast, all gated by `prefers-reduced-motion`).
- **#departments** → glass cards with cursor spotlight + 3D tilt + animated glow halo, per-card cycled accent colors, gradient-shimmer heading, aurora backdrop.
- **#news** → reframed into an **"application journey" timeline** (قبل/أثناء/بعد) with glowing numbered nodes and a connector line that draws itself on scroll-reveal (`.mg-line` + `.is-visible`).
- **#contact** → split layout: aurora gradient CTA panel (WhatsApp button via `wa.me/{contact_whatsapp}` + track link) beside glass cards; phone/email are **click-to-copy** (reuses the shared `$store.toast`).

**Contact details wired to `Setting`** (user choice):
- `Setting::DEFAULTS` += `contact_phone`, `contact_whatsapp`, `contact_email`, `contact_address`, `contact_hours` (defaults = old placeholders).
- `DashboardHeaderWidget::editSettingsAction()` += a "بيانات التواصل" form section, so these are editable from the dashboard settings modal (form already saves all keys). The view reads them via the existing `$settings = Setting::allKeyed()`.

## Gotcha (cost two 500s)

Blade `@php(expr)` **short form breaks when `expr` contains nested `()`** (e.g. `Setting::allKeyed()`, `count($accents)`) — and **mixing** a short-form `@php(...)` with any later `@php ... @endphp` block makes Blade's raw-block matcher swallow everything from the first `@php` to the first `@endphp`. Fix: use the **block form `@php … @endphp` everywhere** in a file that has any block. (Same nested-paren trap seen earlier in the dashboard widget.)

## Notes / next

- Contact defaults are still the old placeholders (`+218 21 000 0000`, `info@cet.edu.ly`, `218910000000`, طرابلس — ليبيا). Replace via the **dashboard settings → بيانات التواصل** when real values are known. WhatsApp must be the international number **without `+`**.
- Public pages use the Tailwind **Play CDN**, so these classes need no `npm run build`; the admin dashboard classes still do.
