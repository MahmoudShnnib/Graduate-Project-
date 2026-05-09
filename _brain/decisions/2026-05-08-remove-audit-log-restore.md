---
date: 2026-05-08
tags: [decision, audit-log, restore, removal]
related: [[../daily-notes/2026-05-08-session-8-tasks]], [[2026-05-08-audit-log-custom-list-view.md]]
---

# Remove Restore Action from Audit Logs

## Context

The audit log had a restore feature that let super-admins revert a model's fields to their previous values by clicking "استعادة" on any log row or its detail page. This feature was dangerous:

- It called `$model->update((array) $record->old_values)` blindly — no validation, no observer-bypass consideration, no cascade-awareness.
- Reverting a `status` field this way would not fire `StudentObserver`, `GradeObserver`, etc., leaving related state inconsistent.
- It was a write operation disguised in a read-only audit trail view, which violated the principle of least surprise.

The user requested it be removed entirely.

## Decision

Remove the restore action from all surfaces and delete all supporting code.

### What was removed

| Location | What was removed |
|---|---|
| `AuditLogResource.php` | `Action::make('restore')` from `recordActions()`; `canRestoreRecord()` method; `Notification` + `View` imports |
| `ViewAuditLog.php` | Entire restore header action; `Action`, `Notification`, `Heroicon`, `View` imports |
| `ListAuditLogs.php` | `$restoreAuditId` property; `getRestoreRecord()`, `openRestoreModal()`, `closeRestoreModal()`, `restore()` methods; `Notification` import |
| `list-audit-logs.blade.php` | `$restoreRecord` variable; per-row restore button; entire RESTORE CONFIRMATION MODAL section |
| `modals/restore-modal.blade.php` | **File deleted** |

### Audit log is now read-only

The audit log page can only **view** records. If an admin needs to revert a change, they must do it through the proper edit UI for that model — which fires all observers and validation.

## Alternatives considered

- **Keep but restrict to super_admin only** — rejected; the underlying problem is that blind `update()` bypasses observers, regardless of who does it.
- **Rewrite to fire proper update paths** — rejected; scope creep, not requested, and each model's update path is different. Proper revert should go through the model's own edit form.
