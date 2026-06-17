<?php

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Semester;
use App\Models\StudentEnrollment;

beforeEach(function () {
    $this->semester = Semester::factory()->active()->create();
    Semester::flushActiveCache();

    ['user' => $this->user, 'student' => $this->student] = studentWithRecord();

    $course = Course::factory()->create();
    $course->specializations()->attach($this->student->specialization_id, ['type' => 'general']);
    $offering = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'semester_id' => $this->semester->id,
    ]);
    StudentEnrollment::factory()->create([
        'student_id' => $this->student->id,
        'course_offering_id' => $offering->id,
        'status' => EnrollmentStatus::Enrolled,
    ]);
});

it('lets the owning student print their registered courses as a PDF', function () {
    $this->actingAs($this->user);

    $response = $this->get(route('print.student-courses', $this->student));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

it('lets an admin print a student courses sheet', function () {
    $this->actingAs(adminUser());

    $this->get(route('print.student-courses', $this->student))->assertOk();
});

it('forbids a different student from printing someone else courses', function () {
    ['user' => $otherUser] = studentWithRecord();

    $this->actingAs($otherUser);

    $this->get(route('print.student-courses', $this->student))->assertForbidden();
});
