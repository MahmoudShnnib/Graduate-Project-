---
date: 2026-05-15
tags: [daily, filament, applications, ux-audit-fix, h-6, design-conventions, banner]
related:
  - "[[2026-05-15-h3-application-view-actions]]"
  - "[[2026-05-15-ux-audit-admin]]"
  - "[[../research/audit-2026-05-15/audit|audit report]]"
  - "[[../research/audit-2026-05-15/fix-plan]]"
  - "[[../project-context/design-conventions]]"
---

# 2026-05-15 — H-6 fix: disable `قبول مبدئي` when exam date isn't set

## What was done

Audit finding **H-6** closed. Before this fix, clicking `قبول مبدئي` on a pending application without first setting an exam date would (a) open the confirmation modal with text describing a successful outcome, (b) only error out with `يجب تحديد موعد الامتحان أولاً` *after* the admin clicked تأكيد — wasting four interactions and the admin's trust in the modal.

Now: the button is visually disabled when no exam date is set, and a tooltip on hover explains why (`حدد موعد الامتحان أولاً`). On the list page, a warning-colored banner appears above the table pointing admins at the `تحديد موعد الامتحان` button in the header.

## Scope

Applies to **three call sites** of the prerequisite check, all sharing the same one-place definition now that [[2026-05-15-h3-application-view-actions|H-3]] extracted the actions:

1. Row action `قبول مبدئي` on `/admin/applications` (list).
2. Header action `قبول مبدئي` on `/admin/applications/{id}` (view, lifted in H-3).
3. Bulk toolbar action `قبول مبدئي للطلاب المحددين` on the list page.

`قبول مباشر` is **not gated** — it calls `ApproveApplication::handle()` directly and bypasses the exam entirely. Only the initial-acceptance path needs the exam date.

## Files touched

| File | Change |
|---|---|
| `app/Filament/Resources/Applications/ApplicationResource.php` | `initialApproveAction()` static method: added `->disabled(...)` + conditional `->tooltip(...)`. Inline `bulkInitialApprove` BulkAction in `table()`: same gate. |
| `resources/views/filament/resources/applications/pages/list-applications.blade.php` | Added a `@if (! $this->scheduledExamDate)` warning banner between the specialization filter tabs and `{{ $this->content }}`, following the [[../project-context/design-conventions]] §4 "red-card" banner pattern (warning palette, `clock` icon, semibold title + body text). |

## Design conventions honored

- [[../project-context/design-conventions]] **§4 state banners** — used the canonical structure (`bg-warning-50` / `border-warning-200`, circular icon container, two-line title + description) verbatim from existing banners (`thesis-registration.blade.php`, `course-survey.blade.php`, etc.). Color = `warning` because the missing date is advisory, not a hard block on the entire workflow (the admin can still review, edit, delete, set direct-acceptance).
- [[../project-context/design-conventions]] **§2 colors** — Filament `warning` semantic token, not raw Tailwind hex.
- **One-place definition** — both row and view-page actions share `ApplicationResource::initialApproveAction()`, so the gate exists in exactly one method. Bulk action is inline (separate from the static method because it processes a collection, not a single record) — I duplicated the closure rather than extract because the duplication is ~3 lines and the contexts differ.
- **Tooltip only shows when disabled** — `->tooltip(fn ($livewire) => ($livewire->scheduledExamDate ?? null) ? null : '…')`. Filament hides the tooltip when the callable returns null, so the enabled state shows no chrome.

## What you'll see in the browser

`/admin/applications` (no exam date set):
- Warning banner under the specialization tabs: title "موعد الامتحان غير محدد" + description telling the admin to use the header button.
- `قبول مبدئي` buttons on each pending row appear disabled (Filament greys them); hovering shows tooltip "حدد موعد الامتحان أولاً".
- Same for `قبول مبدئي للطلاب المحددين` in the bulk toolbar after selecting rows.

After clicking `تحديد موعد الامتحان` and saving a date:
- Banner disappears.
- All `قبول مبدئي` instances (row, bulk, view-page header) become enabled.
- Tooltip stops rendering.
- `إلغاء الموعد` clear-button appears (existing behavior, unchanged).

`/admin/applications/{id}` (no exam date set):
- No banner (skipped on view page — the disabled+tooltip is discoverable enough since the `تحديد موعد الامتحان` button sits one slot to the left).
- `قبول مبدئي` header action disabled + tooltip; `قبول مباشر` and edit/print untouched.

## Verification

- `vendor/bin/pint --dirty --format agent` → pass
- `php artisan optimize:clear` → clean
- `php -d memory_limit=1024M artisan test --compact` → **158 passed (337 assertions)** (memory budget for the test suite tightened recently — needs ≥768MB in this environment)
- Browser walkthrough still pending — user should:
  1. Refresh `/admin/applications` with no date set → confirm banner + disabled buttons + tooltip on hover.
  2. Click `تحديد موعد الامتحان`, set a date → banner vanishes, all buttons enable.
  3. Click `إلغاء الموعد` → banner returns, buttons disable again.
  4. Drill into a pending row → view-page header shows the same enable/disable behavior on `قبول مبدئي`.
  5. Bulk-select a row → toolbar `قبول مبدئي للطلاب المحددين` follows the same gate.

## Next session anchors

- **H-3 + H-6 cluster fully closed.** Wave 3 of the audit fix-plan is now done.
- **C-3 (Critical RBAC leak)** remains the highest-priority open finding. Wave 2 of the fix-plan covers it. Next likely target.
- **L-3** (Print button on application view page) — still present, violates [[feedback_no_print_button]]. Quick cleanup whenever convenient.
- The `applications.scheduled_exam_date` session key now governs four UI states (list banner, list buttons, view button, bulk button). If a fifth surface ever needs the same check, factor the predicate into a tiny `ApplicationResource::hasScheduledExamDate(Component $livewire): bool` helper.
