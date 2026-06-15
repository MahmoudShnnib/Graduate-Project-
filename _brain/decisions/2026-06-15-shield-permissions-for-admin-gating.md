---
date: 2026-06-15
tags: [decision, rbac, shield, filament, permissions, faculty]
related:
  - "[[../daily-notes/2026-06-15-shield-admin-gating]]"
  - "[[2026-05-15-rbac-posture]]"
---

# Admin-panel access gating moves from `users.role` strings to Shield

## Context

Custom Filament Pages and Widgets gated visibility with hardcoded checks on the
legacy `users.role` **column** — e.g. `auth()->user()?->role !== 'faculty'`. The
project already runs `bezhansalleh/filament-shield` (Spatie roles + permissions)
as the source of truth, and the user wants **all admin-side "what can they see/do"
decisions to come from Shield**, not the column. The column is a legacy duplicate
of the Spatie role and the two can drift.

## Decision

Convert every admin-panel access gate to Shield. Two patterns, by intent:

1. **"Hide an admin-only surface from faculty" → gate on a permission faculty LACK.**
   Shield generates a `View:{ClassName}` permission for every page **and** widget
   in the admin panel (verify names with `php artisan permission:show`). Faculty
   hold only `ViewAny:CourseOffering` + `View:CourseOffering`.
   - `ExamListPage` → `can('View:ExamListPage')`
   - `CoursesSurveyResults` → `can('View:CoursesSurveyResults')`
   - `ExceptionalOpportunitiesPage` → `can('View:ExceptionalOpportunitiesPage')`
   - `DashboardOverviewWidget` → `can('View:DashboardOverviewWidget')`
   - `ActionCenterWidget` → `can('View:ActionCenterWidget')`
   - `CourseOfferingResource` (a *Resource*, no per-class page perm; faculty SHARE
     its `ViewAny:CourseOffering`) → `can('Update:CourseOffering')` — faculty have
     View but not Update, so this hides it from them while keeping the model perm.

2. **"Faculty-only / super-admin-only surface" → use `hasRole(...)`, NOT a permission.**
   `Gate::before` makes super_admin pass *every* `->can()`, so a permission cannot
   express "only faculty see this" (super_admin would leak in). Use the Spatie role
   (`hasRole('faculty')` / `hasRole('super_admin')`) — still Shield-sourced (its
   roles table), just the role side.
   - `FacultyStatsOverviewWidget` → `hasRole('faculty')`
   - `MyCourseResource` → `hasRole('faculty') && can('ViewAny:CourseOffering')`
   - `ViewCourseGrades`: row-ownership 403 + "faculty get no publish/approve actions"
     → `hasRole('faculty')`; "super_admin may edit an approved grade" → `hasRole('super_admin')`.

## Out of scope (legitimately role-column / identity, NOT converted)

`EnsureStudentRole` middleware, `User::canAccessPanel`, `UserObserver` notification
scoping, `PrintController` ownership, `PasswordResetController` redirect target, and
`UserInfolist` (which inspects the **displayed record's** role to choose which
academic fields to render — data display, not access control).

## Consequence — factory now syncs the Spatie role

Tests built faculty via `User::factory()->faculty()` which set only the `role`
column, so `hasRole('faculty')` was false and gates denied access. `UserFactory`
now assigns the matching Spatie role in `afterCreating` for `withRole()` and
`faculty()` (column `secretary` → Spatie `manager_assistant`), mirroring
production's `RolesAndPermissionsSeeder::repairUserRoles()`. Tests asserting a
**secretary** can reach a permission-gated surface must use the `secretaryUser()`
helper (seeds real permissions) — a bare `withRole('secretary')` fixture has the
role but no permissions.

Regression guard: `tests/Feature/ShieldAccessControlTest.php` — proves the exam
list is governed by `View:ExamListPage` (a faculty-column user *granted* the perm
gets in) and that faculty-only surfaces stay hidden from super_admin. Full suite:
354 passing.

## Alternatives considered

- *Grant faculty their own `View:FacultyStatsOverviewWidget` and gate on `can()`*
  — rejected: super_admin's `Gate::before` bypass would then show super_admin the
  faculty-only widget, changing behavior.
- *Leave the column checks* — rejected: violates the single-source-of-truth RBAC
  posture and the user's explicit instruction.
