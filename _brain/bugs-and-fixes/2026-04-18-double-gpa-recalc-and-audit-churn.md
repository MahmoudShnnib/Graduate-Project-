---
date: 2026-04-18
tags: [bug, grades, gpa, audit, observer]
related: [[../research/2026-04-18-deep-codebase-analysis]], [[../project-context/gotchas]], [[../daily-notes/2026-04-18-deep-codebase-analysis]]
status: identified
---

# Double GPA recalc + redundant audit rows on grade approval

> **Status:** Identified and verified against source. **Not yet fixed.**

## Symptoms

Every time a grade is approved via `ViewCourseGrades`, the student's GPA is recalculated **twice** and the `audit_logs` table gets **two identical `Student_updated` rows** with no semantic difference between them. Bulk approving N grades writes 2N gpa audit rows.

Not user-visible unless you check `audit_logs` or instrument the service — but it:

- Doubles wall-clock of grade approval (two full GPA recalc queries per grade)
- Bloats `audit_logs` with no new information
- Obscures the real audit trail (actual grade changes are interleaved with duplicate gpa rows)

## Root cause

Two independent write paths both trigger `GradeCalculationService::recalculateStudentGPA`:

1. **Observer path** (correct, intended):
   - [ViewCourseGrades.php:110-114](../../app/Filament/Resources/Grades/Pages/ViewCourseGrades.php#L110-L114) → `$record->update(['status' => GradeStatus::Approved, …])`
   - → [GradeObserver::updated](../../app/Observers/GradeObserver.php) fires because `status` changed
   - → `GradeCalculationService::recalculateStudentGPA($student)`
   - → `$student->save()` inside the service
   - → [AuditObserver::updated(Student)](../../app/Observers/AuditObserver.php#L27) fires, sees `gpa` change, writes `audit_logs` row

2. **Direct path** (redundant):
   - [ViewCourseGrades.php:117](../../app/Filament/Resources/Grades/Pages/ViewCourseGrades.php#L117) → `$calculator->recalculateStudentGPA($student);`
   - → same recalc, same `$student->save()`, same audit row

Same double-call exists in:
- [change_status action, L140-157](../../app/Filament/Resources/Grades/Pages/ViewCourseGrades.php#L140-L157) (only when transitioning into Approved)
- [approve_all bulk action, L204-225](../../app/Filament/Resources/Grades/Pages/ViewCourseGrades.php#L204-L225) (×N grades)

Compounding factor: [AuditObserver::WATCHED_FIELDS:L18](../../app/Observers/AuditObserver.php#L18) includes `'gpa'` in the `Student` watchlist. Dropping it from the watchlist would halve the audit churn even without fixing the double recalc — but the double recalc itself is still wasted compute.

## Historical context

The Apr 16 refactor (memory obs #186–189) added `GradeObserver` and extended `AuditObserver` to cover enrollment status changes. The direct calculator call in `ViewCourseGrades` predates the observer and was never removed. Classic "added the better pattern, left the old one in place" drift.

## Fix

Two-file change, ~15 minutes:

### File 1: `app/Filament/Resources/Grades/Pages/ViewCourseGrades.php`

Remove `GradeCalculationService` calls from all three actions. Keep `NotificationService::notifyGradePublished` (no observer dispatches it).

```diff
// approve action (L109-121)
 ->action(function (Grade $record, GradeCalculationService $calculator, NotificationService $notifier): void {
     $record->update([
         'status' => GradeStatus::Approved,
         'approved_by' => auth()->id(),
         'approved_at' => now(),
     ]);

     $student = $record->enrollment->student;
-    $calculator->recalculateStudentGPA($student);
     $notifier->notifyGradePublished($record);

     Notification::make()->title('تم اعتماد الدرجة بنجاح')->success()->send();
 }),
```

Apply the same removal in `change_status` (L155) and `approve_all` (L219). Remove the `GradeCalculationService` param from each closure signature too (no longer used).

### File 2: `app/Observers/AuditObserver.php`

Drop `'gpa'` from the `Student` watchlist — GPA history is derivable from `grades.approved_at` timestamps, and the dedicated `Grade_updated` audit rows already tell the story.

```diff
 Student::class => ['gpa', 'warnings_count', 'status', 'enrollment_no'],
+Student::class => ['warnings_count', 'status', 'enrollment_no'],
```

## Regression guard

Add a Pest feature test to `tests/Feature/`:

```php
it('records exactly one audit row per grade approval', function () {
    $student = studentWithRecord();
    $grade = Grade::factory()->for($student->activeEnrollments()->first(), 'enrollment')
        ->state(['status' => GradeStatus::PendingApproval])->create();

    expect(AuditLog::where('model_type', Student::class)->where('model_id', $student->id)->count())->toBe(0);

    $grade->update(['status' => GradeStatus::Approved, 'approved_by' => adminUser()->id, 'approved_at' => now()]);

    // Only the Grade audit row — no Student audit row (gpa dropped from watchlist).
    expect(AuditLog::where('model_type', Grade::class)->count())->toBe(1);
    expect(AuditLog::where('model_type', Student::class)->count())->toBe(0);
});

it('recalculates GPA exactly once on approval', function () {
    // spy on GradeCalculationService::recalculateStudentGPA call count → 1
});
```

## Related

- Observer cascade conventions: [[../project-context/gotchas#Soft-delete cascade is observer-driven, not FK-driven]]
- Watchlist pattern: [[../project-context/gotchas#WATCHED_FIELDS in AuditObserver is static + per-class]]
- Full analysis context: [[../research/2026-04-18-deep-codebase-analysis]]
