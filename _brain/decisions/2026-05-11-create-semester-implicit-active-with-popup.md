---
date: 2026-05-11
tags: [decision, semester, archive, ux, filament, create-form]
related:
  - "[[2026-05-11-single-active-semester-invariant]]"
  - "[[2026-05-11-semester-archive-system]]"
  - "[[../bugs-and-fixes/2026-05-11-filament-submit-bypasses-confirmation-modal]]"
  - "[[../daily-notes/2026-05-10-multi-phase-fixes]]"
---

# Create-semester form is implicitly "this is the new active semester" — no toggle, confirmation popup instead

## Context

After landing the [[2026-05-11-single-active-semester-invariant]] (active ≡ not-archived, only one at a time), three UI surfaces still asked the admin about `is_active` directly:

1. **List table** had an `إغلاق الفصل` row action on every non-archived row. Redundant — under the invariant, the previous semester auto-archives the moment the next one is opened, so the manual button is only meaningful in the rare "close current without opening a new one" case.
2. **Create form** had an `الفصل الحالي` Toggle. Misleading — creating a new semester always means opening a new term, so the value is always `true`. A toggle suggested otherwise.
3. **Edit form** had the same `الفصل الحالي` Toggle. Also misleading — the only non-archived row is by definition the active one; toggling it to `false` would create an unreachable intermediate state.

## Decision

Drop the toggle entirely. Drop the row action from the list. Replace with a confirmation popup on the **Create** button that names what's about to happen.

### What changed

- **`SemesterResource::form()`** — `Toggle::make('is_active')` is gone (both create and edit). The form no longer asks the admin to choose.
- **`SemesterResource::table()` recordActions** — `archiveSemesterAction()` is removed from the list row menu. It stays on the **edit** form's `Actions::make([...])` bar for the rare "close without opening a new one" case.
- **`CreateSemester::mutateFormDataBeforeCreate()`** — forces `$data['is_active'] = true` unconditionally on every new semester.
- **`CreateSemester::getCreateFormAction()`** — rebuilt from scratch (see [[../bugs-and-fixes/2026-05-11-filament-submit-bypasses-confirmation-modal]]) so the confirmation modal actually fires. The modal description is dynamic:
  - If a currently-active semester exists: `"سيُصبح الفصل الجديد هو الفصل الحالي وسيتم إغلاق وأرشفة الفصل الحالي ({name}) تلقائياً. التسجيلات والدرجات الحالية ستُحفظ كسجل تاريخي."`
  - If not: `"لا يوجد فصل حالي. سيُصبح هذا الفصل هو الفصل الحالي."`
- Submit label: **إنشاء وتنشيط** (vs. the default **Create**), naming both halves of the action.

## Alternatives considered

- **Keep the toggle, default it to `true`.** Rejected — even checked, the toggle is asking a question the admin doesn't get to meaningfully answer. The form lies about the model.
- **Skip the popup, just auto-archive silently.** Rejected — the auto-archive is a *destructive*-feeling operation (the previous semester becomes read-only forever). User-facing acknowledgement matters. Surfacing it in the modal description doubles as a sanity check ("am I sure I want to close فصل خريف 2025?").
- **Move the `إغلاق الفصل` button to the view page header instead of the edit form.** Rejected — the edit form already hosts the analogous `Actions::make([...])` row of survey controls; co-locating archive there matches the existing pattern.

## Consequences

- Creating a new semester is now a **single, intentional action**: fill the form → click Create → read the warning → confirm. The auto-archive of the previous semester is the same atomic step from the admin's POV.
- The `إغلاق الفصل` button is still available, but only from the edit form. For 99% of workflows admins won't click it — opening the next semester does the close-and-archive in one.
- The `is_active` column on the `semesters` table is still populated and queried by the rest of the system. The only change is that the admin doesn't toggle it directly anymore — it's set by `mutateFormDataBeforeCreate` on create, and flipped by `Semester::archiveCascade()` on archive.

## Test coverage

`tests/Feature/CreateSemesterConfirmationTest.php` — 4 cases pinning:
1. The Create action's modal heading is set (indirect signal that confirmation is configured).
2. The modal description names the current active semester when one exists.
3. The modal description handles the no-active-semester case.
4. After confirming + creating, the new row is `is_active=true` and the previous active is auto-archived.

All pass.

## Files touched

- `app/Filament/Resources/Semesters/SemesterResource.php`
  - Dropped `Toggle::make('is_active')` from `form()`.
  - Dropped `self::archiveSemesterAction()` from `table()->recordActions()`.
  - Removed unused `Toggle` import.
- `app/Filament/Resources/Semesters/Pages/CreateSemester.php`
  - `mutateFormDataBeforeCreate()` forces `is_active=true`.
  - `getCreateFormAction()` rebuilt as a confirmation Action (see linked bug-fix note for the why).
- `tests/Feature/CreateSemesterConfirmationTest.php` (new, 4 cases).
