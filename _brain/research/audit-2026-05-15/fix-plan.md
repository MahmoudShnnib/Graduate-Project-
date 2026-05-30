---
date: 2026-05-15
tags: [ux-audit, fix-plan, admin, filament]
related: [[2026-05-15-admin-ux-audit|audit report]] [[2026-05-15-ux-audit-admin|daily note]] [[2026-05-15-rbac-posture]] [[2026-05-15-arabic-first-leakage]]
---

# UX Audit Fix Plan — for 2026-05-16 session

> **Status as of 2026-05-16 evening (Wave 2 complete):** 36 of 40 findings closed, 12 explicitly skipped (with reason), **1 actionable item left — C-2.** See [[../../daily-notes/2026-05-16-session-handoff]] for the canonical pickup doc + [[../../daily-notes/2026-05-16-wave2-h-and-m-batch]] for the latest per-finding diffs. This plan stays as the original execution guide; the handoff note overrides for resume context.

40 findings (3 Critical / 9 High / 16 Medium / 12 Low). This plan groups them by **fix scope** (single file vs systemic), orders them by **dependency + risk**, and pairs each with a **verification command**.

## Order of attack

```
Wave 1: Brand + cheap a11y       (5 fixes, ≤30 min)
Wave 2: RBAC leak (security)     (1 fix, 30–60 min)
Wave 3: Action-surface gaps      (2 fixes, 30 min)
Wave 4: Localisation gaps        (6 fixes, 1–2 h)
Wave 5: Confirmation copy        (3 fixes, 30 min)
Wave 6: Form / chart a11y        (4 fixes, 1 h)
Wave 7: Misc polish              (rest)
```

Total estimate: **5–7 hours focused** for everything Critical + High + Medium.

---

## Wave 1 — Brand + cheap a11y (≤30 min, no behaviour risk)

| # | Finding | File / Patch | Verify |
|---|---|---|---|
| 1 | **L-1 / L-2** Brand still says "Laravel" | `.env`: set `APP_NAME='كلية التقنية الإلكترونية – طرابلس'` (or chosen brand). Then `php artisan config:clear`. | Open `/admin` — tab title + sidebar brand both Arabic. |
| 2 | **L-7** topbar landmark not unique (axe Moderate, system-wide) | Topbar `<nav>` and sidebar `<nav>` — add `aria-label="القائمة العلوية"` and `aria-label="القائمة الجانبية"` in the Filament panel theme blade if exposed, else attribute via a tiny `<x-slot>` override. | Re-run `axe-core` on any page — landmark-unique drops to 0. |
| 3 | **L-9** sidebar group toggle buttons have empty `aria-label` | `app/Filament/Resources/.../**` group definitions, or Filament navigation group config: pass `description` (already done) AND add an `aria-label` via a custom theme override. | Tab through nav — every focused element announces a label. |
| 4 | **L-10** Default 1px browser focus ring | `resources/css/filament/admin/theme.css` and `.../student/theme.css`: add `:focus-visible { outline: 2px solid var(--ring); outline-offset: 2px; }` to button/link selectors. | Tab around — focus ring is now 2px + offset. |
| 5 | **L-5** Vite font 404 for Noto Kufi | Check `vite.config.js` / `resources/css/filament/admin/theme.css` — font URL likely uses `url(/fonts/...)` which resolves through `[::1]:5173` instead of the app origin. Hardcode `url('/fonts/Noto_Kufi_Arabic/...')` or import via Vite. | Reload `/admin` — Network tab shows no 404. |

## Wave 2 — RBAC leak (security; do this before merging anything else)

See [[2026-05-15-rbac-posture]] for full context.

| # | Finding | File / Patch | Verify |
|---|---|---|---|
| 6 | **C-3** Secretary has read-access to Shield/Roles, Users, Audit Logs | Three resources need both nav + policy gating: **(a)** override `shouldRegisterNavigation()` in `vendor:publish` of `BezhanSalleh\FilamentShield\Resources\RoleResource` (or use a config-level hide), in `app/Filament/Resources/UserResource.php`, and in `app/Filament/Resources/AuditLogResource.php` (or wherever) → return `auth()->user()?->role === 'super_admin'`. **(b)** Update or create policies (`RolePolicy::viewAny`, `UserPolicy::viewAny`, `AuditLogPolicy::viewAny`) to gate on `super_admin` only. | Log in as `secretary@college.edu` / `secretary123` — three resources should disappear from nav AND `/admin/shield/roles` should redirect/403. |

