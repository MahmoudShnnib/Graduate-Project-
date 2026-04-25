<?php

use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\StudentEnrollment;
use App\Models\ThesisRegistration;

function makeStudentEligibleForThesis(array $studentAttributes = []): array
{
    $record = studentWithRecord(array_merge(['gpa' => 3.5], $studentAttributes));
    $student = $record['student'];
    $spec = $student->specialization;
    $semester = Semester::factory()->active()->create();

    foreach (['general' => 4, 'specialized' => 4] as $type => $count) {
        for ($i = 0; $i < $count; $i++) {
            $course = Course::factory()->create();
            $course->specializations()->attach($spec->id, ['type' => $type]);
            $offering = CourseOffering::factory()->create(['course_id' => $course->id, 'semester_id' => $semester->id]);
            $enrollment = StudentEnrollment::factory()->create(['student_id' => $student->id, 'course_offering_id' => $offering->id]);
            Grade::factory()->passing()->create(['enrollment_id' => $enrollment->id]);
        }
    }

    for ($i = 0; $i < 2; $i++) {
        $course = Course::factory()->create();
        $course->specializations()->attach($spec->id, ['type' => 'elective']);
        $offering = CourseOffering::factory()->create(['course_id' => $course->id, 'semester_id' => $semester->id]);
        $enrollment = StudentEnrollment::factory()->create(['student_id' => $student->id, 'course_offering_id' => $offering->id]);
        Grade::factory()->passing()->create(['enrollment_id' => $enrollment->id]);
    }

    return $record;
}

test('student can access thesis registration page', function () {
    ['user' => $user] = studentWithRecord();

    $this->actingAs($user)->get('/portal/thesis-registration-page')->assertOk();
});

test('ineligible student cannot submit thesis registration', function () {
    ['student' => $student] = studentWithRecord(['gpa' => 2.5]);

    expect($student->isEligibleForThesis())->toBeFalse();
});

test('student with incomplete specialization courses is not eligible for thesis', function () {
    ['student' => $student] = studentWithRecord(['gpa' => 3.5]);
    $semester = Semester::factory()->active()->create();

    for ($i = 0; $i < 5; $i++) {
        $course = Course::factory()->create();
        $enrollment = StudentEnrollment::factory()->create([
            'student_id' => $student->id,
            'course_offering_id' => CourseOffering::factory()->create(['course_id' => $course->id, 'semester_id' => $semester->id])->id,
        ]);
        Grade::factory()->passing()->create(['enrollment_id' => $enrollment->id]);
    }

    $student->refresh();

    expect($student->isEligibleForThesis())->toBeFalse();
});

test('student with all required courses and sufficient GPA is eligible', function () {
    ['student' => $student] = makeStudentEligibleForThesis();

    $student->refresh();

    expect($student->isEligibleForThesis())->toBeTrue();
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

    expect($student->fresh()->thesisRegistration)->not->toBeNull();
});
