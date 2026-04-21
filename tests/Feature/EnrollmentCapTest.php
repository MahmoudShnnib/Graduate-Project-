<?php

use App\Actions\Enrollment\EnrollStudentInOffering;
use App\Enums\EnrollmentStatus;
use App\Exceptions\EnrollmentCapReachedException;
use App\Models\CourseOffering;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;

/**
 * Attaches every offering's course to the student's specialization so the
 * enrollment specialization guard passes. Mirrors real-world setup.
 */
function attachOfferingsToStudentSpec(Student $student, iterable $offerings): void
{
    foreach ($offerings as $offering) {
        $offering->course->specializations()->syncWithoutDetaching([
            $student->specialization_id => ['type' => 'general'],
        ]);
    }
}

it('allows enrollment up to the cap', function () {
    ['student' => $student] = studentWithRecord();
    $semester = Semester::factory()->create();

    $offerings = CourseOffering::factory()
        ->count(EnrollStudentInOffering::MAX_ENROLLMENTS_PER_SEMESTER)
        ->create(['semester_id' => $semester->id]);

    attachOfferingsToStudentSpec($student, $offerings);

    $enroll = app(EnrollStudentInOffering::class);
    foreach ($offerings as $offering) {
        $enroll($student, $offering);
    }

    expect(
        StudentEnrollment::where('student_id', $student->id)
            ->where('status', EnrollmentStatus::Enrolled)
            ->count()
    )->toBe(EnrollStudentInOffering::MAX_ENROLLMENTS_PER_SEMESTER);
});

it('blocks enrollment beyond the cap with EnrollmentCapReachedException', function () {
    ['student' => $student] = studentWithRecord();
    $semester = Semester::factory()->create();

    $offerings = CourseOffering::factory()
        ->count(EnrollStudentInOffering::MAX_ENROLLMENTS_PER_SEMESTER + 1)
        ->create(['semester_id' => $semester->id]);

    attachOfferingsToStudentSpec($student, $offerings);

    $enroll = app(EnrollStudentInOffering::class);

    // Fill to cap.
    for ($i = 0; $i < EnrollStudentInOffering::MAX_ENROLLMENTS_PER_SEMESTER; $i++) {
        $enroll($student, $offerings[$i]);
    }

    // The (N+1)th should throw.
    $overflow = $offerings[EnrollStudentInOffering::MAX_ENROLLMENTS_PER_SEMESTER];
    expect(fn () => $enroll($student, $overflow))
        ->toThrow(EnrollmentCapReachedException::class);

    // State unchanged — overflow was never recorded.
    expect(
        StudentEnrollment::where('student_id', $student->id)
            ->where('course_offering_id', $overflow->id)
            ->exists()
    )->toBeFalse();
});

it('re-running enrollment on an already-enrolled offering does not count against the cap', function () {
    // Idempotency guard: calling enroll on the same offering twice must not
    // increment the cap count, so the 4th call for a student at-cap should succeed
    // if it targets an existing active enrollment.
    ['student' => $student] = studentWithRecord();
    $semester = Semester::factory()->create();

    $offerings = CourseOffering::factory()
        ->count(EnrollStudentInOffering::MAX_ENROLLMENTS_PER_SEMESTER)
        ->create(['semester_id' => $semester->id]);

    attachOfferingsToStudentSpec($student, $offerings);

    $enroll = app(EnrollStudentInOffering::class);
    foreach ($offerings as $offering) {
        $enroll($student, $offering);
    }

    // Re-run on the first offering — should not throw, should not create a new row.
    $enrollment = $enroll($student, $offerings->first());

    expect($enrollment->status)->toBe(EnrollmentStatus::Enrolled);
    expect(
        StudentEnrollment::where('student_id', $student->id)
            ->where('status', EnrollmentStatus::Enrolled)
            ->count()
    )->toBe(EnrollStudentInOffering::MAX_ENROLLMENTS_PER_SEMESTER);
});

it('counts enrollments per-semester — student at-cap in one semester can still enroll in another', function () {
    ['student' => $student] = studentWithRecord();
    $semesterA = Semester::factory()->create();
    $semesterB = Semester::factory()->create();

    $offeringsA = CourseOffering::factory()
        ->count(EnrollStudentInOffering::MAX_ENROLLMENTS_PER_SEMESTER)
        ->create(['semester_id' => $semesterA->id]);

    $offeringB = CourseOffering::factory()->create(['semester_id' => $semesterB->id]);

    attachOfferingsToStudentSpec($student, $offeringsA);
    attachOfferingsToStudentSpec($student, [$offeringB]);

    $enroll = app(EnrollStudentInOffering::class);
    foreach ($offeringsA as $offering) {
        $enroll($student, $offering);
    }

    // At cap in semesterA, but semesterB is fresh.
    $enrollment = $enroll($student, $offeringB);

    expect($enrollment->status)->toBe(EnrollmentStatus::Enrolled);
});
