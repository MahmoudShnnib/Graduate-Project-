---
date: 2026-06-13
tags: [daily, postgres, constraints, grades, faculty, rbac, filament, audit, gap-fixes]
related:
  - "[[../bugs-and-fixes/2026-06-13-pg-status-check-crash]]"
  - "[[../bugs-and-fixes/2026-06-13-course-offering-edit-blocked]]"
  - "[[../decisions/2026-06-13-faculty-only-my-courses]]"
  - "[[../decisions/2026-06-13-coursework-alone-grade-flow]]"
---

# 2026-06-13 — Gap fixes: pg constraints, grade flow, faculty lockdown

Branch: `gap-fixes-06-12`. Seven user-reported items worked end-to-end in one auto-mode
session. Full suite after: **299 passed / 4 pre-existing fails + 1 order-flaky
(`TransferCourseEditTest`, passes in isolation)**. ~18 new tests, zero regressions.
Not committed (no-auto-commit).

## #1 — Postgres `*_status_check` crash on survey close (P0, prod-only)
Root cause + fix in [[../bugs-and-fixes/2026-06-13-pg-status-check-crash]] and
[[../decisions/2026-06-13-drop-orphaned-enum-checks]]. `enum()` columns compiled to pg CHECK
constraints that later value renames never updated. **Two live bombs** found: `students.status`
(the reported one) and `thesis_registrations.status` (`printed` would crash). Fix: migration
`2026_06_13_061411_drop_orphaned_enum_check_constraints` dynamically drops every CHECK on the
enum-born tables (pgsql-only, no-op on SQLite/MySQL). Enum cast is now the single source of truth.
Regression guard: survey-close → Inactive test in `SemesterObserverTest`. **pgsql DDL can only be
proven on prod** (local is SQLite) — run `php artisan migrate`, then close the survey.

## #2 — Faculty saw too much → locked to "موادي" only
Decision: [[../decisions/2026-06-13-faculty-only-my-courses]]. Faculty role had
`ViewAny:Grade/Student/CourseOffering`, which surfaced the admin Grades list, full Student
directory, and CourseOfferings in their sidebar. Trimmed the seeder's faculty perms to just
`ViewAny/View:CourseOffering` (grade entry uses plain Eloquent, no Grade policy — proven by the
#4 tests). Added faculty-exclusion `canAccess`/`shouldRegisterNavigation` to `CourseOfferingResource`
(shares the perm with MyCourse) and to 4 ungated custom pages (`ExamListPage`,
`ExceptionalOpportunitiesPage`, `CoursesSurveyResults`, `ReportsPage`). **Browser-confirmed**:
faculty sidebar = Dashboard + موادي only.

## #3 — Grades nav badge
`GradeResource::getNavigationBadge()` = count of `pending_approval` grades, `warning` color +
tooltip. Tested in `GradeNavigationBadgeTest`.

## #4 — Coursework-alone entry + notify on coursework publish
Decision: [[../decisions/2026-06-13-coursework-alone-grade-flow]]. The premise ("notified the
moment teacher sets grade") was **wrong** — notification only ever fired on publish-finals.
Real changes: (a) faculty `enter_grade` form — final optional, coursework still required;
(b) faculty can re-open an approved coursework-only grade to add the final (resets to Draft →
re-approve → publish-finals); (c) `publish_midterms` now sends a new
`CourseworkGradePublishedNotification`. Portal already gates display on the publish timestamps
(not status), so reverting to Draft doesn't hide published coursework. Tests in
`GradeCourseworkFlowTest` (4).

## #5 — Exam-list accept/reject → styled confirmation
Replaced native `wire:confirm` on `ExamListPage` with Filament `Action` objects
(`markPassedAction`/`markFailedAction`) using `requiresConfirmation()` + modalHeading/description,
disabled when nothing selected. Tests in `ExamListConfirmationTest` (3).

## #6 — "Can't edit course offering / Save greyed out" (TWO bugs)
[[../bugs-and-fixes/2026-06-13-course-offering-edit-blocked]]. (a) The global save-button lock
(`AppServiceProvider::saveButtonGuard`) only listened for `input`/`change`; Filament Toggle/Select
render as buttons and never fire those → Save deadlocked. Added a `click` listener (excludes the
submit button). (b) **The real blocker**: the `semester_id` "duplicate open offering" validation
rule didn't exclude the record being edited, so any existing open offering self-matched and could
never save. Added `->when($record, ...->where('id','!=',$record->id))`. Note: a UNIQUE
`(course_id, semester_id)` constraint means a true second offering is impossible — the rule could
*only* ever self-match. **Browser-confirmed** end-to-end: toggle → Save enables → save → toast →
DB persists. Tests in `CourseOfferingEditTest`.

## #7 — Grade audit rows showed "إضافة درجة — —"
Added optional `auditSubjectLabel()` hook in `AuditService::subjectLabel()`. `Grade` →
"student - course"; `CourseOffering` → "course - semester" (fixes the `grade_submitted`/
`grades_published` rows logged against the offering). Tests in `AuditSubjectLabelTest`.

## ⚠️ Dev-DB pollution (mine, this session) — CLEANED UP
A debugging tinker probe ran `Semester::factory()->create(['is_active'=>true])` + `ApproveApplication`
against the **dev** sqlite (mistake — should have used `:memory:`/a test, see
[[feedback_no_factory_probes_on_dev_db]] in memory). It created semester "خريف 1982" (became active,
archived the real "خريف 2025"), a junk specialization **"maiores"** + an accepted student
(`22196101`/user), and I later created `CourseOffering #3` for the #6 repro.

**Cleaned up on user request:**
- Deleted specialization "maiores" (id 4), its student (force-deleted — Student uses SoftDeletes so
  the soft-deleted row kept the FK), and its user.
- Force-restored semesters via raw `DB::table` (no observer cascade): "خريف 2025" `is_active=1,
  archived_at=null`; deleted "خريف 1982"; nulled the 13 audit_logs that pointed at it; deleted offering #3.
- Verified: 1 semester (خريف 2025 active), 3 real specializations, no junk.

**Not fully reversible:** un-archiving خريف 2025 didn't pull its original audit logs back out of
`audit_log_archives` (the archive cascade had moved them), and `cs.faculty@example.ly`'s seeded
password is now `faculty123` (hash can't be recovered). Both negligible (dummy data); offered a full
reseed if a pristine dev state is wanted.

## Deploy notes
- Run `php artisan migrate` (the new constraint-drop migration) on **prod (pgsql)**, then verify
  survey close works.
- Re-run `RolesAndPermissionsSeeder` on prod so faculty perms are trimmed (idempotent).
- No `npm` build needed (save-lock change is an inline render-hook script).

## Next steps
- Commit when the user is satisfied (no-auto-commit held all session).
- Optional: browser-confirm #5 modal + #3 badge with seeded pending-approval grades (logic is tested).
