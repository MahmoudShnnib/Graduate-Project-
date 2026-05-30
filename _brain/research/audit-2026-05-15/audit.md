---
date: 2026-05-15
tags: [ux-audit, admin, filament, a11y, perf]
related: [[design-conventions]] [[figma-view-page-design]] [[gotchas]]
---

# /admin UX Audit — 2026-05-15 (Round 1 + Round 2 merged)

```
═══════════════════════════════════════════════════════════
VERDICT: Fail

Persona: Super Admin (head of graduate-studies office)
Surfaces audited: 20 of 20 routes (full coverage after Round 2)
Interaction Manifest: complete for thread T1 (Applications — drilled through approval modal end-to-end, including blocked-by-prerequisite outcome).

Hard Gates:
  Console errors        0
  Console warnings      systemic on every list page — form fields lack id/name (Filament default)
  Network 5xx           0
  Network 404 (auth)    1  — Noto Kufi font 404 on Vite dev server
  Layout collapse       0  — clean at 1440 / 768 / 375
  axe Critical          6  — dashboard, audit-logs (×2), student detail (×2), reports-page (1)
  axe Serious           6  — link-name (users + courses), color-contrast (figma hero + GPA chip + survey + add-drop), dlitem (78 nodes)
Performance (on /admin): LCP 1.18s / CLS 0.05 / INP n/a  — pragmatic budgets GREEN

Findings (after Round 2):
  Critical: 2    High: 7    Medium: 9    Low: 9

Findings (after self-critique prune):
  Critical: 2    High: 7    Medium: 7 (M-5 + M-7 downgraded)    Low: 6 (L-2 + L-7 + L-10 downgraded)

Self-critique pass:  Drafted: 27   Kept: 22   Generic: 3   Duplicate: 2

Findings (after Round 3 additions):
  Critical: 2    High: 7    Medium: 10 (M-10, M-11 added)    Low: 8 (L-11, L-12 added)
  Net actionable after critique: 25.

Findings (after Round 4 — full extension: T2, T5, keyboard, secretary, portal):
  Critical: 3 (+C-3 RBAC leak)
  High: 9 (+H-8 portal English titles, +H-9 nav doesn't filter by role)
  Medium: 13 (+M-13 mixed Arabic/English empty state, +M-14 portal dropdown mess, +M-15 axe critical on portal suspension page)
  Low: 11 (+L-13 GET /admin/logout no-op, +L-14 bare 403 on portal, +L-15 portal grades contrast)
  Net actionable: 36 findings.

Findings (after Round 5 — faculty role):
  Critical: 3 (unchanged; faculty findings reinforce C-1, C-3)
  High: 9 (unchanged)
  Medium: 16 (+M-16 mixed AR/EN in upload modal, +M-17 28 unlabeled links on /admin/my-courses, +M-18 faculty dashboard shows non-faculty data)
  Low: 12 (+L-16 grade-distribution modal lacks sum-to-100 indicator)
  Net actionable: 40 findings.

  Verdict remains: Fail (hard-gate: axe Critical > 0 across multiple surfaces + RBAC leak).

TOP 5 (impact × ease, senior-designer pick):
  1. [H-3] Application view page has no decision actions — kills the value of "review then decide"
  2. [H-6] قبول مبدئي fires confirmation → error after commit instead of disabling the button when exam date isn't set
  3. [C-1] 6 axe Critical violations across 4+ routes — fails WCAG A on system-wide widgets
  4. [H-7] Required-field validation appears in English ("Please fill out this field.") on Arabic-first forms
  5. [H-1] Figma hero meta text fails colour contrast on every view page (9 nodes/page × ~9 view pages)
═══════════════════════════════════════════════════════════
```

## Persona Lock

**Super Admin** — head of the graduate-studies office (`رئيس مكتب الدراسات العليا`).
- Tech comfort: medium-high. Comfortable with admin panels, not a developer.
- Time pressure: high — owns every queue, makes all approvals.
- Emotional state: harried but careful. Wrong approvals send notifications + mint enrollment numbers.
- Device: desktop, Chrome/Chromium, 1440×900, Arabic RTL.
- Native Arabic reader. Will not tolerate English labels in production UI.

**Mandatory companion**: first-time-user lens. Every multi-page feature also audited as if landing for the first time.

## Routes audited (20/20)

All 20 admin routes covered across Round 1 (sampling) and Round 2 (remaining 7 + full T1 interaction):
Dashboard, Specializations, Students (list + detail), Users (list + edit), Applications (list + detail + approval modal + delete modal + bulk-select toolbar), Exam List Page, Shield/Roles, Courses, Semesters, Course Offerings, Survey Results, Discussion Requests, Graduates, Grades, Suspension Requests, Thesis Registrations, Add/Drop Requests, Exceptional Opportunities, Reports Page, Audit Logs.

## Coverage map — what was walked vs sampled vs not touched

This is the canonical "what was tested" log. Use it to resume the audit in a future session. **Final state after rounds 1-4 below; an additional "Final coverage map (final — updated round 4)" lower in this file is a duplicate and kept only for historical comparison — defer to the table below.**

### Threads (5 planned in Phase 2) + bonus surfaces

