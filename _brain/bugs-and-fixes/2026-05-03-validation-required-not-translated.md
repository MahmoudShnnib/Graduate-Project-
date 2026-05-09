---
date: 2026-05-03
tags: [bug, validation, i18n, arabic, login, filament]
related: [[../daily-notes/2026-05-03-test-execution]]
status: fixed
---

# Login form shows raw `validation.required` instead of Arabic message

> **Status:** Identified during AUTH-008 of full system test run.

## Symptoms

On `/admin/login` (and likely `/portal/login` — same Filament login component), submitting the form with both fields empty triggers validation correctly but the error text below each field reads literally:

```
validation.required
validation.required
```

instead of the expected Arabic message ("هذا الحقل مطلوب" or similar).

Reproduced via:
```js
const lw = window.Livewire.find(document.querySelector('[wire:id]').getAttribute('wire:id'));
lw.call('authenticate');
// → both fields show "validation.required" not Arabic
```

## Root cause (suspected)

Filament's auth form uses `validation.required` translation key. The Arabic translation file (`lang/ar/validation.php` or equivalent) either:
- doesn't exist
- isn't being loaded for the current locale
- doesn't include the `required` key

Likely fix: publish Filament's translation files (`php artisan vendor:publish --tag=filament-translations`) or add `lang/ar/validation.php` with `'required' => 'هذا الحقل مطلوب,'`.

App locale config in `config/app.php` should be confirmed too — `'locale' => 'ar'` and `'fallback_locale' => 'en'`.

## Impact

- Every form using Laravel/Filament built-in validation rules will show raw keys instead of Arabic when the translation isn't found.
- Project-wide convention is "Arabic-first RTL" — this is a visible regression for end users.
- Public `/apply` form may not be affected (it uses inline `$request->validate()` with explicit Arabic messages — see [[../project-context/project-overview#Public HTTP]]).

## Fix sketch

1. Verify `config('app.locale')` returns `'ar'`.
2. Check whether `lang/ar/validation.php` exists.
3. If missing → `php artisan lang:publish` or create file with full Arabic translation map for Laravel's standard validation rules.
4. Re-test AUTH-008 — empty fields should show Arabic messages.

## Regression guard

Add a Pest test under `tests/Feature/`:

```php
it('shows arabic validation message on empty admin login', function () {
    Livewire::test(\Filament\Pages\Auth\Login::class)
        ->call('authenticate')
        ->assertHasErrors(['data.email', 'data.password'])
        ->assertSee('هذا الحقل مطلوب'); // or the actual Arabic copy
});
```

## Observation while testing

The `getDisplayStatusLabel` precedence rule and other custom Arabic copy work fine; this only affects framework-default validation keys.
