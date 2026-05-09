---
date: 2026-05-08
tags: [decision, students, transfer-courses, repeater, filament, ux]
related: [[../daily-notes/2026-05-08-session-8-tasks]]
---

# Transfer Course Repeater — Hide Already-Selected Courses

## Context

The معادلة مواد modal on the student edit form uses a Repeater. Each row has a `course_id` Select. Previously the same course could be added to multiple rows, producing duplicate `TransferCourse` rows for the same student and creating silent data errors (duplicate course in GPA calculation).

## Decision

In the `course_id` Select's `->options()` closure, after filtering by specialization, we additionally:

1. Get all current rows via `$get('../../courses')` — the `../../` path traverses up from the field → up from the repeater item → to the modal form level where the `courses` repeater lives.
2. Collect the `course_id` of every row.
3. Exclude the **current row's own value** (`$get('course_id')`) from the "taken" list — so a row that already has Course A selected can still see Course A in its own dropdown.
4. Filter out all other taken IDs with `->whereNotIn('id', $takenIds)`.

Because the field is `->searchable()` (not `->preload()`), options are re-queried on every keystroke. This means the exclusion list is always fresh — adding a course in row 1 and then searching in row 2 will immediately hide the newly-added course, no extra `->live()` needed.

## Why no `->live()` on `course_id`

Adding `->live()` to every repeater item's Select would cause the repeater to re-render (and reset focus) on every change. Since the filter is applied at search-time by `->searchable()`, the user always sees fresh options when they open a dropdown — `->live()` would be redundant noise.

## File changed

`app/Filament/Resources/Students/StudentResource.php`
