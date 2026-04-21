<?php

use App\Actions\Enrollment\EnrollStudentInOffering;
use App\Enums\EnrollmentStatus;
use App\Exceptions\CourseOfferingClosedException;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Semester;
use App\Models\StudentEnrollment;

/**
 * Helper — creates a course + offering wired into the student's specialization
 * so we isolate the is_open behavior from the spec/cap/suspension guards.
 */
function makeOpenOfferingForStudent(\App\Models\Student $student, Semester $semester): CourseOffering
{
    $course = Course::factory()->create();
    $course->specializations()->attach($student->specialization_id, ['type' => 'general']);

    return CourseOffering::factory()->create([
        'course_id' => $course->id,
        'semester_id' => $semester->id,
        'is_open' => true,
    ]);
}

it('blocks enrollment into a closed offering with CourseOfferingClosedException', function () {
    ['student' => $student] = studentWithRecord();
    $semester = Semester::factory()->create();

    $offering = makeOpenOfferingForStudent($student, $semester);
    $offering->update(['is_open' => false]);

    $enroll = app(EnrollStudentInOffering::class);

    expect(fn () => $enroll($student, $offering))
        ->toThrow(CourseOfferingClosedException::class);

    expect(
        StudentEnrollment::where('student_id', $student->id)
            ->where('course_offering_id', $offering->id)
            ->exists()
    )->toBeFalse();
});

it('cascades-withdraws all active enrollments when an offering transitions from open to closed', function () {
    $semester = Semester::factory()->create();

    ['student' => $s1] = studentWithRecord();
    ['student' => $s2] = studentWithRecord();

    $course = Course::factory()->create();
    // Attach course to both students' specializations.
    $course->specializations()->syncWithoutDetaching([
        $s1->specialization_id => ['type' => 'general'],
        $s2->specialization_id => ['type' => 'general'],
    ]);

    $offering = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'semester_id' => $semester->id,
        'is_open' => true,
    ]);

    $enroll = app(EnrollStudentInOffering::class);
    $enroll($s1, $offering);
    $enroll($s2, $offering);

    expect($offering->fresh()->activeEnrollments()->count())->toBe(2);

    // Close the offering — observer should fire.
    $offering->update(['is_open' => false]);

    expect($offering->fresh()->activeEnrollments()->count())->toBe(0);
    expect($offering->fresh()->enrollments()->pluck('status')->unique()->values()->toArray())
        ->toBe([EnrollmentStatus::Withdrawn]);
});

it('does not cascade when an offering transitions from closed to open', function () {
    ['student' => $student] = studentWithRecord();
    $semester = Semester::factory()->create();

    $offering = makeOpenOfferingForStudent($student, $semester);

    app(EnrollStudentInOffering::class)($student, $offering);

    expect($offering->fresh()->activeEnrollments()->count())->toBe(1);

    // Close → cascade withdraws.
    $offering->update(['is_open' => false]);
    expect($offering->fresh()->activeEnrollments()->count())->toBe(0);

    // Re-open → no re-activation (admin must explicitly re-enroll).
    $offering->update(['is_open' => true]);
    expect($offering->fresh()->activeEnrollments()->count())->toBe(0);
});

it('is idempotent — saving is_open=false when already closed does not re-run the cascade', function () {
    ['student' => $student] = studentWithRecord();
    $semester = Semester::factory()->create();

    $offering = makeOpenOfferingForStudent($student, $semester);

    // Enroll one student, close, re-enroll isn't allowed by guard — but the
    // idempotency we're testing is: re-saving the closed offering with the
    // same is_open=false value must NOT try to re-cascade.
    app(EnrollStudentInOffering::class)($student, $offering);
    $offering->update(['is_open' => false]);

    $enrollmentBefore = StudentEnrollment::where('student_id', $student->id)
        ->where('course_offering_id', $offering->id)
        ->first();

    // Save the same value again — observer should short-circuit on wasChanged.
    $offering->update(['is_open' => false]);
    // Also touch an unrelated field to force a save that doesn't change is_open.
    $offering->update(['coursework_max' => 50]);

    $enrollmentAfter = StudentEnrollment::where('student_id', $student->id)
        ->where('course_offering_id', $offering->id)
        ->first();

    expect($enrollmentAfter->status)->toBe($enrollmentBefore->status);
    expect($enrollmentAfter->updated_at->eq($enrollmentBefore->updated_at))->toBeTrue();
});

it('re-enrolling into a re-opened offering works (idempotency + guard short-circuit)', function () {
    ['student' => $student] = studentWithRecord();
    $semester = Semester::factory()->create();

    $offering = makeOpenOfferingForStudent($student, $semester);
    $enroll = app(EnrollStudentInOffering::class);

    $enroll($student, $offering);
    $offering->update(['is_open' => false]); // student withdrawn
    $offering->update(['is_open' => true]);  // re-open

    // Re-enrollment succeeds — existing Withdrawn row flips back to Enrolled.
    $enroll($student, $offering);

    expect(
        StudentEnrollment::where('student_id', $student->id)
            ->where('course_offering_id', $offering->id)
            ->where('status', EnrollmentStatus::Enrolled)
            ->exists()
    )->toBeTrue();
});
