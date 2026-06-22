<?php

use App\Filament\Resources\Faculty\Pages\ListMyCourses;
use App\Models\CourseOffering;
use App\Models\Semester;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

function facultyWithCourses(): array
{
    $faculty = User::factory()->faculty()->create();
    Permission::firstOrCreate(['name' => 'ViewAny:CourseOffering', 'guard_name' => 'web']);
    $faculty->givePermissionTo('ViewAny:CourseOffering');

    $current = Semester::factory()->active()->create();
    $previous = Semester::factory()->create(['is_active' => false]);

    $currentOffering = CourseOffering::factory()->create([
        'faculty_id' => $faculty->id,
        'semester_id' => $current->id,
    ]);
    $previousOffering = CourseOffering::factory()->create([
        'faculty_id' => $faculty->id,
        'semester_id' => $previous->id,
    ]);

    return compact('faculty', 'current', 'previous', 'currentOffering', 'previousOffering');
}

it('defaults the faculty courses list to the current (active) semester', function () {
    $ctx = facultyWithCourses();

    Livewire::actingAs($ctx['faculty'])
        ->test(ListMyCourses::class)
        ->assertCanSeeTableRecords([$ctx['currentOffering']])
        ->assertCanNotSeeTableRecords([$ctx['previousOffering']]);
});

it('shows a previous semester offering when the filter is changed to it', function () {
    $ctx = facultyWithCourses();

    Livewire::actingAs($ctx['faculty'])
        ->test(ListMyCourses::class)
        ->filterTable('semester_id', $ctx['previous']->id)
        ->assertCanSeeTableRecords([$ctx['previousOffering']])
        ->assertCanNotSeeTableRecords([$ctx['currentOffering']]);
});

it('shows offerings from all semesters when the filter is cleared', function () {
    $ctx = facultyWithCourses();

    Livewire::actingAs($ctx['faculty'])
        ->test(ListMyCourses::class)
        ->filterTable('semester_id', null)
        ->assertCanSeeTableRecords([$ctx['currentOffering'], $ctx['previousOffering']]);
});
