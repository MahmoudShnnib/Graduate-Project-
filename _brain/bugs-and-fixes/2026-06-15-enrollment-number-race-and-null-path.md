---
date: 2026-06-15
tags: [bug, enrollment, race-condition, data-integrity, go-live]
related:
  - "[[../project-context/go-live-readiness-audit]]"
  - "[[../daily-notes/2026-06-15-go-live-fixes-implementation]]"
---

# Enrollment-number race + NULL-on-no-semester (H6)

## Symptoms
1. **Race:** `EnrollmentNumberService::generate` computed the next number as `MAX(enrollment_no)+1`
   with no lock. Two concurrent application approvals could compute the same number; the UNIQUE index
   protected data integrity but the losing approval threw a raw `QueryException` and silently failed
   (secretary saw an unhandled error, no student created).
2. **NULL path:** `ApproveApplication` only generated an enrollment number *if* a semester was active.
   With no active semester it created the User + Student anyway, leaving `enrollment_no = NULL` and no
   retry — a permanently broken student record.

## Root cause
- MAX()+1 read and the save are not atomic, and nothing serialized concurrent approvals.
- The active-semester check was a silent `if` instead of a precondition.

## Fix
- `EnrollmentNumberService`: extracted `assignNextSequential()` used by both `generate()` and
  `regenerateForSpecializationChange()`. It retries (up to 5×) on
  `Illuminate\Database\UniqueConstraintViolationException`, recomputing MAX each pass (so the retry
  sees the winning row). A pessimistic `lockForUpdate` was NOT viable — Postgres forbids `FOR UPDATE`
  alongside the `MAX()` aggregate. After exhausting retries it throws a clear Arabic `RuntimeException`.
- `ApproveApplication`: added a top-of-method guard — if `Semester::active()` is null, throw a clear
  Arabic error and create nothing (consistent with the existing duplicate-email guard). The downstream
  `if ($activeSemester)` was removed so generation always runs.

## Regression guard
- New `tests/Feature/ApproveApplicationTest.php` (the action had **zero** prior coverage):
  - approval creates a student with a non-null enrollment number matching the `YY+spec+session` prefix.
  - approval with no active semester throws and creates no User/Student.
- The retry branch itself is defensive and not unit-tested (forcing a real mid-save unique collision
  would need brittle mocking); documented in code.
