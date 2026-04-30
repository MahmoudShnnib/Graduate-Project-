<?php

use App\Filament\Resources\Students\Pages\EditStudent;
use App\Models\Course;
use App\Models\TransferCourse;
use Livewire\Livewire;

it('populates the transfer_courses form field from existing TransferCourse rows on edit', function () {
    ['student' => $student] = studentWithRecord(['is_transfer' => true]);

    $course = Course::factory()->create();

    $student->transferCourses()->create([
        'course_id' => $course->id,
        'coursework_grade' => 35,
        'final_grade' => 50,
    ]);

    $this->actingAs(adminUser());

    Livewire::test(EditStudent::class, ['record' => $student->getRouteKey()])
        ->assertFormSet(fn (array $data) => count($data['transfer_courses'] ?? []) === 1
            && $data['transfer_courses'][0]['course_id'] === $course->id
            && (int) $data['transfer_courses'][0]['coursework_grade'] === 35
            && (int) $data['transfer_courses'][0]['final_grade'] === 50
        );
});

it('persists transfer_courses on edit save and recalculates gpa', function () {
    ['student' => $student] = studentWithRecord(['is_transfer' => true, 'gpa' => 0]);

    $courseA = Course::factory()->create(['credits' => 3]);
    $courseA->specializations()->attach($student->specialization_id, ['type' => 'general']);
    $courseB = Course::factory()->create(['credits' => 3]);
    $courseB->specializations()->attach($student->specialization_id, ['type' => 'general']);

    $this->actingAs(adminUser());

    Livewire::test(EditStudent::class, ['record' => $student->getRouteKey()])
        ->fillForm([
            'is_transfer' => true,
            'transfer_courses' => [
                ['course_id' => $courseA->id, 'coursework_grade' => 38, 'final_grade' => 55],
                ['course_id' => $courseB->id, 'coursework_grade' => 32, 'final_grade' => 48],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(TransferCourse::where('student_id', $student->id)->count())->toBe(2);
    expect($student->fresh()->gpa)->toBeGreaterThan(0);
});

it('deletes transfer_courses when is_transfer is toggled off', function () {
    ['student' => $student] = studentWithRecord(['is_transfer' => true]);

    $course = Course::factory()->create();
    $student->transferCourses()->create([
        'course_id' => $course->id,
        'coursework_grade' => 35,
        'final_grade' => 50,
    ]);

    expect($student->transferCourses()->count())->toBe(1);

    $this->actingAs(adminUser());

    Livewire::test(EditStudent::class, ['record' => $student->getRouteKey()])
        ->fillForm(['is_transfer' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(TransferCourse::where('student_id', $student->id)->count())->toBe(0);
});
