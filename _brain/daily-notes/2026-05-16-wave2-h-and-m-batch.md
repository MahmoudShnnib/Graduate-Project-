---
date: 2026-05-16
tags: [daily, ux-audit-fix, wave-2, high-priority, medium-priority, a11y, i18n, rbac]
related:
  - "[[2026-05-16-session-handoff]]"
  - "[[2026-05-16-c1-axe-critical-fixes]]"
  - "[[2026-05-16-c3-rbac-secretary-lockdown]]"
  - "[[2026-05-16-wave1-audit-cleanup]]"
  - "[[../research/audit-2026-05-15/audit|audit report]]"
  - "[[../research/audit-2026-05-15/fix-plan]]"
  - "[[../decisions/2026-05-15-rbac-posture]]"
  - "[[../decisions/2026-05-15-arabic-first-leakage]]"
---

# 2026-05-16 — Wave 2 batch: High + Medium audit findings cleared

## What was done

Closed the remaining approved queue from the session handoff (10 findings + 1 verification): **H-4, H-7, H-9 (verify), M-4, M-6, M-8, M-9, M-11, M-13, M-15 (with scope extension), M-17**. C-2 was deferred per user direction. M-1, M-2, M-3, H-5, H-8, M-10, M-14, M-16, dark-mode contrast all remain skipped per earlier user direction.

## Per-finding outcomes

### High

| Finding | Outcome | Files touched |
|---|---|---|
| **H-9** | **Auto-resolved by C-3.** Code review confirmed `RolesAndPermissionsSeeder::syncRolePermissions()` strips `:Role/:User/:AuditLog` from secretary's permission set. Filament's `canAccess → policy → can('ViewAny:X')` chain hides the nav items automatically. `RbacSecretaryRestrictionsTest` proves it. No additional work. | None |
| **H-4** | **Fixed.** Added `protected static ?string $title = 'التقارير والنماذج'` and `$title = 'نتائج استطلاع المقررات'` to the two custom Livewire pages. Filament's `getTitle()` doesn't fall back to `$navigationLabel`, so explicit `$title` is required for the `<h1>` + `<title>` tag. Matches the existing `ExamListPage` pattern. | `ReportsPage.php`, `CoursesSurveyResults.php` |
| **H-7** | **Fixed (panel-wide).** Published Filament v5 schemas `components/form.blade.php` to `resources/views/vendor/filament-schemas/components/form.blade.php` and added `novalidate` to the `<form>` tag. Now every Filament form across both admin + student panels emits `novalidate`, so the browser-native English HTML5 tooltip is suppressed and server-side Arabic validation renders inline. Same vendor-publish pattern as C-1 with a top-of-file marker comment. | `vendor/filament-schemas/components/form.blade.php` (new override) |

### Medium

