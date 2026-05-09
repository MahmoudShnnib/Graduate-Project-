# PROGRESS.md — بوابة الطالب build log

A continuously-updated log of what was built, what's pending, and decisions you should review.

---

## Phase 0 — Audit ✅ COMPLETE

**Built:**
- Read SYSTEM_SPEC.md, DESIGN_SPEC.md, TEST_CREDENTIALS.md.
- Inspected all 21 Eloquent models, all 13 enums, all 38 migrations, all existing seeders.
- Wrote `mobile/AUDIT.md` — complete inventory + decisions.

**Decisions made (review at AUDIT.md §3, §4, §5):**

1. **Sanctum is NOT installed** — Phase 1 installs it; bearer tokens, no SPA cookies.
2. **`routes/api.php` does NOT exist** — registering it via `bootstrap/app.php`, all routes prefixed `/api/v1`.
3. **In-app notifications:** new `app_notifications` table (NOT reusing `notification_logs` which is for outbound email/WhatsApp). Cleaner separation.
4. **Adding two columns to `exceptional_requests`** — `course_id` (nullable FK) and `opportunity_type` (nullable enum string). Required by DESIGN_SPEC. Both nullable so existing rows + Filament admin keep working.
5. **Test student seeding** — new `MobileTestStudentSeeder` runs after existing seeders. Creates `26CS101` (Active) and `26CS102` (OnHold), updates active semester to "الفصل الدراسي الأول 2026", creates a previous semester, course offerings for both, enrollments, grades (full publish on past, midterm-only on active, one F to seed exceptional flow), 1 active warning, 2 unread notifications.
6. **Specialization rename** — existing `code=2` Arabic name "الحاسب الآلي" updated to "علوم الحاسوب" (idempotent) in MobileTestStudentSeeder so display matches TEST_CREDENTIALS without breaking code-keyed lookups.
7. **No Zustand** — TanStack Query for server state, react-hook-form for forms, expo-secure-store for token, local Context for auth state. Revisit only if needed.
8. **Mobile folder layout** — Expo Router (`app/`) + `src/{api,components,hooks,lib,theme}`, `assets/fonts/`. Hidden design preview at `app/_design-preview.tsx`.

**Spec ambiguities resolved (AUDIT.md §5):** 11 small interpretation decisions documented — tab order, badge counts, exceptional opportunity types, clearance trigger, etc. All chose the simpler interpretation.

**Skipped:** nothing.

**Blockers:** none.

**Next:** Phase 1 — Laravel API foundation.

---

## Phase 1 — Laravel API foundation ✅ COMPLETE

**Built:**

- Installed `laravel/sanctum`; published config + migration; added `HasApiTokens` to `User`.
- Registered `routes/api.php` in `bootstrap/app.php` with `apiPrefix: 'api/v1'`.
- Created 2 migrations:
  - `app_notifications` table (id, student_id, type, title, body, read_at) — for in-app feed.
  - Added `course_id` (FK) and `opportunity_type` (string) to `exceptional_requests`.
- Created `App\Models\AppNotification` with `student()` belongsTo, `unread()` scope.
- Updated `Student` model — added `appNotifications()` HasMany.
- Updated `ExceptionalRequest` model — added `course_id`, `opportunity_type` to fillable; added `course()` BelongsTo.
- Created **15 API resources** under `app/Http/Resources/Api/V1/`:
  StudentResource, SemesterResource, CourseResource, CourseOfferingResource, EnrollmentResource, GradeResource (publishing-aware: hides fields by `published_at`/`midterm_published_at`), SurveyResource, AddDropRequestResource, SuspensionRequestResource, ThesisResource, DiscussionResource, ExceptionalRequestResource, WarningResource, AppNotificationResource, ClearanceResource.
