---
date: 2026-05-08
tags: [decision, warnings, inactive, survey, semester, observer]
related: [[2026-04-27-warning-system-and-dismissal]], [[2026-04-23-student-status-restructure]]
---

# Auto-Inactive on Survey Close + Semester Reset

## Context

The warning system and `consecutive_inactive` dismissal flow ([[2026-04-27-warning-system-and-dismissal]]) were built correctly, but the trigger that actually moves students into `Inactive` status was missing. The `SemesterObserver` only processed students who were *already* Inactive when a semester was activated — it never auto-detected which Active students had skipped the survey, and it never reset Inactive students to give them a fresh chance the next semester.

## Decision

### Full lifecycle (two triggers added to `SemesterObserver`)

```
Semester is_active → true
  └─► resetInactiveStudentsToActive()
        All Inactive students → Active (updateQuietly, warnings preserved)

Survey opens (admin action, no observer)

Survey closes (survey_open: true → false)
  └─► processNonSubmittingStudents()
        Active students with zero CourseSurvey rows for this semester
          → update(status = Inactive)  ← normal update, triggers StudentObserver
              StudentObserver → processInactiveWarning() → creates consecutive_inactive warning
              StudentObserver → cascadeWithdraw() → withdraws any active enrollments
              If 2+ active consecutive_inactive warnings → dismissed
```

### Why `updateQuietly` for the reset, `update` for the inactive set

- **Reset to Active** uses `updateQuietly` — bypasses `StudentObserver`, so `clearInactiveWarnings()` does NOT fire. Warnings must accumulate across semesters; the count only resets when an admin manually changes the status.
- **Set to Inactive** uses regular `update` — fires `StudentObserver`, which creates the warning and cascades enrollment withdrawals. Both side-effects are correct and desired.

### Admin override behaviour (unchanged)

- Admin manually sets Inactive → Active: `StudentObserver` fires normally → `clearInactiveWarnings()` clears all active consecutive_inactive warnings → fresh start.
- Admin manually sets Active → Inactive: `StudentObserver` fires → warning created immediately (no need to wait for survey close).

### Dismissal escalation (unchanged, from prior decision)

- 2 active `consecutive_inactive` warnings → `WarningService::dismissStudent()` → `status = Dismissed`.
- Exceptional request can reinstate the student; acceptance clears all inactive warnings.

## File changed

`app/Observers/SemesterObserver.php` — replaced `processInactiveStudents()` (old, only added warnings to already-Inactive students) with two new private methods:
- `resetInactiveStudentsToActive()` — called on `is_active → true`
- `processNonSubmittingStudents(Semester)` — called on `survey_open: true → false`

## Alternatives considered

1. **Cron job** — rejected; business event (survey close) is the natural trigger, not clock time.
2. **Detect inside close_survey Filament action** — works, but observer is cleaner and catches all paths that flip `survey_open` (direct DB edits, future bulk actions, tests).
3. **Reset warnings on semester start** — rejected; warnings must persist so the 2-warning dismissal threshold is cumulative across semesters, not per-semester.
