<?php

use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\Specialization;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;

beforeEach(function () {
    $this->specialization = Specialization::factory()->create();
    $this->user = User::factory()->withRole('student')->create();
    $this->student = Student::factory()->create([
        'user_id' => $this->user->id,
        'specialization_id' => $this->specialization->id,
        'gpa' => 3.5,
    ]);
});

test('student is eligible for thesis when 10+ approved courses and gpa >= 3.0', function () {
    $semester = Semester::factory()->active()->create();

    for ($i = 0; $i < 10; $i++) {
        $course = Course::factory()->create();
        $offering = CourseOffering::factory()->create([
            'course_id' => $course->id,
            'semester_id' => $semester->id,
        ]);
        $enrollment = StudentEnrollment::factory()->create([
            'student_id' => $this->student->id,
            'course_offering_id' => $offering->id,
        ]);
        Grade::factory()->passing()->create(['enrollment_id' => $enrollment->id]);
    }

    expect($this->student->completedCoursesCount())->toBe(10);
    expect($this->student->isEligibleForThesis())->toBeTrue();
});

test('student is ineligible with fewer than 10 completed courses', function () {
    $semester = Semester::factory()->active()->create();

    for ($i = 0; $i < 5; $i++) {
        $course = Course::factory()->create();
        $offering = CourseOffering::factory()->create([
            'course_id' => $course->id,
            'semester_id' => $semester->id,
        ]);
        $enrollment = StudentEnrollment::factory()->create([
            'student_id' => $this->student->id,
            'course_offering_id' => $offering->id,
        ]);
        Grade::factory()->passing()->create(['enrollment_id' => $enrollment->id]);
    }

    expect($this->student->isEligibleForThesis())->toBeFalse();
});

test('student is ineligible with low gpa even with enough courses', function () {
    $this->student->update(['gpa' => 2.5]);
    $semester = Semester::factory()->active()->create();

    for ($i = 0; $i < 10; $i++) {
        $course = Course::factory()->create();
        $offering = CourseOffering::factory()->create([
            'course_id' => $course->id,
            'semester_id' => $semester->id,
        ]);
        $enrollment = StudentEnrollment::factory()->create([
            'student_id' => $this->student->id,
            'course_offering_id' => $offering->id,
        ]);
        Grade::factory()->passing()->create(['enrollment_id' => $enrollment->id]);
    }

    expect($this->student->isEligibleForThesis())->toBeFalse();
});

test('failed grades do not count toward completed courses', function () {
    $semester = Semester::factory()->active()->create();
    $course = Course::factory()->create();
    $offering = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'semester_id' => $semester->id,
    ]);
    $enrollment = StudentEnrollment::factory()->create([
        'student_id' => $this->student->id,
        'course_offering_id' => $offering->id,
    ]);
    Grade::factory()->failing()->create([
        'enrollment_id' => $enrollment->id,
    ]);

    expect($this->student->completedCoursesCount())->toBe(0);
});
