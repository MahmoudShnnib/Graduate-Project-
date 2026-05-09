---
date: 2026-05-03
tags: [bug, audit-log, filament]
related: [[../daily-notes/2026-05-03-grade-publishing-audit-log]]
---

# Audit Log Crash — ?array type hint on string column

## Symptoms

`/admin/audit-logs` crashes with:
```
TypeError: App\Filament\Resources\AuditLogs\AuditLogResource::{closure}():
Argument #1 ($state) must be of type ?array, string given
```

## Root Cause

`old_values` and `new_values` columns are `text` type in SQLite. The `AuditLog` model casts them to `array`, but Filament's `formatStateUsing` closure had `fn (?array $state)` type hint. When the cast occasionally returned a raw string (e.g. for malformed JSON), the type mismatch crashed.

## Fix

Changed type hint from `?array` to `mixed` and added defensive handling:
- `null`/empty → returns `-`
- `string` → tries `json_decode`, falls back to raw string
- `array` → `json_encode` with `JSON_UNESCAPED_UNICODE`

## Files

- `app/Filament/Resources/AuditLogs/AuditLogResource.php`
