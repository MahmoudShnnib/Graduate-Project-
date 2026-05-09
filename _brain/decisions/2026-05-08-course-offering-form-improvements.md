---
date: 2026-05-08
tags: [decision, course-offerings, filament, form, ux]
related: [[../daily-notes/2026-05-08-session-8-tasks]]
---

# Course Offering Form — Active Semester Lock + Course Filter

## Context

When creating a course offering, admins had to manually pick the active semester from a dropdown — easy to get wrong, and there was no guard against creating a duplicate offering for a course already offered in the same semester (beyond a validation rule). The course dropdown showed all courses with no filtering.

## Decision

### 1. Semester field locked on create

`semester_id` gets `->default(fn () => Semester::where('is_active', true)->value('id'))` so it pre-fills on the create form. `->disabled(fn (string $operation): bool => $operation === 'create')` locks it so the admin cannot change it. `->dehydrated(true)` ensures the value is still submitted even though the field is disabled (Filament skips disabled fields by default).

On the **edit** form the field stays editable — an admin may need to reassign an offering to a different semester after the fact.

### 2. Course dropdown filters already-offered courses

`->relationship()` now accepts a `modifyQueryUsing` closure with `Get $get` and `?Model $record` injected. It:

1. Reads `$get('semester_id')` to know which semester's offerings to exclude.
2. Queries `CourseOffering::where('semester_id', ...)->pluck('course_id')` to get taken course IDs.
3. On edit, excludes the current record from the "taken" list (`->when($record?->id, ...)`) so the offering's own course is still selectable.
4. Applies `->whereNotIn('id', $takenIds)` on the courses query.

Because `semester_id` is `->live()`, changing the semester on the edit form causes the course options to re-evaluate.

## Alternatives considered

- **Server-side validation only** — rejected; misleading UX, admin sees invalid options and only gets blocked on submit.
- **Separate Create page with custom form** — rejected; the shared `form()` method handles both correctly with `$operation` and `$record` injection.

## File changed

`app/Filament/Resources/CourseOfferings/CourseOfferingResource.php`
