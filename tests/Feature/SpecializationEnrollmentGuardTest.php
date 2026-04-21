<?php

use App\Actions\Enrollment\EnrollStudentInOffering;
use App\Exceptions\CourseOutsideSpecializationException;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Semester;
use App\Models\Specialization;
use App\Models\StudentEnrollment;

it('blocks enrolling a student into a course outside their specialization', function () {
    ['student' => $student] = studentWithRecord();
    $semester = Semester::factory()->create();

    $otherSpecialization = Specialization::factory()->create();
    $course = Course::factory()->create();
    // Attach course to ONLY the "other" specialization — not the student's.
    $course->specializations()->attach($otherSpecialization->id, ['type' => 'general']);

    $offering = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'semester_id' => $semester->id,
    ]);

    $enroll = app(EnrollStudentInOffering::class);

    expect(fn () => $enroll($student, $offering))
        ->toThrow(CourseOutsideSpecializationException::class);

    // No enrollment row should have been created.
    expect(
        StudentEnrollment::where('student_id', $student->id)
            ->where('course_offering_id', $offering->id)
            ->exists()
    )->toBeFalse();
});

it('allows enrolling when the course is in any of the student specializations', function () {
    ['student' => $student] = studentWithRecord();
    $semester = Semester::factory()->create();

    $otherSpecialization = Specialization::factory()->create();

    $course = Course::factory()->create();
    // Course is in both the student's spec and another one.
    $course->specializations()->attach([
        $student->specialization_id => ['type' => 'general'],
        $otherSpecialization->id => ['type' => 'elective'],
    ]);

    $offering = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'semester_id' => $semester->id,
    ]);

    $enroll = app(EnrollStudentInOffering::class);
    $enrollment = $enroll($student, $offering);

    expect($enrollment->exists)->toBeTrue();
});

it('does not re-check specialization on an idempotent re-enrollment', function () {
    // Even if a course is later removed from the student's spec (unusual but
    // possible), re-running enroll on an already-active enrollment must not
    // throw — idempotency wins.
    ['student' => $student] = studentWithRecord();
    $semester = Semester::factory()->create();

    $course = Course::factory()->create();
    $course->specializations()->attach($student->specialization_id, ['type' => 'general']);

    $offering = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'semester_id' => $semester->id,
    ]);

    $enroll = app(EnrollStudentInOffering::class);
    $enroll($student, $offering);

    // Detach the spec — simulating a spec-assignment change.
    $course->specializations()->detach($student->specialization_id);

    // Re-running should not throw because the student is already actively enrolled.
    $result = $enroll($student, $offering);
    expect($result->exists)->toBeTrue();
});
