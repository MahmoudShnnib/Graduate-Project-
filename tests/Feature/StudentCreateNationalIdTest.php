<?php

use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Models\Specialization;
use Livewire\Livewire;

beforeEach(function () {
    seedShieldRolesAndPermissions();
    $this->actingAs(adminUser());
    Specialization::factory()->create();
});

it('rejects a national number that is not exactly 12 digits for a Libyan student', function () {
    Livewire::test(CreateStudent::class)
        ->fillForm([
            'nationality' => 'ليبيا',
            'national_id' => '12345',
        ])
        ->call('create')
        ->assertHasFormErrors(['national_id']);
});

it('accepts a 12-digit national number for a Libyan student', function () {
    Livewire::test(CreateStudent::class)
        ->fillForm([
            'nationality' => 'ليبيا',
            'national_id' => '120123456789',
        ])
        ->call('create')
        ->assertHasNoFormErrors(['national_id']);
});

it('accepts an alphanumeric ID / passport for a non-Libyan student', function () {
    Livewire::test(CreateStudent::class)
        ->fillForm([
            'nationality' => 'تونس',
            'national_id' => 'AB123456',
        ])
        ->call('create')
        ->assertHasNoFormErrors(['national_id']);
});

it('rejects a too-short ID / passport for a non-Libyan student', function () {
    Livewire::test(CreateStudent::class)
        ->fillForm([
            'nationality' => 'تونس',
            'national_id' => 'AB',
        ])
        ->call('create')
        ->assertHasFormErrors(['national_id']);
});
