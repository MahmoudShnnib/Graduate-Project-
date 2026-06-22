<?php

use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Models\Specialization;
use Livewire\Livewire;

beforeEach(function () {
    seedShieldRolesAndPermissions();
    $this->actingAs(adminUser());
    Specialization::factory()->create();
});

it('renders the live language-guard markup on the create form', function () {
    $html = Livewire::test(CreateStudent::class)->html();

    expect($html)->toContain('langErr')
        ->and($html)->toContain('يرجى إدخال البيانات باللغة العربية فقط')
        ->and($html)->toContain('يرجى إدخال البيانات باللغة الإنجليزية فقط');
});
