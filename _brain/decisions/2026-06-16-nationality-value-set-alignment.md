---
date: 2026-06-16
tags: [decision, data, nationality, consistency]
related: [[2026-06-16-go-live-batch-fixes]]
---

# Nationality value set: country nouns, single source = config/nationalities.php

## Context
`/apply` already stored nationality as the Arabic **country noun** (`ليبيا`, `تونس`, …) validated
against `config('nationalities.list')`. But admin-side free-text inputs and several factories used
the **adjective** form (`ليبية`, `تونسية`, …). When the admin nationality fields were converted to
the same predefined `Select` (user request #2), the adjective values were no longer valid options.

## Decision
One canonical value set = `config('nationalities.list')` (country nouns). All nationality inputs are
a `Select` from that config; all seed/factory/test data uses the noun forms; `ليبيا` (config default)
is the "Libyan" sentinel that drives the 12-digit national-number rule.

## What changed
- Selects: `UserForm`, `StudentResource`, `ApplicationResource`, `ListThesisRegistrations`.
- Data aligned `ليبية → ليبيا` (+ supervisor adjective list → nouns) in `ApplicationFactory`,
  `UserFactory`, `SupervisorFactory`, and tests `TransferCourseEditTest`, `AdminCredentialEditTest`.

## Consequences
- Any **existing DB rows** still holding adjective values would render blank in the new Select.
  Acceptable: all data is dummy/refreshable (see auto-memory `feedback_dummy_data_can_refresh`).
  If real data ever predates this, a one-off data migration (`ليبية→ليبيا`, etc.) would be needed.
- Future nationality work: add to `config/nationalities.php` only; never hardcode.
