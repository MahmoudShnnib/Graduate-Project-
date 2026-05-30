# Graduate Studies Management System — Documentation Index

> A Libyan university graduate-studies admissions, enrollment, and academic-lifecycle management system. Built with Laravel 12 + Filament v5. Arabic-first RTL, multi-panel, role-based.

## Documents

| File | What it covers |
|---|---|
| [01-system-overview.md](01-system-overview.md) | What the system does, actors, end-to-end student journey, tech stack, configuration |
| [02-architecture-and-patterns.md](02-architecture-and-patterns.md) | System architecture, design patterns, observer layer, request lifecycles, auth model |
| [03-data-model.md](03-data-model.md) | All 17 models, relationships, fields, all enums, pivot tables, GPA formula |
| [04-features-admin-panel.md](04-features-admin-panel.md) | Every admin panel resource, page, and widget in detail |
| [05-features-student-portal.md](05-features-student-portal.md) | Every student portal page, public application form, and portal middleware |
| [06-academic-lifecycle-rules.md](06-academic-lifecycle-rules.md) | Enrollment rules, grading, retakes, warnings, dismissal, thesis eligibility, discussion pipeline, clearance |
| [07-notifications-pdf-testing.md](07-notifications-pdf-testing.md) | Notification architecture, PDF/print system, test setup, test plan summary, known limitations |

## Quick Reference

**Panels:**
- Admin panel: `/admin` — super admin, secretary, faculty
- Student portal: `/portal` — students only
- Public application: `/apply` and `/apply/track`

**Key models:** User → Student → StudentEnrollment → Grade (auto-computed) → AuditLog

**Status lifecycle:** Active → OnHold/Inactive/Transferred/Graduated/Dismissed

**Thesis pipeline:** eligible → submit → print → approve → request discussion → 4-phase discussion → clearance

**Grade pipeline:** draft → pending_approval → approved → midterm_published → published (GPA recalculates)

**Roles:** `super_admin` (bypasses all policies) | `secretary` | `faculty` (own courses only) | `student` (portal only)
