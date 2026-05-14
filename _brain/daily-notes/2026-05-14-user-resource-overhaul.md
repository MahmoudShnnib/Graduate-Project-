---
date: 2026-05-14
tags: [daily, users, students, schema, migration, filament, design-system, cleanup]
related:
  - "[[../decisions/2026-05-14-user-resource-scope]]"
  - "[[2026-05-14-shield-permissions-restored]]"
  - "[[2026-05-14-suspension-request-view-page]]"
  - "[[2026-05-14-stale-test-fixes]]"
  - "[[../project-context/figma-view-page-design]]"
---

# 2026-05-14 — User-resource overhaul, schema cleanup, dead code removal

> This is the big one for today. Everything UserResource-related landed in this session, plus the StudentEnrollments resource was deleted. See [[../decisions/2026-05-14-user-resource-scope]] for the decision document.

## What was done (in order)

### 1. Suspension-request view page (Figma pattern, 8th impl)

Already logged at [[2026-05-14-suspension-request-view-page]]. Built `/admin/suspension-requests/{id}` with hero card + 2-column infolist + page-header approve/reject actions. Rows on the index are clickable + a gray "عرض" action button was added per user feedback.

User explicitly asked to **remove the Print button** from this page, and stated as preference going forward: "stop using the print button unless I say otherwise." → Logged as memory `feedback_no_print_button.md`. The [[../project-context/figma-view-page-design]] spec was updated to make Print **opt-in, not default**.

### 2. Shield permissions wipe → restored + seeder self-heal

Already logged at [[2026-05-14-shield-permissions-restored]] and [[../bugs-and-fixes/2026-05-14-shield-permissions-table-empty]]. Permissions table was empty (`Permission::count() === 0`), so every role joined to zero permissions in the Shield UI even though the system kept working via Gate::before bypass + role-column checks.

Fix: ran `shield:generate --all --panel=admin` + re-seeded roles. Wired self-heal into `RolesAndPermissionsSeeder::run()` so this never happens silently again.

