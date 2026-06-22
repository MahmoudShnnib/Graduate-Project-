<?php

use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Models\Specialization;
use Livewire\Livewire;

beforeEach(function () {
    seedShieldRolesAndPermissions();
    $this->actingAs(adminUser());
    Specialization::factory()->create();
});

it('rejects a create when password and confirmation do not match', function () {
    Livewire::test(CreateStudent::class)
        ->fillForm([
            'user_password' => 'secret123',
            'user_password_confirmation' => 'different123',
        ])
        ->call('create')
        ->assertHasFormErrors(['user_password']);
});

it('accepts a create when password and confirmation match', function () {
    Livewire::test(CreateStudent::class)
        ->fillForm([
            'user_password' => 'secret123',
            'user_password_confirmation' => 'secret123',
        ])
        ->call('create')
        ->assertHasNoFormErrors(['user_password']);
});

it('requires the phone number on create', function () {
    Livewire::test(CreateStudent::class)
        ->fillForm([
            'user_phone' => '',
        ])
        ->call('create')
        ->assertHasFormErrors(['user_phone']);
});

it('accepts a valid Libyan phone number on create', function () {
    Livewire::test(CreateStudent::class)
        ->fillForm([
            'user_phone' => '0912345678',
        ])
        ->call('create')
        ->assertHasNoFormErrors(['user_phone']);
});
