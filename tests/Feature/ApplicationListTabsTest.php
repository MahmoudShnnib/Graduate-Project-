<?php

use App\Filament\Resources\Applications\Pages\ListApplications;
use App\Models\Application;
use App\Models\Semester;
use App\Models\Specialization;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

function seedSemesterApplications(): array
{
    $semA = Semester::factory()->active()->create();
    $semB = Semester::factory()->create();
    $spec1 = Specialization::factory()->create();
    $spec2 = Specialization::factory()->create();

    // Unique tracking codes per row: factory()->count(N) builds all rows before
    // insert, so the sequence-based generator would otherwise collide.
    Application::factory()->count(2)
        ->sequence(fn ($seq) => ['tracking_code' => 'APP-TA1-'.$seq->index])
        ->create(['semester_id' => $semA->id, 'specialization_id' => $spec1->id]);
    Application::factory()->create(['tracking_code' => 'APP-TA2-0', 'semester_id' => $semA->id, 'specialization_id' => $spec2->id]);
    Application::factory()->count(5)
        ->sequence(fn ($seq) => ['tracking_code' => 'APP-TB-'.$seq->index])
        ->create(['semester_id' => $semB->id, 'specialization_id' => $spec1->id]);

    return compact('semA', 'semB', 'spec1', 'spec2');
}

it('scopes specialization tab counts to the selected semester filter', function () {
    actingAs(adminUser());
    ['semA' => $semA, 'spec1' => $spec1, 'spec2' => $spec2] = seedSemesterApplications();

    $tabs = collect(
        Livewire::test(ListApplications::class)
            ->set('tableFilters.semester_id.value', $semA->id)
            ->instance()
            ->getSpecializationTabs()
    );

    expect($tabs->firstWhere('id', null)['count'])->toBe(3)
        ->and($tabs->firstWhere('id', $spec1->id)['count'])->toBe(2)
        ->and($tabs->firstWhere('id', $spec2->id)['count'])->toBe(1);
});

it('counts all semesters when the semester filter is cleared', function () {
    actingAs(adminUser());
    ['spec1' => $spec1, 'spec2' => $spec2] = seedSemesterApplications();

    $tabs = collect(
        Livewire::test(ListApplications::class)
            ->set('tableFilters.semester_id.value', null)
            ->instance()
            ->getSpecializationTabs()
    );

    expect($tabs->firstWhere('id', null)['count'])->toBe(8)
        ->and($tabs->firstWhere('id', $spec1->id)['count'])->toBe(7)
        ->and($tabs->firstWhere('id', $spec2->id)['count'])->toBe(1);
});

it('clears the scheduled exam date when the active semester has changed', function () {
    actingAs(adminUser());
    $old = Semester::factory()->create();
    $new = Semester::factory()->active()->create();

    session([
        'applications.scheduled_exam_date' => '2026-01-01 10:00',
        'applications.scheduled_exam_semester_id' => $old->id,
    ]);

    Livewire::test(ListApplications::class)
        ->assertSet('scheduledExamDate', null)
        ->assertSet('scheduledExamSemesterId', $new->id);
});

it('keeps the scheduled exam date while the active semester is unchanged', function () {
    actingAs(adminUser());
    $active = Semester::factory()->active()->create();

    session([
        'applications.scheduled_exam_date' => '2026-01-01 10:00',
        'applications.scheduled_exam_semester_id' => $active->id,
    ]);

    Livewire::test(ListApplications::class)
        ->assertSet('scheduledExamDate', '2026-01-01 10:00')
        ->assertSet('scheduledExamSemesterId', $active->id);
});
