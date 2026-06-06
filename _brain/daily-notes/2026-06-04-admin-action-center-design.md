---
date: 2026-06-04
tags: [daily, dashboard, widget, filament, admin, design, planning]
related:
  - "[[../project-context/admin-action-center-guide]]"
  - "[[2026-06-04-password-management-design]]"
---

# 2026-06-04 — Admin dashboard Action Center (design)

**Design session only — not implemented.** Designed a top-of-dashboard widget that
consolidates every pending-decision item for super_admin / secretary into clickable cards
("things waiting for his command").

Full design + "where to edit" map → [[../project-context/admin-action-center-guide]].

## Decisions locked (via Q&A)
- **Form factor:** clickable stat cards (Action Center), extends Filament `StatsOverviewWidget`,
  pinned above the existing stats widget.
- **Role scope:** super_admin + secretary only (`role !== 'faculty'`, same as current stats widget).
- **Zero items:** always show every card; `0` rendered greyed (muted color when nothing pending).
- **Counts** reuse each resource's existing `getNavigationBadge()` rule verbatim, so dashboard
  + nav never disagree. 8 categories: applications, suspension, add/drop, thesis, discussion,
  course-survey (unprocessed), exceptional, grades-pending-approval.
- **Links** via `Resource::getUrl()` — no hardcoded `/admin/...`.

## Open sub-decision (awaiting user)
- Whether to **move** "pending applications" + "pending grades" out of `StatsOverviewWidget`
  (they'd otherwise appear in both it and the Action Center). Recommended: move them, leaving
  `StatsOverviewWidget` as pure info. Not yet confirmed.

## Next steps
- Implementation not started. Resume with: *"build the Action Center widget per the guide"* →
  test with `Livewire::test`, `pint` + `php artisan test --compact`, leave uncommitted (per
  [[no-auto-commit]]).
