---
date: 2026-05-11
tags: [decision, semester, archive, invariant, observer]
related: [[2026-05-11-semester-archive-system]]
---

# Single-active-semester invariant: "active" and "not archived" are now synonyms

## Context

After the archive system shipped ([[2026-05-11-semester-archive-system]]) the user pointed out that the `الحالي` column (boolean is_active) and the `الحالة` column (archived/active badge) are the same data viewed two ways — because the project always operates on a single semester at a time and previous semesters auto-become history. The previous flow required two manual steps to make a new semester current: (a) flip `is_active=true` on the new one (which silently set `is_active=false` on the previous), then (b) optionally click "إغلاق الفصل" on the previous one to fully archive it. Step (b) was easy to skip, leaving the previous semester in a half-state.

## Invariant

**At any moment exactly one semester satisfies `is_active=true AND archived_at IS NULL`. Every other semester satisfies `archived_at IS NOT NULL` (and therefore `is_active=false`).**

"Active" ≡ "not archived" ≡ "the current operational semester". Whenever a semester becomes active, every other non-archived semester is archived as part of the same transition. The archive cascade is the canonical "previous semester ended" operation — no longer a separate manual click.

## Implementation

- New `Semester::archiveCascade()` model method — single source of truth for the close cascade (`archived_at + is_active=false + survey_open=false + offerings is_open=false`, all in a DB transaction). Idempotent.
- `SemesterObserver::created` and `::updated` now call `archivePeersOf($semester)` whenever the just-saved semester is active (created with `is_active=true` or `is_active` flipped `false → true`). `archivePeersOf` finds every other `archived_at IS NULL` row and invokes `archiveCascade()` on each.
- Removed the redundant pre-save `is_active=false` flip from `EditSemester::mutateFormDataBeforeSave` and `CreateSemester::mutateFormDataBeforeCreate`. Both file methods now defer entirely to the observer — every entry point (Filament create/edit page, seeders, factories, direct Eloquent updates, API endpoints) gets identical behavior.
- `archiveSemesterAction` now calls `$record->archiveCascade()` and uses `->successRedirectUrl(...)` to land the admin on the view page after archiving (the edit page would 403).
- Backfill migration (`2026_05_10_225526_backfill_archive_non_active_semesters`) runs `archiveCascade()` on every existing `is_active=false AND archived_at IS NULL` row so the historical data set matches the new invariant.
- Dropped the redundant `الحالي` IconColumn from the index table — `الحالة` badge (نشط/مؤرشف with lock-open/lock-closed icon) carries both signals.
- Dropped the redundant `is_active` and `survey_open` `IconEntry`s from the view infolist. Replaced with one `TextEntry` showing `نشط` or `مؤرشف منذ {date}`.

## Read-only archived offerings

When the parent semester is archived, the `EnrollStudents` page no longer 403s — the offering becomes a read-only snapshot showing enrolled students and their grades (coursework / final / total / letter, eager-loaded via `activeEnrollments.grade`). Every write action (`toggle_open`, `survey_students`, `retake_requests`, `enroll_manual`, `unenroll`, `unenroll_selected`) is `->visible(fn () => ! $this->isArchived())`. The `print_students` action stays available — printing is read-only too.

## Alternatives considered

- **Auto-archive only the previously-active semester, not all non-archived ones.** Rejected — would leave in-limbo semesters (`is_active=false AND archived_at IS NULL`) silently violating the invariant for arbitrarily long. The "sweep all" approach self-heals.
- **Treat `is_active=false` as semantically archived without setting `archived_at`.** Rejected — would diverge data from behavior (lockdowns key off `archived_at`); cleaner to have a single source of truth.
- **Ternary badge: نشط / مجدول (future scheduled) / مؤرشف.** Rejected (per user) — single-faculty workflow doesn't plan semesters in advance; admin creates a semester at the moment it goes active.

## Consequences

- The "إغلاق الفصل" button is largely redundant during normal workflow — the previous semester auto-archives when the next one is activated. It remains useful when an admin wants to close the current semester without opening a new one immediately (e.g. between academic years).
- Creating a semester with `is_active=false` is technically allowed but leaves it in the unarchived-non-active state until another semester is activated (which sweeps it up). The badge will incorrectly read نشط during this brief limbo; admin should activate the new semester promptly or accept the visual quirk.
- Tests now operate on the new invariant: `SemesterObserverTest` and `SemesterArchiveTest` together pin the full state machine. 12 archive tests + 5 observer tests, all green.
- Pre-existing failures unchanged (5 fails in SuspensionRequestResubmitGuard/WarningSystem/ExceptionalRequest, all pre-Phase-7).

## Files touched (refinement pass)

- `app/Models/Semester.php` — `archiveCascade()` method
- `app/Observers/SemesterObserver.php` — `archivePeersOf()` called on activate
- `app/Filament/Resources/Semesters/Pages/EditSemester.php` — removed redundant pre-save flip
- `app/Filament/Resources/Semesters/Pages/CreateSemester.php` — removed redundant pre-create flip
- `app/Filament/Resources/Semesters/SemesterResource.php` — `archiveSemesterAction` uses `archiveCascade()` + `successRedirectUrl`; dropped `is_active` IconColumn; rebuilt infolist around a single status TextEntry
- `app/Filament/Resources/CourseOfferings/Pages/EnrollStudents.php` — removed `abort_if`; added `isArchived()` helper; gated every write action; added 4 grade columns with eager-loaded `grade` relation
- `database/migrations/2026_05_10_225526_backfill_archive_non_active_semesters.php` — one-shot data fix
- `tests/Feature/SemesterArchiveTest.php` — +2 cases pinning auto-archive-on-activate
