<?php

use App\Enums\ApplicationStatus;
use App\Filament\Pages\ExamListPage;
use App\Models\Application;
use App\Models\Semester;
use App\Models\Specialization;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

it('accepts selected applicants through the confirmed accept action', function () {
    Notification::fake();
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

    $semester = Semester::factory()->create(['is_active' => true]);
    $spec = Specialization::factory()->create();
    $application = Application::factory()->create([
        'specialization_id' => $spec->id,
        'semester_id' => $semester->id,
        'status' => ApplicationStatus::InitiallyApproved,
    ]);

    Livewire::actingAs(adminUser())
        ->test(ExamListPage::class)
        ->set('selected', [$application->id])
        ->callAction('markPassed')
        ->assertHasNoActionErrors();

    expect($application->fresh()->status)->toBe(ApplicationStatus::Accepted)
        ->and($application->fresh()->exam_passed)->toBeTrue();
});

it('rejects selected applicants through the confirmed reject action', function () {
    $semester = Semester::factory()->create(['is_active' => true]);
    $spec = Specialization::factory()->create();
    $application = Application::factory()->create([
        'specialization_id' => $spec->id,
        'semester_id' => $semester->id,
        'status' => ApplicationStatus::InitiallyApproved,
    ]);

    Livewire::actingAs(adminUser())
        ->test(ExamListPage::class)
        ->set('selected', [$application->id])
        ->callAction('markFailed')
        ->assertHasNoActionErrors();

    expect($application->fresh()->status)->toBe(ApplicationStatus::Rejected)
        ->and($application->fresh()->exam_passed)->toBeFalse();
});

it('disables the accept/reject actions when nothing is selected', function () {
    Semester::factory()->create(['is_active' => true]);

    Livewire::actingAs(adminUser())
        ->test(ExamListPage::class)
        ->assertActionDisabled('markPassed')
        ->assertActionDisabled('markFailed');
});