## Wave 3 — Action-surface gaps (high-leverage UX wins)

| # | Finding | File / Patch | Verify |
|---|---|---|---|
| 7 | **H-3** Application view page has no decision actions | `app/Filament/Resources/ApplicationResource/Pages/ViewApplication.php` — override `getHeaderActions()` to return the same `قبول مبدئي` / `قبول مباشر` Actions used in the table column. Extract the two Actions into static methods on `ApplicationResource` so list + view share them. | Open `/admin/applications/9` — hero shows the two acceptance buttons + Edit + (Print removed per #18 below). |
| 8 | **H-6** `قبول مبدئي` triggers full confirmation modal then errors out because exam-date isn't set | Same Actions as #7. Add `->disabled(fn () => is_null(\App\Models\Semester::current()?->exam_date))->tooltip('حدد موعد الامتحان أولاً')` to both. Also render a non-blocking info banner above the table when prerequisite isn't met. | Without setting an exam date, both acceptance buttons are visually disabled with a tooltip explaining why. |

## Wave 4 — Localisation gaps (Arabic-first leakage)

See [[2026-05-15-arabic-first-leakage]] for the full inventory of leaks.

| # | Finding | File / Patch | Verify |
|---|---|---|---|
| 9 | **H-4** `ReportsPage` + `CoursesSurveyResultsPage` have English titles | `app/Filament/Pages/ReportsPage.php` + `app/Filament/Pages/CoursesSurveyResultsPage.php`: override `getTitle()` returning Arabic, and set `protected static ?string $navigationLabel`. | Tab title is Arabic; H1 is Arabic. |
| 10 | **H-8** Portal page H1s leak English class names | `app/Filament/Student/Pages/StudentDashboard.php` (H1 = "Student Dashboard"), `StudentCoursesPage.php`, `GradesPage.php`, `CourseSurveyPage.php` — each gets `getTitle()` + `$navigationLabel` (where missing). | Visit each portal page — H1 + tab title in Arabic. |
| 11 | **M-10** Course names are English-only | DECISION REQUIRED: schema add `name_ar` to `courses` (migration + form + display) OR translate the seed catalogue and accept names are immutable. Recommended: schema add. Migration: `2026_05_16_xxxxxx_add_name_ar_to_courses.php`. | Course list shows Arabic primary + English secondary line. |
| 12 | **M-13 (systemic)** Mixed Arabic+English empty states (`لا توجد students`, `لا توجد student enrollments`) | Two known instances: `app/Filament/Resources/CourseOfferingResource/Pages/EnrollStudents.php` and `app/Filament/Resources/MyCoursesResource/Pages/.../EnterGrades.php` (or wherever the empty-state heading is set). Set `->emptyStateHeading('لا يوجد طلاب مسجلون')`. **Also grep** `lang('en')` + raw English in any `emptyState*()` calls to catch siblings. | Cells show 100% Arabic empty states. |
| 13 | **M-16** Upload-grade-sheet modal mixes Arabic + English (`(Hard Copy)`, `Files`) | Modal heading: in the `رفع ورقة الدرجات` Action, set `->modalHeading('رفع ورقة الدرجات الورقية')`. For Filepond "Files" leak: publish Filepond's locale via `php artisan vendor:publish` then activate Arabic strings in the config. | Modal title + dropzone text 100% Arabic. |
| 14 | **H-7** Required-field validation falls back to English browser-native message | Filament wraps forms in `<form>`. Need `novalidate` so server-side Arabic validation renders instead of the browser tooltip. In the panel provider (`AdminPanelProvider::panel()` / `StudentPanelProvider::panel()`), add `->renderHook('panels::body.start', ...)` or — simpler — override the `EditRecord` page template. Cleanest: contribute a small `<x-filament::form>` override that emits `novalidate`. | Clear required name on `/admin/users/2/edit` and click Save — Arabic error renders inline, no English tooltip. |

## Wave 5 — Confirmation copy

| # | Finding | File / Patch | Verify |
|---|---|---|---|
| 15 | **M-9 (systemic)** Confirmation modals don't name the entity | For every destructive/state-changing `Action` in `ApplicationResource`, `StudentResource`, `SuspensionRequestResource`, etc.: replace `->modalDescription('هل أنت متأكد …')` with `->modalDescription(fn ($record) => "هل أنت متأكد من حذف طلب {$record->full_name}؟")`. **Use the publish-final-grades modal copy as the gold-standard template** — it includes count + side effects. | Open delete modal on application #9 — body names the applicant. |

## Wave 6 — Form & chart a11y

| # | Finding | File / Patch | Verify |
|---|---|---|---|
| 16 | **C-1 (cluster)** axe Critical violations: dashboard chart select, audit-log pagination buttons, student-detail section collapse buttons, reports-page student picker | (a) Dashboard chart-period select — wherever the Chart widget is defined in `app/Filament/Widgets/`, set `->extraAttributes(['aria-label' => 'نطاق التاريخ'])`. (b) Custom Livewire audit-log paginator: add `aria-label="الصفحة السابقة"` / `"الصفحة التالية"`. (c) Section collapse buttons — patch Filament's section-collapse template via `vendor:publish-views` to add `aria-label`. (d) Reports page student select — same `->extraAttributes(['aria-label' => 'اختيار الطالب'])`. | Re-run axe on dashboard / audit-logs / student detail / reports — `critical` count = 0. |
| 17 | **C-2** All Filament list pages fire console issue `form field element should have an id or name attribute` | Most likely Filament v5's table bulk-select checkboxes. Inspect a checkbox `<input>` and confirm. If so, override the row-selection partial: `php artisan vendor:publish --tag=filament-tables-views` and edit `vendor/.../resources/views/columns/...` to add `name="select_record_{{ $recordKey }}"`. | Open `/admin/applications`, Console tab — 0 issues. |

## Wave 7 — Misc Mediums + Lows

| # | Finding | File / Patch | Verify |
|---|---|---|---|
| 18 | **L-3** Print button on application view page (violates `feedback_no_print_button.md`) | `app/Filament/Resources/ApplicationResource/Pages/ViewApplication.php` — remove the `Action::make('print')` from header actions. | Open `/admin/applications/9` — no print button. |
| 19 | **M-1** Applications row has 4 inline buttons | `app/Filament/Resources/ApplicationResource.php` table columns — wrap `قبول مبدئي`, `قبول مباشر`, `حذف` in `ActionGroup::make([...])` while keeping `عرض` inline. | Applications list row shows: `عرض` + dots-menu. |
| 20 | **M-2** Acceptance button labels not self-explanatory | Same file as #19 — rename `قبول مبدئي` → `قبول للامتحان` and `قبول مباشر` → `قبول مباشر بدون امتحان` (or add `->tooltip('...')`). | Hover each button — tooltip clear. |
| 21 | **M-3** No "missing required documents" warning state on application detail | `app/Filament/Resources/ApplicationResource/Pages/ViewApplication.php` (or the section blade) — add an info banner above Documents section when both attachments are null. Render `لم يُرفق` as amber chip. | Open application 9 (missing docs) — amber banner visible. |
| 22 | **M-4** Shield Roles English names + `web` guard column | In `app/Filament/Resources/.../RoleResource.php` (or override `Filament\Shield\Resources\RoleResource`): column `name` uses `->formatStateUsing(fn ($state) => __("roles.$state"))`; column `guard_name` set `->visible(false)`. Add `lang/ar/roles.php` with translations: `'Super Admin' => 'مشرف عام'`, etc. | `/admin/shield/roles` shows Arabic names, no guard column. |
| 23 | **M-6** Status filter tabs missing on Suspension / Discussion / Thesis / Graduates resources | Each resource's `.../Pages/ListXxx.php` — add `getTabs()` returning Filament `Tab` objects for `pending`, `approved`, `rejected` with `badge(fn () => …)`. | Visit `/admin/suspension-requests` — tabs visible with counts. |
| 24 | **M-8** `MaritalStatus::label()` doesn't gender-resolve | `app/Enums/MaritalStatus.php` — change `label(): string` to `label(?string $gender = null): string` returning the matched form. Update callers to pass `gender`. | Application detail shows `مطلقة` for female, `مطلق` for male. |
| 25 | **M-11** Publish-grades buttons stay enabled with 0 grades | In the publish-grades Filament actions: `->disabled(fn ($record) => $record->grades()->count() === 0)->tooltip('لا توجد درجات للنشر')`. | Open offering with 0 grades — buttons disabled. |
| 26 | **M-14** Portal suspension dropdown shows mixed Arabic + English semester names | Same root cause as M-10. Already fixed if M-10 is done. Otherwise: enforce Arabic on Semester model via validation or factory. | Dropdown all Arabic. |
| 27 | **M-15** Portal suspension-request page has 1 axe Critical + 1 Serious | `app/Filament/Student/Pages/SuspensionRequestPage.php` — ensure Filament form fields have explicit `<label for="...">` rendered. | Re-run axe on `/portal/suspension-request-page` — clean. |
| 28 | **M-17** `/admin/my-courses` has 28 unlabeled links | `app/Filament/Resources/MyCoursesResource.php` table columns — toggle/indicator cells need `->extraAttributes(['aria-label' => ...])`. | Re-run axe — `link-name` = 0. |
| 29 | **M-18** Faculty dashboard shows non-faculty widgets | `app/Providers/Filament/AdminPanelProvider.php` widgets — gate each with `Widget::canView()` by role. Create faculty-specific widget: "My pending grade sheets", "Courses I teach this semester". | Log in as faculty — dashboard widgets are role-appropriate. |
| 30 | **H-5** External `ui-avatars.com` for user avatars | Create `app/View/Components/InitialsAvatar.php` SVG component (deterministic colour from name hash). In `app/Models/User.php`, set `getFilamentAvatarUrl(): ?string { return null; }` so Filament falls back. Use the component wherever avatars render. | Reload `/admin` — Network tab shows zero `ui-avatars.com` requests. |
| 31 | **L-4 withdrawn** in round 2 — skip. |  |  |
| 32 | **L-6** Empty `<th>` for action column | Resource table column header: add `->extraHeaderAttributes(['aria-label' => 'إجراءات'])` or `->label('إجراءات')` with `->extraHeaderAttributes(['class' => 'sr-only'])`. | axe `empty-table-header` = 0. |
| 33 | **L-8** Add/drop empty-state contrast | `app/Filament/Resources/AddDropRequestResource/Pages/ListAddDropRequests.php` — empty state text colour bump. | axe contrast = 0. |
| 34 | **L-11** Exam-date setter uses browser-native `datetime-local` picker | Wherever the exam-date action defines its form: replace `Forms\Components\TextInput::make(...)->type('datetime-local')` with `Forms\Components\DateTimePicker::make(...)`. | Open the setter — Filament's date picker renders in Arabic. |
| 35 | **L-12** Suspension reason rendered un-truncated | `app/Filament/Resources/SuspensionRequestResource.php` reason column — `->limit(60)->tooltip(fn ($state) => $state)`. | Long reason in list shows ellipsis + tooltip. |
| 36 | **L-13** `GET /admin/logout` is no-op | Acceptable as-is — Filament intent. If you want, register a tiny `GET /admin/logout` route that POSTs internally + redirects. Skip unless reported. |  |
| 37 | **L-14** Bare 403 page on unauthenticated `/portal` | `bootstrap/app.php` — register an `AuthenticationException` handler that redirects to `/portal/login` when on the student panel. | `curl -i http://127.0.0.1:8000/portal` returns 302 → `/portal/login`. |
| 38 | **L-15** Portal grades page contrast (1 node) | `app/Filament/Student/Pages/GradesPage.php` template — text colour bump on the empty-state line. | axe contrast = 0. |
| 39 | **L-16** Grade-distribution modal lacks sum-to-100 indicator | In the `توزيع الدرجات` Filament action's form: make both inputs `->reactive()` and add a `Placeholder::make('total')->content(fn ($get) => ($get('coursework') + $get('final')) === 100 ? '✓ المجموع = 100' : "⚠ المجموع = ".($get('coursework')+$get('final')))`. | Type 60 + 50 — see "⚠ المجموع = 110" live. |

## Findings deliberately left as-is (per self-critique pass)

- **M-5** Filament infolist `<dt>/<dd>` without `<dl>` — vendor scope, upstream concern.
- **M-7** Audit-log paginator buttons (DUPLICATE of C-1).
- **L-2** Sidebar brand says "Laravel" (DUPLICATE of L-1).

## Verification protocol per fix

After each fix:
1. `vendor/bin/pint --dirty --format agent` (mandatory per CLAUDE.md).
2. Run targeted Pest test if one exists. Else add one via `php artisan make:test --pest`.
3. Re-walk the affected slice in the browser (per the audit's "Verify" column).
4. If touching a confirmation modal or RBAC: log in as the relevant non-admin role and verify.

## After all fixes

1. Re-run `php artisan test --compact` — expect 158/158 + new tests for fixes still green.
2. Re-run axe-core on every previously-failing route.
3. Re-record verdict block in `audit.md` — target: `Pass` or `Conditional Pass`.
4. Document fixed bugs in `_brain/bugs-and-fixes/2026-05-16-*.md` per CLAUDE.md convention.
5. Commit + push, open PR with the audit + fix-plan + verification screenshots.
