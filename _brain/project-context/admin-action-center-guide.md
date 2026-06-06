---
tags: [project-context, dashboard, widget, filament, admin, action-center, durable]
related:
  - "[[architecture]]"
  - "[[design-conventions]]"
  - "[[../daily-notes/2026-06-04-admin-action-center-design]]"
---

# Admin Dashboard — "بانتظار إجرائك" Action Center — durable reference

A pinned-to-top dashboard widget that surfaces everything **waiting for a decision** from
the super_admin / secretary, each as a clickable card linking to the relevant queue. The
counts reuse the exact same "pending" definitions as the existing sidebar nav badges, so
the dashboard and the nav never disagree.

> **STATUS (2026-06-04): IMPLEMENTED & TESTED, UNCOMMITTED.** Built at
> `app/Filament/Widgets/ActionCenterWidget.php` (sort -11, top of dashboard). The dedup
> sub-decision in §4 was applied: "pending applications" + "pending grades" were REMOVED
> from `StatsOverviewWidget`. Test: `tests/Feature/ActionCenterWidgetTest.php` (4). Left
> uncommitted for review per [[no-auto-commit]].

## 1. The widget

- **File (planned):** `app/Filament/Widgets/ActionCenterWidget.php`, extending Filament's
  `Filament\Widgets\StatsOverviewWidget` (same base as `StatsOverviewWidget.php`).
- **Placement:** low `protected static ?int $sort` so it renders **above** the existing
  `StatsOverviewWidget` on `/admin`. (Discovered automatically via the panel's
  `discoverWidgets()` — no manual registration needed.)
- **Visibility:** `canView()` returns `auth()->user()?->role !== 'faculty'` — identical to
  `StatsOverviewWidget`, i.e. super_admin + secretary only in the admin panel. (Matches the
  established widget-visibility convention in this file. RBAC *gates* elsewhere prefer
  Shield — see [[feedback_prefer_shield_over_role_strings]] — but widget `canView` follows
  the sibling widget.)

## 2. The cards

Each is a `Stat::make($label, $count)->icon(...)->url(...)->color($count > 0 ? 'warning' : 'gray')`.
**All cards always render**; a category with nothing pending shows `0` in muted grey
(decision: reassurance over inbox-zero hiding). Count rules mirror each resource's
`getNavigationBadge()` verbatim:

| Card (Arabic) | Count query | Links to (`getUrl()`) |
|---|---|---|
| طلبات القبول | `Application::where('status', ApplicationStatus::Pending)` | `ApplicationResource` |
| طلبات إيقاف القيد | `SuspensionRequest` status `Pending` | `SuspensionRequestResource` |
| طلبات الإضافة والحذف | `AddDropRequest` status `pending` | `AddDropRequestResource` |
| تسجيلات الإشراف | `ThesisRegistration` status `Pending` | `ThesisRegistrationResource` |
| طلبات المناقشة | `DiscussionRequest` status `Pending` | `DiscussionRequestResource` |
| استبيان المقررات | `CourseSurvey` `is_processed = false` for `Semester::activeId()` | `CoursesSurveyResults` page |
| الفرص الاستثنائية | `ExceptionalRequest` status `Pending` | `ExceptionalOpportunitiesPage` |
| درجات بانتظار الاعتماد | `Grade::where('status', GradeStatus::PendingApproval)` | `GradeResource` |

- **Links** use the resource/page `::getUrl()` (and `::getUrl('index')` for resources) —
  never hardcoded `/admin/...` strings. They land on the index; the resource's own default
  view + badge already foreground the pending rows. (Pre-applied status filters via
  `?tableFilters[...]` are a later polish, deliberately out of v1.)

## 3. Where to edit

- **Add / remove a category** → add or delete one `Stat` in `ActionCenterWidget::getStats()`.
  If the count rule should match a nav badge, copy that resource's `getNavigationBadge()`
  query so the two stay in lock-step. (Consider extracting the rule to a shared method on
  the model/resource if you find yourself copy-pasting a third time.)
- **Change a label / icon / color** → that `Stat`'s chained calls.
- **Change who sees it** → `canView()`.
- **Reorder vs other widgets** → `$sort`.
- **Zero-handling** → the `->color()` ternary (and add `->url(null)` on zero if you ever
  want zero cards non-clickable).

## 4. Relationship to `StatsOverviewWidget` (open sub-decision)

`StatsOverviewWidget` today shows **pending applications** and **pending grades** alongside
student counts + current semester. The Action Center owns those same two action items.
**RESOLVED (2026-06-04): moved out.** "pending applications" + "pending grades" were removed
from `StatsOverviewWidget`, so each number lives in exactly one place — Action Center =
"things to act on", StatsOverviewWidget = pure info (student status counts + active
semester).

## 5. Performance

~8 simple indexed `COUNT` queries per dashboard load — same order of cost as the nav badges
that already run on every admin page. Acceptable; no caching needed. (Status/FK columns are
covered by the 2026-06-04 performance-index migration.)

## 6. Testing (planned)

`Livewire::test(ActionCenterWidget::class)`:
- seeded pending records → each card shows the right count; empty category shows `0`.
- visible for secretary / super_admin (`adminUser()` helper), hidden for faculty.
