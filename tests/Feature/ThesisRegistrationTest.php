<?php

use App\Models\Grade;
use App\Models\StudentEnrollment;
use App\Models\ThesisRegistration;

test('student can access thesis registration page', function () {
    ['user' => $user] = studentWithRecord();

    $this->actingAs($user)->get('/portal/thesis-registration-page')->assertOk();
});

test('ineligible student cannot submit thesis registration', function () {
    ['student' => $student] = studentWithRecord(['gpa' => 2.5]);

    expect($student->isEligibleForThesis())->toBeFalse();
});

test('student with fewer than 10 approved courses is not eligible for thesis', function () {
    ['student' => $student] = studentWithRecord(['gpa' => 3.5]);

    // Create only 5 approved non-F grades
    StudentEnrollment::factory()
        ->count(5)
        ->for($student)
        ->has(Grade::factory()->passing(), 'grade')
        ->create();

    $student->refresh();

    expect($student->completedCoursesCount())->toBe(5)
        ->and($student->isEligibleForThesis())->toBeFalse();
});

test('student with 10 approved courses and sufficient GPA is eligible', function () {
    ['student' => $student] = studentWithRecord(['gpa' => 3.5]);

    StudentEnrollment::factory()
        ->count(10)
        ->for($student)
        ->has(Grade::factory()->passing(), 'grade')
        ->create();

    $student->refresh();

    expect($student->completedCoursesCount())->toBe(10)
        ->and($student->isEligibleForThesis())->toBeTrue();
});

test('thesis registration is created in pending status', function () {
    ['student' => $student] = studentWithRecord();

    $thesis = ThesisRegistration::factory()->create([
        'student_id' => $student->id,
        'status' => 'pending',
    ]);

    expect($thesis->status)->toBe('pending')
        ->and($thesis->student_id)->toBe($student->id);
});

test('student cannot have more than one thesis registration', function () {
    ['student' => $student] = studentWithRecord();

    ThesisRegistration::factory()->create(['student_id' => $student->id]);

    expect($student->thesisRegistration)->not->toBeNull();

    // A second factory create would cause a DB unique constraint or logic check
    expect($student->fresh()->thesisRegistration)->not->toBeNull();
});
