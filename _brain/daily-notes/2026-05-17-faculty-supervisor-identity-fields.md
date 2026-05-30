---
date: 2026-05-17
tags: [daily, users, supervisors, schema, migrations, forms, identity, thesis]
related:
  - "[[../decisions/2026-05-17-faculty-supervisor-identity-fields]]"
  - "[[../decisions/2026-05-14-user-resource-scope]]"
  - "[[2026-05-14-user-resource-overhaul]]"
---

# 2026-05-17 — Faculty + external-supervisor identity fields aligned

## What was done

Added 5 identity columns (`national_id` unique, `birth_date`, `birth_place`, `nationality`, `gender`) to both `users` and `supervisors`, surfaced them on the two relevant admin forms, and renamed two labels on both surfaces. Full rationale in [[../decisions/2026-05-17-faculty-supervisor-identity-fields]].

## Per-task outcomes

| # | Task | Outcome |
|---|---|---|
| 1 | Migrations | Two new migrations: `2026_05_17_141017_add_identity_fields_to_users_table.php` + `2026_05_17_141018_add_identity_fields_to_supervisors_table.php`. All 5 columns nullable; `national_id` unique per table. |
| 2 | Models | `User` + `Supervisor` — extended `$fillable`, added `birth_date => 'date'` cast. `Supervisor` gained a new `casts()` method (didn't have one). |
| 3 | UserForm + Infolist | New "البيانات الشخصية" Section in `UserForm` (visible when `role === 'faculty'`) with all 5 fields, each required when visible. `gender` uses `Select::searchable()->preload()` per design-conventions §12. `national_id` has `unique(ignoreRecord: true)`. Labels renamed: `الدرجة العلمية → الدرجة الأكاديمية`, `الجامعة / الكلية / المعهد → جهة العمل / المؤسسة التعليمية`. `UserInfolist` got 5 new `TextEntry`s with per-entry `visible()` gates so non-faculty users don't see empty rows. View-page Blade hero meta needs no edit — it renders values directly. |
| 4 | ThesisRegistrations supervisor modal | Repeater rows in `ListThesisRegistrations` extended with the same 5 fields (all required) between `name` and `academic_rank`. `fillForm` mapping + create/update payload extracted to a single `$payload` array to avoid drift between the two branches. `national_id` unique via `->unique('supervisors', 'national_id', modifyRuleUsing: fn ($rule, $get) => $rule->ignore($get('id')))` so editing an existing row doesn't false-flag. |
| 5 | Factories | `UserFactory::faculty()` now generates 12-digit unique `national_id`, plausible Libyan birth places, default `nationality = ليبية`, random gender, plus the existing academic fields. `SupervisorFactory` got the same — with a broader institution / nationality pool since external supervisors are from elsewhere. |
| 6 | Pint + tests | Pint clean. `php -d memory_limit=2048M ./vendor/bin/pest --compact` → **162 passed (341 assertions)** in 12.50s. No regressions. |
| 7 | Brain notes | This file + the decision note. |

## Files touched

```
New:
  database/migrations/2026_05_17_141017_add_identity_fields_to_users_table.php
  database/migrations/2026_05_17_141018_add_identity_fields_to_supervisors_table.php
  _brain/decisions/2026-05-17-faculty-supervisor-identity-fields.md
  _brain/daily-notes/2026-05-17-faculty-supervisor-identity-fields.md (this file)

Modified:
  app/Models/User.php
  app/Models/Supervisor.php
  app/Filament/Resources/Users/Schemas/UserForm.php
  app/Filament/Resources/Users/Schemas/UserInfolist.php
  app/Filament/Resources/ThesisRegistrations/Pages/ListThesisRegistrations.php
  database/factories/UserFactory.php
  database/factories/SupervisorFactory.php
```

## Design conventions honored

- **Arabic-first.** Every new label, placeholder, default value, and dummy birth-place is Arabic.
- **Select searchable+preload** on the `gender` Select per [[../project-context/design-conventions]] §12 — the rule covers enum-bound Selects, not just relationship Selects.
- **National-ID uniqueness mirrors `students.national_id`.** Same constraint shape, same column name. The students convention is now the canonical "identity field set" across all person-like models.
- **Gender stored as `male`/`female`, displayed as `ذكر`/`أنثى`** — exact match with `students` + `applications` patterns. No new enum class introduced (the codebase doesn't use one for gender; inline match expressions everywhere).
- **Required-when-visible pattern** — `required(fn ($get) => $get('role') === 'faculty')` mirrors the existing academic-fields section in the same form. Existing non-faculty rows aren't broken.

## Browser verification — still pending

User should walk through:

1. **`/admin/users/create`** — pick `عضو هيئة التدريس` from the role select → 5 new fields appear under "البيانات الشخصية", then the academic section with renamed labels (`الدرجة الأكاديمية`, `جهة العمل / المؤسسة التعليمية`). Submitting with any of the 10 required fields blank should block.
2. **`/admin/users/{id}/edit`** on a faculty user — same fields visible + pre-filled (post-refresh).
3. **`/admin/users/{id}`** view page — البيانات الشخصية section shows the 5 new entries for faculty users; non-faculty users see only the original 3 entries (name, role, is_active).
4. **`/admin/thesis-registrations`** → "إدارة المشرفين الخارجيين" button → expand a row → all 11 fields visible with the two renamed labels.

## DB refresh — done, with two side-discoveries

User approved `php artisan migrate:fresh --seed`. First attempt crashed because **two seeder files still referenced the dropped `users.specialization_id` column** (dropped 2026-05-14, never audited the seeders):

- `database/seeders/StudentSeeder.php:54` — `User::firstOrCreate(..., 'specialization_id' => $specialization->id)`
- `database/seeders/DummyStudentSeeder.php:47` — `User::create(..., 'specialization_id' => $specialization->id)`

Both fixed (line removed; `Student::create` calls that legitimately use the column on the `students` table left untouched). Refresh then completed cleanly.

**Why nobody noticed for 3 days:** `RefreshDatabase` in the Pest suite doesn't run the full `DatabaseSeeder` chain — it migrates fresh tables but doesn't invoke seeders. So a stale-column-in-seeder bug only surfaces on a real `migrate:fresh --seed` invocation (rare during dev). Added a regression test: `tests/Feature/DatabaseSeederIntegrityTest.php` shells out to `php artisan migrate:fresh --seed` in a subprocess (separate DB file in /tmp) and asserts exit 0. Catches this class of bug at CI time.

**Subprocess test gotcha logged:** the test environment's `BCRYPT_ROUNDS=4` (from `phpunit.xml`) clashes with seeder-stored bcrypt-12 hashes — the `hashed` cast throws "Could not verify the hashed value's configuration." Fix: override `BCRYPT_ROUNDS=12` in the Process env array so the subprocess matches production / .env.

## Demo faculty user seeded

User approved adding a faculty user to `AdminUserSeeder` so every fresh DB has one ready to test the new form:

```
Name:           د. عبدالله الورفلي
Email:          cs.faculty@example.ly
Password:       password
National ID:    119801011234
Birth date:     1980-01-01
Birth place:    طرابلس
Nationality:    ليبية
Gender:         male
Academic rank:  أستاذ مشارك
Qualification:  دكتوراه
Institution:    كلية التقنية الإلكترونية – طرابلس
```

Spatie role `faculty` assigned alongside the `role` column. Brain notes' references to this user are now authoritative — it's seeded, not ad-hoc.

## Final test count

**163 passed (342 assertions)** — was 162 at session start, +1 for the new `DatabaseSeederIntegrityTest`. Pint clean. ~16s.

## Memories updated

- [[feedback-dummy-data-can-refresh]] — reinforced rule: *never run `migrate:fresh` without explicit per-instance permission, but suggesting is always welcome.* Per 2026-05-17: "yeye refresh, and don't ever do it without my permission but u are always allowed and encouraged to suggest so if it helps."

## Loose ends / next steps

- Browser verification (see above).
- C-2 from the audit (last remaining item) is still untouched — pick it up next session.
- The decision note flags one follow-up: the thesis-registration *student* picker page could surface national_id for stronger supervisor disambiguation. Not scoped here.

## Test credentials (still seeded, all dummy)

| Role | Email | Password |
|---|---|---|
| super-admin | `admin@college.edu` | `admin123` |
| secretary | `secretary@college.edu` | `secretary123` |
| faculty | `cs.faculty@example.ly` | `password` |
| student (factory-seeded) | `mohammed.zawai@example.com` | `12345678` |
| student (test) | `ppsan@pp.com` | `ppsan@69` |
