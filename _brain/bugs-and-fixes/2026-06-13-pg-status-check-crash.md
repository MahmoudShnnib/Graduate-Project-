---
date: 2026-06-13
tags: [bug, postgres, migrations, enum, constraints, production]
related:
  - "[[../decisions/2026-06-13-drop-orphaned-enum-checks]]"
  - "[[2026-06-13-course-offering-edit-blocked]]"
---

# Postgres `students_status_check` crash on closing the survey

## Symptom
On prod (pgsql), clicking "اغلاق الاستطلاع" threw
`SQLSTATE[23514] ... violates check constraint "students_status_check"` on
`update students set status = 'inactive'`. Never reproduced locally (dev is SQLite, which
ignores CHECK constraints).

## Root cause
`create_students_table` declared `status` as `enum(['active','suspended','withdrawn','graduated'])`.
On Postgres `$table->enum()` compiles to a **CHECK constraint**. Later migrations
(`2026_04_21` suspended→inactive, `2026_04_23` restructure) changed the column to `string(20)` and
renamed the *values* but never dropped/updated the constraint. So prod still enforced the original
four values while `StudentStatus` now emits six. `SemesterObserver::processNonSubmittingStudents`
writes `inactive` on survey close → rejected. Prod was migrated on empty tables, so the rename
`UPDATE`s touched 0 rows and never tripped it until real data existed.

## Wider blast radius (the important part)
Audited **every** `enum()`-born column. Two columns had live value-drift from their constraint:
- `students.status` (the reported crash)
- `thesis_registrations.status` — enum now has `printed`, constraint doesn't → setting a thesis
  "printed" would crash prod the same way.
`applications.status` was also at risk (renamed via `->change()`).

## Fix
Migration `2026_06_13_061411_drop_orphaned_enum_check_constraints`: pgsql-only, queries
`pg_constraint` for every CHECK on the enum-born tables and drops them (`DROP CONSTRAINT IF EXISTS`,
catches renamed-by-`change()` constraints too). No-op on SQLite/MySQL. The backed-enum cast is now
the single source of truth — see [[../decisions/2026-06-13-drop-orphaned-enum-checks]].

## Regression guard
`SemesterObserverTest`: survey-close marks a non-submitting active student `Inactive` (and a
submitter stays `Active`). **Caveat:** SQLite can't exercise the pg DDL — the real proof is running
`php artisan migrate` on prod and closing the survey.
