---
date: 2026-06-04
tags: [bug, postgres, filament, widget, sql-portability]
related:
  - "[[../daily-notes/2026-06-04-production-hardening-execution]]"
  - "[[../project-context/production-hardening-backlog]]"
---

# `YEARWEEK()` crashes the weekly applications chart on PostgreSQL

## Symptom
The admin dashboard's "طلبات القبول عبر الزمن" widget, switched to the **weekly** filter, would error in
production. `ApplicationsOverTimeWidget::getWeeklyData()` ran
`selectRaw('YEARWEEK(created_at, 1) as yw, ...')`. `YEARWEEK()` is **MySQL-only** — PostgreSQL (the prod
DB) has no such function, so the query throws. (The daily filter was fine: `DATE()` exists in both.)

## Root cause
SQL written against the local MySQL/SQLite mental model leaked a vendor-specific function into a query
that runs on Postgres in production. Latent because dev/test never exercised it on Postgres.

## Fix
Aggregate by calendar date (`DATE()` — portable across MySQL/Postgres/SQLite), then bucket the daily
counts into ISO weeks in PHP via `Carbon::parse($date)->startOfWeek()`, matching against the 12
pre-computed week-start Carbons. No vendor-specific SQL remains.

## Regression guard
`tests/Feature/ApplicationsOverTimeWidgetTest.php` — seeds applications across two weeks and asserts the
weekly buckets. Runs on the test SQLite DB, which **also lacks `YEARWEEK`**, so the old code would have
failed this test outright.

## Lesson
Prefer DB-portable SQL (`DATE`, `DATE_TRUNC`) or bucket in PHP. When a raw SQL function appears, check it
exists on the production driver (Postgres), not just local. Grep for other `selectRaw`/raw-function uses
when auditing for prod parity.
