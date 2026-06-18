---
date: 2026-06-18
tags: [docs, readme, rbac, actors, repo-cleanup]
related: [[../project-context/project-overview]], [[../project-context/architecture]]
---

# 2026-06-18 — README actor model fix + repo cleanup follow-up

## What was done

### Corrected the actor model (the headline fix)
Both `README.md` and [[../project-context/project-overview]] described the system as having **three actors**, lumping `super_admin · secretary · faculty` into one undifferentiated **"Staff"** row. That hid the real RBAC design — three of those roles share `/admin` but see very different things.

Replaced with the **real five-actor model**, sourced from the role code (`User::canAccessPanel`, `RolesAndPermissionsSeeder::syncRolePermissions`, `UserForm` Arabic labels, `AdminUserSeeder`):

| Actor | `users.role` | Spatie/Shield role | Scope |
|---|---|---|---|
| Applicant (متقدم) | — (not a user) | — | apply + track only; promoted to Student on approval |
| Office Head (رئيس مكتب الدراسات العليا) | `super_admin` | `super_admin` | everything + `Gate::before` bypass; users/roles/audit log |
| Administrative Assistant (مساعد إداري) | `manager_assistant` | `manager_assistant` | day-to-day academics; reads all **except** admin-only models (`Role`, `User`, `AuditLog`, `NotificationLog`) |
| Faculty Member (عضو هيئة التدريس) | `faculty` | `faculty` | own course offerings ("موادي") only; holds just `ViewAny:CourseOffering` + `View:CourseOffering` |
| Student (الطالب) | `student` | `student` | `/portal` (RTL) |

**Key non-obvious points captured:**
- The assistant's Spatie role is `manager_assistant`, but the `users.role` column may still carry the legacy value `secretary` — `canAccessPanel` and `repairUserRoles` both accept and map it. Earlier docs called this actor "secretary."
- Faculty are narrowed at the **resource level** (`faculty_id == auth()->id()` in `MyCourseResource`), not via Shield row-level scoping — they deliberately hold almost no permissions.

### Files touched
- `README.md` — rewrote About actor table; fixed downstream "secretary/super-admin" wording and the Architecture roles bullet.
- `_brain/project-context/project-overview.md` — same five-actor correction + naming note.

### Shipped
- PR #61 (`docs/fix-readme-actors` → `main`): docs-only actor correction. PR #60 (original README rewrite) was already **merged**, so this went on a fresh branch off the current README state.

## Context from this session (no action, but worth recording)
- **Repo cleanup PR #59 is merged.** It `git rm --cached`'d 100 dev/report artifacts: `diagram-shots/` (37), `_backups/` (30), `doc-screenshots/` (11), other-AI configs (`.cursor`, `.junie`, `.codex`, `.gemini`, `.agents`, `.ai`, `GEMINI.md`, `AGENTS.md`), and ERD images (`chen-erd.*`, `diagrams.html`). **None are runtime-critical.**
- **Will those files leave the server?** Yes if the server deploys via `git pull`/`clone`/Docker-from-clone — the recorded deletions remove them on next deploy. No if the server is updated by rsync/SFTP of a file subset. Local copies are untouched (`--cached`), preserved for the graduation report. Deploy/hosting platform is still **TBD** (Azure dropped) — confirm method before relying on auto-removal.

## Open PRs awaiting review
- PR #58 — grade reactivity / survey status / suspension fixes
- PR #61 — README actor-model correction (this session)
- (PR #59 cleanup and PR #60 README rewrite already merged)

## Next steps
- None in flight. If hosting platform gets decided, document it and revisit the server-side cleanup question above.
