---
date: 2026-04-23
tags: [decision, student-status, enum, migration, suspension]
related: [[2026-04-21-student-status-unification]], [[../project-context/design-conventions]], [[../project-context/gotchas]]
---

# Student Status Restructure

> **Supersedes** [[2026-04-21-student-status-unification]]. That decision introduced the unified status gate and `canPerformActions()`. This decision changes the status values, labels, and suspension semantics.

## Context

The original status set (`Active/Inactive/Withdrawn/Graduated`) had several problems:
- "نشط" (Active label) was unclear — "مستمر" better describes a continuing student.
- `Inactive` (القيد موقوف مؤقتًا) conflated two concepts: admin-imposed hold and student disengagement (no enrollments).
- `Withdrawn` (منسحب) didn't distinguish between voluntary transfer and forced withdrawal.
- Semester-scoped suspensions used a display override hack: student stayed `Active` but `getDisplayStatusLabel()` showed "القيد موقوف مؤقتًا" based on an approved SuspensionRequest. Two sources of truth, confusing for admins and code.

## Decision

### New status enum (final, no other statuses may exist)

| Case | Value | Arabic | English | Behavior |
|---|---|---|---|---|
| `Active` | `'active'` | مستمر | Active | Full access |
| `OnHold` | `'on_hold'` | قيده موقوف | On Hold | View-only |
| `Inactive` | `'inactive'` | منقطع | Inactive | View-only (auto-set at end of semester if zero enrollments) |
| `Transferred` | `'transferred'` | منتقل | Transferred | View-only |
| `Graduated` | `'graduated'` | متخرج | Graduated | View-only |

### Data migration

1. `'inactive'` → `'on_hold'` (old concept → new case)
2. `'withdrawn'` → `'transferred'` (per user directive)

### Suspension semantics simplified

**Before:** Approving a SuspensionRequest kept `Student.status = Active` and relied on `isSuspendedInSemester()` for display + gating. Two sources of truth.

**After:** Approving a SuspensionRequest flips `Student.status = OnHold`. The `StudentObserver` cascade handles enrollment withdrawal automatically. One source of truth — the status field.

- `getDisplayStatusLabel()` → simply returns `$this->status->label()`. No override.
- `getDisplayStatusColor()` → maps from status. No override.
- `canPerformActions()` → `return $this->status === Active`. No semester check.
- `isSuspendedInSemester()` method retained on the model for defense-in-depth in the enrollment action.
- `rescindActiveSemesterSuspensionIfOverridden()` retained on EditStudent for when admin sets status back to Active.

### Auto-inactive mechanism (deferred)

`Inactive` (منقطع) will be auto-set at end of semester when a student has zero enrollments. **Deferred** until the semester-closing mechanic is built. The enum case exists and admins can set it manually.

## Alternatives considered

1. **Keep the display override system** — rejected because it confused admins ("why does it say موقوف when the status says نشط?") and required complex precedence logic in 3 places.
2. **Map `withdrawn` → `on_hold`** — rejected; user clarified withdrawn students are transferring.
3. **Remove `isSuspendedInSemester` entirely** — rejected; it's still useful as defense-in-depth in the enrollment action's guard chain.

## Consequences

**Positive:**
- Single source of truth for student status — no display overrides.
- Clear semantic distinction between admin hold, disengagement, and transfer.
- Simpler code: `getDisplayStatusLabel/Color` and `canPerformActions` are all one-liners.

**Negative:**
- Approving a suspension now affects ALL semesters (global status flip), not just one. Previously it was per-semester scoped. This is intentional per user's "one source of truth" directive.
- Existing tests needed dataset updates (4 files).
