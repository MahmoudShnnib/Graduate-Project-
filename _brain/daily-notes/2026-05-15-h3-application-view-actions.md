---
date: 2026-05-15
tags: [daily, filament, applications, view-page, ux-audit-fix, h-3]
related:
  - "[[2026-05-15-ux-audit-admin]]"
  - "[[../research/audit-2026-05-15/audit|audit report]]"
  - "[[../research/audit-2026-05-15/fix-plan]]"
  - "[[../project-context/figma-view-page-design]]"
---

# 2026-05-15 — H-3 fix: surface acceptance actions on the application view page

## What was done

Lifted `قبول مبدئي` + `قبول مباشر` (plus the supporting `تحديد موعد الامتحان` / `إلغاء الموعد` header actions) onto `/admin/applications/{id}`. Audit finding **H-3** (workflow break) closed.

Before: the view page exposed only `تعديل` + `طباعة` in the hero. Admins had to navigate back to the list to actually approve/reject after reviewing. Now the four workflow actions live in Filament's page-header bar (per [[../project-context/figma-view-page-design]] Recipe C — workflow-action-heavy view page), and the exam-date setter state is shared list↔view so admins can run `تحديد موعد الامتحان` from either page.

## Files touched

| File | Change |
|---|---|
| `app/Filament/Resources/Applications/ApplicationResource.php` | Extracted four `Action` definitions into static methods: `scheduleExamAction()`, `clearExamDateAction()`, `initialApproveAction()`, `directApproveAction()`. Row-action array now calls `static::initialApproveAction()` / `static::directApproveAction()` so list page is unchanged. |
| `app/Filament/Resources/Applications/Pages/ListApplications.php` | Replaced inline action bodies with `ApplicationResource::scheduleExamAction()` + `clearExamDateAction()`. Changed `#[Session]` to `#[Session(key: 'applications.scheduled_exam_date')]` so the exam date persists across the list↔view boundary. |
| `app/Filament/Resources/Applications/Pages/ViewApplication.php` | Added the same keyed `#[Session]` property; implemented `getHeaderActions()` with all four shared statics; added `getSubHeading()` mirroring the list page so the "موعد الامتحان: …" status renders on the view page too. |
| `_brain/project-context/figma-view-page-design.md` | Updated implementations table — Application page is now Recipe A + C (infolist + header actions). |

## Design conventions honored

- [[../project-context/figma-view-page-design]] **Recipe C** — workflow-heavy view pages keep `getHeaderActions()` populated; hero card carries only identity (Edit + Print stay where they were).
- [[../project-context/design-conventions]] **§2 colors** — `قبول مبدئي` = `info`, `قبول مباشر` = `success` (unchanged).
- [[../project-context/design-conventions]] **§7 modal copy** — `requiresConfirmation()` + Arabic `modalHeading` / `modalDescription` carried over verbatim from the list-page actions.
- **DRY** — both pages now read from a single source of truth (`ApplicationResource::*Action()`). Future tweaks to the actions touch one method, not two pages.

## Shared-state design choice

Original options were:
- A. **Mirror the list page** — same `#[Session]` property on each page (parallel state, doesn't sync).
- B. **Make exam-date global** — move to cache / DB column.
- C. **Only `قبول مباشر` on the view page** — sidestep the prerequisite entirely.

User chose A. I implemented **A with a shared key**: `#[Session(key: 'applications.scheduled_exam_date')]` on both pages. Livewire scopes the Session attribute by property name + component class by default, so two pages would have parallel state without a shared key. With the explicit key, both pages bind to the same session entry — admin sets the date on the list, drills into a row, and the view page already sees it. Cleaner UX than parallel state and still no schema/cache changes.

## Known issue this fix does NOT resolve

**H-6** (the broader audit finding) is still live at the close of H-3: clicking `قبول مبدئي` when no exam date is set still shows the confirmation modal and only errors out *after* `تأكيد` is clicked. The fix-plan groups H-3 + H-6 in Wave 3, but per user request this session step was H-3 only. Now that the actions are centralised in static methods, H-6's fix (`->disabled(fn() => is_null($scheduledExamDate))->tooltip('حدد موعد الامتحان أولاً')`) is a one-place change — easier than before.

**Update (later same session):** H-6 was fixed right after H-3 — see [[2026-05-15-h6-disable-when-no-exam-date]] for the follow-up. Wave 3 of the audit fix-plan is now fully closed.

## Verification

- `vendor/bin/pint --dirty --format agent` → pass.
- `php artisan optimize:clear` → clean.
- `php artisan route:list --path=admin/applications` → 3 routes registered (index / view / edit).
- `php artisan tinker` → `ViewApplication::getUrl(['record' => Application::first()])` resolves cleanly.
- `php -d memory_limit=512M artisan test --compact` → **158 passed (337 assertions)** — no regressions.
- Browser walk still pending — user should drill into a pending application (e.g. `/admin/applications/9`) and verify the four header actions render and the `تحديد موعد الامتحان` modal works from the view page.

## Next session anchors

- **H-6** — disable the two acceptance actions in their static methods when no exam date is set; add an inline banner above the table on the list page. Should be a small follow-up now that the actions are extracted.
- **L-3** (Print button on application view) — still present in `view-application.blade.php` hero. Established preference (per [[feedback_no_print_button]]) is to remove it. Not bundled this session per CLAUDE.md "one task at a time"; can be a small cleanup pass.
- The next wave of UX-audit fixes per `_brain/research/audit-2026-05-15/fix-plan.md` — **C-3 RBAC leak** (Wave 2) is the highest-impact remaining finding and unrelated to the Applications resource.
