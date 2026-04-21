<?php

use App\Actions\Enrollment\EnrollStudentInOffering;
use App\Enums\EnrollmentStatus;
use App\Enums\SuspensionStatus;
use App\Exceptions\StudentSuspendedInSemesterException;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Semester;
use App\Models\StudentEnrollment;
use App\Models\SuspensionRequest;

/**
 * Helper: create a course attached to the student's specialization + an
 * offering in the given semester. Keeps test bodies readable.
 */
function makeOffering(\App\Models\Student $student, Semester $semester): CourseOffering
{
    $course = Course::factory()->create();
    $course->specializations()->attach($student->specialization_id, ['type' => 'general']);

    return CourseOffering::factory()->create([
        'course_id' => $course->id,
        'semester_id' => $semester->id,
    ]);
}

it('blocks enrollment when student is suspended in the target semester', function () {
    ['student' => $student] = studentWithRecord();
    $semester = Semester::factory()->create();
    $offering = makeOffering($student, $semester);

    SuspensionRequest::create([
        'student_id' => $student->id,
        'semester_id' => $semester->id,
        'reason' => 'test',
        'status' => SuspensionStatus::Approved,
    ]);

    $enroll = app(EnrollStudentInOffering::class);

    expect(fn () => $enroll($student, $offering))
        ->toThrow(StudentSuspendedInSemesterException::class);

    expect(
        StudentEnrollment::where('student_id', $student->id)
            ->where('course_offering_id', $offering->id)
            ->exists()
    )->toBeFalse();
});

it('does not block enrollment when suspension is only pending or rejected', function () {
    ['student' => $student] = studentWithRecord();
    $semester = Semester::factory()->create();
    $offering = makeOffering($student, $semester);

    SuspensionRequest::create([
        'student_id' => $student->id,
        'semester_id' => $semester->id,
        'reason' => 'test',
        'status' => SuspensionStatus::Pending,
    ]);

    $enroll = app(EnrollStudentInOffering::class);

    // Should succeed since the request isn't approved yet.
    $enrollment = $enroll($student, $offering);
    expect($enrollment->status)->toBe(EnrollmentStatus::Enrolled);
});

it('suspension in one semester does not block enrollment in another', function () {
    ['student' => $student] = studentWithRecord();
    $semesterA = Semester::factory()->create();
    $semesterB = Semester::factory()->create();

    SuspensionRequest::create([
        'student_id' => $student->id,
        'semester_id' => $semesterA->id,
        'reason' => 'test',
        'status' => SuspensionStatus::Approved,
    ]);

    $offeringInB = makeOffering($student, $semesterB);

    $enroll = app(EnrollStudentInOffering::class);
    $enrollment = $enroll($student, $offeringInB);

    expect($enrollment->status)->toBe(EnrollmentStatus::Enrolled);
});

it('isSuspendedInSemester returns true only for approved requests', function () {
    ['student' => $student] = studentWithRecord();
    $semester = Semester::factory()->create();

    expect($student->isSuspendedInSemester($semester))->toBeFalse();

    SuspensionRequest::create([
        'student_id' => $student->id,
        'semester_id' => $semester->id,
        'reason' => 'test',
        'status' => SuspensionStatus::Pending,
    ]);

    expect($student->isSuspendedInSemester($semester))->toBeFalse();

    SuspensionRequest::where('student_id', $student->id)->update(['status' => SuspensionStatus::Approved]);

    // Refresh relationship cache.
    expect($student->fresh()->isSuspendedInSemester($semester))->toBeTrue();
});
