---
date: 2026-04-30
tags: [decision, exceptional-opportunities, navigation, filament, url]
related: [[../daily-notes/2026-04-30-session2-quick-fixes]], [[../project-context/design-conventions]]
---

# Exceptional Student Detail View — Separate Page with Filament Actions

## Context

The Exceptional Opportunities admin page had an inline detail view toggled by a Livewire property. Clicking a student showed/hid the detail on the same page — URL never changed. Buttons were custom HTML `<button>` elements with `wire:click` and `wire:confirm`, plus a hand-built `<x-filament::modal>` for summon. Inconsistent with every other admin detail view in the project.

## Decision

### Separate URL page
Created `ViewExceptionalStudent` as a standalone Filament `Page` at slug `exceptional-opportunities-page/{student}`. Hidden from nav (`$shouldRegisterNavigation = false`). Uses `mount(int $student)` to load the record.

The property is named `$record` (not `$student`) to avoid Livewire's `SupportNestingComponents` trying to assign the raw int route parameter directly to a typed `Student` property before `mount()` runs.

### Filament Action components for buttons
Accept, reject, and summon are proper `Action` objects returned from `getHeaderActions()`, matching the pattern in `ViewThesisRegistration`. Each uses `requiresConfirmation()` with Arabic modal text. The summon action has a `Textarea` form field. Accept and reject redirect back to the list page on success.

### Full-row clickable tables
List page uses `onclick="window.location='...'"` on `<tr>` elements with `cursor-pointer`, consistent with the grouped-list index pattern documented in design-conventions.

## Alternatives Considered

1. **Livewire `#[Url]` attribute on `$viewingStudentId`** — gives a query param (`?student=12`), not a path segment. User explicitly wanted path-based URLs.
2. **Keep inline toggle, just fix buttons** — inconsistent with every other detail view in the project which uses separate URLs.

## Consequences

- Bookmarkable detail URLs, browser back button works
- Consistent Filament Action buttons with proper modals
- Tests use `callAction()` API instead of direct `call()` — more resilient to implementation changes
- Custom HTML buttons and manual modals eliminated — less code to maintain
