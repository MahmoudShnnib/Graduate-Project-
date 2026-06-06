<?php

use App\Actions\CreateApplication;
use App\Filament\Resources\Applications\Pages\ListApplications;
use App\Models\Application;
use App\Models\Semester;
use App\Models\Specialization;
use Livewire\Livewire;

it('attaches a newly submitted application to the active semester', function () {
    Semester::factory()->create(['name' => 'فصل قديم']);          // peer (will be archived)
    $active = Semester::factory()->active()->create(['name' => 'الفصل الحالي']);
    $spec = Specialization::factory()->create();

    $application = app(CreateApplication::class)->handle([
        'name' => 'محمد أحمد علي الزاوي',
        'email' => 'applicant@example.com',
        'phone' => '0912345678',
        'specialization_id' => $spec->id,
    ]);

    expect($application->semester_id)->toBe($active->id);
});

it('the admin applications table defaults to the active semester only', function () {
    $this->actingAs(adminUser());

    $old = Semester::factory()->create(['name' => 'فصل قديم']);
    $active = Semester::factory()->active()->create(['name' => 'الفصل الحالي']);
    $spec = Specialization::factory()->create();

    $oldApp = Application::factory()->create([
        'semester_id' => $old->id,
        'specialization_id' => $spec->id,
    ]);
    $newApp = Application::factory()->create([
        'semester_id' => $active->id,
        'specialization_id' => $spec->id,
    ]);

    Livewire::test(ListApplications::class)
        ->assertCanSeeTableRecords([$newApp])
        ->assertCanNotSeeTableRecords([$oldApp]);
});

it('the semester filter can reveal a past semester\'s applications', function () {
    $this->actingAs(adminUser());

    $old = Semester::factory()->create(['name' => 'فصل قديم']);
    $active = Semester::factory()->active()->create(['name' => 'الفصل الحالي']);
    $spec = Specialization::factory()->create();

    $oldApp = Application::factory()->create([
        'semester_id' => $old->id,
        'specialization_id' => $spec->id,
    ]);
    $newApp = Application::factory()->create([
        'semester_id' => $active->id,
        'specialization_id' => $spec->id,
    ]);

    Livewire::test(ListApplications::class)
        ->filterTable('semester_id', $old->id)
        ->assertCanSeeTableRecords([$oldApp])
        ->assertCanNotSeeTableRecords([$newApp]);
});
