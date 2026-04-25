<?php

use App\Actions\Enrollment\DropStudentFromOffering;
use App\Actions\Enrollment\EnrollStudentInOffering;
use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\CourseSurvey;
use App\Models\Semester;

function makeEnrolledStudent(): array
{
    $semester = Semester::factory()->active()->create();
    $course = Course::factory()->create();
    $offering = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'semester_id' => $semester->id,
    ]);

    ['student' => $student] = studentWithRecord();

    // Attach the course to the student's specialization so the enrollment
    // specialization guard passes. Real-world setup requires this pivot.
    $course->specializations()->attach($student->specialization_id, ['type' => 'general']);

    app(EnrollStudentInOffering::class)($student, $offering);

    return compact('semester', 'course', 'offering', 'student');
}

test('dropping a student removes them from the course roster', function () {
    ['offering' => $offering, 'student' => $student] = makeEnrolledStudent();

    expect($offering->activeEnrollments()->count())->toBe(1);

    app(DropStudentFromOffering::class)($student, $offering);

    expect($offering->activeEnrollments()->count())->toBe(0)
        ->and($offering->enrollments()->count())->toBe(1)
        ->and($offering->enrollments()->first()->status)->toBe(EnrollmentStatus::Dropped);
});

test('dropping a student is idempotent', function () {
    ['offering' => $offering, 'student' => $student] = makeEnrolledStudent();
    $drop = app(DropStudentFromOffering::class);

    expect($drop($student, $offering))->toBeTrue()
        ->and($drop($student, $offering))->toBeFalse();
});

test('re-enrolling a dropped student reactivates the same row', function () {
    ['offering' => $offering, 'student' => $student] = makeEnrolledStudent();

    app(DropStudentFromOffering::class)($student, $offering);
    app(EnrollStudentInOffering::class)($student, $offering);

    expect($offering->enrollments()->count())->toBe(1)
        ->and($offering->activeEnrollments()->count())->toBe(1);
});

test('flipping student to on_hold cascades to drop their active enrollments', function () {
    ['offering' => $offering, 'student' => $student] = makeEnrolledStudent();

    expect($offering->activeEnrollments()->count())->toBe(1);

    $student->update(['status' => StudentStatus::OnHold]);

    expect($offering->fresh()->activeEnrollments()->count())->toBe(0)
        ->and($offering->fresh()->enrollments()->first()->status)->toBe(EnrollmentStatus::Withdrawn);
});

test('soft-deleting a student cascades to drop their active enrollments', function () {
    ['offering' => $offering, 'student' => $student] = makeEnrolledStudent();

    $student->delete();

    expect($offering->fresh()->activeEnrollments()->count())->toBe(0)
        ->and($offering->fresh()->enrollments()->first()->status)->toBe(EnrollmentStatus::Withdrawn);
});

test('enrolling a student marks the matching course survey as processed', function () {
    ['offering' => $offering, 'student' => $student, 'course' => $course, 'semester' => $semester] = makeEnrolledStudent();

    CourseSurvey::create([
        'student_id' => $student->id,
        'semester_id' => $semester->id,
        'course_id' => $course->id,
        'priority' => 1,
        'is_processed' => false,
    ]);

    app(EnrollStudentInOffering::class)($student, $offering);

    $processed = CourseSurvey::where('student_id', $student->id)
        ->where('course_id', $course->id)
        ->value('is_processed');

    expect((bool) $processed)->toBeTrue();
});
