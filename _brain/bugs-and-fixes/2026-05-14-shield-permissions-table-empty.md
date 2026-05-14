---
date: 2026-05-14
tags: [bug, shield, permissions, spatie, filament, seeder, recurrence]
related:
  - "[[2026-04-20-filament-shield-roles-broken]]"
  - "[[../project-context/gotchas]]"
status: fixed
---

# Shield UI showed 0 صلاحيات for every role (permissions table wiped — second occurrence)

## Symptoms

- `/admin/shield/roles` listed every role with `0` in the الصلاحيات column.
- The rest of the system felt fine: super_admin could do everything, faculty could see their courses, students could log in.

## Root cause

The `permissions` table was empty. `Permission::count() === 0` and consequently every `role.permissions` pivot was empty too.

The system kept working anyway because authorization in this project doesn't actually flow through Shield for the paths the user typically exercises:

- **super_admin** — `Gate::before(fn ($user, $ability) => $user->hasRole('super_admin') ? true : null)` in `AppServiceProvider` bypasses every policy. They never hit Shield.
- **faculty / student** — narrow checks against the `users.role` string column (`MyCourseResource::canAccess`, `EnsureStudentRole` middleware). Shield isn't consulted.
- **secretary** — the only role that actually depends on Shield permissions. Would have been the first to notice, but the user is mainly logged in as super_admin during dev.

This is a recurrence of [[2026-04-20-filament-shield-roles-broken]]. That bug was fixed by re-running `RolesAndPermissionsSeeder`, but the seeder only **maps** existing permissions to roles — it doesn't **create** them. When the permissions table is empty (e.g. after `migrate:fresh`), the seeder maps every role to an empty set and exits silently.

## Fix (this session)

1. `php artisan shield:generate --all --panel=admin --no-interaction` — regenerated 192 permissions across 17 policies for the 29 admin entities.
2. `php artisan db:seed --class=RolesAndPermissionsSeeder` — re-mapped roles and repaired user role drift.

Post-fix counts:

```
Permissions total: 192
  super_admin: 192
  secretary: 62
  faculty: 8
  student: 0
  panel_user: 0
```

## Regression guard

Wired self-heal into the seeder so this can't happen silently again. `RolesAndPermissionsSeeder::run()` now starts with:

```php
if (Permission::count() === 0) {
    Artisan::call('shield:generate', [
        '--all' => true,
        '--panel' => 'admin',
        '--no-interaction' => true,
    ]);
}
```

`migrate:fresh --seed` is now self-sufficient. The check is idempotent — if permissions already exist, `shield:generate` is skipped entirely.

## Why student shows 0 (not a bug)

The `student` role is intentionally empty. Student-panel access is gated by:

- `EnsureStudentRole` middleware on the `student` Filament panel — checks `$user->role === 'student'`
- `User::canAccessPanel()` — checks role + `is_active`
- The student panel exposes **plain `Filament\Pages\Page` subclasses**, not `Resources`. Pages don't auto-bind to policies, so there are no Shield permissions to grant.

If Shield ever introduced student-panel permissions, the seeder's `$student->syncPermissions([])` line would be where they'd go.

## What `panel_user` is (and why it's also 0)

`panel_user` is a fallback role injected by the `HasPanelShield` trait on the `User` model. Whenever a Filament panel boots and finds a user with no Spatie role, it auto-assigns `panel_user` as a graceful default — without it, every unconfigured user would 403 out of the panel.

The role is intentionally **permission-less**: it grants panel access (so the user sees the chrome) but no resources or actions (so they see an empty navigation). It's only useful as a "you exist but you haven't been configured yet" stub.

In practice every real user in this project has one of `super_admin / secretary / faculty / student`, so `panel_user` should never be the active role for anyone. It exists only because `HasPanelShield` assumes it exists — removing it would require also removing/patching the trait, which isn't worth it.

## Files touched

- `database/seeders/RolesAndPermissionsSeeder.php` — added self-heal block at top of `run()`, plus `Artisan` import.
- 15 auto-generated `app/Policies/*.php` files — `shield:generate` regenerated them; Pint applied project formatting (whitespace, import ordering). No behavior changes.
