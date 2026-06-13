---
date: 2026-06-12
tags: [notifications, whatsapp, filament, audit-log, dashboard, bell]
related: [[../project-context/architecture]], [[../bugs-and-fixes/2026-06-12-whatsapp-status-was-optimistic]]
---

# 2026-06-12 — Notification Log Viewer + Admin Activity Bell

Branch: `gap-fixes-06-12` (off fresh `main`, 16 commits ahead of prior local).

## What was done

### Part 1 — Fixed WhatsApp delivery status accuracy

**Root bug:** `NotificationService::dispatch()` marked WhatsApp as `'sent'` based on config existence alone — before the HTTP call. A Qonvo failure was invisible in the DB.

**Fix:**
- Migration `2026_06_12_194551_fix_notification_logs_table`: `student_id` → nullable, `status` ENUM → `VARCHAR(20)`, added `error_message TEXT NULL`.
- Moved WhatsApp logging into `WhatsAppChannel::send()` — creates `NotificationLog` **after** the real HTTP call: `'sent'` on success, `'failed'` + `error_message` on catch.
- Stripped all logging from `NotificationService::dispatch()` (it was the source of the lie). Simplified the service significantly — callers no longer pass `$student`, `$event`, `$message` args.
- Added `public string $logEvent = '...'` to all 9 notification classes so the channel can read the event name without a class-name map.

### Part 2 — NotificationLog Filament Resource

New read-only resource at `/admin/notification-logs`:
- Nav group `إدارة الوصول والنظام`, sort 5 (after AuditLog).
- Table: student name + enrollment no, event (Arabic label from `EVENT_LABELS`), status badge (sent=green/failed=red), error message (truncated + tooltip), sent_at.
- Filters: status, event, date range.
- Policy: uses Shield permissions (`ViewAny:NotificationLog`, `Delete:NotificationLog`). Seeder updated — `NotificationLog` added to `$adminOnlyModels` so secretary/faculty never get it.
- Shield permissions generated (12 permissions). Seeder re-run.

### Part 3 — Admin Activity Bell

- Render hook `PanelsRenderHook::USER_MENU_BEFORE` in `AdminPanelProvider`.
- Queries last 15 `AuditLog` entries by `super_admin` / `manager_assistant` users.
- Amber badge = count of entries in last 24h.
- Alpine dropdown: click to open, click outside to close, each row links to `AuditLogResource::getUrl('view', ...)`, footer links to audit log index.
- Blade view: `resources/views/filament/admin-activity-bell.blade.php`.

## Files changed

| File | Change |
|------|--------|
| `database/migrations/2026_06_12_*` | nullable student_id, error_message, varchar status |
| `app/Models/NotificationLog.php` | add error_message to fillable |
| `app/Channels/WhatsAppChannel.php` | log after real HTTP call |
| `app/Services/NotificationService.php` | remove all log calls, simplify dispatch() |
| 9 notification classes | add `public string $logEvent` |
| `app/Filament/Resources/NotificationLogs/*` | new resource + list page |
| `app/Policies/NotificationLogPolicy.php` | new policy |
| `database/seeders/RolesAndPermissionsSeeder.php` | add NotificationLog to adminOnlyModels |
| `app/Providers/Filament/AdminPanelProvider.php` | register USER_MENU_BEFORE render hook |
| `resources/views/filament/admin-activity-bell.blade.php` | bell dropdown view |

## Test results (Part 1–3)

258 passed, 4 failed — all 4 failures are pre-existing on `main` (routing/portal access tests unrelated to this work).

---

## Part 4 — Bug fixes and bell overhaul (second session, same day)

### Bug: Badge count not clearing on click

**Attempt 1 (failed):** Alpine `seen: false` boolean — resets on every page load.
**Attempt 2 (failed):** `$persist(0).as('admin_bell_seen')` — `$persist` timing was unreliable at Alpine init.
**Attempt 3 (failed):** Session + `$wire.showBadge` + `wire:click="markAsSeen"` — `render()` always ran after the action and overwrote `showBadge`. Also `$wire.markAsSeen()` in `@click` handler may not scope correctly to the child Livewire component in a render hook context.
**Root cause found:** `markAsSeen` was only called on button click. Closing the dropdown by clicking *outside* (via `@click.outside`) never triggered it.
**Final fix:** `x-init="$watch('open', val => { if (val) markSeen() })"` — fires the moment the dropdown *opens* regardless of how it was opened. Uses `localStorage` for `lastSeenId`. `$wire.latestId` (reactive Livewire public property) keeps the comparison target fresh on polls.

