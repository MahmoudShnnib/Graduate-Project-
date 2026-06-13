<?php

use App\Enums\StudentStatus;
use App\Enums\SuspensionStatus;
use App\Models\Course;
use App\Models\CourseSurvey;
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

it('marks active students who submitted no survey as Inactive when the survey closes (prod crash regression)', function () {
    ['student' => $student] = studentWithRecord();
    $semester = Semester::factory()->create(['is_active' => true, 'survey_open' => true]);

    // On PostgreSQL this write previously crashed with students_status_check
    // because the stale CHECK constraint rejected the 'inactive' value.
    $semester->update(['survey_open' => false]);

    expect($student->fresh()->status)->toBe(StudentStatus::Inactive);
});

it('keeps active students who submitted survey entries Active when the survey closes', function () {
    ['student' => $student] = studentWithRecord();
    $semester = Semester::factory()->create(['is_active' => true, 'survey_open' => true]);

    CourseSurvey::create([
        'student_id' => $student->id,
        'semester_id' => $semester->id,
        'course_id' => Course::factory()->create()->id,
        'priority' => 1,
    ]);

    $semester->update(['survey_open' => false]);

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
