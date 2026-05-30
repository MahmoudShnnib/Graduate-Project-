---
date: 2026-05-15
tags: [ux-audit, admin, portal, filament, a11y, perf, complete]
related: [[2026-05-15-admin-ux-audit|audit report]] [[fix-plan]] [[2026-05-15-rbac-posture]] [[2026-05-15-arabic-first-leakage]] [[design-conventions]] [[figma-view-page-design]]
---

# UX Audit of `/admin` + `/portal` — All 4 Roles

> **HANDOFF FOR 2026-05-16 SESSION:** Start by reading [[fix-plan]]. It's an ordered, 7-wave fix list with file paths + verification commands for every finding. The two decisions to read first: [[2026-05-15-rbac-posture]] (security) and [[2026-05-15-arabic-first-leakage]] (i18n architecture).

## What

Ran a 5-round UX audit of the Filament admin panel + student portal across **all 4 roles** (super-admin, secretary, faculty, student), using the `ux-audit` skill driving the live app via `chrome-devtools-mcp` attached to a Chromium on `:9222`. **No code changes** — purely diagnostic.

All artifacts in `_brain/research/audit-2026-05-15/`:
- `audit.md` — verdict, findings (40), scorecard, roadmap, interaction manifest, self-critique log, per-page coverage matrix
- `fix-plan.md` — **ordered fix plan for 2026-05-16 session** (7 waves, file paths, verification per fix)
- `screenshots/` — 52 chronologically numbered PNGs

Decision notes spawned by the audit:
- [[2026-05-15-rbac-posture]] — C-3 RBAC leak is a per-role config gap, not a framework limit (faculty proves the pattern works)
- [[2026-05-15-arabic-first-leakage]] — 6 categories of English bleeding into the Arabic-first UI

## Verdict

**Fail** (hard-gate: 6 axe Critical + systemic Filament form-field issue).