Also explained for the user the meaning of `student` and `panel_user` showing 0 permissions (both intentional — student panel doesn't authorize via Shield; panel_user is the HasPanelShield fallback role).

### 3. UserResource view page refinements

A series of incremental tweaks driven by user feedback while looking at `/admin/users/{id}`:

1. **Removed the Print button** (per the no-print preference above) and its `.fsv-btn--secondary` CSS + `@media print` block.
2. **Hid `name_en` for faculty users** — the row in the infolist and the big English-name subtitle in the hero. Faculty don't use it; other roles still do.
3. **Reordered the layout**: "البيانات الأكاديمية" section moved from the LEFT column (under contact-extra) to the RIGHT column (under personal-data), making it visually contiguous with the rest of the identity info.
4. **Removed dead student-role branches** in the formatter `match` statements (UsersTable + UserInfolist). The user confirmed all DB data is dummy → no defensive code for legacy student rows is needed. → Logged as memory `feedback_dummy_data_can_refresh.md` with the rule: "ask before refresh; assume DB can be refreshed; verify no auto-refresh exists." Auto-refresh audit ran clean (no `migrate:fresh` calls anywhere in `app/`, `bootstrap/`, `routes/`, `config/`, `composer.json`).

### 4. Faculty-supervisor academic fields (schema addition)

User noticed that `/admin/thesis-registrations` has an "إدارة المشرفين الخارجيين" modal collecting **name + academic_rank + qualification + institution + email + phone** for external supervisors, while internal faculty (User rows) had none of those fields. → Decision: align the schema.

Added 3 nullable columns to `users` via migration `2026_05_14_174132_add_academic_fields_to_users_table`:
- `academic_rank` (الدرجة العلمية)
- `qualification` (المؤهل العلمي)
- `institution` (الجامعة / الكلية / المعهد)

Form shows them **only when role = faculty** (`->live()` on the role Select + `->visible(fn ($get) => ...)` on the section). Required when shown. `institution` defaults to **"كلية التقنية الإلكترونية – طرابلس"** (the home faculty, per user's exact string with en-dash).

View page hero meta picks up `academic_rank` (Star icon) + `institution` (BuildingLibrary icon) when populated. New "البيانات الأكاديمية" section in the infolist shows the 3 fields, visible for any faculty user or any user with backfilled academic data.

The `supervisors` table for external (non-system-user) supervisors is unchanged. Both surfaces now collect the same data shape — only the storage differs (users vs supervisors).

### 5. UserResource excludes students

User: "let the table not display the students since they have their own resource."

Added `UserResource::getEloquentQuery(): Builder` returning `parent::getEloquentQuery()->where('role', '!=', 'student')`. Filters everywhere — list, search, direct URL access (404s on student IDs from this resource).

Also removed the `student` option from the role Select on create/edit, so admins can't create a student-role user here (it'd vanish immediately from the list).

Cleaned dead `'student' => '...'` branches in:
- `UsersTable::formatStateUsing` for the role column
- `UserInfolist`'s role TextEntry formatter + color mapping
- `view-user.blade.php` hero block's `roleLabel` / `roleColor` matches

### 6. `users.specialization_id` removed

User: "besides the students, I don't want the other users to have a `التخصص` specialization since that doesn't really matter for them they can teach whatever."

Followed `superpowers:writing-plans` style — used Explore agent to map all 11 usages of `users.specialization_id` before touching anything. Found that NO query, observer, policy, or business logic outside the UserResource ever read the column. Students use their own `students.specialization_id` (different table). Safe to drop.

Migration `2026_05_14_202545_drop_specialization_id_from_users_table` drops the FK + column. `User::specialization()` relation deleted. `UserForm`, `UserInfolist`, `UsersTable`, view-user Blade all stripped. `UserFactory::faculty()` no longer sets it (Specialization import removed). `AdminUserSeeder` no longer sets the user-row value (but still sets `students.specialization_id` for the demo student row).

### 7. StudentEnrollments resource deleted

User asked what `/admin/student-enrollments` was for. I explained: a read-only roster browser keyed by course offering. They responded: "I don't want this resource anymore."

Map of references before deletion (via grep): every hit was internal to the resource files themselves — no external code linked to its URLs, classes, or Blade views. The `StudentEnrollment` model, policy, and Shield permissions stayed (they're used by `CourseOfferings/Pages/EnrollStudents`). Only the Filament Resource UI was deleted.

Verification: `optimize:clear` clean; routes show `/admin/student-enrollments*` no longer registered; test suite unchanged.

Backup at `_backups/student-enrollments-resource-2026-05-14/` with a `cp -r` revert script.

### 8. 5 failing tests fixed

Logged separately at [[2026-05-14-stale-test-fixes]] + [[../bugs-and-fixes/2026-05-14-stale-test-assertions]]. Used `superpowers:systematic-debugging` to diagnose root causes before fixing. Net: 158/158 passing (was 153/158).

## Memories saved this session

- `feedback_no_print_button.md` — skip the hero-card Print button on Figma view pages unless the user explicitly asks
- `feedback_dummy_data_can_refresh.md` — all DB data is dummy; don't keep defensive code for legacy rows; ask before refresh; verify no auto-refresh exists; obsolete the moment real data lands

## Project-context updates

- [[../project-context/figma-view-page-design]] — implementations table updated (added suspension-request as 8th impl; removed deleted student-enrollments line); added a callout that Print is no longer default; added a callout that `/admin/student-enrollments` was deleted

## Verification

- `vendor/bin/pint --dirty --format agent` → pass throughout
- `php artisan optimize:clear` → no Filament discovery errors after the resource deletion
- `php artisan route:list --path=...` confirms suspension-request view route registered; student-enrollments routes gone
- `php artisan tinker` round-trip: faculty user created cleanly without specialization_id; academic fields persist with en-dash institution string intact; student-record specialization still resolves
- `php artisan test --compact` → **158 passed (337 assertions)** — up from 153/158 at session start

## Next session anchors

- The thesis-registration page's supervisor picker for students could now be sourced from `users.role = faculty` with the academic fields rendered the same way as the `supervisors` rows. Currently still uses the separate `supervisors` table for everyone. Not done this session — would be a follow-up unification of the picker UI.
- Consider whether the home institution string (`"كلية التقنية الإلكترونية – طرابلس"`) should move to `config/services.php` or a dedicated `config/faculty.php` so it's editable in one place. Currently typed inline in: registration controller text, PDF templates, exam-list page, clearance page printout, student-courses printout, layout component, view-user form default. Some of these were already inline before this session.
- The 9 brain notes created today (2 daily, 1 decision, 2 bug-fix, 2 memory, 1 context-doc update, 1 backup README) are dense — future sessions can use this single overhaul note as the entry point.
