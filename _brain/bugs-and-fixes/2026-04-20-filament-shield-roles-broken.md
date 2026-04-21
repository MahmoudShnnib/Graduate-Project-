---
date: 2026-04-20
tags: [bug, shield, permissions, spatie, filament, auth]
related: [[../daily-notes/2026-04-20-task-session-multi-fix]], [[../project-context/gotchas]]
status: fixed
---

# Filament Shield roles broken — faculty & secretary see nothing

> **Status:** Fixed. T17 of the 2026-04-20 task session.

## Symptoms

- Faculty users (`users.role = 'faculty'`) log into `/admin` and see no `موادي` resource, no course offerings, no grades page.
- User tried manually assigning every Spatie role to the faculty user via the Shield UI — "nothing changed".
- Super admin worked fine (via `Gate::before` bypass).
- Secretary nominally worked but had zero actual Shield permissions — any resource with `ShouldHandleAuthorization` would silently hide.

## Root causes (two distinct, compounding)

### 1. Seeder used stale permission naming

[`database/seeders/RolesAndPermissionsSeeder.php`](../../database/seeders/RolesAndPermissionsSeeder.php) filtered permissions by snake_case names:

```php
Permission::where('name', 'like', 'view_%')
Permission::whereIn('name', ['create_student', 'update_grade', ...])
```

But Shield is configured with `permissions.case = 'pascal'` + `separator = ':'` (see `vendor/bezhansalleh/filament-shield/config/filament-shield.php:107-111`), so actual permission names are:

```
ViewAny:CourseOffering
Create:Student
Update:Grade
...
```

Every `where like 'view_%'` and `whereIn([...snake_case...])` matched **zero rows**. Roles got empty permission sets.

Shield permissions were regenerated at some earlier point when the naming convention was changed from snake to pascal, but the seeder was never updated.

### 2. User-creation paths never assigned the Spatie role

Three paths create Users but only set `users.role` (string column) — never `$user->assignRole(...)`:

- [`app/Actions/ApproveApplication.php:35-44`](../../app/Actions/ApproveApplication.php)
- [`app/Filament/Resources/Students/Pages/CreateStudent.php:97-106`](../../app/Filament/Resources/Students/Pages/CreateStudent.php)
- [`app/Filament/Resources/Users/Pages/CreateUser.php`](../../app/Filament/Resources/Users/Pages/CreateUser.php) (default `CreateRecord`, no `afterCreate` hook)

The `HasPanelShield` trait on `User` auto-assigns `panel_user` as a fallback when no Spatie role is assigned. So users 7–12 (created after the Apr 16 refactor) all ended up with `[panel_user]` in Spatie — a role that intentionally has zero permissions.

Combined effect:
- `role` column says `faculty` → passes first check in `MyCourseResource::canAccess()`
- `$user->can('ViewAny:CourseOffering')` → false (user has `panel_user`, which has no perms; even if they had `faculty`, the seeder hadn't given `faculty` any perms either)
- Resource hidden, navigation empty.

The [Hybrid role system gotcha](../project-context/gotchas.md#hybrid-role-system) predicted this — we were checking both systems inconsistently, and the seeder miswiring stayed hidden because super_admin's `Gate::before` masked the problem during dev.

## Fix

Five files changed:

### 1. [`database/seeders/RolesAndPermissionsSeeder.php`](../../database/seeders/RolesAndPermissionsSeeder.php) — rewritten

- Permission filters use pascal `:` format.
- `super_admin` → `Permission::all()`.
- `secretary` → all `ViewAny:*` + `View:*`, plus explicit create/update/delete on students, enrollments, grades, requests, courses, etc.
- `faculty` → `ViewAny:Grade`, `View:Grade`, `Create:Grade`, `Update:Grade`, `ViewAny:Student`, `View:Student`, `ViewAny:CourseOffering`, `View:CourseOffering`. Row-level ownership (faculty can only enter grades for *their own* courses) is enforced in the resource, not via Shield.
- `student` → no Shield permissions (student panel gated via `EnsureStudentRole` middleware + role column, not policies).
- Added `repairUserRoles()` — walks all users, `syncRoles([role_column])` for known values. Idempotent.

### 2. [`app/Actions/ApproveApplication.php`](../../app/Actions/ApproveApplication.php)

Added `$user->syncRoles(['student'])` immediately after `User::create(...)` inside the DB transaction.

### 3. [`app/Filament/Resources/Students/Pages/CreateStudent.php`](../../app/Filament/Resources/Students/Pages/CreateStudent.php)

Same — `syncRoles(['student'])` after `User::create`.

### 4. [`app/Filament/Resources/Users/Pages/CreateUser.php`](../../app/Filament/Resources/Users/Pages/CreateUser.php)

Added `afterCreate()` hook: syncs Spatie role to `role` column value (only for the 4 known role strings).

### 5. [`app/Filament/Resources/Users/Pages/EditUser.php`](../../app/Filament/Resources/Users/Pages/EditUser.php)

Added `afterSave()` hook — same logic, so editing a user and changing their `role` column also resyncs Spatie.

### 6. Data repair ran

`php artisan db:seed --class=RolesAndPermissionsSeeder` — recomputes role permissions **and** resyncs all existing users' Spatie role from column.

## Verification

Post-fix state (from tinker):

```
Role permission counts:
  super_admin: 178
  secretary: 58
  faculty: 8
  student: 0
  panel_user: 0

All 12 users: role column ↔ Spatie role match.

soad@ly.com (faculty): can(ViewAny:CourseOffering)? YES, can(ViewAny:Grade)? YES
secretary@college.edu: can(ViewAny:Student)? YES, can(Create:Student)? YES, can(Delete:Student)? YES
```

All 55 tests still pass.

## Regression guards

The seeder is idempotent — re-running it is safe and self-healing if new users drift. The `afterCreate`/`afterSave` hooks on `Create/EditUser` prevent the drift at its source for future admin-created users. The explicit `syncRoles` calls in `ApproveApplication` + `CreateStudent` cover programmatic user creation.

**If Shield regenerates permission names again** (e.g. someone flips `filament-shield.permissions.case` from `pascal` to `snake`), the seeder will silently match zero again. The gotchas note should flag this.

## Follow-up / loose ends

- Consider removing the hybrid `role` column entirely and relying on Spatie only. The column exists "for test environments" per [User.php:74 comment](../../app/Models/User.php) but adds a consistency burden. Logging as a potential [[../decisions/README|future decision]].
- The `panel_user` role is now effectively unused (since every user has their "real" role). Could be removed, but `HasPanelShield` trait assumes it exists — safer to leave.
