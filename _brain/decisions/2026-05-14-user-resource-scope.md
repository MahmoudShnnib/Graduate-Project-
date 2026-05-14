---
date: 2026-05-14
tags: [decision, users, students, resource-scope, schema, supervisors, filament]
related:
  - "[[../daily-notes/2026-05-14-user-resource-overhaul]]"
  - "[[../daily-notes/2026-05-14-suspension-request-view-page]]"
  - "[[../project-context/figma-view-page-design]]"
  - "[[../bugs-and-fixes/2026-05-14-shield-permissions-table-empty]]"
---

# UserResource scope narrowed; faculty schema aligned with external supervisors

## Context

Up to today, `/admin/users` showed every user — super_admin, secretary, faculty, AND student. Students appearing here duplicated `/admin/students`, which is the dedicated resource for managing student records (with `enrollment_no`, GPA, transfer-course equivalence, dismissal state, etc.). Editing a student via `/admin/users/{id}` only exposed a thin slice of their data and bypassed every domain rule on the Student model.

Separately, `users.specialization_id` was a nullable FK on the users table, surfaced as a form field and table column for all roles. It was set by the seeder on the historical student-user row, by the `UserFactory::faculty()` state, but **never read by any business logic** — every student-side query and faculty-scoped feature reads `students.specialization_id` instead. Faculty in this project can teach across specializations (an Arabic-letters lecturer can teach in two different specs' general-courses lists), so storing a single faculty specialization is fundamentally wrong for the domain.

Third concern: the thesis-registrations page has a dedicated "إدارة المشرفين الخارجيين" modal collecting **name, academic_rank, qualification, institution, email, phone** into a separate `supervisors` table. Internal faculty (who are User rows) had none of those fields — they showed up in the supervisor picker as bare names, while external supervisors had full academic context. Inconsistency.

## Decision

Three intertwined changes to UserResource, applied together:

### 1. UserResource excludes students

`UserResource::getEloquentQuery()` now filters `WHERE role != 'student'`. Students do not appear in:
- The `/admin/users` list
- Search results
- Direct-URL access to `/admin/users/{student_id}` (404s from this resource — students are still accessible at `/admin/students/{id}`)
- Future relation managers or nav badges sourced from this resource

The `student` option is also removed from the role select on create/edit, so admins can't create a student-role user from here (they'd vanish immediately).

### 2. `users.specialization_id` removed entirely

Schema change: new migration drops the FK + column. `User::specialization()` relation deleted. `UserForm`, `UserInfolist`, `UsersTable`, and the view-user Blade all stripped. `UserFactory::faculty()` no longer sets it. `AdminUserSeeder` no longer sets the user-row specialization (the student-row write on the `students` table is untouched).

The `Specialization` model, its resource, and the `specializations` table itself are unchanged. Students keep `students.specialization_id` as their source of truth.

### 3. Faculty get academic-rank fields matching external supervisors

Three new nullable columns on `users`:
- `academic_rank` (الدرجة العلمية) — e.g. أستاذ، أستاذ مشارك
- `qualification` (المؤهل العلمي) — e.g. دكتوراه
- `institution` (الجامعة / الكلية / المعهد) — defaults to **"كلية التقنية الإلكترونية – طرابلس"** (the home faculty)

Fields appear on the user form **only when role = faculty** (`->visible(fn ($get) => $get('role') === 'faculty')`), required when shown. The view page shows a new "البيانات الأكاديمية" section in the right column under "البيانات الشخصية", visible for faculty users or any user with backfilled academic data.

Same schema shape as the external-supervisor modal on `/admin/thesis-registrations` (name + academic_rank + qualification + institution + email + phone — minus name/email/phone which the User already has). The `supervisors` table stays separate — external supervisors aren't system users (no login), but both surfaces now collect the same level of detail.

### 4. Faculty hide `name_en`

Faculty users don't use an English name (no transcripts to print, no certificates). The `name_en` row in the infolist and the big English-name subtitle in the hero are hidden when `role === 'faculty'`. Other roles still see them. The DB column stays — students use it heavily.

