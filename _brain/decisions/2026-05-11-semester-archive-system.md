---
date: 2026-05-11
tags: [decision, semester, archive, schema, observer, filament]
related: [[../daily-notes/2026-05-10-multi-phase-fixes]]
---

# Semester archive: explicit `إغلاق الفصل` button, permanent one-way, enriched view

## Context

Once a semester ends, admins need a frozen historical record of what happened: which applications were decided, which courses were opened, who enrolled, what grades were assigned. Until now there was no concept of "closed" — a semester just had `is_active=false` and could still be edited, re-activated, and surveys re-opened. No data was archived; you'd have to mentally reconstruct from timestamps.

## Decision

A semester has two states: **active** (default) and **archived** (terminal). Archiving is explicit, manual, and permanent. Once archived, the semester is read-only for everything except viewing.

Implementation answers the four design questions the user picked:

1. **Trigger**: explicit admin button (`إغلاق الفصل`, danger color, confirmation modal). No auto-archive based on `end_date`.
2. **Applications linkage**: new nullable FK `applications.semester_id`, populated by an `ApplicationObserver` whenever an application transitions to `Accepted`/`Rejected` (one-time set, never overwritten). One-shot backfill command `applications:backfill-semester` infers from decision timestamps for legacy rows.
3. **Reopen**: never. `archived_at` is set once and the archive action self-hides afterwards. Even direct URL edit attempts 403.
4. **View**: same `/admin/semesters/{id}` page, enriched. Archive-only sections appear when archived; active semesters still show the lean view.

## Schema

Two migrations added today:

- `semesters.archived_at` — nullable `timestamp`, the "closed" flag.
- `applications.semester_id` — nullable FK to `semesters`, `ON DELETE SET NULL`. Set automatically by the observer at decision time.

## Lockdown rules (when `semester.archived_at IS NOT NULL`)

| Surface | Behavior |
|---|---|
| `archiveSemesterAction` | Hidden (one-way) |
| `openSurveyAction` | Hidden |
| `editSurveyCoursesAction` | Hidden |
| `closeSurveyAction` | Hidden |
| `EditAction` in list + `EditSemester` page | Hidden in table; direct URL 403s via `mount()` guard |
| `DeleteAction` in list | Hidden |
| `is_active` toggle on form | Disabled + not dehydrated (can't flip back) |
| Publish-grades header actions on `ViewSemester` | Hidden (`getHeaderActions()` returns `[]`) |
| `CourseOfferingsRelationManager` (Create / toggle-open / Edit / Delete) | All actions hidden via `parentIsArchived()` |
| `EditCourseOffering` page (direct URL) | 403 via `mount()` guard |
| `EnrollStudents` page (direct URL) | 403 via `mount()` guard |
| `CourseOfferingResource` index list | Already pinned to active semester only (Phase 2) — archived offerings invisible |
| Course offerings within the semester | All `is_open` flipped to false on archive (cascade in DB transaction) |
| Existing enrollments + grades | **Preserved** as historical data — never deleted |

## Archive view enrichment

The semester view page registers a new `DecidedApplicationsRelationManager` whose `canViewForRecord()` returns true only when `isArchived()`. Active semesters never see it; archived ones get a read-only list of accepted/rejected applications belonging to that semester. The existing `CourseOfferingsRelationManager` continues to show the offerings on both active and archived semesters (only the action buttons differ).

A new `الحالة` column on the index table renders a `مؤرشف`/`نشط` badge so admins can spot archived rows at a glance.

## Alternatives considered

- **Auto-archive when `end_date` passes**. Rejected (per user choice) — admin retains control; bad dates would otherwise auto-lock the wrong semester.
- **Infer apps→semester via decision-date windows instead of a FK column**. Rejected (per user choice) — fragile across overlapping or missing date ranges; cleaner data wins.
- **Allow super_admin to reopen**. Rejected (per user choice) — keeps invariants simple. If reopen is ever needed, a separate reverse migration is fine.
- **Separate `/admin/semesters-archive/{id}` page**. Rejected (per user choice) — same URL with conditional sections is less routing surface and less confusing.

## Files touched

**Migrations** (new)
- `database/migrations/2026_05_10_221405_add_archived_at_to_semesters_table.php`
- `database/migrations/2026_05_10_221406_add_semester_id_to_applications_table.php`

**Models**
- `app/Models/Semester.php` (cast `archived_at`, new `applications()` / `suspensionRequests()` relations, `isArchived()` helper)
- `app/Models/Application.php` (fillable + `semester()` relation)

**Observers**
- `app/Observers/ApplicationObserver.php` (new) — captures `semester_id` at decision time
- `app/Providers/AppServiceProvider.php` — register `ApplicationObserver`

**Console**
- `app/Console/Commands/BackfillApplicationSemesters.php` — one-off backfill via `php artisan applications:backfill-semester`

**Filament**
- `app/Filament/Resources/Semesters/SemesterResource.php` — `archiveSemesterAction()` factory, archive-aware visibility on every existing survey action, `is_active` disabled on archived, new `الحالة` column, hide list-row edit/delete on archived
- `app/Filament/Resources/Semesters/Pages/EditSemester.php` — `mount()` 403 guard
- `app/Filament/Resources/Semesters/Pages/ViewSemester.php` — `getHeaderActions()` returns `[]` on archived (hides publish-grades + edit)
- `app/Filament/Resources/Semesters/RelationManagers/DecidedApplicationsRelationManager.php` (new) — only visible on archived semesters
- `app/Filament/Resources/Semesters/RelationManagers/CourseOfferingsRelationManager.php` — `parentIsArchived()` helper; all action visibility gated
- `app/Filament/Resources/CourseOfferings/Pages/EditCourseOffering.php` — `mount()` 403 guard
- `app/Filament/Resources/CourseOfferings/Pages/EnrollStudents.php` — `mount()` 403 guard

**Tests**
- `tests/Feature/SemesterArchiveTest.php` (new) — 10 cases covering archive cascade, lockdown visibility, observer semester capture, isArchived helper

## Conventions to remember (apply to future archive-like systems)

1. **Decision-time capture via observer beats decision-point patches.** When you need to record "what was true at the moment X happened," put it in a `saving()` observer on the model — it catches every code path that writes that status, including future ones, without touching every call site.
2. **Lockdown is a checklist, not a single switch.** Form fields, table actions, header actions, direct-URL `mount()` guards, relation manager actions — every entry point needs its own gate. The list table being filtered to active doesn't lock direct URLs.
3. **Cascade closures in a DB transaction.** The archive action wraps `archived_at + is_active=false + survey_open=false + offerings is_open=false` in `DB::transaction` so a partial failure leaves no half-archived state.
4. **Preserve historical data; only flip operational flags.** Don't delete enrollments/grades/offerings — flip `is_open`, never destroy records.
