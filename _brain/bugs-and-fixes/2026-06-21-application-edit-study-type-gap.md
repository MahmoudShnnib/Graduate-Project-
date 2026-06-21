---
date: 2026-06-21
tags: [bug, filament, layout, grid, applications, students]
related:
  - "[[../daily-notes/2026-06-21-student-form-docs-and-validation]]"
---

# Empty gap under نوع الدراسة on the application edit form

## Symptom
"Empty UI gaps under نوع الدراسة" on `/admin/applications/{id}/edit`.

## Real root cause (confirmed from the user's screenshot)
The **form schema renders its section cards in a 2-column grid** (the resource form had
`$schema->components([...])` with no `->columns()`, which laid the sections out 2-up). Row 1 =
`البيانات الشخصية` (≈12 field rows, very TALL) on the right + `نوع الدراسة` (radio + toggle, SHORT)
on the left. CSS grid row height = the tallest item, so the whole **left column below the short
نوع الدراسة card is empty** until the next section row (`المستندات`) begins. That empty column is
the "gap under نوع الدراسة".

> Not the delegation fields. My first pass wrongly blamed `delegation_number`/`delegation_entity`.
> Hidden fields are `.fi-hidden{display:none}` and take no space, so on a `self_funded` app they
> never caused a gap. The cited app #24 is `self_funded` — that's why the "fix" didn't help.

## Fix
- `ApplicationResource::form` → `$schema->columns(1)->components([...])`. Sections now stack
  full-width (each keeps its internal `->columns(2)` field grid). One column → no empty second
  column → no gap. Matches `StudentResource::form` which was already `->columns(1)`.
- Kept (separately valid) within-section fix: `study_type` Radio `->live()` and
  `delegation_number`/`delegation_entity` `->columnSpan(2)` so a lone visible delegation field
  fills its section row instead of leaving a 1-col sibling hole. Same applied to `StudentResource`.

## Verification
Dumped the rendered Livewire HTML: top-level form grid went from `--cols-lg: repeat(2,…)` to
`repeat(1,…)`; inner section grids stay `repeat(2,…)`.

## Lesson
For a Filament "empty area beside/under a card" complaint, suspect the **form/section grid
column count** first (short card next to a tall card in a multi-col layout), not conditional
field visibility. Dump the rendered HTML and read the top-level `--cols-*` before theorizing.
A `self_funded` repro that shows the gap rules out the conditional fields immediately.
