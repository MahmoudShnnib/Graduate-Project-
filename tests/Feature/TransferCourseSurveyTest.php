<?php

use App\Filament\Student\Pages\CourseSurveyPage;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\StudentEnrollment;
use Livewire\Livewire;

function transferSurveyContext(): array
{
    ['user' => $user, 'student' => $student] = studentWithRecord();

    $semester = Semester::factory()->active()->create(['survey_open' => true]);

    return compact('user', 'student', 'semester');
}

function makeSemesterCourse(array $ctx): Course
{
    $course = Course::factory()->create(['is_active' => true]);
    $course->semesters()->attach($ctx['semester']);
    $course->specializations()->attach($ctx['student']->specialization_id);

    return $course;
}

it('excludes a transfer (معادلة) course from the survey list', function () {
    $ctx = transferSurveyContext();
    $transferCourse = makeSemesterCourse($ctx);
    $freshCourse = makeSemesterCourse($ctx);

    $ctx['student']->transferCourses()->create([
        'course_id' => $transferCourse->id,
        'coursework_grade' => 30,
        'final_grade' => 45, // 75 → B+ (passed, below A)
    ]);

    $available = Livewire::actingAs($ctx['user'])
        ->test(CourseSurveyPage::class)
        ->instance()
        ->getAvailableCourses()
        ->pluck('id');

    expect($available)->not->toContain($transferCourse->id)
        ->and($available)->toContain($freshCourse->id);
});

it('excludes a regular passed course from the survey list', function () {
    $ctx = transferSurveyContext();
    $passedCourse = makeSemesterCourse($ctx);

    $offering = CourseOffering::factory()->create([
        'course_id' => $passedCourse->id,
        'semester_id' => $ctx['semester']->id,
    ]);
    $enrollment = StudentEnrollment::factory()->create([
        'student_id' => $ctx['student']->id,
        'course_offering_id' => $offering->id,
    ]);
    Grade::factory()->passing()->create(['enrollment_id' => $enrollment->id]);

    $available = Livewire::actingAs($ctx['user'])
        ->test(CourseSurveyPage::class)
        ->instance()
        ->getAvailableCourses()
        ->pluck('id');

    expect($available)->not->toContain($passedCourse->id);
});

it('lists a below-A transfer course as retakeable', function () {
    $ctx = transferSurveyContext();
    $transferCourse = makeSemesterCourse($ctx);

    $ctx['student']->transferCourses()->create([
        'course_id' => $transferCourse->id,
        'coursework_grade' => 30,
        'final_grade' => 45, // 75 → B+ (below A)
    ]);

    $retakeable = Livewire::actingAs($ctx['user'])
        ->test(CourseSurveyPage::class)
        ->instance()
        ->getRetakeableCourses()
        ->pluck('id');

    expect($retakeable)->toContain($transferCourse->id);
});

it('does not list an A-grade transfer course as retakeable', function () {
    $ctx = transferSurveyContext();
    $transferCourse = makeSemesterCourse($ctx);

    $ctx['student']->transferCourses()->create([
        'course_id' => $transferCourse->id,
        'coursework_grade' => 50,
        'final_grade' => 38, // 88 → A
    ]);

    $retakeable = Livewire::actingAs($ctx['user'])
        ->test(CourseSurveyPage::class)
        ->instance()
        ->getRetakeableCourses()
        ->pluck('id');

    expect($retakeable)->not->toContain($transferCourse->id);
});
