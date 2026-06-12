<?php

use App\Filament\Resources\Applications\ApplicationResource;
use App\Models\Application;
use App\Models\Semester;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('injects the save-button lock guard into the admin panel', function () {
    actingAs(adminUser());
    Semester::factory()->active()->create();
    $application = Application::factory()->create();

    get(ApplicationResource::getUrl('edit', ['record' => $application]))
        ->assertOk()
        ->assertSee('data-save-locked', escape: false)
        ->assertSee('__saveChanged', escape: false);
});
