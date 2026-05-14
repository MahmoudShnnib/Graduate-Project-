---
date: 2026-05-14
tags: [daily, tests, systematic-debugging, suspension, warnings, dismissal, exceptional-request]
related:
  - "[[../bugs-and-fixes/2026-05-14-stale-test-assertions]]"
  - "[[../decisions/2026-05-08-suspension-request-blocking-rules]]"
  - "[[../decisions/2026-05-08-auto-inactive-survey-close]]"
  - "[[2026-05-14-user-resource-overhaul]]"
---

# 2026-05-14 — Fixed the 5 long-standing failing tests via systematic-debugging

## What was done

Brought the test suite from **153/158** (with 5 long-standing fails carried since the 2026-05-10 multi-phase session) to **158/158**. Full root-cause analysis + fix in [[../bugs-and-fixes/2026-05-14-stale-test-assertions]]. Triggered by the `superpowers:systematic-debugging` skill.

## TL;DR

All five tests asserted **pre-2026-05-08 domain rules**. Production code correctly implements the new rules per the 2026-05-08 decision notes. The tests were never updated when the rules tightened, ended up failing on every CI run, and got labeled "pre-existing" for several sessions in a row.

**Zero production code changed. Five test rewrites.**

## Process

1. **Phase 1 (root cause)** — read all 5 failing tests, all related production code (`SuspensionRequestPage`, `WarningService`, `SemesterObserver`, `StudentObserver`, `ViewExceptionalStudent`), and three relevant decision docs in parallel before forming any hypothesis.
2. **Phase 2 (pattern)** — confirmed production implements the documented decisions:
   - [[../decisions/2026-05-08-suspension-request-blocking-rules]] — approved → permanent block; rejected → per-semester block
   - [[../decisions/2026-05-08-auto-inactive-survey-close]] — consecutive-inactive dismissal goes through the survey-close path
3. **Phase 3 (hypothesis)** — wrote the diagnosis as a side-by-side comparison (old rule vs new rule) for the user to confirm before any code change.
4. **Phase 4 (implementation)** — five focused test rewrites, each verified in isolation before the next.

## The fixes

### Group 1 — `SuspensionRequestResubmitGuardTest` (3 tests)

The old tests asserted: approved suspension for sem A doesn't prevent a request for sem B; rejected suspension allows re-submission. The 2026-05-08 rules say: approved is **permanent**, rejected is **per-semester**.

Three tests rewritten:
- "allows new for different semester when another approved" → "blocks any new suspension request once one has been approved (different semester too)"
- "allows re-submission after rejection for same semester" → "blocks re-submission for the same semester after a rejection, but allows for a different semester"
- "isSelectedSemesterAlreadyApproved returns true" → "hasAnyApprovedRequest and isSelectedSemesterBlocked report correctly" (the old method was renamed/replaced; new test uses the actual API)

### Group 2 — `WarningSystemTest > dismisses student inactive for 2 consecutive semesters`

The old test did:
1. Set student status to Inactive (warning #1 for sem1)
2. Activate sem2 (SemesterObserver resets the student to Active via `updateQuietly`; warning #1 persists)
3. Assert student.status == Dismissed ← **fails**

The dismissal trigger requires a third step that the test was skipping. Per the documented flow:
- Step 3: sem2's survey closes → `SemesterObserver::processNonSubmittingStudents` marks the (now-Active) student Inactive again → fires `StudentObserver` → `WarningService::processInactiveWarning` creates warning #2 (sem2) → count reaches 2 → `dismissStudent`

Test rewritten to use **Option A (survey-close cycle)** rather than a direct status flip — drives the full production chain end-to-end. Sem2 is created with `survey_open => true`, gets activated, then `survey_open => false` is flipped. The full cascade fires; student dismisses with `dismissal_type = ConsecutiveInactive`.

### Group 3 — `ExceptionalRequestTest > accepts inactive dismissal and gives fresh start`

Identical root cause to Group 2 — the test's setup didn't reach the Dismissed state, so the assertions about `acceptAction` reactivation never ran. Setup fixed using the same survey-close cycle. The `acceptAction` (which correctly calls `WarningService::clearInactiveWarnings` + `syncWarningsCount`) now exercises end-to-end.

## Why this took a while to fix

Each prior session labeled these 5 as "pre-existing failures, not my problem" — including me, in [[2026-05-10-multi-phase-fixes]] and [[../bugs-and-fixes/2026-05-10-onhold-students-not-released-on-new-semester]]. The brain explicitly flagged them as worth fixing in a future session.

Today's user prompt finally allocated dedicated focus: "before please think deeply read the files read the brain and make a plan and show me ask if u want and find any skills u want." That activated the systematic-debugging skill and the writing-plans-via-AskUserQuestion pattern, which produced the right diagnosis quickly.

## Convention this surfaces

When a decision note tightens a rule, **review the tests for that domain in the same PR**. A test that asserts the old behavior should not survive the merge that changes the behavior. If it does — because it's failing and gets dropped as "pre-existing" — it becomes a permanent landmine of "tests that pass on this branch but fail on main."

Process suggestion (added to the bug-and-fix note): when a decision document changes a rule, grep for every test touching the affected service/page/model, and either update or explicitly delete them in the same change set.

## Verification

- Each test passes in isolation (4 / 1 / 1 — Group 1 had 4 tests total, 3 rewritten + 1 untouched)
- Full suite: `php artisan test --compact` → **158 passed (337 assertions)**
- `vendor/bin/pint --dirty --format agent` → pass
- Zero production code touched (verified by `git diff --name-only` showing only the 3 test files outside the user-resource overhaul change set)

## Files touched

- `tests/Feature/SuspensionRequestResubmitGuardTest.php` — 3 tests rewritten
- `tests/Feature/WarningSystemTest.php` — 1 test setup extended (added the `$semester2->update(['survey_open' => false])` step)
- `tests/Feature/ExceptionalRequestTest.php` — 1 test setup extended (same pattern)

## Next steps

None blocking. Consider documenting the "decision changes a rule → audit tests in the same PR" practice somewhere structural (CLAUDE.md "Core Working Principles" or a new section under `design-conventions.md`).
