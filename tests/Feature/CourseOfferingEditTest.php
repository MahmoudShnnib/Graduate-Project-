<?php

use App\Filament\Resources\CourseOfferings\Pages\EditCourseOffering;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Semester;
use App\Models\User;
use Livewire\Livewire;

it('saves an existing open offering without a false duplicate error', function () {
    $semester = Semester::factory()->create(['is_active' => true]);
    $course = Course::factory()->create();
    $offering = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'semester_id' => $semester->id,
        'is_open' => true,
    ]);

    Livewire::actingAs(adminUser())
        ->test(EditCourseOffering::class, ['record' => $offering->id])
        ->fillForm(['is_open' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($offering->fresh()->is_open)->toBeFalse();
});

it('lets an admin change the faculty on an existing offering and save', function () {
    $semester = Semester::factory()->create(['is_active' => true]);
    $course = Course::factory()->create();
    $offering = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'semester_id' => $semester->id,
        'is_open' => true,
        'faculty_id' => null,
    ]);
    $professor = User::factory()->faculty()->create();

    Livewire::actingAs(adminUser())
        ->test(EditCourseOffering::class, ['record' => $offering->id])
        ->fillForm(['faculty_id' => $professor->id])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($offering->fresh()->faculty_id)->toBe($professor->id);
});
