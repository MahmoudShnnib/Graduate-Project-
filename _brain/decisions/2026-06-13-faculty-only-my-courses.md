---
date: 2026-06-13
tags: [decision, rbac, shield, filament, faculty]
related:
  - "[[2026-06-13-coursework-alone-grade-flow]]"
  - "[[../project-context/architecture]]"
---

# Faculty admin-panel scope = "موادي" only

## Context
User: "professors see most things now." The `faculty` Shield role was granted
`ViewAny:Grade`, `ViewAny:Student`, `ViewAny:CourseOffering`, so every resource keyed on those
models (admin Grades list, full Student directory, CourseOfferings, Graduates) appeared in the
faculty sidebar. (An earlier exploration *assumed* policies denied faculty — wrong; Shield was
granting.)

## Decision
A professor's admin experience is **only "موادي" (`MyCourseResource`)** + Dashboard +
change-password. Implemented two ways:
1. **Trim seeder perms** (`RolesAndPermissionsSeeder`): faculty → only
   `ViewAny:CourseOffering`, `View:CourseOffering`. Grade entry on MyCourse uses plain Eloquent
   (no Grade policy check — proven by `GradeCourseworkFlowTest` granting only CourseOffering perms),
   so Grade/Student perms were pure leakage. Removing them auto-hides GradeResource, StudentResource,
   GraduateResource.
2. **Explicit faculty exclusion** where the perm is shared or pages are ungated:
   - `CourseOfferingResource` (shares `ViewAny:CourseOffering` with MyCourse) →
     `canAccess`/`shouldRegisterNavigation` return false for faculty.
   - 4 ungated custom pages (`ExamListPage`, `ExceptionalOpportunitiesPage`,
     `CoursesSurveyResults`, `ReportsPage`) → same exclusion (Pages aren't gated by Shield perms).

## Alternatives
- Keep perms + only add resource overrides: more overrides, violates least-privilege.
- Expand faculty access (read-only students etc.): not requested; start minimal, expand on request.

## Consequences
- Browser-confirmed: faculty sidebar = Dashboard + موادي.
- Secretary/super-admin unaffected (exclusion is `role !== 'faculty'`).
- `ViewCourseGrades`'s faculty-owner `boot()` branch is now unreachable (faculty can't reach
  GradeResource) — dead but harmless; MyCourse is the faculty path.
- Tests: `FacultyResourceVisibilityTest`. Re-run the seeder on deploy.
