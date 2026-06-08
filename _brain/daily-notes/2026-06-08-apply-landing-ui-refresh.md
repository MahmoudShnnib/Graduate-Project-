---
date: 2026-06-08
tags: [daily, ui, apply, landing, branding, filament-public]
related:
  - "[[2026-06-08-apply-500-docker-fix]]"
---

# 2026-06-08 — `/apply` landing UI refresh

All changes are on the **public** `/apply` page (`registration/create.blade.php`) and the
shared public layout (`components/layout/app.blade.php`). The public site uses the **Tailwind
CDN** (browser JIT) + an inline `tailwind.config`, so no `npm` build is needed — edits show on refresh.

## What was done

1. **Mobile "رحلة التقديم" timeline** — added a mobile-only vertical timeline (`md:hidden`),
   kept the desktop 3-col grid (`hidden md:grid`). A single JS-positioned line connects node 1→3,
   and an `IntersectionObserver` reveals nodes 1→2→3 on scroll while a gradient fill grows with
   scroll. Page-scoped `<script>` + `.tl-node` CSS; respects `prefers-reduced-motion`.
2. **Logo** — replaced `public/images/logo.png` (downscaled to 512px → ~93 KB) and added a
   `?v=filemtime` cache-buster in the header. Sized `h-14` mobile / `md:h-24` desktop.
3. **Hide-on-scroll navbar** — header slides up on scroll-down, returns on scroll-up
   (`$store.ui.hideNav`, tracked in the shared scroll handler). Won't hide while the mobile menu
   is open. The reading-progress bar was moved **out** of the header to a `fixed top-0 z-50` element
   so it stays visible when the nav hides.
4. **Brand palette (Azure + Teal)** — added `azure`/`teal` scales to the CDN `tailwind.config`
   and remapped `blue` + `sky` → azure, `teal` → teal, so existing utility classes rebrand
   site-wide without per-element edits. Anchors: azure `#1774B6/#3A93D6/#125E93`,
   teal `#16B0A0/#2BC7B6/#0E7D72`.
5. **Removed all purple** — swapped indigo/violet/fuchsia gradients (timeline steps + line,
   specialization accents) for azure→teal. Recolored then **flattened** the `.mg-gtext` title
   gradient to solid black (`#0f172a`) per request.
6. **Consistent section titles** — unified all eyebrow badges (ringed style) and brought the
   form header in line with the Departments/News headers.
7. **Sticky progress card** — `sticky top-28` → `top-4` (the gap was sized for the old
   always-visible header; the header now auto-hides).
8. **Specialization icons** — per-department white PNG glyphs on colored tiles, mapped by
   `code` (with Arabic-name fallback, graduation-cap SVG fallback for unmapped):
   1=الإتصالات→`comm.png` (antenna), 2=الحاسب الآلي→`computer.png` (monitor),
   3=التحكم الآلي→`control.png` (robot). Removed the `#1/#2/#3` code badges.

## Deploy

- Shipped **UI only** on `feature/apply-ui-refresh` → fast-forwarded into `main` → pushed.
- The nginx `/apply` 500 fix (`c7c8e1b`) was already on `origin/main`; left untouched.
- **Operator:** trigger a Dokploy redeploy of `main` to pick up the new blade + images.

## Note

- `graduate-project.test` (in CLAUDE.md) is stale — the Herd site actually serves at **`pgsms.test`**.
- Filament admin/portal panels use Vite-built theme CSS and were **not** rebranded (separate change + build).
</content>
</invoke>
