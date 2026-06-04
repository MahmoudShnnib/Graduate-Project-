---
date: 2026-06-04
tags: [daily, apply, registration, ui, alpinejs, fonts, kufi, hero, illustration]
related:
  - "[[../project-context/design-conventions]]"
  - "[[2026-06-01-whatsapp-otp-apply-verification]]"
---

# 2026-06-04 — Apply page revamp (Kufi font, Alpine, SVG hero)

Visual + interactivity overhaul of the public application flow. Pure **view-layer** changes (two Blade files, no PHP). Decisions confirmed with the user up-front via a 4-question prompt. `view:cache` compiles clean, page returns 200, OTP suite still 10/10 green, no new backend errors.

## Decisions (user-confirmed)

1. **Hero "main pic"** → hand-crafted **inline SVG illustration** (graduation cap + stacked books) in the blue brand palette, replacing the Unsplash photo. No external image dependency.
2. **Scope** → change the **shared layout** (`x-layout.app`), so both `/apply` and `/apply/track` get the new hero/font consistently.
3. **Kufi font** → **Noto Kufi Arabic** (Google Fonts).
4. **Alpine scope** → conditional fields + UX touches; **left the WhatsApp OTP vanilla JS intact** (it's the tested security flow — not worth regressing).

## What changed

### `resources/views/components/layout/app.blade.php` (shared layout)
- Loads **Noto Kufi Arabic** (preconnect + `css2` link).
- Configures the **Tailwind Play CDN** `tailwind.config` to set `fontFamily.sans`/`kufi` → Noto Kufi Arabic; body font updated from the (never-loaded) `Tajawal/Cairo` to Noto Kufi.
- Adds **Alpine.js** via CDN (`defer`, so the global `applyForm()` helper defined in the body is available at `alpine:init`).
- Adds `[x-cloak]{display:none!important}`.
- **Hero rewritten**: removed the `<img>` Unsplash photo + `.hero-overlay`. Now a `bg-gradient-to-bl from-blue-900 via-blue-800 to-sky-700` section, two-column grid (copy right / illustration left in RTL), soft blurred glow blobs, and an inline **graduation-cap + books SVG** (viewBox 560×460) using brand blues + gold tassel. CTA button restyled to solid white-on-blue.

### `resources/views/registration/create.blade.php` (apply form)
- Form now `x-data="applyForm()"` + `@fill-sample.window="fillSample()"`.
- **Conditional fields → Alpine** (replaces the old vanilla toggle scripts):
  - Nationality "أخرى" → `x-show="isOther"` + `::required="isOther"` (escaped `::` so the Blade component emits Alpine's `:required`, not a PHP binding).
  - Study-type delegation rows → `x-show="showDelegationNumber"` / `showDelegationEntity`.
  - All conditional rows use `x-cloak` + `x-transition` (no flash before Alpine boots).
- **Live UX validation**: phone validity hint (`phoneValid` = `/^09\d{8}$/`, green ✓ / red warning), password length + confirmation-match indicators.
- **Sample-data filler → Alpine**: the button lives in the page header *outside* the form scope, so it's wired through an `Alpine.store('apply', { filling })` + a `$dispatch('fill-sample')` window event the form listens for. `fillSample()` still sets DOM values and dispatches `input`/`change` so Alpine-bound fields stay in sync. Spinner + label driven by the store.
- Initial Alpine state seeded from `old(...)` via `@js(...)` so validation errors repopulate correctly.

## Gotchas / notes for next time

- The public pages use the **Tailwind Play CDN**, *not* the Vite build — so font/theme config goes in an inline `tailwind.config` script, and there's no `npm run build` step for these pages.
- Passing an Alpine `:`-binding through a **Blade component** (`<x-form.input>`) requires the **double colon** `::required` — a single `:` is consumed by Blade as a PHP attribute expression.
- The hero is **shared** — any future hero change hits `/apply/track` too. Track page verified compatible (it's just a form below the hero).
- OTP flow untouched: still gated only when `$whatsappEnabled`; server (`RegistrationController::store`) remains the real verification guard.

## Verification

- `php artisan view:cache` → compiles clean.
- `curl http://pgsms.test/apply` → **200**; rendered HTML confirmed to contain the Kufi font link, Alpine CDN, `applyForm` x-data, the SVG illustration, 8 `x-model` bindings, 9 `x-cloak`, and **no** `unsplash`.
- `php artisan test --filter=WhatsAppOtp` → **10 passed**.

## Follow-up: responsive nav bar + mobile hamburger

`components/layout/app.blade.php` header reworked (shared by `/apply` + `/apply/track`):
- **Problem fixed:** desktop nav was `hidden md:flex` with *no mobile fallback* — links were unreachable on phones; fixed `h-28` + `text-2xl` brand title overflowed on small screens.
- **Now:** header is `sticky top-0 z-40` with `backdrop-blur`; height `h-20` mobile → `md:h-28`; brand title scales `text-base → md:text-2xl` with `truncate`, English subtitle hidden `<sm`, CET badge shrinks.
- **Hamburger**: Alpine `x-data="{ mobileOpen: false }"` on `<header>`, button toggles between menu/close SVG icons, `:aria-expanded`. Mobile dropdown nav with `x-show` + `x-cloak` + `x-transition` (no `x-collapse` — that plugin isn't loaded, only core Alpine). `@click.outside` closes it; each link closes on tap; register CTA repeats in the menu for `<sm` (where the header button is hidden).
- **Breakpoint correction (after first screenshot):** first pass put the inline nav at `lg` — at ~1280px the long brand title **truncated** ("طراب…") and the 6 Arabic links were squeezed enough that "هيئة التدريس" wrapped to two lines. Fix: inline nav now `xl:flex` / hamburger `xl:hidden` (6 long RTL links + a long brand name only fit ≥1280px); links given `whitespace-nowrap`; brand title shrunk to `lg:text-lg` (was `md:text-2xl`) and CET badge to `size-12`; header height `md:h-24` (was `h-28`). Below `xl` the hamburger handles everything.

## Follow-up: real college logo in navbar

- Replaced the placeholder `CET` circle **and** the hand-typed Arabic/English brand text with the official logo lockup (user chose "full logo, replace the text too").
- Asset copied to `public/images/logo.png` (5881×1475, ~252KB), referenced via `asset('images/logo.png')`. Brand block is now a single `<a href="/">` wrapping an `<img>` sized `h-11 md:h-14 w-auto object-contain` with the college name as `alt`.
- Note: the source PNG is very high-res (5881px wide shown at ~255px) → ~252KB on every public page. A navbar-sized copy (~600px) would cut it to ~40KB if bandwidth matters.

## Follow-up: landing sections + scroll-to-section nav

The `/apply` page is now a single-page landing site; nav links smooth-scroll to on-page sections.
- **Layout** (`app.blade.php`): added `html { scroll-behavior: smooth; scroll-padding-top: 7rem }` (offsets the sticky header on anchor jumps). Defined a shared `$navLinks` array (label + `route('registration.create').'#id'`) and looped it in BOTH the desktop nav and mobile menu — single source of truth, absolute URLs so the links also work from `/apply/track`. Hero got `id="home"`; hero CTA + "التسجيل" buttons now target `#apply`.
- **Breakpoint fix:** mobile menu was `lg:hidden` while the hamburger is `xl:hidden` → 1024–1280px showed the button but no menu. Both now `xl:hidden`.
- **Apply page** (`create.blade.php`): five new `<section>`s above the form, each `scroll-mt-28`:
  - `#about` (عن الكلية) — intro using `Setting::get('college_name')` + رؤية/رسالة/أهداف cards.
  - `#faculty` (هيئة التدريس) — leadership cards from real `Setting` (`dean_name`, `director_name`, `office_name`).
  - `#departments` (الأقسام) — grid built from `$specializations` (real data; 3 cards), each links to `#apply`.
  - `#news` (الأخبار) — **placeholder** cards (marked with a Blade comment).
  - `#contact` (اتصل بنا) — **placeholder** phone/email/address cards (marked).
  - Form wrapped in `<div id="apply" class="… scroll-mt-28">`.

> Placeholders to replace with real values: news items, and contact phone/email/address (not in `Setting`). Departments + leadership names are live data.

## Loose ends

- No automated test renders this Blade; visual correctness verified via rendered-HTML assertions only (no headless browser available this session). Worth an eyeball in the browser.
- Pre-existing, unrelated: `ExamListPrintTest` mPDF `Mpdf\Config\ConfigVariables not found`; full suite OOMs at 128MB.
