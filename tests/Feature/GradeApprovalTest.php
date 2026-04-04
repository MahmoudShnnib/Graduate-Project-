<?php

use App\Enums\GradeStatus;
use App\Models\Grade;
use App\Models\StudentEnrollment;
use App\Services\GradeCalculationService;

test('admin can access grades list page', function () {
    $admin = adminUser();

    $this->actingAs($admin)->get('/admin/grades')->assertOk();
});

test('grade auto-calculates total and letter when both components are set', function () {
    $enrollment = StudentEnrollment::factory()->create();

    $grade = Grade::create([
        'enrollment_id' => $enrollment->id,
        'coursework_grade' => 50.0,
        'final_grade' => 30.0,
        'status' => GradeStatus::PendingApproval,
    ]);

    expect((float) $grade->total)->toBe(80.0)
        ->and($grade->letter)->toBe('B+')
        ->and((float) $grade->points)->toBe(3.5);
});

test('grade with total 85+ gets letter A', function () {
    $enrollment = StudentEnrollment::factory()->create();

    $grade = Grade::create([
        'enrollment_id' => $enrollment->id,
        'coursework_grade' => 55.0,
        'final_grade' => 35.0,
        'status' => GradeStatus::PendingApproval,
    ]);

    expect((float) $grade->total)->toBe(90.0)
        ->and($grade->letter)->toBe('A')
        ->and((float) $grade->points)->toBe(4.0);
});

test('grade below 50 gets letter F', function () {
    $enrollment = StudentEnrollment::factory()->create();

    $grade = Grade::create([
        'enrollment_id' => $enrollment->id,
        'coursework_grade' => 20.0,
        'final_grade' => 15.0,
        'status' => GradeStatus::PendingApproval,
    ]);

    expect((float) $grade->total)->toBe(35.0)
        ->and($grade->letter)->toBe('F')
        ->and((float) $grade->points)->toBe(0.0);
});

test('approving a grade recalculates student GPA', function () {
    ['student' => $student] = studentWithRecord(['gpa' => 0.0]);

    $enrollment = StudentEnrollment::factory()->create(['student_id' => $student->id]);

    $grade = Grade::factory()->create([
        'enrollment_id' => $enrollment->id,
        'coursework_grade' => 55.0,
        'final_grade' => 35.0,
        'status' => GradeStatus::PendingApproval,
    ]);

    $grade->update([
        'status' => GradeStatus::Approved,
        'approved_by' => adminUser()->id,
        'approved_at' => now(),
    ]);

    $calculator = app(GradeCalculationService::class);
    $calculator->recalculateStudentGPA($student->fresh());

    $student->refresh();

    expect((float) $student->gpa)->toBeGreaterThan(0.0);
});

test('grade does not auto-calculate when only one component is set', function () {
    $enrollment = StudentEnrollment::factory()->create();

    $grade = Grade::create([
        'enrollment_id' => $enrollment->id,
        'coursework_grade' => 50.0,
        'final_grade' => null,
        'status' => GradeStatus::Draft,
    ]);

    expect($grade->total)->toBeNull()
        ->and($grade->letter)->toBeNull();
});
