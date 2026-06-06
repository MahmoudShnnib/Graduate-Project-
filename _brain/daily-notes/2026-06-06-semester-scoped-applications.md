---
date: 2026-06-06
tags: [daily, semester, applications, filament, exam-list, scoping, registration]
related:
  - "[[../project-context/architecture]]"
  - "[[2026-06-04-apply-page-revamp]]"
---

# 2026-06-06 — Semester-scoped applications (fresh term = empty tables)

User wanted: opening a new semester should make the admin **Applications** surface look empty (like grades/subjects already do), with the previous term's data archived but still browsable. Aligned via a 3-question prompt before coding.

## Key discovery — the real gap

- **Grades, course offerings, enrollments, surveys** are already per-semester and start empty in a fresh term. ✅ (no change needed — user confirmed.)
- **Applications** *had* a `semester_id` column (migration `2026_05_10_221406`) **but**:
  1. The public `/apply` flow (`CreateApplication`) **never set it** → every new submission saved `semester_id = NULL`.
  2. `ApplicationResource` table + `ExamListPage` showed **all** applications regardless of semester.
- Live data at start: 11 apps on archived `خريف 2025` (id 1), 0 on active `خريف 2026` (id 2), 1 orphan NULL.

## What changed

1. **`app/Actions/CreateApplication.php`** — stamps `'semester_id' => Semester::where('is_active', true)->value('id')` on every new application. **Root fix** — without it, scoping would hide all new applicants.
2. **`app/Filament/Resources/Applications/ApplicationResource.php`**
   - Added `SelectFilter::make('semester_id')` (`->relationship('semester','name')`) **defaulting to the active semester** → table looks empty in a fresh term; switch/clear the dropdown to view any past term.
   - Navigation "pending" badge now scoped to the active semester too.
3. **`app/Filament/Pages/ExamListPage.php`** + `resources/views/filament/pages/exam-list.blade.php`
   - New `public ?int $selectedSemester` (defaulted in `mount()` to active), `getSemesters()`, query scoped via `->when($this->selectedSemester, …)`.
   - Blade: a `wire:model.live="selectedSemester"` `<select>` in the toolbar; specialization tab counts scoped to the selected semester.
4. **Migration `2026_06_06_111507_backfill_null_semester_id_on_applications`** — assigns the 1 orphan NULL app to the earliest semester (id 1) so it isn't stranded outside every filter. Ran clean → all 12 apps now on semester 1.

## Verification

- New test `tests/Feature/ApplicationSemesterScopingTest.php` (3 passing): new app gets active semester; admin table defaults to active-semester rows only; `filterTable('semester_id', …)` reveals a past term.
- `php artisan test --filter='ExamList|Registration|Application|Semester|WhatsAppOtp'` → **52 passed, 1 failed**. The 1 failure is the **pre-existing** `ExamListPrintTest` mPDF `Class "Mpdf\Config\ConfigVariables" not found` (documented in [[2026-05-30-print-and-table-bug-fixes]] / OTP note — unrelated).
- Pint clean; `view:cache` compiles.

## Notes / loose ends

- **Behaviour now:** a brand-new semester → Applications table + Exam List both empty until `/apply` submissions arrive for that term; old terms reachable via the semester dropdown/filter. Matches grades/subjects.
- `ApproveApplication` already used the active semester for enrollment numbers — untouched.
- Earlier in the day: mobile hero on `/apply` reworked to a faded (`opacity-20`) illustration backdrop with text-shadow overlay (in `components/layout/app.blade.php`).

## Follow-up: SPA mode on both panels (kill the page-load flash)

