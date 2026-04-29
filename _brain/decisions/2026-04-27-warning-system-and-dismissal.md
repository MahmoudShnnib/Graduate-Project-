---
date: 2026-04-27
tags: [decision, warnings, dismissal, status, domain-rule]
related: [[2026-04-23-student-status-restructure]], [[../project-context/design-conventions]]
---

# Warning System Rework + Dismissed Status

## Context

The `warnings_count` field on `students` was a manual integer with no automated logic. The system needed two types of automated warnings with escalation to a new "Dismissed" (مفصول) status.

## Decision

### Two Warning Types

**Type 1: Course Failure** — when a grade is approved with letter=F:
- First F on a course → create a `course_failure` warning
- Pass the same course later → warning resolved
- Fail the same course again → student is DISMISSED

**Type 2: Consecutive Inactive** — when student status is set to Inactive:
- 1st semester inactive → create a `consecutive_inactive` warning
- Becomes active again → warning cleared
- 2nd consecutive semester inactive → student is DISMISSED
- A normal semester in between resets the count

### New Status: Dismissed

Added `StudentStatus::Dismissed = 'dismissed'` (مفصول) with `danger` color. Behaves like all other non-Active statuses for cascading/gating purposes — canPerformActions() blocks, StudentObserver cascades withdrawals, GPA is frozen.

### Data Model

**New `student_warnings` table** tracks individual warning records with `type` (course_failure/consecutive_inactive), `course_id` (for type 1), `semester_id`, `is_active`, `resolved_at`.

**New columns on `students`:** `dismissed_at`, `dismissal_type`, `dismissal_course_id` — records when and why a student was dismissed.

### Trigger Architecture

- **GradeObserver** → calls `WarningService::evaluateGradeApproval()` on both `created` and `updated` (status → Approved)
- **StudentObserver** → calls `processInactiveWarning()` when status → Inactive, `clearInactiveWarnings()` when status leaves Inactive
- **SemesterObserver** (new) → when a semester is activated, processes all Inactive students for consecutive warnings

### `warnings_count` is now auto-computed

The field stays on the `students` table but is no longer manually editable. Updated by `WarningService::syncWarningsCount()` after every warning create/resolve. Uses `updateQuietly` to avoid observer loops.

## Alternatives Considered

1. **Cron job for inactive detection** — rejected; semester activation is the natural business event
2. **Remove `warnings_count`, compute on read** — rejected; the field is widely read in UI, keeping it denormalized avoids N+1
3. **Single `warnings` integer without records** — rejected; need to track per-course and per-semester detail for resolution and dismissal reason

## Consequences

- Admin can still manually set Dismissed via the changeStatusAction (override)
- Admin can manually set a Dismissed student back to Active (override)
- Dismissal records are permanent (dismissed_at, dismissal_type, dismissal_course_id)
- Warning records are never deleted, only resolved (is_active=false) for audit trail
