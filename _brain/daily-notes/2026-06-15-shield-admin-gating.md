---
date: 2026-06-15
tags: [daily, rbac, shield, filament, permissions, faculty]
related:
  - "[[../decisions/2026-06-15-shield-permissions-for-admin-gating]]"
  - "[[2026-06-15-go-live-fixes-implementation]]"
---

# 2026-06-15 (evening) — Admin gating switched to Shield

## Trigger
User asked: can faculty add grades, why aren't they on `/admin/exam-list-page`, and
"his roles better be from the filament shields and not hardcoded." Confirmed scope =
**all admin-side access gates → Shield, behavior preserved**.

## Answers given
- **Faculty CAN add grades** — via "موادي" (`MyCourseResource` → `ViewMyCourse`),
  row-scoped to `faculty_id`, plain Eloquent, no Grade permission. The admin
  **Grades** list (`/admin/grades`) is the secretary/admin *approval* screen, not
  entry — correctly hidden from faculty.
- **Exam list hidden from faculty by design** — it's the differential-exam admissions
  review (secretary/super-admin), previously gated by `role !== 'faculty'`.

## Done
- Converted 10 admin-side hardcoded `role` checks to Shield across 8 files
  (ExamListPage, CoursesSurveyResults, ExceptionalOpportunitiesPage,
  DashboardOverviewWidget, ActionCenterWidget, CourseOfferingResource,
  FacultyStatsOverviewWidget, MyCourseResource, ViewCourseGrades). Mapping +
  rationale → [[../decisions/2026-06-15-shield-permissions-for-admin-gating]].
- `UserFactory::withRole()` / `faculty()` now assign the matching Spatie role in
  `afterCreating` (mirrors `repairUserRoles`). Added `facultyUser()` /
  `secretaryUser()` Pest helpers (seed real Shield roles+perms).
- New `tests/Feature/ShieldAccessControlTest.php` (regression guard); updated
  `ActionCenterWidgetTest` to use the permission-seeded helpers.

## Verification
- `php artisan test --compact`: **354 passed** (868 assertions).
- `pint --dirty`: pass.

## Out of scope (left as role-column — identity/routing/ownership, not access perms)
`EnsureStudentRole`, `User::canAccessPanel`, `UserObserver`, `PrintController`
ownership, `PasswordResetController` redirect, `UserInfolist` record-display.

## Loose ends
- Uncommitted (per no-auto-commit) — awaiting go-ahead to commit on
  `chore/go-live-high-medium-fixes` (PR #55 open).
- Future: any new admin Page/Widget should gate on its `View:{Class}` Shield perm,
  not a role string. Captured in auto-memory `feedback-prefer-shield-over-role-strings`.
