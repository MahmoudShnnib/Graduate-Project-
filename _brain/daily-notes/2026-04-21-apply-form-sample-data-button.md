---
date: 2026-04-21
tags: [session, testing, public-form, refactor, sample-data]
related: [[../project-context/design-conventions]], [[../project-context/gotchas]], [[2026-04-20-task-session-multi-fix]]
---

# 2026-04-21 — "توليد بيانات تجريبية" button on /apply

## What was done

Added a sample-data fill button to the public `/apply` registration form, matching the existing button on `/admin/students/create`. Public form is plain HTML (not Livewire), so the mechanism is JSON fetch + JS fill instead of `$form->fill(...)`.

### Bonus refactor (DRY)

The admin-side `CreateStudent::getSampleStudentData()` had all the Arabic name pools inline (~50 lines of data literals). Extracted both sides' data generation into a shared class so they stay in sync.

## Files touched (5 created / changed)

1. New [`app/Support/SampleApplicantData.php`](../../app/Support/SampleApplicantData.php)
   - Private `const` arrays: male/female first names (Ar + En), middle names, surnames, cities, streets.
   - `generateBase(): array` — shared core (gender-correlated Arabic+English full name, birth, address, ID).
   - `forAdminStudent(): array` — admin `CreateStudent` form keys (user_name, user_email, …).
   - `forPublicApplication(): array` — public `/apply` form keys (name, email, …). Omits admin-only fields (`status`, `warnings_count`) + file inputs.

2. [`app/Filament/Resources/Students/Pages/CreateStudent.php`](../../app/Filament/Resources/Students/Pages/CreateStudent.php)
   - Removed ~55 lines of inline pool arrays + name construction.
   - Header action now calls `$this->form->fill(SampleApplicantData::forAdminStudent())`.
   - Dropped unused `Str` and `Specialization` imports.

3. [`app/Http/Controllers/RegistrationController.php`](../../app/Http/Controllers/RegistrationController.php)
   - New `sample(): JsonResponse` method returning `SampleApplicantData::forPublicApplication()`.

4. [`routes/web.php`](../../routes/web.php)
   - New route `GET /apply/sample` → `RegistrationController::sample`, throttled `20 req/min` as a basic abuse guard (public endpoint).

5. [`resources/views/registration/create.blade.php`](../../resources/views/registration/create.blade.php)
   - Added a warning-amber "توليد بيانات تجريبية" button below the header with a sparkles icon (same heroicon the admin action uses).
   - JS: button click → `fetch('/apply/sample')` → fill every matching input by `name`, handle radio groups + checkboxes specifically, skip `type=file`, auto-tick the `declaration` checkbox, re-run `updateDelegationFields()` + `updateNationalityField()` so conditional fields re-evaluate.

## Design choices (new, logged to [[../project-context/design-conventions]] if reused elsewhere)

- **JSON fetch + JS fill** is the pattern for "generate test data" on non-Livewire pages. Livewire pages use `$form->fill(...)` directly.
- **Single source of truth for sample data:** `App\Support\SampleApplicantData`. Any future "generate" button anywhere in the app should add a new generator method here rather than duplicating pools.
- **Button style (public form, non-Filament):** warning-amber (`bg-amber-50`, `text-amber-700`, `border-amber-200`, `hover:bg-amber-100`) — echoes Filament's `warning` color semantics while fitting the `/apply` Tailwind palette. Sparkles icon matches the admin action.
- **File inputs are always skipped** — browsers don't allow programmatic `<input type=file>` population. Helper text under the button tells users they must attach files manually.
- **Rate limit on the public endpoint:** `throttle:20,1`. Matches the defensive posture on `/apply/track` (already rate-limited per gotcha about public endpoints).

## Gotchas discovered / reinforced

- `<input type="file">` cannot be set via JS — skipped explicitly in the fill loop. Documented in the Blade comment + the user-facing helper text.
- Radio groups need the `checked` flag set on the matching element AND a `change` event dispatched so conditional-visibility JS (delegation fields) re-runs.
- After filling `nationality` + `study_type`, the existing `updateNationalityField()` and `updateDelegationFields()` functions must be re-invoked explicitly — `change` events dispatched above do that, but I also call them once more at the end for safety.

## Tests

- **78/78 still pass.** No new feature tests added — this is a testing convenience, not a domain invariant. The underlying data generator produces deterministic-format values (phone matches `^09\d{8}$`, national_id is 12 digits, etc.) verified via tinker.
- Considered writing a test for `RegistrationController::sample()` returning 200 + expected keys — not worth the ceremony for a test-convenience endpoint. Noted as a follow-up candidate if this is ever prod-gated.

## Loose ends

- **Prod gating:** the endpoint + button are always-available. User said "for testing" — fine for now. If this ever ships to production, wrap both the route and the button visibility in `App::environment('local')` or a `config('app.testing_helpers_enabled')` flag.
- **Admin-side generator used unchanged behavior** — no test was run comparing old vs new sample output. The refactor is mechanical (same pools, same Faker calls, same keys) so semantically equivalent, but a diff-based test would have been stronger. Accepting this risk because the admin button is itself a testing convenience.
- [[2026-04-20-task-session-multi-fix|T1 attachment popup]] still PINNED — unchanged.
