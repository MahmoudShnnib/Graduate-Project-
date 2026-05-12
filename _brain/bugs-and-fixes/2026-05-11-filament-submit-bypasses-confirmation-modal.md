---
date: 2026-05-11
tags: [bug, filament, livewire, gotcha, confirmation-modal, create-record]
related:
  - "[[../decisions/2026-05-11-create-semester-implicit-active-with-popup]]"
  - "[[../daily-notes/2026-05-10-multi-phase-fixes]]"
---

# Filament's default Create button silently swallows `requiresConfirmation()`

> [!warning] Trap pattern
> Calling `->requiresConfirmation()` on the action returned by `parent::getCreateFormAction()` looks correct, runs without error, and does absolutely nothing. The confirmation modal never opens — the form submits straight through.

## Symptoms

Added a confirmation modal on a `CreateRecord` page by overriding `getCreateFormAction()` like this:

```php
protected function getCreateFormAction(): Action
{
    return parent::getCreateFormAction()
        ->requiresConfirmation()
        ->modalHeading('إنشاء فصل جديد')
        ->modalDescription(fn () => /* dynamic */)
        ->modalSubmitActionLabel('إنشاء وتنشيط');
}
```

Page loaded fine. Clicking the **إنشاء** button immediately created the record. No popup. No warning. No error logged.

## Root cause

Filament v5's default `CreateRecord::getCreateFormAction()` (`vendor/filament/filament/src/Resources/Pages/CreateRecord.php:242`) builds the action like this:

```php
return Action::make('create')
    ->label(...)
    ->submit($hasFormWrapper ? $this->getSubmitFormLivewireMethodName() : null)
    ->action($hasFormWrapper ? null : $this->getSubmitFormLivewireMethodName())
    ->keyBindings(['mod+s']);
```

Because the page renders inside Filament's `<form>` wrapper, `$hasFormWrapper` is `true`, so the action becomes:

```php
->submit('create')   // <-- this is the killer
->action(null)
```

`->submit('...')` turns the button into a **native HTML form submit button** (`<button type="submit">`). When the user clicks it, the browser submits the form. Livewire intercepts the submit and calls the `create` method directly — **bypassing Filament's Action invocation flow**, where the modal would normally fire.

`->requiresConfirmation()` is an Action-level flag that's only consulted by Filament's Action invocation runtime. Submit buttons skip that runtime entirely. So the flag is set, ignored, and the user never sees the modal.

## Fix

Rebuild the action *from scratch* in the override, omit the `->submit()` call, and put the create logic inside `->action()`:

```php
protected function getCreateFormAction(): Action
{
    return Action::make('create')
        ->label(__('filament-panels::resources/pages/create-record.form.actions.create.label'))
        ->color('primary')
        ->keyBindings(['mod+s'])
        ->requiresConfirmation()
        ->modalIcon('heroicon-o-exclamation-triangle')
        ->modalIconColor('warning')
        ->modalHeading('إنشاء فصل جديد')
        ->modalDescription(fn (): string => /* dynamic, references DB state */)
        ->modalSubmitActionLabel('إنشاء وتنشيط')
        ->modalCancelActionLabel('إلغاء')
        ->action(fn () => $this->create());
}
```

The button is now a regular Action button (no `type="submit"`). Click → modal opens → confirm → `->action()` callback invokes `$this->create()` → standard Livewire validation + persistence flow continues. Form state is already wire:model-synced to the component, so `$this->create()` validates and saves normally.

## Regression guard

Tests in `tests/Feature/CreateSemesterConfirmationTest.php` assert:
1. The action's `getModalHeading()` is set (indirect confirmation that the modal is configured).
2. The action's `getModalSubmitActionLabel()` is the override string.
3. Calling `$this->create()` after fill+confirm actually persists the record and triggers the downstream observer (auto-archive of the previously-active semester).

Reading `requiresConfirmation()` back as a getter does NOT work in Filament v5 — it's a setter-only API. Don't write tests against it.

## Convention to remember

> [!important] When adding a confirmation popup to a Filament `CreateRecord` / `EditRecord` form submit
> **Don't** chain `->requiresConfirmation()` onto `parent::getCreateFormAction()`. The parent's `->submit()` binding silently kills the modal.
>
> **Do** rebuild the action manually, drop `->submit()`, and invoke `$this->create()` (or `$this->save()` for edit) from inside `->action()`.

Same trap applies to `EditRecord::getSaveFormAction()` (the Save button on edit pages) and any other Filament page that wires submit buttons via `->submit(string $livewireMethod)`.

## Files touched

- `app/Filament/Resources/Semesters/Pages/CreateSemester.php` (the override)
- `tests/Feature/CreateSemesterConfirmationTest.php` (4 cases, all passing)