- Created **12 controllers** under `app/Http/Controllers/Api/V1/`:
  AuthController (login/logout/me), SemesterController, EnrollmentController, GradeController, CourseTreeController (groups by general/specialized/elective + per-course status: passed/enrolled/failed/not_taken), SurveyController (transactional bulk replace), AddDropController (with options endpoint exposing window state, usage, addable, droppable), SuspensionController (with three-state options), ThesisController (with eligibility breakdown + phase derivation), ExceptionalRequestController (with failed-courses options), ClearanceController, NotificationController.
- Created `MobileTestStudentSeeder` — produces:
  - Active semester "الفصل الدراسي الأول 2026" + previous "الفصل الدراسي الثاني 2025"
  - Updates spec name `الحاسب الآلي → علوم الحاسوب` (idempotent)
  - Test student `26CS101` (Active, "محمود علي الترهوني", phone 0910000000, password=password)
  - Second student `26CS102` (OnHold) for OnHold banner verification
  - 4 enrollments in previous semester with fully-published grades (A, B+, C+, F)
  - 4 enrollments in active semester (2 midterm-only, 1 unpublished, 1 dropped)
  - 1 active CourseFailure warning (CP611)
  - 2 unread app_notifications
  - GPA recomputed to 2.50

**Verified:**

- `php artisan migrate:fresh --seed --force` runs clean.
- Tinker query confirms: 26CS101 status=active, gpa=2.5, 7 active enrollments, 4 published grades, 2 midterm-only, 1 warning, 2 unread notifications.
- Started `php artisan serve --port=8000`. `curl POST /api/v1/auth/login` with `26CS101 / password` returns a real Sanctum token + full StudentResource.
- Smoke tested 5 endpoints with bearer token: `/me`, `/enrollments`, `/grades`, `/course-tree`, `/notifications` — all return real seeded data.
- Pest test suite: **9/9 API tests passing** (login + auth flows + endpoint resources).
- `vendor/bin/pint --dirty --format agent` ran cleanly.

**Decisions during build (review):**

- `apiPrefix: 'api/v1'` set in `bootstrap/app.php` so routes file holds clean unprefixed paths.
- Login accepts `device_name` (defaults to `"mobile"`) so the user can have multiple device tokens.
- `GradeResource.publish_state` returns `"published" | "midterm_only" | "unpublished"` so client doesn't have to re-derive from timestamps.
- Suspension `options` endpoint returns `{state: 'available'|'pending_block'|'approved_block', semesters: [...]}` — client just picks the right banner from `state`.
- Add/drop `options` endpoint includes `window_open`, `usage` (with `max:3`), `addable`, `droppable` — single roundtrip for the form.
- `course-tree` endpoint pre-groups by type (general/specialized/elective) on the server, so client renders directly.
- Course pivot type mapping uses `enum CourseType` ("general" | "specialized" | "elective"). DESIGN_SPEC says "إجباري / اختياري"; we treat general+specialized as إجباري, elective as اختياري in the mobile UI.
- Discovered + worked around: when the seeder is re-run standalone (without `WithoutModelEvents`), GradeObserver fires and can flip a student to dismissed if it sees a re-published F. Fresh `migrate:fresh --seed` is the correct path; the seeder is idempotent for adds but the F-grade re-publication is event-sensitive — documented for myself.
- Gave the Grade booted hook explicit fallback in the seeder by computing `total/letter/points` ahead of time, so seeded grades are correct even when `WithoutModelEvents` is in effect during `db:seed`.

**Skipped:**

- `clearance/pdf` binary endpoint — listed for Phase 4 stretch.
- Wiring observers to write `app_notifications` rows on real events — Phase 1 only seeds them. Mobile reads whatever's in the table.
- Form Request classes — used inline `$request->validate()` for now (small validation shapes, project doesn't have other API Form Requests yet).

**Blockers:** none.

**Next:** Phase 2 — Expo skeleton + design system.

---

## Phase 2 — Expo skeleton + design system

(pending)

---

## Phase 3 — Screens

(pending)

---

## Phase 4 — Polish

(pending)
