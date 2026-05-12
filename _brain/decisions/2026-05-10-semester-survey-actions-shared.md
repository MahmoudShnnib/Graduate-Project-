---
date: 2026-05-10
tags: [decision, filament, refactor, semester, survey]
related: [[../daily-notes/2026-05-10-multi-phase-fixes]]
---

# Survey controls on the semester edit form are action buttons (same modal as the index page), not a Toggle

## Context

The semester edit form had a plain `Toggle::make('survey_open')` that just flipped a boolean on save. The semesters *list* page already had a much richer flow: a "فتح الاستطلاع" action button that opens a modal letting the admin pick which courses are available before opening the survey (writes the `semester_course` pivot, then sets `survey_open=true`). When the survey was open, the list also exposed "تعديل المقررات" + "إغلاق الاستطلاع" actions.

Editing a semester offered none of that — admin had no in-form way to set the survey course list.

## Decision

Replace `Toggle::make('survey_open')` on the edit form with the same three action buttons used on the list (`open_survey` / `edit_survey_courses` / `close_survey`). The buttons self-show/hide based on the current `survey_open` value:

- `survey_open = false` → only "فتح الاستطلاع" is visible.
- `survey_open = true` → "تعديل المقررات" and "إغلاق الاستطلاع" are visible.

Extracted all three actions into `public static` factory methods on `SemesterResource`:

- `openSurveyAction()`
- `editSurveyCoursesAction()`
- `closeSurveyAction()`

Plus a private helper `surveyCourseSelectionSchema()` for the shared "filter by specialization + checkbox list of courses" modal body.

Both `table()->recordActions()` and `form()` now invoke these factories — single source of truth.

The action buttons in the form are hidden via `->hiddenOn('create')` so they only appear when editing an existing semester (a new semester has no record yet to attach courses to).

## Alternatives considered

- **Make the Toggle itself trigger a modal via Livewire wire:click.** Rejected — fights Filament's form-input model, requires custom JS, and the toggle visually implies "this state will flip on form save" which is the opposite of the modal-confirm pattern we want.
- **Keep the Toggle and add the modal as a sibling button.** Rejected — two ways to do the same thing is worse than one. The Toggle has no value here because opening the survey without picking courses is meaningless (students would see an empty list).
- **Inline the action definitions a second time in the form.** Rejected — already 80+ lines of duplication on the list page; doing it again would be three copies of the same actions. The static factories keep one source of truth.

## Consequences

- `survey_open` is no longer toggled as part of a normal form submit. The admin must click the dedicated button + confirm the modal. This is desired: opening a survey without selecting courses is a broken state.
- The form has no `survey_open` form input at all — the underlying flag is only set by the action's `->action(...)` closure.
- New convention: when a column has a richer "open" workflow than a plain boolean flip, do not expose it as a Toggle on the form. Use the same factory-action approach so list and form stay in lockstep.

## Files touched

- `app/Filament/Resources/Semesters/SemesterResource.php` (extracted 3 factory methods + 1 shared schema helper; removed inline duplication from `table()`; replaced `Toggle::make('survey_open')` in `form()` with an `Actions::make(...)` invoking the factories)
