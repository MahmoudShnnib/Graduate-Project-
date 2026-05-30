---
date: 2026-05-17
tags: [decision, filament, widgets, settings, dashboard, ux]
related:
  - "[[../daily-notes/2026-05-17-settings-widget-and-print-plan]]"
  - "[[../project-context/gotchas]]"
---

# Settings live as a dashboard widget, not a standalone Filament page

## Context

The print-system overhaul needs editable defaults for the college name, office name, director, and dean — values that appear in the header and footer of every printable document. The values can't be hardcoded (admin/secretary need to update them when officials change), can't be `config()`-based (they'd require redeploys), and shouldn't be `.env`-based (admins don't touch `.env`).

First attempt this session built a standalone `SettingsPage` (Filament Page subclass) registered as a route at `/admin/settings-page` with its own nav entry under the "النظام" group. User pushed back immediately: *"u made it tis own resource which i told u i want it in the http://127.0.0.1:8000/admin"*.

## Decision

Settings live as a **Filament widget on the dashboard** (`/admin`), not as a standalone page/resource:

- `app/Filament/Widgets/SettingsWidget.php` — auto-discovered, sort `-10` (top of dashboard), small gray button right-aligned in its own row.
- Click → Filament Action modal containing the form (sections + 4 fields: `college_name`, `office_name`, `director_name`, `dean_name`).
- Submit → writes via `Setting::set()` → toast "تم حفظ الإعدادات" → modal closes.
- Visible only to `super_admin` + `secretary` (via `canView()`); faculty/student see nothing.
- No nav entry. Only entry point is the dashboard.

## Alternatives considered

| Option | Why rejected |
|---|---|
| Standalone Filament Page with its own route + nav entry | User explicitly rejected — wants settings accessible from `/admin` dashboard, not as a navigable resource |
| Single-row settings table with explicit columns | Less extensible — each new setting needs a migration. Key/value table accepts new keys with zero schema work |
| `spatie/laravel-settings` package | New dependency for a 4-field needs. Custom 50-line Setting model is plenty |
| Embed full form on the dashboard (no modal) | Clutters the dashboard for the 99% of sessions when admin isn't editing settings. Button + modal keeps the dashboard clean |
| `config()` file editable via Filament | Filament can't write `config/*.php`; would need a custom file writer; not worth the complexity |

## Implementation notes

### Storage shape

```sql
CREATE TABLE settings (
    id BIGINT PRIMARY KEY,
    key VARCHAR UNIQUE,
    value TEXT NULL,
    created_at, updated_at
);
```

Key/value lets future settings drop in without migrations.

### Helper API

```php
Setting::get('college_name');          // → DB row, or DEFAULTS const, or explicit fallback
Setting::set('director_name', 'د. …'); // → updateOrCreate
Setting::allKeyed();                   // → array<key, value> with DEFAULTS as floor
```

`DEFAULTS` const on the model is the safety net — every key always resolves to *something* even if the row is missing. Templates can call `Setting::get('director_name')` with no null-check.

### Widget mount

`SettingsWidget` implements `HasActions` + `HasSchemas`, uses `InteractsWithActions` + `InteractsWithSchemas`. The Blade renders:

```blade
<x-filament::button wire:click="mountAction('editSettings')" ...>إعدادات النظام</x-filament::button>
<x-filament-actions::modals />
```

The `<x-filament-actions::modals />` slot is mandatory inside the widget — Filament's page-level modal slot doesn't pick up widget actions. See gotchas.md "Filament widget action modals".

## Consequences

- **Positive:** Settings live where the admin already lives (dashboard). Zero nav-clutter. One source of truth (`Setting::get($key)`) callable from any controller/blade. Faculty/students never see admin chrome.
- **Negative:** Slightly more complex than a standalone page (widget needs `<x-filament-actions::modals />` slot; magic `$this->actionName` render proved brittle so we use explicit `wire:click`). Worth it for the cleaner UX.

## How to apply (for future settings additions)

1. Add the new key to `Setting::DEFAULTS` const with its default value.
2. Add a `TextInput::make('new_key')` in `SettingsWidget::editSettingsAction()` form.
3. Add the key to `SettingsSeeder` (it iterates `DEFAULTS`, so just adding to the const is enough).
4. Read in templates via `Setting::get('new_key')`.
5. No migration needed.

## Related

- [[../project-context/gotchas|Gotchas: Filament widget action modals]]
- [[../daily-notes/2026-05-17-settings-widget-and-print-plan]] — the print-system overhaul this is the first step of
