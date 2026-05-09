---
date: 2026-05-08
tags: [decision, enrollment, specialization, enrollment-number]
related: [[2026-05-08-audit-log-ui-redesign]], [[2026-04-23-student-status-restructure]]
---

# Enrollment Number Regeneration on Specialization Change

## Context

Enrollment numbers encode the intake year, specialization code, session flag (regular/transfer × autumn/spring), and a 2-digit sequence. When an admin changes a student's specialization via `EditStudent`, the old number references the wrong specialization code and becomes misleading. The system should re-issue a correct number automatically.

## Decision

### Parsing approach

Rather than accepting a `Semester` object (which is unavailable mid-lifecycle and would change the year), the new `regenerateForSpecializationChange` method parses the existing `enrollment_no` directly:

```
enrollment_no format: {YY}{specCode}{sessionFlag}{seq}
  YY          = substr($no, 0, 2)   — always 2 digits
  sessionFlag = substr($no, -3, 1)  — char immediately before the 2-digit seq
  seq         = substr($no, -2)     — always 2 digits (str_pad 2)
```

This works for any specCode length because `sessionFlag` is anchored from the end.

The new prefix is `{YY}{newSpecCode}{sessionFlag}`. MAX across `withTrashed()` rows matching that prefix gives the last-issued sequence in that namespace; +1 is the new one.

### Why not reuse `generate()`

`generate()` requires a `Semester` object and recomputes the session flag from `$semester->season` + `$isTransfer`. At spec-change time there is no "current semester" guarantee, and the student's session flag is already correct and must be preserved. Parsing it from the existing number is simpler and more correct.

### Trigger location

`EditStudent::handleRecordUpdate` captures `$oldSpecializationId` before the parent save and compares after. It runs after transfer-course sync and GPA recalc (order doesn't matter — they're independent) and shows a Filament notification with both old and new numbers.

### AuditObserver coverage

`enrollment_no` is in `AuditObserver::WATCHED_FIELDS` for `Student`, so the change is automatically logged without any additional code.

## Alternatives considered

1. **Require admin to manually re-enter the number** — rejected; defeats the point of an automated system and error-prone.
2. **Auto-regenerate on any save where spec_id is in the form** — rejected; the spec field is always in the edit form. Only regenerate when it *changed*.
3. **Regenerate using a cron / background job** — rejected; the business event (spec change by admin) is synchronous and already has a save hook.