## Alternatives considered

| Option | Why rejected |
|---|---|
| Keep students visible on UserResource alongside the dedicated Students resource | Two surfaces editing the same person via different field slices guarantees drift. Bypass risk on the User side. |
| Hide students via list filter only, no policy/query change | Direct URL `/admin/users/{student_id}` still loads; admins lose the safety net. `getEloquentQuery()` enforces it everywhere. |
| Keep `users.specialization_id`, just hide it in UI | The dummy-data cleanup policy ([[feedback-dummy-data-can-refresh]]) says: drop dead schema when nothing reads it. The column had no consumers. |
| Add per-faculty multi-specialization pivot (faculty can teach in N specs) | Over-engineered. The repo's faculty filtering uses `course_offerings.faculty_id` directly — specialization isn't part of the join. Faculty already teach across specs without any DB record of it. |
| Fold external supervisors INTO the users table with a `role = 'external_supervisor'` | Tempting for one-source-of-truth, but external supervisors don't log in or need credentials — turning them into User rows brings auth/permissions baggage. Two tables stay; only the field schema is unified. |
| Add `institution` only to faculty when they're an external visiting lecturer | Always-required + a sensible default is simpler than conditional optionality. Override on edit is one click. |

## Consequences

- **Cleaner separation of concerns.** Students live in their resource, with the full Student model; admin staff + faculty live in UserResource.
- **No more "where is this person?"** — no role-based confusion when the same user might appear in two places.
- **Thesis-registrations gets a consistent supervisor view.** Internal faculty now have rank/qualification/institution to display, parallel to the external-supervisor modal's fields. The supervisor picker on the student thesis page could later be sourced from `users.role = faculty` with the academic fields rendered the same way as `supervisors` rows. Not done in this session; logged as a follow-up.
- **Migrations are clean both ways.** The dropping migration's `down()` restores the FK; the academic-fields migration's `down()` drops the new columns. With dummy data, reversibility is verified (test suite 158/158 with both migrations applied).
- **`UserFactory::faculty()` no longer creates a `specialization_id`-bearing user**, but no test depended on that field (verified during the removal). The `Specialization` import was also removed from the factory.
- **`AdminUserSeeder` no longer sets `users.specialization_id` on the demo student user**, but `students.specialization_id` is still set on the corresponding Student row (which is what every query actually reads).

## Test impact

158/158 passing after the migration. Pre-existing 5 failing tests were also fixed today in the same session ([[../bugs-and-fixes/2026-05-14-stale-test-assertions]]) — net result is **+5 passing, 0 failing, 0 new flaky**.

## Files touched

**Schema**
- `database/migrations/2026_05_14_174132_add_academic_fields_to_users_table.php` (new, adds 3 columns)
- `database/migrations/2026_05_14_202545_drop_specialization_id_from_users_table.php` (new, drops 1 column)

**Models / observers / policies** — none touched (relation removed in User.php, no observer/policy involvement)

**Filament**
- `app/Filament/Resources/Users/UserResource.php` — `getEloquentQuery()` filters students; `BelongsTo` import + `specialization()` removed via User.php
- `app/Filament/Resources/Users/Schemas/UserForm.php` — academic-fields section; specialization Select dropped; student option dropped from role select
- `app/Filament/Resources/Users/Schemas/UserInfolist.php` — academic-fields section moved under personal; specialization entry dropped; `name_en` conditional on `role !== 'faculty'`
- `app/Filament/Resources/Users/Tables/UsersTable.php` — specialization column dropped; dead student-role formatter branch dropped
- `resources/views/filament/resources/users/pages/view-user.blade.php` — hero meta picks up academic_rank + institution; Print button + its CSS + `@media print` removed; specialization meta block removed; English-name hero subtitle removed
- `app/Models/User.php` — fillable updated; `specialization()` removed; unused `BelongsTo` import removed

**Factories / seeders**
- `database/factories/UserFactory.php` — `faculty()` state no longer sets specialization_id; Specialization import removed
- `database/seeders/AdminUserSeeder.php` — user-row specialization_id write removed (student-row stays)
