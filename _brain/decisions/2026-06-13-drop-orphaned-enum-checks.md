---
date: 2026-06-13
tags: [decision, postgres, enum, constraints, migrations]
related:
  - "[[../bugs-and-fixes/2026-06-13-pg-status-check-crash]]"
---

# Drop all orphaned enum-born CHECK constraints; enum cast is the source of truth

## Context
`$table->enum()` on Postgres creates a `<table>_<col>_check` constraint that later value/column
migrations don't keep in sync. This caused a prod crash (students.status) with more latent bombs
(thesis_registrations.status). The app already validates these columns via backed-enum casts.

## Decision
A single pgsql-only migration drops **every** CHECK constraint on the enum-born tables
(`semesters, applications, students, courses, student_enrollments, grades, suspension_requests,
thesis_registrations, clearance_requests, notification_logs, add_drop_requests, course_specialization`).
Done dynamically via `pg_constraint` so it also catches constraints renamed by `->change()`.
**Do NOT re-add "correct" DB-level checks** — that just recreates the same drift trap. The backed
enum + Eloquent cast is the single source of truth.

## Alternatives considered
- Patch only `students.status`: rejected — leaves thesis (and others) to page us next week.
- Re-create correct CHECK constraints: rejected — reintroduces the maintenance hazard; the user
  also said the DB engine may change, so DB-specific constraints are extra baggage.

## Consequences
- No DB-level enforcement of enum values; rely on the cast + form validation (already the case in
  practice). Acceptable for this app.
- The migration is irreversible (`down()` is a no-op) — re-adding the stale checks would reintroduce
  the crash.
- Portable: no-op on SQLite (dev) and MySQL (native ENUM altered by column migrations).
- Verification is prod-only (dev is SQLite). Run `migrate` on prod and exercise a status write.
