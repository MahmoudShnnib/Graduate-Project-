---
date: 2026-06-15
tags: [bug, observer, eloquent, gotcha, gpa]
related: [[../decisions/2026-06-15-flush-current-semester-on-suspend]]
---

# Infinite recursion: saving a model inside its own `updated` observer

#observer #eloquent #gotcha

## Symptom

After adding a forced GPA recalc to `StudentObserver` for the suspend/inactive
flush, the test suite hung forever (PHPUnit produced zero output; `timeout`
killed it with exit 124). The first suspension test never returned.

## Root cause

`flushCurrentSemester()` called `recalculateStudentGPA($student, force: true)`,
which does `$student->save()` — **while still inside that same `$student`'s
`updated` event** (fired by the outer status change).

Eloquent runs `syncOriginal()` only in `finishSave()`, *after* the `updated`
event returns. So during the outer event the model's original still holds the
pre-change `status`. The nested `save()` therefore re-computes `getDirty()` with
`status` still dirty → `wasChanged('status')` is **true** on the nested
`updated` → the observer re-enters `flushCurrentSemester` → save → … forever.

## Fix

Recompute on a **fresh** instance, whose `status` is already synced from the DB
(the status write happens before the event fires), so only `gpa` is dirty:

```php
$fresh = $student->fresh();
if ($fresh) {
    $this->gradeCalculationService->recalculateStudentGPA($fresh, force: true);
    $student->setAttribute('gpa', $fresh->gpa); // keep the in-memory copy in sync
}
```

## Lesson / guard

Never call `$model->save()` on the *same instance* from within its own
`saved`/`updated` observer if the event handler branches on
`wasChanged()`/`isDirty()` — the original isn't synced yet, so the prior change
still reads as dirty. Use a fresh instance, `saveQuietly()`, or a re-entrancy
flag. Regression guard: `SuspensionFlushesCurrentSemesterTest` (would hang, not
just fail, if this regresses — keep the per-file `timeout` in mind when running).

## Debugging note

A hang shows up as empty test output + a stuck `php artisan test` process. A
leading `pkill ... ` in a run command can also abort the whole script under the
shell's `set -e` (pkill returns 1 when nothing matches) — guard with `|| true`
or omit it, or you'll wrongly think the test produced no output.
