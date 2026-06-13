---
date: 2026-06-13
tags: [bug, filament, course-offering, validation, livewire, save-button]
related:
  - "[[2026-06-13-pg-status-check-crash]]"
---

# "Can't edit course offering / Save button greyed out" — two bugs

User report: open `/admin/course-offerings/{id}/edit`, the Save button is greyed out, can't save.

## Bug A — save-button lock never released on Select/Toggle-only forms
`AppServiceProvider::saveButtonGuard()` injects a JS guard that disables Save (`data-save-locked`)
until the form is dirty, listening only for `input`/`change` on the `<form>`. Filament's **Toggle
and Select render as `<button>`s**, not native inputs — they never emit `input`/`change` that
bubbles to the form. The CourseOffering edit form is all Selects + a Toggle, so the lock never
released. Verified in-browser: toggling the switch left `__saveChanged=false`, Save stayed locked.

**Fix:** added a `click` listener to the guard (excludes `button[type="submit"]`). Any click on a
form control now marks it dirty — can't deadlock. Safe direction (worst case: a no-op save).

## Bug B — the actual blocker: self-matching uniqueness validation
Even with Save enabled, saving failed with *"يوجد مقرر مفتوح بالفعل لهذا الفصل الدراسي"*. The
`semester_id` rule checked `CourseOffering::where(course_id)->where(semester_id)->where('is_open',true)->exists()`
**without excluding the record being edited** → an existing open offering matched *itself* and
could never save.

**Fix:** `->when($record, fn (Builder $q) => $q->where('id', '!=', $record->id))` (record injected
via the Filament closure param — name must be `$record`, per the evaluate() param-name rule).
Note: a UNIQUE `(course_id, semester_id)` constraint means a real second offering is impossible, so
the rule could *only* ever self-match — the exclusion is the whole fix.

## Verified
Browser end-to-end: toggle `is_open` → Save enables → save → "تم الحفظ" toast → DB persisted
(`is_open` flipped). Tests: `CourseOfferingEditTest` (save without false error; change faculty + save).
