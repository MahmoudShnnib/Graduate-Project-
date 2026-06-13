---
date: 2026-06-13
tags: [decision, audit-log, architecture, performance]
related:
  - "[[2026-06-13-audit-log-collapse-by-batch]]"
  - "[[../daily-notes/2026-06-13-activity-bell-only-new]]"
---

# Decision — Audit-log hardening (which of the 7 review findings to fix)

## Context

A critical review of the audit-log system surfaced 7 issues. Decided which to fix and how.

## Decision

**Fix 5, skip 2.**

- **#2 batch-id landmine → Tier 1.** Reset the memoized batch id at every unit-of-work
  boundary (request middleware + queue + console listeners) so it can't leak across
  operations under Octane/queue workers.
- **#5 misleading bulk badge → Tier 2.** Add `batch_decision_count`; bulk approvals show
  "N قرار" instead of "+N".
- **#6 two list definitions → Tier 2.** The list renders a custom Blade (`getAuditLogs()`),
  not the Filament `table()`. Documented the dead `table()` loudly + removed its dead badge.
- **#3 unbounded growth → Tier 3.** Stamp `semester_id` on every log; on semester archive,
  move that semester's logs to an `audit_log_archives` cold-store table (preserve trail, keep
  live table lean).
- **#4 slow list → consciously NOT precomputed.** See below.

**Skipped:**
- **#1 hybrid logging (manual decisions + auto observer).** Fixing = rewriting ~30 `->log()`
  call sites for an invisible gain; the collapse already makes the duplication cheap & correct.
- **#7 tamper-evidence (hash chain).** Compliance engineering with no threat model here; rows
  are already read-only in the UI. Documented the limitation instead.

## The notable call: no `is_representative` precompute (#4)

The "compute the headline row once and store it" optimization was planned, then dropped.
Reason: it trades a **performance** worry for a **correctness** risk — any code path that
writes a log but misses the "finalize batch" hook would leave those rows unflagged and they'd
**silently disappear from the list**. For an *audit* log, silent data loss is far worse than a
slightly heavier query, and the correlated `collapsedQuery()` is plenty fast at this project's
scale (thousands–tens-of-thousands of rows). If the table ever gets huge, revisit with a
*guaranteed* finalize (e.g. a backfill job) — not before.

## Consequences

- Batch grouping is now safe under any runtime.
- The bell and list both stay correct; the list query is unchanged in shape (just one extra
  count sub-select for the honest badge).
- Audit history is now semester-scoped and archivable; live table won't grow without bound.
- Four migrations pending for deploy.

## Council

Not consulted this round — these were judgment calls off the earlier review, not a new design
search.
