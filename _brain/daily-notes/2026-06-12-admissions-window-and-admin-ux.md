---
date: 2026-06-12
tags: [daily, admissions, semester, filament, ux, applications]
related:
  - "[[2026-06-11-apply-admin-auth-polish]]"
  - "[[../project-context/architecture]]"
---

# 2026-06-12 — admissions window + admin UX polish

## What was done

1. **Application window (stop applications by toggle + deadline)** — applications are
   semester-scoped, so the window lives on the active `Semester`:
   - Migration adds `applications_open` (bool, default **true** to preserve prior
     always-open behaviour) + `application_deadline` (date, nullable) to `semesters`.
   - `Semester::acceptsApplications()` = `applications_open && (deadline null || today <= deadline)`.
     `archiveCascade()` also turns applications off.
   - Semester Filament form gains a "فتح التقديم" toggle + non-native "آخر موعد للتقديم" picker.
   - `/apply` `create()` passes `$applicationsOpen`; the form is replaced with a
     "باب التقديم مغلق حالياً" notice when closed; `store()` hard-blocks a stale/direct POST.
   - Tests: `ApplicationWindowTest`.

2. **Semester date pickers → non-native** — `start_date/end_date/add_drop_*` now use
   Filament's `native(false)` picker (fixes the native input opening at a stray year like
   2096; the Filament picker focuses the current month/year).

3. **Specialization tab counts follow the table's semester filter** (`ListApplications` +
   blade) — default (filter = active semester) → that semester only (new term reads 0);
   admin clears the filter → all-semester totals. New `getSpecializationTabs()`.

4. **Scheduled exam date auto-resets on a new active semester** — the session exam date is
   now paired with `scheduledExamSemesterId`; `ListApplications::mount()` clears it when the
   active semester changed, and the schedule/clear actions set the id. Tests: `ApplicationListTabsTest`.

5. **Save button locked until the form changes (all Edit pages)** — `$wire.$dirty()` flashed
   (cleared by Filament's reactive field syncs) and collides with the button's own
   `x-data="filamentFormButton"`. Replaced with a render-hook guard
   (`AppServiceProvider::saveButtonGuard`, `PanelsRenderHook::BODY_END`) that toggles a
   `data-save-locked` CSS class (opacity + pointer-events) driven by real `input`/`change`
   events and re-applied on Livewire `morphed`. Targets `form[wire:submit="save"]` only, so
   Create pages are untouched; covers every resource incl. Shield's Roles. Test: `SaveButtonDirtyStateTest`.

## Test-isolation fixes

- `Semester::flushActiveCache()` exposed + called in a global `beforeEach` (`tests/Pest.php`)
  so the memoized active semester doesn't leak across tests.

## Tests

- New suites pass: ApplicationWindow, ApplicationListTabs, SaveButtonDirtyState plus updated
  submission suites. Full run: 257 passing; the only failures are the 5 known pre-existing,
  unrelated ones (ExampleTest stale `/admin`, ExamListPrint PDF 500, ProductionHardening /
  StudentPortal 403→302). `TransferCourseEditTest` is intermittently flaky on random order
  (passes in isolation) — pre-existing isolation quirk, not from this work.

## Loose ends / next steps

- **Migration must run on production** (`php artisan migrate`) at deploy for the window columns.
- Senior-manager gap review done (verbally): top opportunities are Excel/CSV **exports**,
  **bulk reminders/announcements** (reuse WhatsApp+email; pair with the new deadline),
  **notification log viewer + queue + delivery status**, a **trends/analytics dashboard**, and
  two integrity guards (single-active-semester DB guard, transaction-safe enrollment numbers).
- Still open from before: prod `MAIL_*` unset, `LOG_LEVEL=debug`, pinned StudentResource modal bug.
