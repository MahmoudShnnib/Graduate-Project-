<?php

use App\Filament\Student\Pages\CourseSurveyPage;
use App\Models\Course;
use App\Models\CourseSurvey;
use App\Models\Semester;
use Livewire\Livewire;

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

it('reports hasSubmittedSurvey correctly', function () {
    $ctx = openSurveyContext();

    $before = Livewire::actingAs($ctx['user'])->test(CourseSurveyPage::class);
    expect($before->instance()->hasSubmittedSurvey())->toBeFalse();

    $before->set('selections', [$ctx['course']->id => 1])->call('submit');

    $after = Livewire::actingAs($ctx['user'])->test(CourseSurveyPage::class);
    expect($after->instance()->hasSubmittedSurvey())->toBeTrue();
});