40 net actionable findings (after rounds 1–5 + self-critique prune):
- Critical: 3 (C-3 RBAC leak — strengthened in round 5 by counter-evidence that faculty nav DOES filter, so secretary's exposure is a per-role config gap, not a framework issue)
- High: 9 (H-8 portal English titles, H-9 nav doesn't filter by role)
- Medium: 16 (round 5 added: M-16 upload modal mixes AR+EN, M-17 28 unlabeled links on /admin/my-courses, M-18 faculty dashboard widgets show non-faculty data)
- Low: 12 (round 5 added: L-16 grade-distribution modal lacks sum-to-100 validation feedback)

## Top 5 to fix first

1. **[H-3]** Application view page has no decision actions — surface the two acceptance buttons in the hero `HeaderActions`.
2. **[H-6]** `قبول مبدئي` triggers full confirmation modal then errors out because exam date isn't set — disable the button when prerequisite isn't met.
3. **[C-1]** 6 axe Critical violations across 4+ routes (icon-only buttons + selects missing `aria-label`).
4. **[H-7]** Required-field validation appears in English ("Please fill out this field.") on Arabic-first forms — add `novalidate` to Filament forms.
5. **[H-1]** Figma hero meta text fails colour contrast on every view page — bump the muted-palette text colour one step darker.

## What I tested (final after 5 rounds)

**Round 1 (initial sweep)**
- All 20 admin routes — axe-core + console + network on each
- Performance trace on dashboard (LCP 1.18s, CLS 0.05 — green)
- Responsive sweep (1440 / 768 / 375)

**Round 2 (super-admin interactions)**
- T1 — Applications thread end-to-end. Approval modal triggered, hit exam-date prerequisite (H-6 found here).
- Confirmation modals (approve + delete), bulk-select toolbar
- Form stress on User edit (Arabic + diacritics + emoji + apostrophes + HTML injection)
- Required-field validation (H-7 found here — English browser-native validation)
- Tab order on dashboard (28 tabs to reach main content)

**Round 3 (grade + suspension flows)**
- T3 walked admin-side (grade publish modal — best confirmation copy in the app)
- T4 read-only (the one suspension request was already approved)
- Exam-date setter modal opened + cancelled

**Round 4 (deep + second role + portal)**
- T2 enrollment manager + Add Student modal
- T5 student detail deep walk
- Keyboard-only first 30 tabs (no skip-to-content link)
- Logged in as `secretary@college.edu` — discovered C-3 RBAC leak
- Logged in as student `mohammed.zawai@example.com` — walked portal dashboard + 6 nav pages

**Round 5 (faculty role)**
- Logged in as `cs.faculty@example.ly` (`د. عبدالله الورفلي`)
- Faculty nav correctly filtered to 9 items (proves the pattern works → strengthens C-3)
- Drilled `/admin/my-courses/1`, opened distribution + upload-sheet modals
- Found M-16 (upload modal mixes AR+EN), M-17 (28 unlabeled links), M-18 (irrelevant widgets), L-16 (no sum-to-100 indicator)

## Test credentials (seeded — all dummy, refreshable)

| Role | Email | Password |
|---|---|---|
| super-admin | `admin@college.edu` | `admin123` |
| secretary | `secretary@college.edu` | `secretary123` |
| faculty | `cs.faculty@example.ly` | `password` |
| student (test) | `ppsan@pp.com` | `ppsan@69` |
| student (factory) | `mohammed.zawai@example.com` | `12345678` |

## Browser-MCP setup that worked

```bash
chromium-browser --remote-debugging-port=9222 --user-data-dir=/tmp/chrome-audit
claude mcp add chrome-devtools -- npx -y chrome-devtools-mcp@latest --browserUrl http://127.0.0.1:9222
```

Then in the Chromium window, log into the role being audited, leave the tab on a logged-in page, restart Claude Code. The `chrome-devtools-mcp` attaches via the debug port. Sessions don't co-exist across `/admin` and `/portal` — logging in to one logs out of the other.

## What I deliberately did not test (final gap list)

See `audit.md` "Coverage map" + "Surfaces still not opened" for the canonical version. Headlines:

- **Phase 7 fix-and-verify** — that's tomorrow's session.
- **Full keyboard end-to-end thread** — only first 30 tabs tested, did not complete a thread.
- **Heavy data scenarios** — lists have <30 rows; would need seed expansion.
- **Skill battery scenarios still skipped**: returning user, interrupted workflow, slow network, offline, reduced motion, high-contrast, print stylesheet, race conditions.
- **A few admin sub-routes never opened**: `admin/student-add-drop/{student}/{semester}`, `admin/student-survey/{student}/{semester}`, `admin/exceptional-opportunities-page/{student}`, audit-log detail, graduate detail, discussion-request detail, thesis-registration detail, specialization CRUD, semester CRUD, role CRUD. Each is a Filament default page and likely inherits the same finding patterns already documented.

## Decisions made this session

Two architectural-level decisions were promoted from the audit findings:

- [[2026-05-15-rbac-posture]] — adopt explicit "nav + policy must both gate on the same role check" rule; fix C-3 by extending the faculty filtering pattern to secretary's view of Shield/Roles, Users, Audit Logs.
- [[2026-05-15-arabic-first-leakage]] — adopt explicit "if a user can see it, it must be Arabic" rule; six categories of leak identified with concrete fix patches.

These promote pre-existing implicit conventions to explicit, documented decisions so future code review can enforce them.

## Loose ends / next steps

- **All 4 admin roles walked**: super-admin, secretary, faculty, student. Three role audits complete.
- **Round 5 completed**: faculty dashboard, /admin/my-courses, drilled offering 1, opened distribution + upload-sheet modals.
- **Still gapped**: full keyboard thread completion (only first 30 tabs tested), heavy data scenarios (would need seed expansion), skill-battery scenarios still skipped (returning user, interrupted workflow, slow network, offline, reduced motion, high-contrast, print). Several admin sub-routes never opened (see `audit.md` "Surfaces still not opened" list).
- **Fix-and-verify** (Phase 7) is not started — that's the next session after findings are reviewed.
- **CI integration**: roadmap suggests wiring `axe-core` to run against `/admin` AND `/portal` on every PR.
- **Highest-priority follow-up**: C-3 (RBAC leak) is a security finding, not just UX. Round 5 *strengthened* this finding by proving faculty's nav filtering works correctly — so secretary's leak is a per-role config oversight, not a framework limitation. Fix this before shipping.

## Skills used this session

- `find-skills` → discovered + installed `ux-audit` and `nielsen-usability-heuristics`
- `ux-audit` → drove the 6-phase audit across 5 rounds
- Self-critique sub-agent (fresh general-purpose agent) → pruned 5 of 27 findings after round 2

## Tooling notes

- `chrome-devtools-mcp` configured with `--browserUrl http://127.0.0.1:9222` to attach to a user-managed Chromium with remote debugging. Same approach works for any future browser-driving session.
- Plan file (round 2): `~/.claude/plans/cryptic-knitting-pelican.md` (historical, superseded by `_brain/research/audit-2026-05-15/fix-plan.md`).

## Quick links for tomorrow

| Doc | What it's for |
|---|---|
| [[fix-plan]] | **Start here.** Ordered 7-wave fix list with file paths + verification per fix. |
| [[2026-05-15-admin-ux-audit\|audit report]] | Full findings (40), scorecard, coverage map, interaction manifest. Reference. |
| [[2026-05-15-rbac-posture]] | Context + alternatives for the C-3 fix. Read before touching policies. |
| [[2026-05-15-arabic-first-leakage]] | Context + alternatives for Wave 4 (i18n fixes). Read before touching titles/empty states. |
| `screenshots/` | 52 PNGs as visual evidence per finding. |

