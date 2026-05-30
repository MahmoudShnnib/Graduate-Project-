---
date: 2026-05-17
tags: [decision, users, supervisors, schema, identity, thesis]
related:
  - "[[../daily-notes/2026-05-17-faculty-supervisor-identity-fields]]"
  - "[[2026-05-14-user-resource-scope]]"
  - "[[../project-context/design-conventions]]"
---

# Align faculty (users) + external supervisors identity-field schema

## Context

The 2026-05-14 [[2026-05-14-user-resource-scope]] decision aligned the *academic* fields between internal faculty (`users`) and external supervisors (`supervisors`) — both gained `academic_rank`, `qualification`, `institution`. The motivation was: the thesis-registration page picks supervisors from one of two pools, and the displayed shape should be consistent regardless of which pool the supervisor came from.

The remaining asymmetry was the 5 **identity** fields that the `students` table already carries — `national_id`, `birth_date`, `birth_place`, `nationality`, `gender`. Neither `users` (faculty) nor `supervisors` had them, so the thesis-supervisor picker silently showed less data for faculty + external supervisors than for students.

User domain note (confirmed 2026-05-17): faculty in the system are inherently potential thesis supervisors. An "external supervisor" is a faculty member from a different university — never the same person as a `users` row. So the two tables describe disjoint populations of the same kind of person.

## Decision

Add the same 5 identity columns to both `users` (visible only when `role = faculty`) and `supervisors`. Both tables get a `unique` constraint on `national_id` (per-table — the populations don't overlap, so cross-table uniqueness isn't required).

### Schema

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `national_id` | string | yes (nullable for non-faculty rows; required at the form layer for faculty + every supervisor) | `unique` index per table |
| `birth_date` | date | yes | cast to `date` on both models |
| `birth_place` | string | yes | |
| `nationality` | string | yes | defaults to `ليبية` in forms |
| `gender` | string | yes | stores `'male'`/`'female'`, displays as `ذكر/أنثى` — matches the students convention |

### Form-layer rules

- **`/admin/users/create` and `/admin/users/{id}/edit`** — new "البيانات الشخصية" section visible only when `role === 'faculty'`. All 5 fields required when visible. Mirrors the same toggling pattern as the existing "البيانات الأكاديمية" section.
- **`/admin/thesis-registrations` → "إدارة المشرفين الخارجيين" modal** — 5 fields added to each Repeater row, all required (every external supervisor is a complete record).
- **Label rename in both surfaces:**
  - `الدرجة العلمية` → `الدرجة الأكاديمية`
  - `الجامعة / الكلية / المعهد` → `جهة العمل / المؤسسة التعليمية`

### Display in `UserInfolist`

The 5 new entries render inside the existing "البيانات الشخصية" section. Each entry is gated `visible(fn ($record) => $record?->role === 'faculty' || filled($record?->{column}))` so non-faculty users (super_admin, secretary) don't see empty rows.

## Alternatives considered

| Option | Why rejected |
|---|---|
| Store identity fields on a polymorphic `people` table linked to both `users` and `supervisors` | Over-engineered for the current scale; introduces a join on every supervisor render. The two tables stay independent. |
| Promote `supervisors` into `users` with a `role = 'external_supervisor'` and a `university` column | The 2026-05-14 decision already considered this and rejected it — external supervisors don't authenticate, don't have Spatie roles, don't access any panel. Same logic applies here. |
| Skip the rename and keep `الدرجة العلمية` / `الجامعة / الكلية / المعهد` | User requested the rename specifically; the new strings (`الدرجة الأكاديمية`, `جهة العمل / المؤسسة التعليمية`) better match the bilingual nature of the faculty pool — some supervisors aren't strictly at a "كلية" or "معهد". |
| Cross-table unique constraint on `national_id` | DB can't express it. User clarified that the two populations are disjoint by definition (faculty in this system ≠ faculty from other universities entered as external supervisors), so per-table uniqueness is sufficient. |
| Leave `national_id` non-unique | Mirrors `students.national_id` which IS unique. A real person has exactly one national ID; the DB should enforce that. |

## Consequences

- **Two new migrations** (`users` + `supervisors`), both purely additive with nullable columns + a unique index on `national_id`. Reversible.
- **Existing rows** (the seeded admin, secretary, and any pre-2026-05-17 faculty/supervisor records) have NULL on all 5 columns. Form-layer required validation only blocks new submissions and edits where role=faculty — they won't crash on read.
- **Factories updated**: `UserFactory::faculty()` and `SupervisorFactory` now generate realistic dummy values (Libyan-format 12-digit national_id via `fake()->unique()->numerify(...)`, plausible Arabic birth places, gender, etc.). `migrate:fresh --seed` produces a clean, fully-populated demo dataset.
- **Test suite 162/162 passing** with no regressions — see [[../daily-notes/2026-05-17-faculty-supervisor-identity-fields]].
- **Convention reinforced**: when adding identity fields to a person-like model, mirror the students table's shape (column name, type, gender encoding `male/female`, default `nationality = ليبية`). This decision documents the canonical set.

## Follow-up candidates

- The thesis-registration supervisor picker (where a student chooses their supervisor) currently shows `name + academic_rank + institution` only. With these new fields the picker could also show نationality / national_id for stronger identity disambiguation. Not scoped here.
- Future: add `birth_date` to the audit log watchlist if the field becomes a candidate for impersonation-risk monitoring.
