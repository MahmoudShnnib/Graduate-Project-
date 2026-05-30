---
date: 2026-05-16
tags: [daily, filament, widgets, dashboard, faculty, ux-audit-fix, m-18, rbac-view-scoping]
related:
  - "[[2026-05-15-ux-audit-admin]]"
  - "[[2026-05-16-c1-axe-critical-fixes]]"
  - "[[2026-05-16-h1-h2-light-mode-contrast]]"
  - "[[../decisions/2026-05-15-rbac-posture]]"
  - "[[../research/audit-2026-05-15/audit|audit report]]"
---

# 2026-05-16 — M-18 fix: faculty-specific dashboard widgets

## What was done

Audit finding **M-18** closed. Faculty dashboard (`/admin` when logged in as a faculty user) no longer shows super-admin oriented widgets. Built a faculty-specific `StatsOverviewWidget` subclass with the 3 stats the audit suggested + the current-semester chrome, and gated the 4 existing dashboard widgets so faculty doesn't see them.

User confirmed: no "survey results awaiting my review" widget (deliberately excluded).

## Files touched (5)

| File | Change |
|---|---|
| `app/Filament/Widgets/Faculty/FacultyStatsOverviewWidget.php` | **new** — 4 stats: `مقرراتي هذا الفصل`, `أوراق درجات معلقة`, `طلابي`, `الفصل الحالي`. `canView()` returns true only for `role === 'faculty'`. |
| `app/Filament/Widgets/StatsOverviewWidget.php` | Added `canView()` returning `auth()->user()?->role !== 'faculty'`. |
| `app/Filament/Widgets/ApplicationsOverTimeWidget.php` | Same `canView()` gate. |
| `app/Filament/Widgets/ApplicationStatusWidget.php` | Same. |
| `app/Filament/Widgets/StudentsPerSpecializationWidget.php` | Same. |

## Stat definitions

| Stat | Query |
|---|---|
| `مقرراتي هذا الفصل` | `CourseOffering` where `faculty_id == me` AND `semester_id == active`. |
| `أوراق درجات معلقة` | Same scope, plus `paper_sheet_path IS NULL`. Description flips between "بانتظار الرفع" (warning color) and "كل الأوراق مرفوعة" (success color). |
| `طلابي` | `StudentEnrollment::active()->whereHas('courseOffering', fn => faculty_id+semester_id)`. Uses the existing `active()` scope per design-conventions ("presentation code always uses `active*` relations / scopes"). |
| `الفصل الحالي` | Same logic as the existing `StatsOverviewWidget` — semester name + add/drop window status. Kept on the faculty dashboard because the add/drop window is relevant info for faculty too (it tells them whether students can still drop their courses). |

## RBAC posture honored

This fix is the audit's M-18 (UX/relevance), but it also touches the same pattern as the [[../decisions/2026-05-15-rbac-posture]] decision: every widget that exposes super-admin data now has `canView()` gating. Faculty doesn't see application analytics, global student counts, or pending-grade-approval counts. Pattern parallels what we'll need for C-3 (Shield/Roles, Users, Audit Logs resources).

The 4 existing widgets are still visible to **super_admin** and **secretary** (everyone except faculty). That preserves existing behavior for those roles.

## Verification

- `vendor/bin/pint --dirty --format agent` → pass.
- `php artisan optimize:clear` → clean (widget discovery refreshes).
- `php artisan tinker` smoke test confirmed:
  - `FacultyStatsOverviewWidget::canView()` returns `false` with no auth, `true` for `cs.faculty@example.ly`.
  - `StatsOverviewWidget::canView()` correctly returns `false` for the same faculty user.
- `php -d memory_limit=4096M artisan test --compact` → **158 passed (337 assertions)**. (Memory creep continues — this run needed 4GB. Worth investigating after this audit pass.)
- Browser walk pending:
  1. Log in as `cs.faculty@example.ly` / `password` → visit `/admin`.
  2. Expected widgets: AccountWidget chrome + FacultyStatsOverviewWidget with the 4 stats only.
  3. Stats should reflect this faculty's offerings (`د. عبدالله الورفلي` teaches CS faculty courses). Verify the `طلابي` count matches the sum of enrollments across all CS faculty offerings in the active semester.
  4. Log out and log in as super-admin → confirm the dashboard still shows all 4 original widgets.
  5. Log in as secretary → same — should be unchanged from before.

## Audit progress

- ✓ Wave 3 (H-3, H-6) — done.
- ✓ C-1 — done.
- ✓ H-1, H-2 — done.
- ✓ **M-18 — done this session.**

User's current queue: **all 5 items closed.** Audit fix-plan has these remaining items unaddressed:
- **Wave 2 — C-3 RBAC leak** (highest-impact remaining: security finding).
- **Wave 1 — brand + cheap a11y** (5 fixes, ≤30 min — `APP_NAME`, focus ring, Vite font 404, sidebar group toggle labels).
- H-4, H-7, H-8, H-9 (Arabic-first leaks).
- H-5 (ui-avatars.com privacy/perf — user wanted more context first).
- M-1..M-17 long tail.
- L-1..L-16 long tail.

## Next session anchors

- **C-3 RBAC leak** — highest-priority outstanding finding ([[../decisions/2026-05-15-rbac-posture]] has the full context). Fix: extend the pattern used in `MyCourseResource::shouldRegisterNavigation()` / `canAccess()` to `RoleResource`, `UserResource`, `AuditLogResource` (secretary should not read those).
- **`isAddDropOpen()`** — invoked by both `StatsOverviewWidget` and the new `FacultyStatsOverviewWidget`. If that logic ever changes, both call sites need to keep working — search for it before refactoring.
- **Test memory** — went from 512MB to 4096MB needed in 4 sessions. Likely a factory loop or test setUp accumulating. Defer until current audit-fix queue is done.
- **`Semester::current()` candidate** — both widgets do `Semester::where('is_active', true)->first()` inline. Worth a `Semester::current()` static helper sometime. Not in scope today.
