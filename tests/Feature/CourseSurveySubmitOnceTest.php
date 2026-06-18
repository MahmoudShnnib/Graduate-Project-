<?php

use App\Enums\EnrollmentStatus;
use App\Filament\Student\Pages\CourseSurveyPage;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\CourseSurvey;
use App\Models\Semester;
use App\Models\StudentEnrollment;
use Livewire\Livewire;

function surveyStatusFor(array $ctx): string
{
    return Livewire::actingAs($ctx['user'])
        ->test(CourseSurveyPage::class)
        ->instance()
        ->getEnrollmentStatuses()[$ctx['course']->id] ?? 'missing';
}

function openSurveyContext(): array
{
    ['user' => $user, 'student' => $student] = studentWithRecord();

    $semester = Semester::factory()->active()->create(['survey_open' => true]);

    $course = Course::factory()->create(['is_active' => true]);
    $course->semesters()->attach($semester);
    $course->specializations()->attach($student->specialization_id);

    return compact('user', 'student', 'semester', 'course');
}

it('opens the confirmation modal before submitting', function () {
    $ctx = openSurveyContext();

    Livewire::actingAs($ctx['user'])
        ->test(CourseSurveyPage::class)
        ->set('selections', [$ctx['course']->id => 1])
        ->call('confirmSubmit')
        ->assertSet('confirmingSubmit', true);

    expect(CourseSurvey::where('student_id', $ctx['student']->id)->count())->toBe(0);
});

it('submits the survey once and cannot be resubmitted', function () {
    $ctx = openSurveyContext();

    Livewire::actingAs($ctx['user'])
        ->test(CourseSurveyPage::class)
        ->set('selections', [$ctx['course']->id => 1])
        ->call('submit')
        ->assertSet('confirmingSubmit', false);

    expect(CourseSurvey::where('student_id', $ctx['student']->id)->where('is_retake', false)->count())->toBe(1);

    // A second attempt (fresh page load) must not duplicate or replace the survey.
    Livewire::actingAs($ctx['user'])
        ->test(CourseSurveyPage::class)
        ->set('selections', [$ctx['course']->id => 1])
        ->call('submit');

    expect(CourseSurvey::where('student_id', $ctx['student']->id)->where('is_retake', false)->count())->toBe(1);
});

it('locks course toggling after the survey is submitted', function () {
    $ctx = openSurveyContext();

    Livewire::actingAs($ctx['user'])
        ->test(CourseSurveyPage::class)
        ->set('selections', [$ctx['course']->id => 1])
        ->call('submit');

    $other = Course::factory()->create(['is_active' => true]);
    $other->semesters()->attach($ctx['semester']);
    $other->specializations()->attach($ctx['student']->specialization_id);

    $component = Livewire::actingAs($ctx['user'])
        ->test(CourseSurveyPage::class)
        ->call('toggleCourse', $other->id);

    // The selection set is unchanged — only the originally submitted course remains.
    expect($component->get('selections'))->toHaveCount(1)
        ->and(array_key_exists($other->id, $component->get('selections')))->toBeFalse();
});

it('keeps a submitted choice pending (not "unavailable") while no offering exists yet', function () {
    $ctx = openSurveyContext();

    Livewire::actingAs($ctx['user'])
        ->test(CourseSurveyPage::class)
        ->set('selections', [$ctx['course']->id => 1])
        ->call('submit');

    // No CourseOffering opened yet — the choice is under review, not unavailable.
    expect(surveyStatusFor($ctx))->toBe('pending');
});

it('marks a surveyed choice enrolled once the admin registers the student', function () {
    $ctx = openSurveyContext();

    Livewire::actingAs($ctx['user'])
        ->test(CourseSurveyPage::class)
        ->set('selections', [$ctx['course']->id => 1])
        ->call('submit');

    $offering = CourseOffering::factory()->create([
        'course_id' => $ctx['course']->id,
        'semester_id' => $ctx['semester']->id,
    ]);
    StudentEnrollment::factory()->create([
        'student_id' => $ctx['student']->id,
        'course_offering_id' => $offering->id,
        'status' => EnrollmentStatus::Enrolled,
    ]);

    expect(surveyStatusFor($ctx))->toBe('enrolled');
});

it('marks a surveyed choice rejected when the admin rejects it', function () {
    $ctx = openSurveyContext();

    Livewire::actingAs($ctx['user'])
        ->test(CourseSurveyPage::class)
        ->set('selections', [$ctx['course']->id => 1])
        ->call('submit');

    CourseSurvey::where('student_id', $ctx['student']->id)
        ->where('course_id', $ctx['course']->id)
        ->update(['is_processed' => true, 'rejected_at' => now()]);

    expect(surveyStatusFor($ctx))->toBe('rejected');
});

it('reports hasSubmittedSurvey correctly', function () {
    $ctx = openSurveyContext();

    $before = Livewire::actingAs($ctx['user'])->test(CourseSurveyPage::class);
    expect($before->instance()->hasSubmittedSurvey())->toBeFalse();

    $before->set('selections', [$ctx['course']->id => 1])->call('submit');

    $after = Livewire::actingAs($ctx['user'])->test(CourseSurveyPage::class);
    expect($after->instance()->hasSubmittedSurvey())->toBeTrue();
});
