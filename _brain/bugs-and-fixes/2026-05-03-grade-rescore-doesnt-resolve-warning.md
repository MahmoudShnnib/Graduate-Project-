---
date: 2026-05-03
tags: [bug, grade, warning, observer, course-failure]
related: [[../daily-notes/2026-05-03-test-execution]]
status: fixed
---

# Re-scoring an F grade in-place (status stays Approved) does not resolve the course_failure warning

## Symptoms

Reproduced in tinker:

1. Approve grade with letter=F → `course_failure` warning created, `warnings_count = 1` ✅
2. Update the same Grade row's `coursework_grade` and `final_grade` to passing values **without changing `status`** (still `Approved`) → letter recomputes to B
3. `student_warnings` for that course is **still active** — `is_active=1`, `warnings_count=1` still

Per [[../daily-notes/2026-04-30-session3-discussion-requests]] (WARN-CF-007b), the test plan expects: "Approve A=F, then change A=F to A=C (re-approve as passing) → Warning resolved on re-approval; GPA recalc uses C; no dismissal."

## Root cause

`GradeObserver::updated` only re-evaluates warnings when `status` transitions to `Approved`. If the grade is already approved and only the numeric grades change, the observer does nothing — no `WarningService::evaluateGradeApproval()` call is dispatched.

The `Grade::saving` booted hook recomputes `total/letter/points` correctly, so the letter does change to B in the row. But the warning that was created on the prior F-approval remains stale.

## Workaround for admins

To "re-grade" an F to a passing grade properly:
1. Set status back to `PendingApproval` (or `Draft`)
2. Update the grades
3. Re-approve

The observer fires on the second status→Approved transition and the warning resolves.

## Fix sketch

Two options:

**Option A** — also fire `evaluateGradeApproval` from `GradeObserver::saved` when:
- `status === Approved` AND
- `wasChanged('letter')` (or `wasChanged('total')` / `wasChanged('points')`)

**Option B** — make the in-place grade edit forbidden once status=Approved (require re-pending first). This is more conservative but enforces the grade approval workflow.

Option A is simpler and matches the test plan expectation:

```php
// in GradeObserver::saved (NEW handler — currently only listens to created/updated for status transitions)
public function saved(Grade $grade): void
{
    if ($grade->status !== GradeStatus::Approved) return;

    if ($grade->wasChanged(['coursework_grade', 'final_grade', 'total', 'letter'])) {
        // Re-evaluate warning state
        app(WarningService::class)->evaluateGradeApproval($grade);
        // Re-recalc GPA (letter may have changed)
        app(GradeCalculationService::class)->recalculateStudentGPA($grade->enrollment->student);
    }
}
```

## Regression guard

```php
it('resolves course_failure warning when grade is rescored from F to passing without status change', function () {
    $student = studentWithRecord();
    $enrollment = $student->activeEnrollments()->first();
    $grade = Grade::factory()->for($enrollment, 'enrollment')->create([
        'coursework_grade' => 5, 'final_grade' => 5,  // F
        'status' => GradeStatus::Approved,
    ]);
    expect($student->fresh()->warnings_count)->toBe(1);

    $grade->update(['coursework_grade' => 30, 'final_grade' => 50]);  // letter → B (status stays Approved)

    expect($student->fresh()->warnings_count)->toBe(0);
    expect(StudentWarning::where('student_id', $student->id)->where('is_active', true)->count())->toBe(0);
});
```

## Impact

- **Severity: medium.** Admins working a grade-correction workflow will leave stale warnings on student records.
- Combined with the dismissal logic (2nd F → dismissed), a misclick + correction could leave a student "almost dismissed" with one stale F-warning.

## Related

- [[../decisions/2026-04-27-warning-system-and-dismissal]] — original warning design
- [[../bugs-and-fixes/2026-04-18-double-gpa-recalc-and-audit-churn]] — already-known issue with grade approval observer firing wrong things
