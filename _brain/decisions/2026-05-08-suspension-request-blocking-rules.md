---
date: 2026-05-08
tags: [decision, suspension, student-portal, blocking, rules]
related: [[../daily-notes/2026-05-08-session-8-tasks]], [[2026-04-23-student-status-restructure]]
---

# Suspension Request Blocking Rules

## Context

The suspension request page previously only blocked per-semester (if an approved request existed for the *selected* semester). There was no rule for rejected requests, and the "approved" block was not truly permanent — a student could still pick a different semester and submit there even after a previous approval.

## Decision

Three tiers of blocking, evaluated in this order in `submit()`:

| State | Scope | Behaviour |
|---|---|---|
| Any **Approved** request ever | Permanent | Form hidden entirely; student sees a red banner with `heroicon-o-no-symbol` |
| **Pending** request | Until resolved | Form hidden; student sees amber "under review" banner (unchanged) |
| **Rejected** request for the selected semester | That semester only | Dropdown option disabled; inline red warning; textarea + submit disabled |

### Why permanent for Approved

A suspension approval is a one-time administrative decision. Allowing the student to effectively "re-apply" in a different semester would bypass this. Once approved, the student's academic trajectory is affected (status → OnHold, enrollments withdrawn). There is no legitimate reason for a second approval.

### Why semester-scoped for Rejected

A rejected request means "not this time, for this semester." The student should be allowed to try again in a future semester if their situation changes. A blanket permanent block for rejections would be overly punitive.

### Implementation

- **`hasAnyApprovedRequest()`** — `EXISTS` query, no semester scope.
- **`getBlockedSemesterIds()`** — combines Approved + Rejected semester IDs into a single array; used to disable dropdown options and block `submit()`.
- **`isSelectedSemesterBlocked()`** — checks `$this->semesterId` against blocked list; drives the inline warning and disabled state.
- Gate order in `submit()`: approved-permanent → pending → semester-specific → create.

## Alternatives considered

- **Permanent block for rejected** — rejected; too harsh, defeats the appeal mechanism.
- **Semester-scoped block for approved** — rejected; this was the previous (wrong) behaviour. An approval is a terminal decision, not a per-semester one.

## Files changed

- `app/Filament/Student/Pages/SuspensionRequestPage.php`
- `resources/views/filament/student/pages/suspension-request.blade.php`