| Thread / Surface | Status | Round | Notes |
|---|---|---|---|
| **T1 — Daily intake (Applications)** | **Walked end-to-end** | R1+R2 | List → detail → approval modal → confirm → toast (blocked by exam-date prerequisite, no side effect). Also tested delete modal (cancelled), bulk-select toolbar, form stress on user edit. |
| **T2 — Enroll student in offering** | **Walked** | R4 | Opened `/admin/course-offerings/22/enroll`, opened Add Student modal, observed default focus on Submit (vs Cancel for destructive). Didn't commit enroll (Filament Select options didn't render in DOM query). |
| **T3 — Grade entry & publish (admin side)** | **Walked** | R3 | Drilled `/admin/grades/1/grades`, observed empty state, opened publish-final-grades modal (good copy with side-effects disclosure). Grade *entry* itself is faculty role — pending in round 5. |
| **T4 — Approve suspension** | **Walked read-only** | R3 | Opened the single suspension request (already approved). Verified figma view-page layout. No pending request available to test approve/reject UI. |
| **T5 — Drill a full student record** | **Walked** | R4 | `/admin/students/1` deep-walked. No tabs; everything renders linearly. Hero only has تعديل + طباعة. |
| **Exam-date setter** | **Walked** | R3 | Opened `تحديد موعد الامتحان` modal. Single datetime-local input. Cancelled. Closes H-6 prerequisite loop. |
| **Keyboard-only T1** | **Walked (partial)** | R4 | 28 tabs to reach main content (no Skip-to-content link, sidebar-first tab order). Did not complete a full thread keyboard-only. |
| **Secretary role audit** | **Walked** | R4 | Logged in as `secretary@college.edu`. Nav identical to super-admin. Confirmed read access to Shield/Roles, Users, Audit Logs → C-3 RBAC leak. |
| **Faculty role audit** | **Walked** | R5 | Logged in as `cs.faculty@example.ly`. Nav correctly filtered to 9 items (strengthens C-3 — secretary's leak is config, not framework limitation). Walked `موادي`, drilled offering 1, opened both header-action modals. Surfaced M-16, M-17, M-18, L-16. Reproduced C-1, M-10, M-13. |
| **Student portal `/portal`** | **Walked (sampled 7 pages)** | R4 | Logged in as `mohammed.zawai@example.com`. Dashboard + 6 nav pages audited. English H1 leak on 4 pages. 1 critical + 1 serious axe on suspension-request page. |

### Surfaces still not opened

- **Filament global search bar** (`بحث عام` top-bar) — never clicked or typed.
- **User menu dropdown** (top-right avatar) — never opened (logout/profile links inside).
- **Dashboard chart widgets** — screenshotted but never interacted with (chart-period combobox is where dashboard's axe Critical lives).
- **Course Offering edit page** (`/admin/course-offerings/{id}/edit`) — never opened.
- **Application edit page** (`/admin/applications/{id}/edit`) — only view page audited.
- **Application full direct-acceptance flow** (`قبول مباشر`) — never triggered; only initial-acceptance was tested.
- **Notification panel / bell icon** — not seen; not tested.
- **Routes discovered via `php artisan route:list` but never opened**:
  - `admin/my-courses` + `admin/my-courses/{record}` — faculty's personal course view (to test in round 5)
  - `admin/student-add-drop/{student}/{semester}` — admin-side add/drop view per student
  - `admin/student-survey/{student}/{semester}` — admin-side survey view per student
  - `admin/exceptional-opportunities-page/{student}` — detail of an exceptional opportunity
  - `admin/audit-logs/{record}` — single audit-log detail view
  - `admin/graduates/{record}` — graduate detail
  - `admin/discussion-requests/{record}` — discussion request detail
  - `admin/thesis-registrations/{record}` — thesis registration detail
  - `admin/specializations/create` + `/{record}` + `/{record}/edit` — specialization CRUD
  - `admin/semesters/create` + `/{record}/edit` — semester CRUD (touched view briefly)
  - `admin/shield/roles/create` + `/{record}` + `/{record}/edit` — role CRUD

### Scenarios from the 11-scenario battery

| Scenario | Status |
|---|---|
| 1. First Contact | Inlined via first-time-user lens on every page |
| 2. Interrupted Workflow | **Skipped** |
| 3. Wrong Turn Recovery | **Skipped** |
| 4. Returning User | **Skipped** |
| 5. Keyboard Only | Partial (R4) — tab order from dashboard only; did not complete a thread |
| 6. Heavy Data | **Skipped** — lists have <30 rows |
| 7. Destructive Confidence | **Done** (R2) — confirmation modals tested + bulk-action toolbar |
| 8. Second User (Role) | **Done** (R4) for Secretary; **pending** for Faculty (R5) |
| 9. Lifecycle Position | **Skipped** |
| 10. Round-Trip Workflow | Inlined — list → detail → back transitions verified |
| 11. Data Seasoning | **Skipped** |

### Stress recipes from the extended catalogue

| Recipe | Status |
|---|---|
| Empty / saturated / long content | Partial — saw empty states on suspension / discussion / thesis / graduates / add-drop / enrollment-manager |
| Race conditions | **Skipped** |
| Slow network (3G throttle) | **Skipped** |
| Reduced motion | **Skipped** |
| i18n / long German / RTL Arabic | RTL natively tested; not stress-tested with extra-long Arabic strings |
| Offline mode | **Skipped** |
| Print stylesheet | **Skipped** |
| High-contrast mode | **Skipped** |
| Real-flavour data battery | Partial — one input on User edit form (R2). Did not test file upload, length truncation, SQL/XSS canaries at scale |

### Phase 7 (fix-and-verify)

**Not run.** Out of scope per user constraint ("no code changes"). Future session would patch findings then re-walk affected slices.

### What surfaces were tested per page

| Surface | Loaded | Snapshotted | axe ran | Console read | Interaction (click/type) |
|---|---|---|---|---|---|
| `/admin` (dashboard) | ✓ | ✓ | ✓ | ✓ | Tab keyboard test |
| `/admin/applications` | ✓ | ✓ | ✓ | ✓ | Open modal + cancel + bulk-select |
| `/admin/applications/9` | ✓ | ✓ | ✓ | ✓ | — (read only) |
| `/admin/students` | ✓ | ✓ | (table) | ✓ | — |
| `/admin/students/1` | ✓ | ✓ | ✓ | ✓ | — |
| `/admin/users` | ✓ | ✓ | ✓ | ✓ | — |
| `/admin/users/2/edit` | ✓ | ✓ | — | ✓ | form-stress (cancelled) |
| `/admin/grades` | ✓ | (table) | ✓ | ✓ | — |
| `/admin/grades/1/grades` | ✓ | ✓ | ✓ | ✓ | publish modal opened + cancelled |
| `/admin/suspension-requests` | ✓ | ✓ | — | ✓ | — |
| `/admin/suspension-requests/1` | ✓ | — | ✓ | — | — |
| `/admin/audit-logs` | ✓ | ✓ | ✓ | ✓ | — |
| `/admin/course-offerings` | ✓ | ✓ | (table) | ✓ | — |
| `/admin/course-offerings/22/enroll` | ✓ | ✓ | ✓ | — | Add Student modal opened + cancelled |
| `/admin/courses` | ✓ | ✓ | ✓ | — | — |
| `/admin/semesters` | ✓ | ✓ | ✓ | — | — |
| `/admin/courses-survey-results` | ✓ | ✓ | ✓ | — | — |
| `/admin/discussion-requests` | ✓ | ✓ | ✓ | — | — |
| `/admin/graduates` | ✓ | ✓ | ✓ | — | — |
| `/admin/thesis-registrations` | ✓ | ✓ | ✓ | — | — |
| `/admin/add-drop-requests` | ✓ | ✓ | ✓ | — | — |
| `/admin/specializations` | ✓ | ✓ | ✓ | — | — |
| `/admin/exam-list-page` | ✓ | ✓ | ✓ | — | — |
| `/admin/exceptional-opportunities-page` | ✓ | ✓ | ✓ | — | — |
| `/admin/reports-page` | ✓ | ✓ | ✓ | — | — |
| `/admin/shield/roles` | ✓ | ✓ | ✓ | — | — |
| `/admin/login` | ✓ | ✓ | — | — | Filled + submitted (secretary login) |
| `/portal/login` | ✓ | ✓ | — | — | Filled + submitted (student login) |
| `/portal/student-dashboard` | ✓ | ✓ | ✓ | — | — |
| `/portal/student-courses-page` | ✓ | ✓ | ✓ | — | — |
| `/portal/grades-page` | ✓ | ✓ | ✓ | — | — |
| `/portal/course-survey-page` | ✓ | ✓ | ✓ | — | — |
| `/portal/suspension-request-page` | ✓ | ✓ | ✓ | — | — |
| `/portal/course-tree-page` | ✓ | ✓ | ✓ | — | — |
| `/admin/my-courses` (faculty) | ✓ | (table) | ✓ | — | — |
| `/admin/my-courses/1` (faculty) | ✓ | ✓ | ✓ | — | Distribution + upload modals opened + cancelled |

## Findings

### Critical (2)

**[C-1] Multiple axe Critical violations across system-wide widgets.**
- **Surface**: Dashboard, Audit Logs (×2), Student detail figma view (×2), Reports Page.
- **Reproduce**: Inject axe-core@4.10.0 via CDN, `axe.run()`. Violations:
  - Dashboard: `select-name` (chart-period combobox).
  - Audit Logs: `button-name` ×2 (pagination prev/next icon buttons), `select-name` (per-page combobox).
  - Student detail `/admin/students/1`: `aria-allowed-attr` (1 node), `button-name` ×2 (section collapse buttons).
  - Reports Page: `select-name` (student picker).
- **Smallest patch**:
  1. Add `aria-label="نطاق التاريخ"` to chart-period select.
  2. Add `aria-label="الصفحة السابقة"` / `"الصفحة التالية"` to icon-only pagination buttons in custom Livewire paginators.
  3. Add `aria-label="اختيار الطالب"` to the Reports Page student picker.
  4. Audit `.fi-section-collapse-btn` markup — add `aria-label` derived from the section heading.
- **Severity**: Critical (hard-gate breach: axe `critical` > 0 anywhere).

**[C-2] Missing form-field `id` / `name` attributes — systemic across every list page.**
- **Surface**: Applications, Students, Users, Grades, Suspension Requests, Course Offerings, Audit Logs, Specializations, Courses — every list page tested in Chrome DevTools fires `[issue] A form field element should have an id or name attribute (count: 2)` in the console.
- **Confirmation from Round 2**: the User-edit form also has `<input name="">` (Livewire `wire:model` only — no traditional `name` attribute). Reproducible system-wide.
- **Smallest patch**: register a custom Filament checkbox / input template that emits `name="..."` alongside Livewire bindings. Patch once at Filament layer, fixes everywhere.
- **Severity**: Critical (hard-gate breach: > 0 console issues on every list page).

### High (7)

**[H-1] Figma hero meta text fails colour contrast — 9 nodes per page × ~9 view pages.**
- **Surface**: every figma view page. axe ID: `color-contrast`.
- **Suspected location**: `.fsv-hero__meta-item` in the Figma view-page CSS (`resources/css/filament/admin/theme.css` or scoped `view-*.blade.php`). The muted palette decision (2026-05-13) traded contrast for aesthetic.
- **Smallest patch**: bump meta-text colour one step darker (`text-gray-500 → text-gray-600` or your token equivalent that passes WCAG AA 4.5:1).
- **Severity**: High (axe `serious`; user-invested design language is the source).

**[H-2] Figma GPA chip (`.fsv-gpa-chip__label`) fails contrast — 3 nodes on student detail.**
- **Surface**: every student view page.
- **Smallest patch**: darken label text or deepen chip background; check with a contrast checker before merging.
- **Severity**: High.

**[H-3] No decision actions on the application view page `/admin/applications/{id}`.**
- **Surface**: every application detail page.
- **Reproduce**: open any application from the list. Hero shows only `تعديل` + `طباعة`. The two acceptance buttons (`قبول مبدئي`, `قبول مباشر`) are on the list row but absent from the detail. Persona reviewing the application has to navigate back to act.
- **Suspected location**: `app/Filament/Resources/ApplicationResource/Pages/ViewApplication.php` `getHeaderActions()`.
- **Smallest patch**: lift the two acceptance Action definitions and register them as HeaderActions on the view page.
- **Severity**: High (workflow break).

**[H-4] Custom Livewire pages with English titles** — confirmed two cases in Round 2:
- `/admin/reports-page`: `document.title === "Reports Page - Laravel"`, `<h1>` = "Reports Page".
- `/admin/courses-survey-results`: `document.title === "Courses Survey Results - Laravel"`, `<h1>` = "Courses Survey Results".
- Other custom pages (`Exam List Page`, `Exceptional Opportunities`) correctly use Arabic titles.
- **Smallest patch**: override `getTitle()` / `$navigationLabel` / `$slug` in those two Page classes to Arabic.
- **Severity**: High (brand inconsistency on Arabic-first promise; tab + bookmark + screen-reader title).

**[H-5] User avatars fetched from external `ui-avatars.com` on every page.**
- **Reproduce**: every page load shows `GET https://ui-avatars.com/api/?name=...` in the network tab. Arabic names are URL-encoded and sent to a 3rd party.
- **Side effects**: privacy leak (faculty IT shouldn't transmit user names externally), breaks offline / air-gapped deployment, adds 200-400ms blocking image fetch per page.
- **Smallest patch**: write an `<x-initials-avatar>` Blade component (SVG initials, deterministic colour from name hash). Wire `User::getFilamentAvatarUrl()` to return null so Filament uses the component.
- **Severity**: High (privacy + offline + perf).

**[H-6] `قبول مبدئي` triggers full confirmation flow → fails after commit with hidden-prerequisite error.** *(Round 2)*
- **Reproduce**: on `/admin/applications`, click `قبول مبدئي` on row 9. A confirmation modal opens ("سيتم إضافة الطالب إلى قائمة الامتحان التفاضلي. / إلغاء / تأكيد"). Click تأكيد. **Result**: toast appears with message `يجب تحديد موعد الامتحان أولاً` (Exam date must be set first).
- **Why this is bad**: the persona has invested 4 interactions (find row → click button → read modal → confirm) before learning the system refuses the action. The required prerequisite (`تحديد موعد الامتحان` — the header-action button) is *on the same page* but the persona doesn't know it must run first.
- **Smallest patch**: when `Semester::current()->exam_date` is null, disable the `قبول مبدئي` action with `->disabled(fn() => …)->tooltip('حدد موعد الامتحان أولاً')`. Even better: render an info banner above the table when the prerequisite isn't met, with an inline "Set exam date now" button.
- **Severity**: High (every super-admin will hit this at least once per semester; the fix is small but the friction is real).

**[H-7] Required-field validation falls back to English browser-native message on Arabic-first forms.** *(Round 2)*
- **Reproduce**: `/admin/users/2/edit`. Clear `الاسم*`, click `حفظ التغييرات`. A native browser tooltip appears: `"Please fill out this field. "` (uid=10_0 in the a11y tree confirms `alert` role).
- **Why this is bad**: Filament's server-side Arabic validation (`هذا الحقل مطلوب`) never gets a chance to render — the browser-native HTML5 validation blocks the submit first, in the OS/browser language (English on this machine). On an Arabic-first panel, the most jarring moment (being corrected) reverts to English.
- **Smallest patch**: add `novalidate` to the Filament form `<form>` element so server-side Arabic validation renders. Or set a localised `title` on each required input. Filament-level fix preferred.
- **Severity**: High (silent until triggered, but jarring; affects every required field).

### Medium (9)

**[M-1] Action column overload on Applications list (4 inline buttons per row).** Same as Round 1.

**[M-2] `قبول مبدئي` vs `قبول مباشر` labels are not self-explanatory.** Same as Round 1.

**[M-3] No "missing required documents" warning state on application detail.** Same as Round 1.

**[M-4] Shield Roles page leaks English role names + Spatie "Guard" jargon.** *(Refined in Round 2)*
- **Counter-observation from Round 2**: the User edit form (`/admin/users/2/edit`) DOES translate role labels to Arabic (`رئيس مكتب الدراسات العليا`, `السكرتير`, `عضو هيئة التدريس`). Translation layer exists.
- **What's wrong**: only the Shield Roles admin page (`/admin/shield/roles`) bypasses the translation — shows raw `Super Admin / Secretary / Faculty / Student / Panel User` plus a `اسم الحارس: web` column.
- **Smallest patch**: in the `RoleResource` (or wherever Shield's RoleResource is overridden), set `->formatStateUsing(fn ($state) => __("roles.$state"))` on the name column; hide the guard-name column with `->visible(false)` since the app uses a single guard.
- **Severity**: Medium.

**[M-5] Filament infolist renders `<dt>` / `<dd>` without wrapping `<dl>` — 78 nodes.** Same as Round 1 (Filament-vendor scope).

**[M-6] Suspension Requests list lacks status filter tabs.** Same as Round 1. *(Round 2 confirmed: Discussion Requests, Thesis Registrations, Graduates also lack filter tabs by status — pattern is wider than first found.)*

**[M-7] Audit-log custom paginator buttons have no accessible name.** Same as Round 1.

**[M-8] Marital status enum doesn't gender-resolve.** Same as Round 1.

**[M-9] Confirmation modals don't name the entity being acted on.** *(Round 2)*
- **Reproduce**:
  - Delete-application modal body: `هل أنت متأكد من القيام بهذه العملية؟` — generic; no applicant name.
  - Approve-application modal title: `قبول مبدئي`; body: `سيتم إضافة الطالب إلى قائمة الامتحان التفاضلي.` — uses generic "الطالب", not the actual name.
- **Counter-example**: the `نشر الدرجات النهائية` modal *does* parameterise — `سيتم نشر الدرجات النهائية لـ {N} طالب من أصل {M} مسجل. سيتم تحديث المعدل التراكمي وتفعيل التحذيرات.` — and explicitly discloses side effects. Use it as the template for all destructive/state-changing modals.
- **Smallest patch**: parameterise the modal body. Filament `Action::make('...')->modalDescription(fn ($record) => "هل أنت متأكد من حذف طلب {$record->full_name}؟")`.
- **Severity**: Medium.

**[M-10] Course names are English-only on the Arabic-first panel.** *(Round 3)*
- **Surface**: `/admin/courses` list, `/admin/grades` list, `/admin/grades/{id}/grades` heading, anywhere `course.name` is rendered.
- **Sampled**: `Advanced Mathematics`, `Digital Signal Processing`, `Advanced Electronics`, `Microprocessors & Embedded Systems`, `Computing`, `VHDL Design and Synthesis`, `Fault Tolerant Systems`, `Information Theory`, `Communication Networks`, `Microwave Engineering`, `Wireless Communication Networks`. The grade-entry page H1 reads `درجات مادة: Advanced Mathematics` — Arabic prefix, English course name.
- **Suspected location**: `courses` table has a single `name` column. The CourseResource form likely has one Arabic label `اسم المقرر` but a single value field. The seeder produces English course names from the catalogue.
- **Smallest patch**: add `name_ar` column to `courses` (migration), surface both fields on the CourseResource form, render `name_ar` in lists/headings with `name` as a small secondary line. Or — if cheaper — translate the seed catalogue to Arabic and treat this as a seeder-only issue. Pick the schema fix if course names will ever be user-edited; the seeder fix if course names are immutable.
- **Severity**: Medium (visible everywhere a course is mentioned, including PDFs and notifications).

**[M-11] Publish-grades buttons stay enabled even when there are zero grades to publish.** *(Round 3)*
- **Surface**: `/admin/grades/{id}/grades` for any offering with 0 grades entered.
- **Reproduce**: drill into Advanced Mathematics (EE601, archived). Header shows `0 طالب مسجل` and `0 / 0 درجة مدخلة`, but `نشر درجات أعمال الفصل` and `نشر الدرجات النهائية` buttons remain enabled and clickable. Click `نشر الدرجات النهائية` — modal opens saying "Final grades will be published for **0 students out of 0 enrolled**".
- **Why this is bad**: clicking through publishes nothing meaningful and creates audit-log noise. The persona's mental model is "this button does something" — but here it confirms a no-op.
- **Smallest patch**: `->disabled(fn ($record) => $record->grades()->count() === 0)->tooltip('لا توجد درجات للنشر')` on both publish actions.
- **Severity**: Medium.

### Low (9)

**[L-1] `APP_NAME=Laravel`** — `document.title` ends in `- Laravel`. Set `APP_NAME` in `.env`.

**[L-2] Sidebar brand link says "Laravel"** — same root cause as L-1.

**[L-3] Print button on application view page** — violates `feedback_no_print_button.md`.

**[L-5] Vite dev-server 404 for Noto Kufi font.** Dev-only; fix import path.

**[L-6] Empty `<th>` for action column** (axe `empty-table-header`).

**[L-7] Filament topbar landmark not unique** (axe `landmark-unique`, system-wide). Assign `aria-label`s to disambiguate.

**[L-8] Add/drop empty-state text fails contrast** — single `color-contrast` node on `/admin/add-drop-requests` (empty list state).

**[L-9] Sidebar group toggle buttons have empty accessible names.** *(Round 2)* The expand/collapse buttons next to nav group headings (`إدارة الوصول`, `الإعدادات الأكاديمية`, `طلبات المناقشة`, `إدارة الطلاب`, `النظام`) — five `<button>` elements with no `aria-label`. Add `aria-label="${groupName} (طي/فتح)"`.

**[L-10] Default focus ring is the 1px browser default blue outline.** *(Round 2)* Works for WCAG 2.4.7 but feels minimal for a polished panel. Consider adding a stronger `:focus-visible` ring via Tailwind classes (e.g. `focus-visible:ring-2 focus-visible:ring-primary-500`).

**[L-11] Exam-date setter uses browser-native `datetime-local` picker.** *(Round 3)* On `/admin/applications` → `تحديد موعد الامتحان`, the form has a single `<input type="datetime-local">`. The native picker renders in the browser/OS locale — English month names on a non-Arabic system. Combined with [H-7], this is the same localisation gap on the input side rather than the validation side. Filament has a `DateTimePicker` form component that renders consistently in the app locale; swap it in.

**[L-12] Suspension request reason is rendered un-truncated in the list.** *(Round 3)* The seed reason `"11111111…"` (50 chars) renders full-width in the list. A real 500-char reason from a student would push the row off-screen on small viewports. Truncate with `->limit(60)->tooltip(fn ($state) => $state)` in the SuspensionRequestResource table column.

## Round 4 — Final extension findings

### Critical (3 → adds C-3)

**[C-3] RBAC leak: Secretary has read access to admin-only resources.** *(Round 4)*
- **Surface**: Logged in as `secretary@college.edu`. Verified read access to:
  - `/admin/shield/roles` — full role catalogue visible (Super Admin / Secretary / Faculty / Student / Panel User + permission counts).
  - `/admin/audit-logs` — full activity log visible, including filter by user/role.
  - `/admin/users` — all 15 users listed including super-admin's email.
- **Why this is Critical**: information disclosure across security boundary. Secretary should not know the permission catalogue, should not see other admin emails, should not be able to inspect who-did-what across the system. Header `إضافة` actions appear gated (Filament's policy on `create()`) but `viewAny()`/`view()` policies aren't blocking the resource lists.
- **Suspected location**: `app/Policies/RolePolicy.php`, `app/Policies/AuditLogPolicy.php`, `app/Policies/UserPolicy.php` — or, since Shield's `RoleResource` is a vendor file, an explicit `shouldRegisterNavigation()` + `canViewAny()` override is needed.
- **Smallest patch** (immediate): override `shouldRegisterNavigation()` to return `auth()->user()->role === 'super_admin'` for the three resources, AND ensure the policy methods (`viewAny`, `view`) check the same. Both are needed: hiding from nav doesn't stop URL-typing access.
- **Severity**: Critical (security/RBAC, not just UX).

### High additions (7 → 9)

**[H-8] Portal page H1 + browser title inconsistently leak English class names.** *(Round 4)*
- **Surface**: `/portal` (student panel). Sampled 7 pages:
  - English: `Student Dashboard`, `Student Courses Page`, `Grades Page`, `Course Survey Page` (also the browser tab title)
  - Arabic: `شجرة موادي`, `طلبات إيقاف قيد`
- **Why this is High** (and not Medium like H-4 for admin): this is the **student-facing** panel — the page H1 is the first thing every student reads. The mismatch ("Student Dashboard" then immediately below in Arabic "مرحباً، محمد أحمد علي الزاوي") looks unfinished.
- **Suspected location**: each Livewire page class in `app/Filament/Student/Pages/` — those without an explicit `$navigationLabel` or `getTitle()` fall back to a kebab-derived English class name.
- **Smallest patch**: set `protected static ?string $navigationLabel = 'لوحة التحكم'` (etc.) and override `getTitle()` on each page.
- **Severity**: High.

**[H-9] Sidebar/Nav doesn't filter by role.** *(Round 4)* — strongly related to C-3.
- Secretary sees the same 20 nav items as super-admin even though they shouldn't have access to several. Even before clicking, the sidebar advertises capabilities the secretary doesn't have.
- **Smallest patch**: pair with C-3's `shouldRegisterNavigation()` overrides. The nav and the page-access check share the same condition.
- **Severity**: High (less critical than C-3 because it's only visual, but same root cause).

### Medium additions

**[M-13] Empty-state on enrollment manager mixes Arabic + English.** *(Round 4)*
- **Surface**: `/admin/course-offerings/{id}/enroll` empty-students state shows `لا توجد students` — Arabic preposition + English noun (`students` left untranslated).
- **Suspected location**: missing translation key in the table's `emptyStateHeading()` — likely something like `__('table.empty.students')` where the key fell through to the English fallback.
- **Smallest patch**: locate the empty-state heading in the Resource (probably `app/Filament/Resources/CourseOfferingResource/Pages/EnrollStudents.php`) and set `->emptyStateHeading('لا يوجد طلاب مسجلون')`.
- **Severity**: Medium.

**[M-14] Portal suspension-request semester dropdown shows raw seed names mixing Arabic + English + placeholders.** *(Round 4)*
- **Surface**: `/portal/suspension-request-page`. Dropdown options observed: `ijij`, `ربيع 2026`, `summer 2026`, `ijj`, `خريف 2025`, `الفصل الدراسي الثاني 2025`, `popop`, `fvfvfvfv`, `v`, `v`, `v`.
- **Why noted as a finding (not just dummy data)**: the seed catalog is mostly fine, but `summer 2026` is in English alongside `خريف 2025` and `ربيع 2026` in Arabic. The Semester model accepts any name and the system doesn't enforce Arabic — same root cause as M-10 (no `name_ar` on the model, single `name` field accepts whatever was typed).
- **Smallest patch**: same family as M-10 — add a validation rule or accept the limitation and educate seed-editors. If user-input is allowed, normalise via a form rule.
- **Severity**: Medium.

**[M-15] Suspension-request portal page has 1 axe Critical + 1 axe Serious.** *(Round 4)*
- **Surface**: `/portal/suspension-request-page`. axe shows Critical and Serious violations. Suspected: the unlabelled semester `<select>` (Critical = `select-name`) + the reason `<textarea>` (Serious = `label` missing).
- **Smallest patch**: ensure both Filament form fields have explicit labels rendered as `<label for="...">`.
- **Severity**: Medium (less than C-1 because surface is smaller — single page — but same axe-Critical class).

### Low additions

**[L-13] Filament's `/admin/logout` is POST-only — GET returns 405/no-op.** *(Round 4)*
- Noticed in this audit when my initial GET to `/admin/logout` silently failed to terminate the session. Real users won't hit this (the logout button submits POST), but anyone bookmarking or pasting the URL would. Standard Filament behaviour; document if a tutorial mentions it.
- **Severity**: Low.

**[L-14] Unauthenticated GET to `/portal` returns the bare framework "FORBIDDEN" 403 page** *(Round 4)* — no styling, no redirect to `/portal/login`. CLAUDE.md mentions "403→/portal/login exception handler" in `bootstrap/app.php`, but the handler doesn't catch the unauthenticated case (auth middleware fires first and returns 403 without invoking the handler).
- **Smallest patch**: in `bootstrap/app.php`, add a redirect for `AuthenticationException` on the `portal` panel to `/portal/login`. Or register a custom 403 view styled with the Filament panel chrome.
- **Severity**: Low.

**[L-15] Portal grades page has a color-contrast issue (1 node).** *(Round 4)* `/portal/grades-page` axe shows one Serious contrast violation — likely the empty-state text. Smallest patch: darken the text color one step.
- **Severity**: Low (single page, single node).

## Round 5 — Faculty-role findings

Logged in as `cs.faculty@example.ly` (`د. عبدالله الورفلي`, password `password`). Walked dashboard, `/admin/my-courses`, drilled `/admin/my-courses/1`, opened `توزيع الدرجات` modal, opened `رفع ورقة الدرجات` modal.

### Counter-evidence reinforcing C-3 (RBAC leak)

Faculty's nav is **correctly filtered** to 9 items (vs 20 for super-admin and secretary):
- Visible: لوحة التحكم, الطلاب, قائمة الامتحانات, موادي (my-courses), عروض المقررات, نتائج استطلاع المقررات, الدرجات, فرص استثنائية, التقارير والنماذج
- Hidden (correctly): التخصصات, المستخدمون, التسجيل والقبول, الأدوار, الفصول الدراسية, طلبات إيقاف قيد, تسجيلات الرسائل, طلبات الإضافة/الحذف, سجل التدقيق, طلبات المناقشة, الخريجون

**This proves the `shouldRegisterNavigation()` filtering pattern works in this codebase** — secretary's leak (C-3) is therefore not "Filament can't do it" but "the filter wasn't applied to Shield/Roles, Users, Audit Logs for the secretary role specifically." Strengthens C-3 severity and points the fix at the exact policy/registration files.

### New findings

**[M-16] Mixed Arabic + English in upload-grade-sheet modal.** *(Round 5)*
- **Surface**: faculty drills into a course offering, clicks `رفع ورقة الدرجات`.
- **Modal title**: `رفع ورقة الدرجات (Hard Copy)` — bracketed English.
- **Filepond drop zone**: `اسحب و ادرج ملفاتك أو تصفح Files` — "Files" left untranslated.
- **Smallest patch**: change the title in the Filament Action `->modalHeading('رفع ورقة الدرجات الورقية')`. For Filepond, publish the Arabic locale file and set it as the active translation (or override the specific string in the published config).
- **Severity**: Medium (faculty's only grade-entry path; brand inconsistency on a critical surface).

**[M-17] Faculty's "My Courses" page has 28 unlabeled links (axe `link-name`).** *(Round 5)*
- **Surface**: `/admin/my-courses` — each of the 14 rows has clickable status indicators (open-for-registration toggle, grades-status pill) rendered as `<a>` without `aria-label`. 14 rows × 2 indicators = 28 nodes.
- **Smallest patch**: in the resource's table columns, set `->extraAttributes(['aria-label' => 'حالة …'])` on the toggle/indicator cells.
- **Severity**: Medium (axe Serious; single page, but high volume).

**[M-18] Faculty dashboard widgets show non-faculty data.** *(Round 5)*
- **Surface**: `/admin` as faculty. Widgets shown: `الطلاب المستمرون (10)`, `طلبات القبول المعلقة (9)`, `درجات بانتظار الاعتماد (0)`, current semester, add/drop period.
- **Why this is a finding**: pending applications and global student counts aren't faculty's job. The widgets should swap to faculty-relevant ones: "My courses this semester (N)", "Grade sheets I haven't uploaded (N)", "Students enrolled in my courses (N)", "Survey results awaiting my review (N)".
- **Smallest patch**: register faculty-specific widgets in `AdminPanelProvider` via `->widgets([...])` conditional on `auth()->user()->role`, or filter the existing widgets to be `canView` only by super-admin/secretary.
- **Severity**: Medium.

**[L-16] Grade distribution modal lacks sum-to-100 validation feedback.** *(Round 5)*
- **Surface**: faculty `/admin/my-courses/1` → `توزيع الدرجات`. Two number inputs (coursework + final), each min=1 max=99. Persona can enter 60+60=120 and only learn on submit.
- **Smallest patch**: add a live `total = coursework + final` indicator below the inputs with green/red state. Or constrain via Filament's `->reactive()` + `->afterStateUpdated()` so the second input mirrors `100 - first`.
- **Severity**: Low (faculty does this once per offering, so failure rate is low; but trivial to fix).

### Faculty findings that reinforce existing items (no new severity)

- **M-10 (English course names)** confirmed again — every page faculty visits says `Advanced Mathematics / Digital Signal Processing / …`. The page title is `Advanced Mathematics — الفصل الدراسي الثاني 2025` — mixed Arabic/English em-dash.
- **M-13 (mixed Arabic+English empty state)** confirmed a third occurrence: `/admin/my-courses/1` shows `لا توجد student enrollments` as the empty-state heading. Same pattern as `لا توجد students` on the admin enrollment manager. Promote M-13 to "systemic translation gap" finding — same root cause across at least 3 pages.
- **C-1 (axe Critical select-name)** reproduced on faculty's dashboard — same chart-period combobox.
- **L-1 / L-3 / L-5 / L-7 / L-9 (Laravel brand, focus ring, font 404, landmark-unique, group toggle labels)** — all reproduce as faculty since they're system-wide.

## Coverage map (final — updated round 4)

| Thread | Status | Notes |
|---|---|---|
| **T1 — Daily intake (Applications)** | **Walked end-to-end** | Approval modal triggered exam-date prerequisite. Delete modal opened + cancelled. Bulk-select toolbar verified. |
| **T2 — Enroll student in offering** | **Walked** | Opened `/admin/course-offerings/22/enroll`, opened Add Student modal, observed default focus on Submit (vs Cancel for destructive). Didn't commit enroll (Filament Select options didn't render in DOM query, would have needed real interaction). |
| **T3 — Grade entry & publish** | **Walked (admin side)** | Drilled `/admin/grades/1/grades`, observed empty state, opened publish-final-grades modal (good copy with side-effects disclosure). |
| **T4 — Approve suspension** | **Walked (read-only)** | View page captured; only-record already approved, can't test pending-decision UI. |
| **T5 — Drill student record** | **Walked** | `/admin/students/1` deep-walked. No tabs; everything renders linearly. Hero only has تعديل + طباعة. |
| **Exam-date setter** | **Walked** | Modal opened, structure captured, cancelled. Closes H-6 loop. |
| **Keyboard-only T1** | **Walked** | 28 tabs to reach main content (no Skip-to-content link, sidebar-first tab order). |
| **Secretary role** | **Walked** | Logged in. Nav unchanged from super-admin. Confirmed read access to Shield/Roles, Users, Audit Logs → C-3. |
| **Student portal `/portal`** | **Walked (sampled)** | Logged in as `mohammed.zawai@example.com`. Dashboard + 5 nav pages audited. English H1 leak on 4 pages. 1 critical + 1 serious axe on suspension-request page. |

## Net findings (final)

After Round 4 + critique:
- **Critical: 3** (C-1, C-2, C-3-new)
- **High: 9** (H-1..H-7 from rounds 1-3, H-8 portal English titles, H-9 nav doesn't filter by role)
- **Medium: 13** (M-1..M-9, M-10, M-11, M-13, M-14, M-15)
- **Low: 11** (L-1, L-3, L-5, L-6, L-8, L-9, L-11, L-12, L-13, L-14, L-15)

Plus 5 downgraded (M-5, M-7, L-2, L-7, L-10) per the round-2 self-critique.

**Top 5 after Round 4:**
1. **[C-3] RBAC leak** — Secretary reads admin-only resources (Shield/Roles, Users, Audit Logs). Highest-impact finding of the entire audit.
2. **[H-3] Application view page has no decision actions.**
3. **[H-6] قبول مبدئي confirmation → post-commit error instead of pre-disabled button.**
4. **[C-1] 6 axe Critical violations across 4+ routes.**
5. **[H-8] Portal page H1s leak English class names** — most-student-visible issue.

## Round 2 — Interaction Manifest (Thread T1, end-to-end)

```
INTERACTION MANIFEST — /admin/applications  (Round 2 walk)
  Persona: Super Admin, time-pressed, Arabic native
  [✓] Click sidebar nav → /admin/applications (uid=5_17 in snapshot)
  [✓] Observe list: 9 pending applications, 4 inline action buttons per row
  [✓] Click `قبول مبدئي` on row 9 (uid=5_97)
  [✓] Verified confirmation modal opened (uid=7_0 dialog)
  [✓] Verified default focus on `إلغاء` (Cancel) — safe default (uid=7_4)
  [✓] Verified body text describes consequence: "سيتم إضافة الطالب إلى قائمة الامتحان التفاضلي"
  [✓] Click `تأكيد` (Confirm, uid=7_5)
  [✓] Observed result: toast appeared "يجب تحديد موعد الامتحان أولاً" (uid=8_1)
  [✓] Verified application row status unchanged ("قيد المراجعة" still)
  [✓] Verified nav badge unchanged (still 9)
  [✓] Click `حذف` on row 1 → modal opened with generic body "هل أنت متأكد من القيام بهذه العملية؟"
  [✓] Default focus on Cancel ✓, danger-colored Delete button ✓, generic copy ✗ (M-9)
  [✓] Press Escape → modal dismissed
  [✓] Click bulk-select on row 9 → toolbar appeared with selection count + "حذف المحدد" bulk action
  [✓] Console reads after each step (0 errors / 1 systemic Filament warning per page)
  [✓] Network reads: only Livewire POSTs, 0 5xx, 0 403/404 (except the dev-only font 404)
```

## Withdrawn from Round 1

- **[L-4] Bulk-select checkboxes on Applications list show no selection toolbar.** *Wrong* — Round 2 confirmed the toolbar appears on first selection with a `حذف المحدد` (Delete Selected) bulk action. Withdrawn.

## Routes axe scorecard (cumulative)

| Route | Critical | Serious | Moderate | Minor |
|---|---|---|---|---|
| `/admin` (dashboard) | 1 | 0 | 1 | 0 |
| `/admin/applications` | 0 | 0 | 1 | 1 |
| `/admin/applications/9` | 0 | 2 | 1 | 0 |
| `/admin/students/1` | 2 | 2 | 1 | 0 |
| `/admin/users` | 0 | 1 | 1 | 1 |
| `/admin/audit-logs` | 2 | 0 | 1 | 1 |
| `/admin/reports-page` | 1 | 0 | 1 | 0 |
| `/admin/exam-list-page` | 0 | 0 | 1 | 0 |
| `/admin/exceptional-opportunities-page` | 0 | 0 | 1 | 0 |
| `/admin/shield/roles` | 0 | 0 | 1 | 1 |
| `/admin/specializations` | 0 | 0 | 1 | 1 |
| `/admin/courses` | 0 | 1 | 1 | 1 |
| `/admin/semesters` | 0 | 0 | 1 | 1 |
| `/admin/courses-survey-results` | 0 | 1 | 1 | 0 |
| `/admin/discussion-requests` | 0 | 0 | 1 | 0 |
| `/admin/graduates` | 0 | 0 | 1 | 0 |
| `/admin/thesis-registrations` | 0 | 0 | 1 | 0 |
| `/admin/add-drop-requests` | 0 | 1 | 1 | 0 |
| **TOTAL** | **6** | **8** | **18** | **6** |

## Perfection Roadmap

### Quick Wins (24-48h)
- L-1 / L-2: set `APP_NAME` in `.env`.
- L-3: remove print button from Application view page.
- L-5: fix Vite font path.
- L-7 / L-9: add `aria-label`s to topbar, sidebar `<nav>`, and sidebar group toggle buttons.
- L-10: stronger `:focus-visible` ring.
- M-7 / part of C-1: aria-labels on icon-only pagination/select widgets.
- H-4: Arabic titles on `ReportsPage` + `CoursesSurveyResultsPage`.

### Structural (1-2 weeks)
- C-1 / C-2: systematic a11y sweep — replace Filament's default checkbox / select with `aria-label`ed wrappers.
- H-1 / H-2: re-tune the muted palette to pass WCAG AA contrast.
- H-3: lift application acceptance actions onto the view page header.
- H-5: write `InitialsAvatar` SVG component, kill `ui-avatars.com` dependency.
- H-6: disable acceptance buttons when exam-date prerequisite isn't met; add inline "Set exam date" affordance.
- H-7: `novalidate` on Filament forms so server-side Arabic validation renders instead of the browser-native English tooltip.
- M-1: collapse Applications row actions into a dots menu.
- M-2: rename or tooltip the two acceptance buttons.
- M-3: missing-documents warning banner.
- M-4: translate role labels + hide guard-name column on Shield Roles.
- M-6: status filter tabs on Suspension Requests, Discussion Requests, Thesis Registrations, Graduates.
- M-8: gender-resolved `MaritalStatus::label()`.
- M-9: name the entity in confirmation modal bodies.

### Advanced Polish (post-launch)
- M-5: upstream Filament infolist `<dl>` semantic wrapper.
- Wire a CI hook that runs axe-core against `/admin` on every PR.
- Playwright killer-flow tests for the 5 threads.

## Hold this in your hands

If this admin panel were a physical object I'd want it to feel like a well-thumbed Arabic ledger — leatherbound, tabbed, the secretary opens it and her finger knows where each form lives. Today, mostly, it does. The figma view-page redesign is real progress; muted pills + sectioned cards + breadcrumbs give it a confident, premium feel that I haven't seen on most academic admin tools. Where it falls short isn't the ambition — it's the last 5%. The brand still whispers "Laravel" in the tab and the top-left corner. The figma hero's quietest text is too quiet for actual reading. The application view page has all the information you need to decide and then makes you walk back to the list to actually do it. The accept button asks you to confirm and *then* tells you it can't proceed. The validation error suddenly switches to English the moment you're being corrected. None of these are hard fixes; they're tells that the design language is younger than the build pace. Spend a week on the Highs and this stops looking like a graduation project and starts looking like a system someone proudly demos.

## Self-critique pass (fresh sub-agent)

Drafted: 27  Kept: 22  Generic: 3  Duplicate: 2

**Downgraded (consider before acting):**

- **M-5 (Filament infolist dt/dd)** → GENERIC. Vendor-scope; app can't fix without upstreaming. Keep on the roadmap but don't treat as a Medium against this product.
- **M-7 (audit-log pagination button-name)** → DUPLICATE of C-1. Same root cause as the icon-button axe Critical; merge into C-1 patch list.
- **L-2 (sidebar brand "Laravel")** → DUPLICATE of L-1. Both driven by `APP_NAME` env; one fix solves both.
- **L-7 (Filament topbar landmark not unique)** → GENERIC. System-wide Filament default, applies to every Filament app; not a product-specific finding.
- **L-10 (default focus ring 1px)** → GENERIC. "Make focus ring stronger" with no app-specific anchor; treat as a future polish item, not a finding.

**Net actionable findings**: 22 (Critical 2 / High 7 / Medium 7 / Low 6).

## Notes

- Self-critique pass dispatched as a fresh sub-agent — results above.
- Round 2 was authorised explicitly: bulk-select toolbar verified, one approval committed (blocked by exam-date prerequisite — system rejected gracefully, no side effects), one delete dialog opened + dismissed, one form stress test discarded via Cancel.
- No code changed during this audit, per the user's instruction.
- Browser used: Chromium (remote-debugging :9222) attached via `chrome-devtools-mcp`. Sessions logged in as the seeded super-admin (`رئيس مكتب الدراسات العليا`).