User reported a full-page-reload "flash" when clicking sidebar resources. Enabled Filament SPA mode (Livewire `wire:navigate`) on **both** panels:
- `AdminPanelProvider` + `StudentPanelProvider` → `->spa(hasPrefetching: true)`.
- `hasPrefetching` adds `wire:navigate.hover` → pages preload on hover, click feels instant.
- ⚠️ Trade-off: prefetch runs a page's query on *hover* (not just click), so heavy list resources get queried more. If DB load shows up, drop to plain `->spa()` or use `->spaUrlExceptions([...])` to exclude heavy URLs.
- Requires one hard refresh of each panel for the new config to boot. Both panels return 302→login as expected; Pint clean.

## Follow-up: custom Blade dashboard (replaced the Filament chart widgets)

User wanted to replace the generic Chart.js dashboard widgets. Chose **idea 3 (needs-attention hub) + idea 2 (admissions funnel) on a custom Blade look (idea 1)**.

**New:** `app/Filament/Widgets/DashboardOverviewWidget.php` (plain `Widget`, `columnSpan = 'full'`, `canView` = non-faculty) + `resources/views/filament/widgets/dashboard-overview.blade.php`. One full-width widget renders three sections in custom Tailwind/Noto-Kufi:
- **4 KPI cards** — active students (+sub-counts), طلبات هذا الفصل, نسبة القبول %, الفصل الحالي + add/drop window (folds the old `StatsOverviewWidget`).
- **Admissions funnel** (active semester) — الطلبات المقدمة → قبول مبدئي → قبول نهائي, gradient bars (inline `width:%`), conversion % per stage, مرفوض badge. Monotonic by construction (stage2 = InitiallyApproved+Accepted, stage3 = Accepted).
- **Needs-attention hub** — 6 work-queue rows (pending apps · grades · add/drop · discussion · thesis · suspension) with live counts and `wire:navigate` deep-links via `Resource::getUrl('index')`; zero-count rows dim with a ✓.

**Wiring/cleanup:**
- `Dashboard.php` `getWidgets()` now returns `[SettingsWidget, AccountWidget, DashboardOverviewWidget, FacultyStatsOverviewWidget]` (visibility filters handle faculty vs admin).
- **Fixed a latent dup:** `AdminPanelProvider` imported the *vendor* `Filament\Pages\Dashboard` in `->pages([])` while the custom `App\Filament\Pages\Dashboard` was also discovered — so the vendor one was serving and `getWidgets()` was ignored (all discovered widgets rendered). Switched the import to the custom Dashboard; panel now lists a single Dashboard.
- **Deleted** `StatsOverviewWidget`, `ApplicationStatusWidget`, `ApplicationsOverTimeWidget`, `StudentsPerSpecializationWidget` (only referenced from Dashboard, no tests).

