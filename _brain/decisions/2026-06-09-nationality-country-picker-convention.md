---
date: 2026-06-09
tags: [decision, apply, nationality, validation, registration]
related:
  - "[[2026-06-09-apply-form-enhancements]]"
---

# Nationality stored as Arabic country name + single-source country list

## Context

The public `/apply` form previously stored nationality as the **adjective**
`ليبية` (or a free-text "أخرى" value via `nationality_other`). The national-number
rule was hardcoded to 12 digits for everyone. The new requirement: a searchable
country picker with flags, storing the **country name** (`ليبيا`, `مصر`, …), with the
12-digit rule applying only to Libyans.

## Decision

1. **Store the Arabic country name**, not the adjective. New canonical Libyan value is
   **`ليبيا`** (was `ليبية`). The "أخرى / nationality_other" mechanism is removed.
2. **`config/nationalities.php`** is the single source of truth — a `list` of
   `['name','flag']` plus `default => 'ليبيا'`. The blade picker iterates it (injected via
   `@js`), and `RegistrationController` validates with
   `Rule::in(array_column(config('nationalities.list'), 'name'))`. This keeps client and
   server allow-lists aligned by construction.
3. **National-number rule is nationality-dependent:** `nationality === config(...default)`
   → `^\d{12}$`; otherwise `^[A-Za-z0-9]{4,20}$` (passport/foreign ID).

## Alternatives considered

- Hardcoding the country list twice (JS + PHP) — rejected, drifts out of sync.
- Keeping `ليبية` as the stored value — rejected, the requirement explicitly asks for
  country names (`ليبيا، السعودية، مصر، تونس`).

## Consequences

- Existing Application rows keep their old `ليبية` value (harmless — nationality is a
  display-only string; admin Filament resources show it as plain text, no logic keys off it).
- Admin-side defaults (`UserForm`, `StudentResource`, `ApplicationResource`,
  `ThesisRegistrations`) still default to `ليبية` — **not** changed, since they're staff
  forms unrelated to the public picker. Flagged here in case we later unify them.
- The shared test helper `validApplicationPayload()` in `WhatsAppOtpVerificationTest`
  was updated `ليبية → ليبيا` to satisfy the new `Rule::in`.
