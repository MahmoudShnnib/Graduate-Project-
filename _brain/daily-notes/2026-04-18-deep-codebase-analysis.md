---
date: 2026-04-18
tags: [session, analysis, audit, security, grades, enrollment]
related: [[../research/2026-04-18-deep-codebase-analysis]], [[../bugs-and-fixes/2026-04-18-double-gpa-recalc-and-audit-churn]], [[../project-context/gotchas]], [[../../CLAUDE]]
---

# 2026-04-18 — Deep codebase analysis session

## What was done

- Full systematic scan of the Laravel 12 + Filament v5 application — every folder under `app/`, every Filament resource, every migration, factory, seeder, and route.
- Mapped all major flows (public apply, approval, staff enrollment, add/drop, grade lifecycle, portal reads) to file-level anchors.
- Produced prioritized issue backlog (P0/P1/P2) with fix sketches — see [[../research/2026-04-18-deep-codebase-analysis|full analysis]].
- **Verified three previously-flagged-unverified claims** against source:
  1. ✅ Double GPA recalc in `ViewCourseGrades` — confirmed.
  2. ✅ `Student.gpa` in `AuditObserver::WATCHED_FIELDS` — confirmed.
  3. ❌ `EnsureStudentRole` not registered on portal — was wrong; it **is** registered at [StudentPanelProvider.php:55](../../app/Providers/Filament/StudentPanelProvider.php#L55).
- Captured verified bugs in [[../bugs-and-fixes/2026-04-18-double-gpa-recalc-and-audit-churn]].

## Decisions made

None finalized this session. The fix plan for double-recalc + audit churn is documented but not applied. No new architectural decisions — the [[../project-context/gotchas]] already covers most of the durable pitfalls this scan surfaced.

A potential upcoming decision: whether to adopt Form Request classes (currently zero in codebase). If picked up, log to `_brain/decisions/YYYY-MM-DD-adopt-form-requests.md`.

## Bugs identified

- [[../bugs-and-fixes/2026-04-18-double-gpa-recalc-and-audit-churn]] — grade approval triggers `GradeCalculationService::recalculateStudentGPA` twice and writes duplicate `Student_updated` audit rows. Two-file fix ready to apply.

## State of the branch

`omar-admin-bbg` still has **uncommitted Phase 4–5 changes** (30+ modified files + deletions of `FacultyGradeEntry.php`, `EnrollmentConfirmedNotification.php`, Student stub dirs). The Apr 16 refactor work was completed but never committed.

Before applying any of the fixes below, either commit the pending work or confirm that's intentional.

## Loose ends / next steps

1. **Commit Phase 4–5 work** (or explicitly defer). The working tree is cluttered.
2. **Apply the double-recalc fix** — 15 min, no blockers. See [[../bugs-and-fixes/2026-04-18-double-gpa-recalc-and-audit-churn#Fix]].
3. **Security sweep** — P0-1, P0-2, P0-3 from the analysis:
   - Add `$this->authorize(...)` to `EnrollStudents` page actions.
   - Add faculty ownership check in `ViewMyCourse::mount()`.
   - Add `throttle:5,1` middleware to `/apply/track`.
4. **Cache active semester** (P1-4) — singleton/`->remember()` lookup.
5. **Confirm lingering questions** logged under "Things still unverified" in [[../research/2026-04-18-deep-codebase-analysis#Things still unverified]]:
   - Does `super_admin` bypass mask any broken policy methods?
   - Is `EnrollmentNumberService` ever called from queued workers?
   - Are all `Student::enrollments()` callers in non-presentation code swept?

## Session meta

- First populated entry in `_brain/daily-notes/` — prior sessions (Apr 16 Phase 4–5, Apr 17 brain setup) never wrote their notes. The automatic session rule in CLAUDE.md now has precedent to follow.
- `_brain/project-context/` is unchanged this session — `gotchas.md` already covers 90% of what this deep scan surfaced (enrollment race, singleton semester, audit watchlist, grade auto-compute duplication, etc.). Only genuinely-new findings went into the research note above.
