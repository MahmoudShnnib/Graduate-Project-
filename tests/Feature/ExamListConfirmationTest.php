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
        ->set('scores', [$application->id => 17.5])
        ->callAction('markPassed')
        ->assertHasNoActionErrors();

    expect($application->fresh()->status)->toBe(ApplicationStatus::Accepted)
        ->and($application->fresh()->exam_passed)->toBeTrue()
        ->and((float) $application->fresh()->exam_score)->toBe(17.5);
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
        ->set('scores', [$application->id => 6])
        ->callAction('markFailed')
        ->assertHasNoActionErrors();

    expect($application->fresh()->status)->toBe(ApplicationStatus::Rejected)
        ->and($application->fresh()->exam_passed)->toBeFalse();
});

it('refuses to accept when a selected applicant has no grade', function () {
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

    expect($application->fresh()->status)->toBe(ApplicationStatus::InitiallyApproved)
        ->and($application->fresh()->exam_passed)->toBeNull();
});

it('refuses to reject when a selected applicant has no grade', function () {
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

    expect($application->fresh()->status)->toBe(ApplicationStatus::InitiallyApproved);
});

it('refuses to accept applicants when the selected semester is archived', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

    $archived = Semester::factory()->archived()->create();
    $spec = Specialization::factory()->create();
    $application = Application::factory()->create([
        'specialization_id' => $spec->id,
        'semester_id' => $archived->id,
        'status' => ApplicationStatus::InitiallyApproved,
    ]);

    Livewire::actingAs(adminUser())
        ->test(ExamListPage::class)
        ->set('selectedSemester', $archived->id)
        ->set('selected', [$application->id])
        ->set('scores', [$application->id => 18])
        ->call('markPassed');

    expect($application->fresh()->status)->toBe(ApplicationStatus::InitiallyApproved)
        ->and($application->fresh()->exam_passed)->toBeNull();
});

it('reports the selected semester as archived for the read-only view gate', function () {
    $active = Semester::factory()->create(['is_active' => true]);
    $archived = Semester::factory()->archived()->create();

    $component = Livewire::actingAs(adminUser())->test(ExamListPage::class);

    expect($component->instance()->isSelectedSemesterArchived())->toBeFalse();

    $component->set('selectedSemester', $archived->id);
    expect($component->instance()->isSelectedSemesterArchived())->toBeTrue();

    $component->set('selectedSemester', $active->id);
    expect($component->instance()->isSelectedSemesterArchived())->toBeFalse();
});

it('lists processed applications in the results query scoped to the active semester', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

    $semester = Semester::factory()->create(['is_active' => true]);
    $spec = Specialization::factory()->create();

    $pending = Application::factory()->create([
        'specialization_id' => $spec->id,
        'semester_id' => $semester->id,
        'status' => ApplicationStatus::InitiallyApproved,
    ]);
    $accepted = Application::factory()->create([
        'specialization_id' => $spec->id,
        'semester_id' => $semester->id,
        'status' => ApplicationStatus::InitiallyApproved,
    ]);

    $component = Livewire::actingAs(adminUser())
        ->test(ExamListPage::class)
        ->set('selected', [$accepted->id])
        ->set('scores', [$accepted->id => 15])
        ->callAction('markPassed');

    $processed = $component->instance()->getProcessedApplications();

    expect($processed->pluck('id')->all())->toContain($accepted->id)
        ->and($processed->pluck('id')->all())->not->toContain($pending->id);
});

it('disables the accept/reject actions when nothing is selected', function () {
    Semester::factory()->create(['is_active' => true]);

    Livewire::actingAs(adminUser())
        ->test(ExamListPage::class)
        ->assertActionDisabled('markPassed')
        ->assertActionDisabled('markFailed');
});

it('select-all populates the selection so the actions enable in one round-trip', function () {
    $semester = Semester::factory()->create(['is_active' => true]);
    $spec = Specialization::factory()->create();
    collect(['SEL-1', 'SEL-2', 'SEL-3'])->each(fn (string $code) => Application::factory()->create([
        'specialization_id' => $spec->id,
        'semester_id' => $semester->id,
        'status' => ApplicationStatus::InitiallyApproved,
        'tracking_code' => $code,
    ]));

    Livewire::actingAs(adminUser())
        ->test(ExamListPage::class)
        ->call('toggleSelectAll', true)
        ->assertCount('selected', 3)
        ->assertActionEnabled('markPassed')
        ->assertActionEnabled('markFailed')
        ->call('toggleSelectAll', false)
        ->assertCount('selected', 0)
        ->assertActionDisabled('markPassed');
});
