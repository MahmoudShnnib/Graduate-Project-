---
date: 2026-06-13
tags: [seeders, testing, dev, factories]
related: [[project-overview]]
---

# Getting dev/test data the right way

> When you want data to test the live app with (students, applications, grades, faculty courses),
> use a **dedicated dev seeder** — never ad-hoc `tinker` factory writes against the dev sqlite.

## What the default seeders give you
`php artisan db:seed` (or `migrate:fresh --seed`) runs `DatabaseSeeder`, which seeds only the
**skeleton**:

`RolesAndPermissionsSeeder → SettingsSeeder → SpecializationSeeder → SemesterSeeder → CourseSeeder → AdminUserSeeder`

i.e. roles/permissions, settings, 3 specializations, 1 semester, courses, and the admin user.
**No students, applications, enrollments, or grades** — the dummy-data seeders were removed before
deployment (2026-06-04). So the skeleton alone can't exercise the admissions/grade/faculty flows.

## The right way: a dedicated `DevDataSeeder`
Create `database/seeders/DevDataSeeder.php` that uses **factories inside a seeder** (the legit use of
factories — repeatable, version-controlled, wiped cleanly by `migrate:fresh`). It should generate a
realistic spread so every screen has something:
- applications in各 states (pending / initially_approved / accepted),
- a few students with enrollments,
- 1–2 course offerings assigned to the faculty user, with draft/pending/approved grades.

Run it explicitly: `php artisan db:seed --class=DevDataSeeder`.

**Keep it OUT of `DatabaseSeeder::run()`** so it can never run in production. Reset to a known state
with `php artisan migrate:fresh --seed` then the dev seeder.

## Why NOT ad-hoc tinker factories
`php artisan tinker` (and Boost's tinker) write to the **real dev sqlite**
(`database/database.sqlite`), not the test DB. Factory writes there:
- generate Faker junk (this is where the "maiores" specialization came from),
- fire observers/cascades (`Semester::factory()->create(['is_active'=>true])` archived the real
  active semester via the single-active invariant),
- persist with no automatic cleanup (vs a `:memory:` test that rolls back).

To **debug** a swallowed error, write a throwaway Pest test (`:memory:`, isolated) and `dump()` —
do not probe with tinker writes. See the 2026-06-13 incident in
[[../daily-notes/2026-06-13-gap-fixes-constraints-grades-faculty]].

Test login credentials live in the AdminUserSeeder + the memory note `reference_test_credentials`.
