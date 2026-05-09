---
date: 2026-05-03
tags: [decision, grades, publishing, architecture]
related: [[../daily-notes/2026-05-03-grade-publishing]]
---

# Grade Publishing System — Separate Approval from Visibility

## Context

Previously, when an admin approved a grade (status → Approved), it immediately became visible to students, GPA recalculated, and warnings fired. The faculty wanted the ability to:
1. Publish midterm grades (coursework only) partway through the semester
2. Publish final grades (full results + GPA + warnings) at end of semester
3. Edit published grades without students seeing mid-correction states

## Decision

**Two-timestamp approach** instead of adding a new enum status:
- `midterm_published_at` — when set, students see `coursework_grade` only
- `published_at` — when set, students see full grades, GPA recalculates, warnings fire

The `GradeStatus` enum stays unchanged: `Draft → PendingApproval → Approved`.

**Rationale:** Approval (quality gate) and publishing (visibility gate) are separate concerns. We need two publish levels (midterm vs full), which doesn't fit a linear enum. Timestamps provide auditability for free (when exactly was each publish?).

## Alternatives Considered

1. **Add `Published` to GradeStatus enum** — rejected because we need two publish events (midterm + final), which makes a linear enum awkward.
2. **Single `is_published` boolean** — rejected because it can't distinguish midterm from final publish.
3. **`publish_scope` enum (null/midterm/full) + one `published_at`** — cleaner for state but loses the midterm publish timestamp.

## Auto-Revert Rules (Option B)

When grades are edited after publishing:
- Edit `coursework_grade` → clears BOTH `midterm_published_at` AND `published_at`
- Edit `final_grade` → clears ONLY `published_at` (midterm stays visible)

Implemented in `GradeObserver::updating()`.

## Publish Granularity

Both per-course-offering (header actions on `ViewCourseGrades`) and per-semester (header actions on `ViewSemester`).

## Consequences

- ~35 files touched across the codebase
- `GradeObserver` now triggers on `published_at` being set, not `status → Approved`
- `GradeCalculationService` filters by `whereNotNull('published_at')` instead of `GradeStatus::Approved`
- Student portal pages conditionally show grade fields based on midterm/full publish state
- Factory states `passing()` and `failing()` auto-set `published_at`
- Admin panel sees all grades regardless of publish status (publish is student-facing only)
