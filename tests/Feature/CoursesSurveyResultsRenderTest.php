<?php

use App\Filament\Pages\CoursesSurveyResults;
use App\Models\Course;
use App\Models\CourseSurvey;
use App\Models\Semester;
use Livewire\Livewire;

it('renders the collapsible survey results table without errors', function () {
    $semester = Semester::factory()->create(['is_active' => true]);
    $course = Course::factory()->create();
    ['student' => $student] = studentWithRecord();

    CourseSurvey::create([
        'student_id' => $student->id,
        'semester_id' => $semester->id,
        'course_id' => $course->id,
        'priority' => 1,
        'is_processed' => false,
    ]);

    Livewire::actingAs(adminUser())
        ->test(CoursesSurveyResults::class)
        ->assertOk()
        ->assertSee('نتائج استطلاع')
        ->assertSee($course->name);
});

it('keeps the session toggle in sync when collapsing the table', function () {
    Semester::factory()->create(['is_active' => true]);

    Livewire::actingAs(adminUser())
        ->test(CoursesSurveyResults::class)
        ->assertSet('showCoursesTable', true)
        ->call('toggleCoursesTable')
        ->assertSet('showCoursesTable', false);
});
