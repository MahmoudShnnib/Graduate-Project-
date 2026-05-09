---
date: 2026-05-02
tags: [daily, test-plan, qa, architect-session]
related: [[../project-context/test-plan]], [[../project-context/project-overview]], [[../project-context/architecture]], [[../project-context/gotchas]]
---

# Architect session — Full System Test Plan

## What was done

- Read every file in `_brain/` (39 files: 4 project-context, 10 daily-notes, 10 decisions, 7 bugs-and-fixes, 2 research, 4 READMEs, plus `SESSION_START_PROMPT.md`).
- Confirmed seeded credentials by reading `database/seeders/AdminUserSeeder.php`, `StudentSeeder.php`, `DummyStudentSeeder.php`. Only Active students seeded; non-Active personas need tinker setup.
- Asked 4 clarifying questions about persona setup, suspension semantics (global flip vs semester-scoped), faculty seeding, and upload-test scope. User answered: tinker pre-creates personas, both suspension behaviors checked, faculty creation as Tier-1 setup row, functional upload edges only.
- Generated comprehensive test plan with **414 test cases** across 34 sections covering every feature, status restrictions, cross-feature interactions, RTL/Arabic UI, Livewire reactivity, auth/session, notification verification, audit log sanity, and garbage inputs.
- Saved to [[../project-context/test-plan]].

## Test plan structure

Each row carries: Test ID · Tier · Category · Description · Expected Result · Verification Method · Status. IDs prefix-coded by feature (APPLY-, ENR-, WARN-CF-, MTX-OH-, etc.). Tier 1 = critical path (~145), Tier 2 = restrictions & cross-feature (~195), Tier 3 = UI/edge spot-checks (~74).

Notable matrix sections:
- **§26 Status Restrictions Matrix** — every non-Active status × every action (30 rows).
- **§27 Cross-Feature Interactions** — dismissal mid-semester, status flip after thesis approval, course closure cascade, etc. (15 rows).
- **§23 Discussion Request** — full 4-phase pipeline coverage including dual-label admin/student abstraction (25 rows).
- **§19 Warnings — course failure / consecutive inactive** — full domain rule coverage (10+8 rows).
- **§31 Notification Verification** — 10 rows ensuring `storage/logs/laravel.log` is the sole verification path (per project rule, never fire real WhatsApp/Qonvo).

## Open questions logged in plan §"Open questions"

8 items where brain didn't fully clarify expected behavior — flagged for review when running:
- un-approving a F grade (warning auto-resolve?)
- coursework_max + final_max sum validation
- duplicate application by same email/national_id
- `/apply/track` rate-limit (P0-3)
- concurrent application approval race (P2-11 gotcha)
- CSRF expiry copy
- faculty cross-course access (P0-2)

## Next steps

- User reviews plan; flags any test rows that need scope/expected-result tweaks.
- After approval, run Setup section (10 rows) end-to-end first, then Tier 1, then Tier 2, then Tier 3 spot-check.
- Keep `storage/logs/laravel.log` baselined per SETUP-009 so notification rows are clean to inspect.

## Files touched

- New: `_brain/project-context/test-plan.md` (414 test rows, ~34 sections)
- New: `_brain/daily-notes/2026-05-02-full-system-test-plan.md` (this note)
