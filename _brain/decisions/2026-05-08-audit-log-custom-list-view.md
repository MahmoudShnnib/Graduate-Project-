---
date: 2026-05-08
tags: [audit-log, architecture, filament, livewire, pagination, modal]
related: [[../daily-notes/2026-05-08-audit-log-ui-redesign.md]]
---

# Decision: Custom Blade list view for Audit Logs

## Context

The audit log list page used Filament's default table renderer. The goal was to
replace it with a pixel-matched v0 design while keeping all backend behaviour.

## Decision

Override `protected string $view` on `ListAuditLogs` (which extends `ListRecords`)
to point at a hand-written Blade file. All data fetching and filter state lives on the
Livewire page class. The Filament Resource's `table()` definition stays in place but is
not rendered on the list page — it still governs the `ViewAuditLog` page's filter
definitions and the restore action config.

## Alternatives considered

| Option | Why rejected |
|---|---|
| Style Filament's default table with CSS overrides | Filament's table classes are deeply nested; can't match v0's exact structure |
| Override Filament table slots | Filament v5 doesn't expose enough slots for full structural replacement |
| Separate Filament Page (not Resource) | Would break Filament Shield permissions which are Resource-scoped |

## Restore modal approach

The restore action exists in two places:
1. **List page** — implemented as an Alpine-toggled modal (via `$restoreAuditId` Livewire property). Full visual control, exact v0 match. The PHP logic is a Livewire method `restore()` with the same `$model->update($record->old_values)` logic.
2. **ViewAuditLog** — uses the existing Filament Action with `requiresConfirmation()` + `modalContent()`. Untouched. The `restore-modal.blade.php` was updated to v0 table styling but keeps the same structure.

## Pagination approach

Manual skip/take with a `$page: int` Livewire property instead of `Livewire\WithPagination`.
Reason: `ListRecords` already uses Filament's own `InteractsWithTable` trait which handles
its own pagination; mixing `WithPagination` risked URL parameter collisions.
The manual approach uses two queries per render (count + get) which is acceptable
for an audit log admin page with no strict latency SLA.

## Consequences

- New amber Tailwind classes (`bg-amber-500`, `text-amber-600`, `bg-amber-100`, etc.)
  need `npm run build` to compile into the admin theme CSS.
- The Filament Resource's `table()` method is now "dead code" for the list page but
  stays in place for (a) the `getUrl('view', ...)` helper and (b) `recordActions` /
  `filters` that could be reused in the future.
- Filter state is NOT persisted in the URL (no `#[\Livewire\Attributes\Url]`). This
  was a deliberate choice to keep the implementation simple; add `#[Url]` if needed.