| Finding | Outcome | Files touched |
|---|---|---|
| **M-4** | **Fixed via subclass + Pages override + lang file (two iterations).** First pass created `App\Filament\Resources\Roles\RoleResource` extending the vendor + a `lang/ar/roles.php` file. **Discovery wasn't enough** — browser still showed Shield's English columns + guard column. Two root causes uncovered: (a) `discoverResources()` is lazy, so at plugin-register time `$panel->getResources()` returned an empty list and Shield's `isResourcePublished()` check failed → Shield registered its own resource. (b) Even after that was fixed, the inherited `getPages()` returned Shield's Page classes, whose `static $resource = ShieldRoleResource::class` made Filament render Shield's table for URL `/admin/shield/roles`, ignoring my override entirely. **Final fix**: (1) explicit `->resources([App\…\RoleResource::class])` in `AdminPanelProvider` BEFORE the `->plugins()` call so Shield's escape-hatch check fires correctly; (2) created 4 local Page subclasses (`ListRoles`, `CreateRole`, `EditRole`, `ViewRole`) that extend Shield's pages but rebind `static $resource` to ours; (3) overrode `getPages()` in my RoleResource to use them. Verified via `php artisan route:list -v`: the route now resolves to `App\Filament\Resources\Roles\Pages\ListRoles`. Only super_admin sees this page (C-3 lockdown), so impact is limited but the leak is closed. | `app/Filament/Resources/Roles/RoleResource.php` (new), `app/Filament/Resources/Roles/Pages/{ListRoles,CreateRole,EditRole,ViewRole}.php` (new), `lang/ar/roles.php` (new), `AdminPanelProvider.php` (resources call) |
| **M-6** | **Fixed (3 of 4 resources).** Added `getTabs()` with All / قيد المراجعة / مقبول / مرفوض tabs + counts to `ListSuspensionRequests`, `ListThesisRegistrations`, `ListDiscussionRequests`. Discussion request "approved" tab folds 5 positive states (Scheduled, Discussed, AcceptedWithEdits, DiscussionApproved, Completed); thesis "pending" folds Pending+Printed. **Graduates list skipped** — it's already a filtered view of `DiscussionRequest where status=Completed`, so a status-tab pattern doesn't add value there. Could revisit with a year/semester tab pattern if requested. | 3 `List…Resource.php` files |
| **M-8** | **Fixed (minimal).** No `MaritalStatus` enum exists in the project — labels are inlined match expressions at 4 sites (2 forms, 2 infolists). Form Select options keep the combined form (`أعزب / عزباء`) since gender isn't known at selection time. The **infolist** sites (StudentResource + ApplicationInfolist) now resolve by gender: `أعزب`/`عزباء`, `متزوج`/`متزوجة`, `مطلق`/`مطلقة`, `أرمل`/`أرملة`. | `StudentResource.php` (infolist), `ApplicationInfolist.php` |
| **M-9** | **Fixed (highest-impact callsites).** Parameterized the modal heading/description on the audit's specific gripes: `initialApproveAction` and `directApproveAction` on Applications now include the applicant's name in both heading + description; `DeleteAction` on Applications shows `هل أنت متأكد من حذف طلب التقديم الخاص بـ {name}؟`; suspension approve action on both the resource table and the view page names the student. Long tail of other modals (thesis approve, discussion approve, etc.) left as-is — they already render on view pages where the student name is in the hero. | `ApplicationResource.php`, `SuspensionRequestResource.php`, `ViewSuspensionRequest.php` |
| **M-11** | **Fixed.** Both publish-grades actions now `->disabled()` when their respective `$readyMidterm` / `$readyFinal` count is 0, with an Arabic tooltip explaining why (`لا توجد درجات أعمال فصل جاهزة للنشر`). Reuses the existing per-record counts the action header already computes. | `ViewCourseGrades.php` |
| **M-13** | **Fixed (the two confirmed instances).** Added explicit `->emptyStateHeading('لا يوجد طلاب مسجلون في هذه المادة')` + Arabic description to `EnrollStudents` (admin enrollment manager) and `ViewMyCourse` (faculty per-course grades). Chose per-table heading override rather than resource-level `$modelLabel` to avoid side-effects on breadcrumbs / toasts. | `EnrollStudents.php`, `ViewMyCourse.php` |
| **M-15** | **Fixed + scope extension applied.** Two-part change: **(1) A11y** — the portal suspension page now has a proper `<label for="suspension-reason">` linked to the textarea (closes the axe Critical / Serious violations on `/portal/suspension-request-page`). **(2) Scope** — removed the semester picker entirely per user direction; the form always targets `Semester::current()`. Renamed `isSelectedSemesterBlocked()` → `isCurrentSemesterBlocked()`, dropped `getBlockedSemesterIds()` (no longer needed since there's only one candidate semester), updated `submit()` to use the active semester directly. New "no active semester" empty-state banner. Updated `SuspensionRequestResubmitGuardTest` (4 tests) to the new API. | `SuspensionRequestPage.php`, `suspension-request.blade.php`, `tests/Feature/SuspensionRequestResubmitGuardTest.php` |
| **M-17** | **Fixed (faculty my-courses).** Set `$recordTitleAttribute = 'course.name'` on `MyCourseResource` for row-level accessibility. Added `extraAttributes(['aria-label' => ...])` on the `is_open` `IconColumn` so each row's status indicator announces "مفتوح للتسجيل" / "مغلق للتسجيل" instead of being a bare icon-only link. | `MyCourseResource.php` |

## On the test memory budget

Default 128M wasn't enough this run — needed 512M. Last session showed 162/162 passing at the default 128M after a clean `optimize:clear`, so this is consistent with the "stale Filament cache during long edit session" pattern documented in [[2026-05-16-wave1-audit-cleanup#Memory "creep" — investigated and dismissed]]. Not investigating again — the suite passes cleanly at 512M and the diagnostic from the prior wave proved the actual baseline is well under 128M.

## Test results

- `vendor/bin/pint --dirty --format agent` → **pass**
- `php artisan optimize:clear` → clean
- `php -d memory_limit=512M artisan test --compact` → **162 passed (341 assertions)** in 21.74s. No regressions.

## Files touched

```
Modified:
  app/Filament/Pages/CoursesSurveyResults.php
  app/Filament/Pages/ReportsPage.php
  app/Filament/Resources/Applications/ApplicationResource.php
  app/Filament/Resources/Applications/Schemas/ApplicationInfolist.php
  app/Filament/Resources/CourseOfferings/Pages/EnrollStudents.php
  app/Filament/Resources/DiscussionRequests/Pages/ListDiscussionRequests.php
  app/Filament/Resources/Faculty/MyCourseResource.php
  app/Filament/Resources/Faculty/Pages/ViewMyCourse.php
  app/Filament/Resources/Grades/Pages/ViewCourseGrades.php
  app/Filament/Resources/Students/StudentResource.php
  app/Filament/Resources/SuspensionRequests/Pages/ListSuspensionRequests.php
  app/Filament/Resources/SuspensionRequests/Pages/ViewSuspensionRequest.php
  app/Filament/Resources/SuspensionRequests/SuspensionRequestResource.php
  app/Filament/Resources/ThesisRegistrations/Pages/ListThesisRegistrations.php
  app/Filament/Student/Pages/SuspensionRequestPage.php
  app/Providers/Filament/AdminPanelProvider.php
  resources/views/filament/student/pages/suspension-request.blade.php
  tests/Feature/SuspensionRequestResubmitGuardTest.php

New:
  app/Filament/Resources/Roles/RoleResource.php
  app/Filament/Resources/Roles/Pages/ListRoles.php
  app/Filament/Resources/Roles/Pages/CreateRole.php
  app/Filament/Resources/Roles/Pages/EditRole.php
  app/Filament/Resources/Roles/Pages/ViewRole.php
  lang/ar/roles.php
  resources/views/vendor/filament-schemas/components/form.blade.php
```

## Design conventions honored

- **Arabic-first.** All new copy is Arabic. Tab labels use the standard status palette from `design-conventions.md §3` (`قيد المراجعة` / `مقبول` / `مرفوض` with their canonical colors).
- **State banners.** The new "no active semester" empty state on the suspension page uses the same `flex items-start gap-3 p-4 rounded-xl …` pattern from §4 (red-card style).
- **`active*` scope rule.** M-11's disable check uses the already-computed `$readyMidterm` / `$readyFinal` rather than firing a fresh query, preserving the resource's single-query header.
- **Vendor override marker.** `resources/views/vendor/filament-schemas/components/form.blade.php` has a top-of-file comment noting why the file was forked, mirroring the C-1 overrides.
- **Subclass over fork.** M-4 used Shield's plugin escape hatch (`isResourcePublished` check) to swap the resource by registering an extension class — keeps us out of the vendor tree.

## Audit progress snapshot

After this batch the audit's queue stands at:

### Closed (this session — Wave 2)
H-4, H-7, H-9 (verify), M-4, M-6, M-8, M-9, M-11, M-13, M-15, M-17.

### Closed earlier this cycle
C-1, C-3, H-1, H-2, H-3, H-6, M-18, L-1, L-2, L-3, L-5, L-6 (auto), L-8, L-9 (auto), L-11 (auto), L-12, L-14, L-15, L-16.

### Explicitly skipped (user direction)
H-5, H-8, M-1, M-2, M-3, M-10, M-14, M-16, dark-mode contrast, L-7, L-10, L-13.

### Still open
**C-2** — deferred this session; the only remaining Critical. Same vendor-publish pattern as C-1 + H-7; needs investigation to find the offending Filament list-page form field.

## Next session anchors

1. **C-2** when ready — needs ~45-60 min to find the form-field offender via DevTools, publish the right vendor view, and add an explicit `name`/`id` attribute.
2. **M-4 verification** — confirm the subclass is being discovered and Shield's own RoleResource isn't double-registered. Open `/admin/shield/roles` as super_admin; expect translated names + no guard column.
3. **H-7 verification** — clear a required field on `/admin/users/2/edit` and click Save; expect Filament's Arabic error inline (no English browser tooltip). Repeat on `/portal/suspension-request-page` (textarea) and a few other forms across both panels.
4. **M-15 verification** — open `/portal/suspension-request-page` as a student; expect "الفصل الدراسي: {currentSemesterName}" badge in place of the dropdown, and a single labeled textarea below it.
5. **Faculty IconColumn aria-label re-check** — if axe still flags links on `/admin/my-courses`, consider replacing `recordUrl` with an explicit View action (more invasive but resolves the axe "every cell is an unlabeled link" pattern entirely).
6. **Design conventions doc** — promote the new "status tabs at top of list" pattern (M-6) and the "current-semester chip" pattern (M-15) into `design-conventions.md` once visually confirmed.
