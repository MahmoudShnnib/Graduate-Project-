---
date: 2026-05-14
tags: [daily, filament, infolist, figma, suspension-requests, view-page, design-system]
related:
  - "[[../project-context/figma-view-page-design]]"
  - "[[2026-05-13-figma-view-pages-batch]]"
  - "[[2026-05-14-shield-permissions-restored]]"
---

# 2026-05-14 — Suspension-request view page (Figma pattern, 9th implementation)

## What was done

Added a Figma-style view page at `/admin/suspension-requests/{id}`. Rows on the index are now clickable and navigate to the view, where approve/reject also exist as page-header actions (in addition to the existing row actions on the index).

### Files

| File | Change |
|---|---|
| `app/Filament/Resources/SuspensionRequests/SuspensionRequestResource.php` | Added `infolist()` (2-column: request details / decision details); registered `view` page in `getPages()`; added `->recordUrl(...)` on table |
| `app/Filament/Resources/SuspensionRequests/Pages/ViewSuspensionRequest.php` | New — `getHeading('')`, `getBreadcrumb($name)`, page-header `approve` + `reject` Actions cloned from row actions |
| `resources/views/filament/resources/suspension-requests/pages/view-suspension-request.blade.php` | New — hero card (`.figma-suspension-view` wrapper) + scoped CSS + `{{ $this->infolist }}` |
| `_backups/suspension-request-view-2026-05-14/` | Revert script |

### Hero composition

- Student name (h1)
- Status pill (Pending=warning, Approved=success, Rejected=danger)
- Semester chip (info pill)
- رقم القيد subtitle (tabular nums)
- Meta row: specialization, تقديم date, قرار date (when decided)
- Print button (no Edit — suspensions aren't edited, only decided)

### Infolist layout

| Column | Section | Entries |
|---|---|---|
| Right | تفاصيل الطلب | الطالب · رقم القيد · التخصص · الفصل · تاريخ التقديم · سبب الإيقاف (full-width) |
| Left | قرار الإدارة | الحالة (badge) · قرر بواسطة · تاريخ القرار · ملاحظات القرار · pending callout |

The "pending callout" is a `TextEntry` with `fsv-pending-callout` extra class — visible only while status = Pending — that nudges admins to use the page-header buttons.

### Row → view navigation

`->recordUrl(fn (SuspensionRequest $record): string => ViewSuspensionRequest::getUrl(['record' => $record]))` on the table. Filament v5 turns this into the row-link affordance automatically; the row hovers as a clickable element and clicking anywhere outside an action button navigates to the view page.

The existing `approve` + `reject` row actions still work — Filament handles action-button clicks before the row link, so they don't conflict.

### Approve/Reject duplication

Approve/Reject logic now lives in two places:
1. The original row actions on `SuspensionRequestResource::table()` (kept so admins can decide directly from the index)
2. The page-header actions on `ViewSuspensionRequest` (added so a clicked-into row can be decided in place)

Both call the same `SuspensionStatus` flips, `AuditService::log()`, `NotificationService::notifySuspensionDecision()`, and `Student::status = OnHold` cascade. The row action stays in the Resource because that's the only surface that sees the row in list context; the page-header action lives on the View page so it has direct access to `$this->record`, `$this->refreshFormData()`, etc.

Could be refactored to a single shared closure later if a third surface ever needs the same action — for now duplication is cheaper than abstraction (two call sites, same shape, both small).

## Verification

- `php artisan optimize:clear` clean
- `php artisan route:list --path=suspension-requests` shows `GET admin/suspension-requests/{record}` registered
- `php artisan tinker` resolves `ViewSuspensionRequest::getUrl(['record' => $req])` to `http://localhost/admin/suspension-requests/1` cleanly with full eager-load chain (student → user/specialization, semester, decidedBy)
- `vendor/bin/pint --dirty --format agent` pass
- `php artisan test --compact --filter=SuspensionRequest`: 6 pass, 3 pre-existing fails unrelated to this change (`SuspensionRequestResubmitGuardTest` referencing a removed API method — see [[../bugs-and-fixes/2026-05-10-onhold-students-not-released-on-new-semester#Pre-existing issue surfaced (NOT fixed in this phase)]])

## Pattern update

[[../project-context/figma-view-page-design]] currently lists 8 implementations. With this one we're at 9. When the next view-page redesign happens, also update the implementations table there.

## Next steps

- Browser-test the view page in both light + dark themes
- Confirm approve/reject from the view page's header refreshes the page state correctly (the `$this->refreshFormData(...)` call should redraw the infolist without full reload)
- Consider promoting `.fsv-*` hero/section CSS to `resources/css/filament/admin/theme.css` — we now have 9 implementations sharing the exact same block, the API is stable enough
