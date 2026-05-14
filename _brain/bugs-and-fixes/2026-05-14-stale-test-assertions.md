---
date: 2026-05-14
tags: [bug, tests, suspension, warnings, dismissal, exceptional-request, systematic-debugging]
related:
  - "[[../daily-notes/2026-05-14-stale-test-fixes]]"
  - "[[../decisions/2026-05-08-suspension-request-blocking-rules]]"
  - "[[../decisions/2026-05-08-auto-inactive-survey-close]]"
  - "[[../decisions/2026-04-27-warning-system-and-dismissal]]"
status: fixed
---

# 5 stale tests vs current domain rules

> **Status:** Fixed via the systematic-debugging skill. Net result: 158/158 passing (was 153/158 for several sessions).

## Symptoms

Five tests failing on HEAD for the last several sessions, all flagged as "pre-existing" in [[../daily-notes/2026-05-10-multi-phase-fixes]]:

```
FAILED  SuspensionRequestResubmitGuardTest > allows new suspension request for a different semester when another semester has approved
        Failed asserting that 0 is identical to 1.

FAILED  SuspensionRequestResubmitGuardTest > allows re-submission after a prior rejection for the same semester
        Failed asserting that false is true.

FAILED  SuspensionRequestResubmitGuardTest > isSelectedSemesterAlreadyApproved returns true for approved semester
        Method App\Filament\Student\Pages\SuspensionRequestPage::isSelectedSemesterAlreadyApproved does not exist.

FAILED  WarningSystemTest > it dismisses student inactive for 2 consecutive semesters
        Failed asserting that Active equals Dismissed.

FAILED  ExceptionalRequestTest > it accepts inactive dismissal and gives fresh start with zero warnings
        Failed asserting that Active equals Dismissed.
```

## Root causes (THREE distinct, not one)

### Group 1 — SuspensionRequestResubmitGuardTest (3 tests)

The tests reflect **pre-2026-05-08** suspension rules. On 2026-05-08, the blocking rules were tightened per [[../decisions/2026-05-08-suspension-request-blocking-rules]]:

| Old rule (what tests asserted) | New rule (what production does) |
|---|---|
| Approved suspension blocks just that semester | Approved → **permanently** blocks any future submission |
| Rejected suspension allows re-submission for same semester | Rejected → **per-semester** block (that one semester only) |
| Helper method `isSelectedSemesterAlreadyApproved()` | Renamed/replaced by `hasAnyApprovedRequest()` + `isSelectedSemesterBlocked()` + `getBlockedSemesterIds()` |

Production code correctly implements the new rules. The tests were never updated.

### Group 2 — WarningSystemTest > "dismisses student inactive for 2 consecutive semesters"

The test stops one step short of the dismissal trigger. Per [[../decisions/2026-05-08-auto-inactive-survey-close]] + [[../decisions/2026-04-27-warning-system-and-dismissal]], the consecutive-inactive flow needs THREE steps:

1. **Sem1 active, student → Inactive** — fires `StudentObserver` → `WarningService::processInactiveWarning(student)` → creates warning #1 for sem1
2. **Sem2 activates** — fires `SemesterObserver::resetInactiveStudentsToActive()` → `updateQuietly(['status' => Active])` (bypasses StudentObserver) → student silently → Active. Warning #1 persists by design.
3. **Sem2's survey closes** — fires `SemesterObserver::processNonSubmittingStudents(sem2)` → finds Active students who didn't submit a survey for sem2 → marks each as Inactive → fires `StudentObserver` → creates warning #2 (sem2) → count == 2 → `dismissStudent()` → status = Dismissed

The old test did steps 1 + 2 only. Without step 3, the second warning is never created and the student stays Active.

### Group 3 — ExceptionalRequestTest > "accepts inactive dismissal and gives fresh start with zero warnings"

Identical root cause to Group 2. The test's setup section tries to put the student into Dismissed state via the consecutive-inactive flow but skips step 3 (survey close on sem2). The Dismissed-state precondition is never met, so the assertion on line 224 (`expect($this->student->fresh()->status)->toBe(StudentStatus::Dismissed)`) fails before the actual subject-under-test (`acceptAction`) is exercised.

