<?php

use App\Enums\StudentStatus;
use App\Enums\SuspensionStatus;
use App\Models\Semester;
use App\Models\SuspensionRequest;

it('releases OnHold students who are not suspended for the newly active semester', function () {
    ['student' => $student] = studentWithRecord();
    $oldSemester = Semester::factory()->create(['is_active' => false]);

    SuspensionRequest::create([
        'student_id' => $student->id,
        'semester_id' => $oldSemester->id,
        'reason' => 'test',
        'status' => SuspensionStatus::Approved,
    ]);

    $student->updateQuietly(['status' => StudentStatus::OnHold]);

    Semester::factory()->create(['is_active' => true]);

    expect($student->fresh()->status)->toBe(StudentStatus::Active);
});

it('keeps OnHold students whose approved suspension covers the newly active semester', function () {
    ['student' => $student] = studentWithRecord();
    $newSemester = Semester::factory()->create(['is_active' => false]);

    SuspensionRequest::create([
        'student_id' => $student->id,
        'semester_id' => $newSemester->id,
        'reason' => 'test',
        'status' => SuspensionStatus::Approved,
    ]);

    $student->updateQuietly(['status' => StudentStatus::OnHold]);

    $newSemester->update(['is_active' => true]);

    expect($student->fresh()->status)->toBe(StudentStatus::OnHold);
});

it('still resets Inactive students to Active when a semester becomes active (regression)', function () {
    ['student' => $student] = studentWithRecord();
    $student->updateQuietly(['status' => StudentStatus::Inactive]);

    Semester::factory()->create(['is_active' => true]);

    expect($student->fresh()->status)->toBe(StudentStatus::Active);
});

it('releases OnHold via flipping an existing semester to active (not only on create)', function () {
    ['student' => $student] = studentWithRecord();
    $oldSemester = Semester::factory()->create(['is_active' => false]);

    SuspensionRequest::create([
        'student_id' => $student->id,
        'semester_id' => $oldSemester->id,
        'reason' => 'test',
        'status' => SuspensionStatus::Approved,
    ]);

    $student->updateQuietly(['status' => StudentStatus::OnHold]);

    $futureSemester = Semester::factory()->create(['is_active' => false]);
    $futureSemester->update(['is_active' => true]);

    expect($student->fresh()->status)->toBe(StudentStatus::Active);
});

it('does not release OnHold students if a pending (not approved) suspension exists for the active semester', function () {
    ['student' => $student] = studentWithRecord();
    $newSemester = Semester::factory()->create(['is_active' => false]);

    SuspensionRequest::create([
        'student_id' => $student->id,
        'semester_id' => $newSemester->id,
        'reason' => 'test',
        'status' => SuspensionStatus::Pending,
    ]);

    $student->updateQuietly(['status' => StudentStatus::OnHold]);

    $newSemester->update(['is_active' => true]);

    expect($student->fresh()->status)->toBe(StudentStatus::Active);
});
