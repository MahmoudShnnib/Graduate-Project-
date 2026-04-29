---
date: 2026-04-28
tags: [decision, thesis, supervisor, status, resubmission]
related: [[../daily-notes/2026-04-28-thesis-rework-and-fixes]]
---

# Thesis Registration Rework

## Context

The thesis registration system had a simple flat form (title + 3 inline supervisor fields), a single `pending/approved/rejected` status, and a unique constraint on `student_id` preventing resubmission.

## Decisions

### Supervisor model split: internal vs external
- **Internal supervisors** = faculty users (`users.role = 'faculty'`) — already in the system, no separate management needed
- **External supervisors** = new `supervisors` table — managed by admin via slideOver on the thesis list page
- Student picks a radio ("من الجامعة" / "من خارج الجامعة") then selects from the appropriate dropdown
- `thesis_registrations` stores either `faculty_supervisor_id` (FK to users) or `supervisor_id` (FK to supervisors) depending on the choice

### 4-step status flow with student-facing abstraction
- `pending` → `printed` → `approved` / `rejected`
- Student sees `pending` and `printed` both as "قيد المراجعة" — the distinction is admin-internal only
- New `ThesisStatus` enum with separate `studentLabel()` / `studentColor()` methods for the abstracted view

### Resubmission after rejection
- Dropped the unique constraint on `student_id`
- Changed `Student::thesisRegistration` hasOne → `thesisRegistrations` hasMany + `latestThesisRegistration` latestOfMany
- Student can resubmit only when latest registration status is `rejected`
- Previous registrations preserved in history (collapsible section on student portal)

### "تاريخ الانتهاء من المقررات"
- Derived as `end_date` of the latest semester where the student has an enrollment with an approved grade
- Not a stored field — computed on demand via join query

## Alternatives Considered

1. **Separate model for external supervisors** with inline fields for internal — rejected because both types share the same display pattern
2. **Polymorphic supervisor relationship** — over-engineering for two known types
3. **Delete old registration on resubmit** — rejected in favor of keeping history for audit trail

## Consequences

- Old thesis registrations (pre-rework) still work — they have `supervisor_name` / `supervisor_degree` / `supervisor_employer` inline fields which are kept nullable
- The `ThesisStatus` enum must be used everywhere — string comparisons like `=== 'pending'` will break with the enum cast
- Admin must click "طباعة للوزارة" before accept/reject becomes available — enforces the ministry print step