**Gotchas:**
- Admin theme `@source` didn't scan `views/filament/widgets/**` → added it to `resources/css/filament/admin/theme.css`, then `npm run build` (else custom classes don't compile). Kept all Tailwind class literals in the **blade** (tone→classes via `match`), funnel widths via inline `style` — so nothing relies on dynamic class strings in PHP (which aren't scanned).
- `@php($x = $this->method())` short-form breaks on the nested `()` → used the block `@php … @endph` form.

**Tests:** `tests/Feature/DashboardOverviewWidgetTest.php` (4 passing) — widget renders funnel + hub; full `/admin` page 200s with all widgets; hidden from faculty; funnel counts monotonic. Pint clean. (Pre-existing `ExamListPrintTest` mPDF failure still unrelated.)

> ⚠️ Deploy: `npm run build` output must ship; the new widget classes only exist after a build.
> Note: funnel/hub read **0** right now because active خريف 2026 has no apps/pending items yet — by design (active-semester scoped); it populates as activity arrives.

### Redesigned the top of the dashboard (welcome + settings banner)

User disliked the default Filament `AccountWidget` (مرحباً/welcome card) and `SettingsWidget` (إعدادات النظام button) look — asked to **redesign**, not remove.
- **Replaced both** with one full-width gradient banner: `app/Filament/Widgets/DashboardHeaderWidget.php` + `resources/views/filament/widgets/dashboard-header.blade.php`. Brand-blue gradient matching the `/apply` hero, with greeting (مرحباً + name), role badge, active-semester chip, and right-side actions: **settings** (role-gated to super_admin/manager_assistant, opens the same modal — the action+schema moved over verbatim from SettingsWidget) and **logout** (`route('filament.admin.auth.logout')` form post).
- `canView()` now returns for everyone (welcome shows to all roles incl. faculty); the **settings button** is gated inside the blade via `canEditSettings`.
- **Deleted** `SettingsWidget.php` + `settings-widget.blade.php`; removed `AccountWidget` from `Dashboard::getWidgets()`. Dashboard widgets are now: `DashboardHeaderWidget`, `DashboardOverviewWidget`, `FacultyStatsOverviewWidget`.
- Seeded 5 sample apps (2 pending / 1 initial / 1 accepted / 1 rejected) into خريف 2026 (`notes = "بيانات تجريبية للوحة التحكم"`) so the funnel/hub demo as **5 → 2 → 1**, مرفوض 1, pending 2. Deletable by that note.
- Tests extended to 6 (header renders greeting + settings + logout for admin; settings button hidden for faculty). Pint clean; `npm run build` ran.

## Follow-up: reordered the whole admin sidebar (lifecycle-based)

Sidebar was "messy" — no explicit group order (Filament rendered groups in discovery order), workflow items split between top-level and groups, conflicting sort numbers. Reorganized around the **student lifecycle**.

**Group order pinned** in `AdminPanelProvider` via `->navigationGroups([...])`:
1. **القبول والتسجيل** — التسجيل والقبول, قائمة الامتحانات
2. **شؤون الطلاب** — الطلاب, الدرجات, طلبات الإضافة/الحذف, طلبات إيقاف قيد, فرص استثنائية
3. **المناقشة والتخرج** — تسجيلات الرسائل, طلبات المناقشة, الخريجون
4. **الإعدادات الأكاديمية** — التخصصات, المقررات, الفصول الدراسية, عروض المقررات, نتائج استطلاع المقررات
5. **إدارة الوصول والنظام** — المستخدمون, الأدوار, التقارير والنماذج, سجل التدقيق
6. **هيئة التدريس** (faculty-only) — موادي

`لوحة التحكم` stays top-level (ungrouped). Set explicit `$navigationGroup` + sequential `$navigationSort` (1,2,3…) on ~16 resources/pages. **Old groups removed:** `إدارة الطلاب`, `طلبات المناقشة`, `النظام`. **Moved into groups:** Applications + Exam List (were top-level), Specializations (was top-level), Users (was top-level); Graduates moved from discussion→graduation group.

**Gotcha:** `RoleResource` extends Shield's `RoleResource`, which **overrides the navigation getter methods** (reading Shield config) — so static `$navigationGroup`/`$navigationSort` are ignored. Had to override `getNavigationGroup()` / `getNavigationSort()` as methods to move الأدوار into the access/system group.

Verified by dumping the resolved group→sort→label tree (matches the target exactly); 49 related tests pass; Pint clean.

### Follow-up: parent-group alert dot for collapsed groups

When a nav group is collapsed you can't see a child's pending badge (e.g. التسجيل والقبول "2"). Added an **amber pulsing dot on the parent group label** when any child has a badge.
- Filament v5 has **no native group badge**. Implemented DRY via a render hook (`PanelsRenderHook::SIDEBAR_NAV_END` in `AdminPanelProvider`) → `resources/views/filament/nav-alerts.blade.php`: CSS dot + a tiny idempotent script that toggles `.fi-nav-alert` on any `.fi-sidebar-group` containing a `.fi-sidebar-item-badge-ctn`. **Mirrors existing child badges** (no duplicated counts), re-runs on `livewire:navigated` (SPA-safe).
- Color `#f59e0b` (matches the yellow pending badges); swap to `#22c55e` for green. Verified present in the authenticated /admin render (200).
