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

## Follow-up: landing-section revisions (user feedback)

- **عن الكلية redesigned** (user disliked the 3 generic رؤية/رسالة/أهداف cards + their text). Now a 2-column split: applicant-focused intro + a 4-item benefit **checklist** (معتمد / تقديم إلكتروني / متابعة فورية / إشعارات بريد+واتساب) + "ابدأ التقديم الآن" CTA on the right; a blue-gradient visual card (academic-cap icon + "برامج الإجازة العليا") on the left.
- **هيئة التدريس section removed** entirely, and its nav link removed from `$navLinks`. (`Setting` dean/director values no longer used in the view.)
- **الرئيسية removed from the navbar** (hero keeps `id="home"`; just no nav link).
- **الأخبار rewritten** from vague placeholders into 3 genuinely useful applicant cards: كيفية التقديم (→ #apply), المستندات المطلوبة (real format/size rules), متابعة الطلب (→ track route). No more fake news.
- **Nav breakpoint lowered `xl` → `lg`** now that there are only 4 short links — desktop users at 1024–1280px get the inline nav instead of the hamburger. Hamburger + mobile menu moved to `lg:hidden` to match (avoids the both-visible overlap).

## Follow-up: heavy Alpine.js pass (user requested "a lot of alpine")

Added four interaction sets — **all on core Alpine, no plugins** (avoids Intersect/Collapse CDN deps + their invisibility/focusability risks).

**Shared `Alpine.store('ui')`** (in `app.blade.php`, registered + `init()`-ed on `alpine:init`): one scroll listener + two IntersectionObservers drive everything:
1. **Scroll-spy** — `[data-spy]` sections (home/about/departments/news/contact/apply); active section sets `store.ui.active`; desktop nav links highlight + animate an underline (`scale-x`).
2. **Reading-progress bar** — gradient bar in the header width-bound to `store.ui.progress`.
3. **Sticky-header shadow** — `:class` grows shadow once `store.ui.scrolled`.
4. **Back-to-top** — fixed button, `x-show="store.ui.showTop"` (>600px), smooth `scrollTo`.
5. **Scroll-reveal** — `.reveal` sections fade/slide up via an IO that adds `.is-visible`. Safety: a sync `reveal-enabled` class gates the hidden state (no-JS shows content), IO-missing fallback reveals all, plus a 2.5s timeout. **Deliberately NOT on the `#apply` form** — the form must never depend on JS to be visible.

**Live department search** (`#departments` x-data): search box filters specialization cards via `match(name)` (`x-show` per card) + a "no results" state with clear button. List sourced from `@js($specializations->pluck('name'))`.

**Form UX** (extended `applyForm()`): `recalc()` (on `x-init` + `@input`/`@change`) scans `[required]` to drive a **sticky completion progress bar** (`top-28`), and per-section **"مكتمل" badges** via `[data-section]` (personal, contact — the sections that have required fields). Live counters: national ID `x/12`, phone `x/10`. OTP flow untouched.

> Gotchas confirmed: `@js(array)` renders as `JSON.parse('…')`, `@js(string)` as a single-quoted literal — both safe inside the double-quoted `x-data`/`x-show` attributes. Reveal transform is cleared (`transform:none`) once visible so it doesn't break the sticky progress bar's positioning.

## Follow-up: 413 on submit + 5 MB client-side file guard

**Root cause of `413 Request Entity Too Large` (nginx):** Herd capped the request body at **2M** in three places while the form allows 5 MB × 2 files. Raised in Herd config (local env, NOT in git) then `herd restart`:
- `~/Library/Application Support/Herd/config/nginx/herd.conf` → `client_max_body_size` 2M → **15M** (this file is Herd-generated and mirrors PHP `post_max_size`).
- `~/Library/Application Support/Herd/config/php/85/php.ini` → `upload_max_filesize` 2M → **6M**, `post_max_size` 2M → **15M** (memory_limit left at 128M).
- Sizing rationale: 2 files × 5 MB ≈ 10 MB + fields → 15 MB body ceiling; `upload_max_filesize` 6M gives 1 MB headroom above the 5 MB rule so a legit 5 MB file isn't *silently dropped* by PHP before Laravel's clean `max:5120` message.

**Client-side 5 MB guard:** `applyForm().checkFile($event)` on both file inputs — if a selected file > 5 MB it `alert()`s an Arabic message (with the actual size) and clears the input, so a huge file is never uploaded. Server `max:5120` per file + the tightened ceiling remain the real guards.

> ⚠️ **Production deploy:** the same nginx `client_max_body_size` + PHP `post_max_size`/`upload_max_filesize` must be raised on the prod server, or applicants hit the identical 413. (Server config is environment-specific — not carried by the repo.)

## Follow-up: removed ApplicationStatusChangedNotification entirely (user request)

User didn't want the "application status changed" message **at all** (confirmed twice, including the consequence). Removed completely — both email + WhatsApp:
- Deleted `app/Notifications/ApplicationStatusChangedNotification.php`.
- Removed `NotificationService::notifyApplicationStatusChanged()` + its import.
- Removed the 3 dispatch sites: `ExamListPage::markFailed()` (exam-fail → rejected), `ApplicationResource` bulk + single `initialApprove` actions (Pending → InitiallyApproved). Cleaned up the now-unused `$notifier`/`NotificationService` (dropped the import from `ApplicationResource`; kept it in `ExamListPage` — still used by `markPassed`).
- Removed `status_changed` from the `whatsapp:test` command (import, array entry, `--type` help).

> ⚠️ **Behavioural consequence (accepted by user):** applicants are **no longer notified of their exam date** on initial approval, and **rejected applicants get no notice**. The status still updates + audit logs still fire — only the notification is gone. The dedicated notifications (received / exam-passed / welcome) are untouched. WhatsApp message types are now **8** (was 9).

Verified: `pint` clean; related suite 9 passed (the 1 failure is the pre-existing `ExamListPrintTest` mPDF autoload issue, unrelated).

## Follow-up: renamed `secretary` role label → "مساعد إداري"

Display label only — the role **key** (`secretary` in `users.role` + Spatie) is unchanged, so auth/permissions are unaffected. Labels were duplicated (no central enum), so updated all 4:
- `Users/Tables/UsersTable.php` (badge), `Users/Schemas/UserForm.php` (role select), `Users/Schemas/UserInfolist.php` (view) — were "السكرتير"/"سكرتير".
- `AuditLogs/AuditLogResource.php::ROLE_LABELS` — was a different term "أمين السجل"; unified to "مساعد إداري".
- Left `AdminUserSeeder.php` user *name* `'السكرتير'` as-is (that's the account name, not the role; only affects fresh seeds — offered to update).

Pint clean.

## Follow-up: renamed role KEY `secretary` → `manager_assistant`

Not just the label — the actual role identifier (string `users.role` + Spatie role name). The app authorizes mainly via the `users.role` string (`in_array($this->role, [...])`) plus Spatie for Shield perms.

**Code/seeders/test** (all quoted `'secretary'` → `'manager_assistant'`; email `secretary@college.edu` + password `secretary123` untouched):
- `User.php` (canAccessPanel `in_array`), `Users/Pages/{Create,Edit}User.php`, `SettingsWidget.php`, `PrintController.php` (×2), `PrintCourseStudentsController.php`, `Users/{Tables/UsersTable,Schemas/UserForm,Schemas/UserInfolist}.php`, `AuditLogResource::ROLE_LABELS`.
- `RolesAndPermissionsSeeder.php` (role name + `$knownRoles`), `AdminUserSeeder.php` (`role` + `assignRole`).
- `tests/Feature/RbacSecretaryRestrictionsTest.php` (file name + descriptions left as-is — cosmetic). 4 passed.

**Data migration** `2026_06_05_184719_rename_secretary_role_to_manager_assistant`:
- `users.role` `secretary` → `manager_assistant`.
- Spatie `roles.name` `secretary` **and** `مساعد اداري` → `manager_assistant`. (DISCOVERY: the live DB's Spatie role had been **manually relabelled** to Arabic `مساعد اداري` — there was no `secretary` Spatie role; the `users.role` column still said `secretary`.) Rename preserves `roles.id`, so the user's assignment + the role's **53 permissions** stayed intact.
- `down()` reverts to `secretary`. Calls `forgetCachedPermissions()`.

Verified: migration ran; `users.role` + Spatie role both `manager_assistant`; secretary@college.edu still holds the role with 53 perms; RBAC test 4/4; admin login 200. Ran `permission:cache-reset` + `config:clear` (avoided `optimize:clear` — Vite-manifest gotcha). Label across the 4 UI maps already reads "مساعد إداري" from the earlier change.

## Loose ends

- No automated test renders this Blade; visual correctness verified via rendered-HTML assertions only (no headless browser available this session). Worth an eyeball in the browser.
- Pre-existing, unrelated: `ExamListPrintTest` mPDF `Mpdf\Config\ConfigVariables not found`; full suite OOMs at 128MB.
