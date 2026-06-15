---
date: 2026-06-15
tags: [decision, semester, database, observer, go-live]
related:
  - "[[../project-context/go-live-readiness-audit]]"
  - "[[../daily-notes/2026-06-15-go-live-fixes-implementation]]"
---

# Defer the single-active-semester partial unique index (M3)

## Context
The go-live audit (M3) recommended two changes to enforce the one-active-semester invariant:
1. `Semester::active()` → `orderByDesc('id')` so a tie is resolved deterministically (newest wins)
   instead of silently picking the lowest id.
2. A **partial unique index** `... WHERE is_active` to enforce "at most one active row" at the DB.

## Decision
Shipped (1). **Deferred (2).**

## Why the index was deferred
The activation flow creates a transient two-active-rows state that a partial unique index would reject:
- `CreateSemester::mutateFormDataBeforeCreate` forces `is_active = true` on the **INSERT**.
- `SemesterObserver::created/updated → archivePeersOf` deactivates the previous active semester only
  **after** the write (this is deliberate — the team consolidated all is_active enforcement into the
  observer so every entry point behaves uniformly; see the comment in `EditStudent`/`SemesterObserver`).
- So at INSERT time both the new row and the old active row are `is_active = true`. A
  `WHERE is_active` unique index → the INSERT fails → **semester creation breaks**.
- Same hazard for factories/seeders that create multiple active semesters in one test.

Making the index safe requires reordering archive-peers to run **before** the activating write (e.g. in
`creating`/`updating`, handling the null-id case in `creating`), plus auditing factory/seeder usage.
That's a non-trivial change to a recently-stabilized, invariant-critical flow — the wrong risk to take
days before go-live for defense-in-depth whose practical value is low (the observer already guarantees
single-active; two-active is only ever transient within one request, and `orderByDesc('id')` now makes
even that transient state read deterministically).

## Consequences
- The invariant is enforced in app code (observer) + read deterministically (`orderByDesc('id')`), not
  by the DB.
- If the create-then-archive ordering is ever refactored to deactivate-before-activate, revisit adding
  the partial unique index as a cheap DB-level guarantee.
