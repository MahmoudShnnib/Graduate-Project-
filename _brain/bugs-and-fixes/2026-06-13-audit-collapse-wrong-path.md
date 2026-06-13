---
date: 2026-06-13
tags: [bug, filament, audit-log, testing]
related:
  - "[[../daily-notes/2026-06-13-activity-bell-only-new]]"
  - "[[../decisions/2026-06-13-audit-log-collapse-by-batch]]"
---

# Bug — audit-log collapse "done" but never showed (wrong render path + false-confidence test)

## Symptom

Built the "one row per operation" collapse for `/admin/audit-logs`, tests green, declared done.
User reported the live list **still showed every row uncollapsed** — while the detail page
(which I'd also changed) correctly reflected the new code. Classic "works in test, broken in
app."

## Root cause

`ListAuditLogs` sets `protected string $view` to a **custom Blade**
(`list-audit-logs.blade.php`) and builds its query in its own **`getAuditLogs()`** method. It
does **not** render `AuditLogResource::table()`. My collapse wiring (`->query(collapsedQuery())`,
the `+N` column, the batch-aware filter) all sat on the Filament `table()` path — which is
**dead for this page**.

Worse: my test asserted `Livewire::test(ListAuditLogs::class)->instance()->getTableRecords()`
— the Filament table path — so it passed while the *real* page (`getAuditLogs()`) was untouched.
A test that green-lights broken code.

(First red herring: a stale Filament/OPcache cache — real, cleared with `optimize:clear`, but
not the actual cause.)

## Fix

- `ListAuditLogs::getAuditLogs()` now starts from `AuditLogResource::collapsedQuery()` (default)
  / raw query (when "show system" is on) and uses `applyActionFilter()`.
- The `+N` badge moved into the custom Blade's action cell.
- Test rewritten to call the **real** `getAuditLogs()` (+ a "show system = raw rows" test).

## Regression guard

- `AuditLogCollapseTest` now asserts on `getAuditLogs()['items']`, not `getTableRecords()`.
- Memory `reference_auditlog_list_custom_view.md` records the trap.
- Loud doc-comment added on the dead `table()` method.

## Lesson

**When a Filament page overrides `$view`, the resource `table()`/columns/filters are dead.**
Change the page's own data method and test *that* — never `getTableRecords()` for a custom-view
page. Verifying on real data (tinker against the actual DB: 49→30 rows) caught what the unit
test missed.