### Bug: 3 redundant WhatsApp notifications on student acceptance

**Root cause:** `ExamListPage::markPassed()` called `$approve->handle($application)` (which fires `notifyWelcomeStudent`) AND THEN called `$notifier->notifyApplicationExamPassed($application)` redundantly.
**Fix:** Deleted line 99 from `ExamListPage.php` (`$notifier->notifyApplicationExamPassed(...)`). Also removed now-unused `NotificationService` import from that file.
**Verified via NotificationLog table:** Most recent approval (ID 5, 21:30) produced exactly 1 notification (`welcome_student`). Earlier entries (IDs 3+4) were from pre-fix test run.
**Suspension/Thesis double-fire investigation:** Both are safe — status guards (`.visible()` checks on Pending/Printed) prevent double-firing since status changes after first action, hiding buttons everywhere.

### Bug: Bell showing 3 entries per approval (audit log noise)

**Root cause:** `AuditObserver` writes one row per field change (`Application_updated`, `Student_updated`, etc.). A single approval writes 3 rows. Bell query returned all of them.
**Fix:** Added `->whereNotIn('action', ['Application_updated', 'Student_updated', 'Grade_updated', 'StudentEnrollment_updated', 'ThesisRegistration_updated'])` to the bell query. These observer-generated entries use `TitleCase_updated` naming. Manual admin actions use `snake_case` specific names. Bell now shows only meaningful decisions (1 entry per approval).

### Refactor: Bell converted to Livewire component

**Why:** Static render hook blade was server-rendered once — data went stale. User had to refresh page to see new activity.
**New component:** `app/Livewire/AdminActivityBell.php` with `wire:poll.30s` on root div.
- Public properties: `$latestId`, `$badgeCount` (reactive, accessible via `$wire` from Alpine)
- `render()` runs the filtered AuditLog query and updates both properties on every poll
- View: `resources/views/livewire/admin-activity-bell.blade.php`
- Wrapper: `resources/views/filament/admin-activity-bell.blade.php` → `@livewire('admin-activity-bell')`
- Render hook in `AdminPanelProvider` simplified to one line: `fn () => view('filament.admin-activity-bell')`
- Added `@source "../../../views/livewire/*.blade.php"` to Tailwind config

### Feature: "تمييز كمقروء" button in bell header

Button appears in the dropdown header only when `showBadge` is true (`x-show="showBadge"`). Calls `markSeen()` which writes `latestId` to localStorage. Pure Alpine — no Livewire roundtrip.

### Test data seeded

- 10 pending applications (IDs 5–14) seeded via tinker for testing approvals.
- App IDs 2–3 had `semester_id = null` and didn't appear in filtered views — fixed by setting `semester_id = 1`.

## Files changed (Part 4)

| File | Change |
|------|--------|
| `app/Filament/Pages/ExamListPage.php` | Removed `notifyApplicationExamPassed` call + unused import |
| `app/Livewire/AdminActivityBell.php` | New Livewire component (replaces static render hook query) |
| `resources/views/livewire/admin-activity-bell.blade.php` | New — Livewire view with poll, Alpine badge logic, markSeen |
| `resources/views/filament/admin-activity-bell.blade.php` | Replaced with `@livewire('admin-activity-bell')` |
| `app/Providers/Filament/AdminPanelProvider.php` | Simplified render hook (no more inline query) |
| `resources/css/filament/admin/theme.css` | Added `livewire/*.blade.php` to Tailwind source scanning |

## Lessons / gotchas

- **`$wire` scoping in render hooks:** `$wire.method()` in `@click` is unreliable when the Livewire component is a child embedded via a render hook inside the main Filament panel component. `wire:click="method"` is more reliable as Livewire resolves it by DOM ownership. Even better: avoid round-trips entirely and handle badge state in Alpine.
- **`render()` always runs after actions in Livewire 4:** Any property set in an action method can be overwritten by `render()`. Don't set state in actions that `render()` also computes.
- **`@click.outside` doesn't trigger button's `@click` or `wire:click`:** If marking-as-seen logic is on the button only, closing the dropdown by clicking elsewhere silently skips it. Use `$watch('open')` instead.
- **Observer audit logs are noisy:** `TitleCase_updated` actions come from `AuditObserver`; exclude them from any "summary" views.

## Test results (Part 4)

258 passed, 4 failed — same 4 pre-existing failures. No regressions.

## Next steps

- User to confirm badge clears correctly and stays cleared across navigation.
- Commit all work on `gap-fixes-06-12` branch when satisfied.