The `acceptAction` itself works correctly: for `dismissal_type === ConsecutiveInactive` it calls `WarningService::clearInactiveWarnings($student)` then `syncWarningsCount` — exactly what the assertions expect once the precondition is reached.

## The fix

**Zero production code touched.** All five fixes are test rewrites.

Investigation followed the `superpowers:systematic-debugging` skill (Iron Law: "NO FIXES WITHOUT ROOT CAUSE INVESTIGATION FIRST"). After completing Phase 1 the diagnosis was clear that the production code matches the documented decisions — the tests are stale.

### Group 1 — three tests rewritten

- "allows new request for different semester when another approved" → renamed and assertions inverted: **"blocks any new suspension request once one has been approved (different semester too)"**. Asserts the count stays at 1 (the original Approved row; no new one created).
- "allows re-submission after rejection for same semester" → split into two paths in one test: **"blocks re-submission for the same semester after a rejection, but allows for a different semester"**. Asserts no Pending on the rejected semester + a Pending on a different semester.
- "isSelectedSemesterAlreadyApproved returns true" → rewritten to call the new methods: **"hasAnyApprovedRequest and isSelectedSemesterBlocked report correctly"**. Asserts all 3 of `hasAnyApprovedRequest()` returning true, `isSelectedSemesterBlocked()` returning true, and `getBlockedSemesterIds()` containing the approved semester.

### Group 2 — setup extended

After step 2 (sem2 activates), the test now also flips `sem2.survey_open` from `true` → `false`. This is the **real production trigger** — runs `processNonSubmittingStudents`, which marks the student Inactive again under sem2 and fires the rest of the cascade.

Verification: warning #2 is created with `semester_id = sem2`, warnings count reaches 2, `dismissStudent` runs, student lands on `Dismissed` with `dismissal_type = ConsecutiveInactive`.

### Group 3 — same setup pattern as Group 2

The dismissal precondition is now reached through the survey-close cycle. The actual subject-under-test (`acceptAction` reactivating the student + clearing inactive warnings via `WarningService::clearInactiveWarnings`) runs successfully.

## Regression guards

These tests are the ONLY ones covering:

- The full lifecycle of the suspension blocking rules (Approved permanent + Rejected per-semester) → if anyone weakens those rules in `SuspensionRequestPage::submit()`, the new Group 1 tests catch it
- The full chain: semester transition → reset-to-active → survey close → mark-non-submitter-inactive → 2nd warning → dismissal — this chain spans 4 components (SemesterObserver, StudentObserver, WarningService, dismissStudent) → Groups 2 + 3 are the only end-to-end coverage

If someone reverts to the old loose rules, splits the cascade across services, or changes `processNonSubmittingStudents`'s trigger, these tests will fail. That's the intended regression net.

## Files touched

- `tests/Feature/SuspensionRequestResubmitGuardTest.php` — 3 tests rewritten (one renamed, one split into two assertions, one rebound to new methods)
- `tests/Feature/WarningSystemTest.php` — 1 test setup extended
- `tests/Feature/ExceptionalRequestTest.php` — 1 test setup extended (same pattern)

Zero changes to:
- `app/Filament/Student/Pages/SuspensionRequestPage.php`
- `app/Services/WarningService.php`
- `app/Observers/SemesterObserver.php`
- `app/Observers/StudentObserver.php`
- `app/Filament/Pages/ViewExceptionalStudent.php`

## How to avoid this class of bug

When a domain rule is intentionally tightened (like the 2026-05-08 suspension blocking redesign), **the tests for that domain must be reviewed in the same PR**. A test that asserts the old behavior shouldn't survive the merge that changes the behavior. If it does — because it was failing and got labeled "pre-existing" — it sits as a permanent landmine of "tests that pass on this branch but fail on main, that we don't fix yet."

Process suggestion: when a decision document changes a rule (especially a tightening), grep for every test that touches the affected service/page/model, and either update them or explicitly delete them in the same change set.
