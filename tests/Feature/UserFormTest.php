<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    seedShieldRolesAndPermissions();
    $this->actingAs(adminUser());
});

it('requires a role to be chosen when creating a user', function () {
    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'منسق جديد',
            'email' => 'coordinator@example.com',
            'password' => 'password123',
            'role' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['role' => 'required']);

    expect(User::where('email', 'coordinator@example.com')->exists())->toBeFalse();
});

it('creates a user when a role is chosen', function () {
    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'منسق جديد',
            'email' => 'coordinator@example.com',
            'password' => 'password123',
            'role' => 'manager_assistant',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::where('email', 'coordinator@example.com')->where('role', 'manager_assistant')->exists())->toBeTrue();
});

it('only accepts a nationality from the predefined list for faculty', function () {
    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'د. أحمد',
            'email' => 'faculty@example.com',
            'password' => 'password123',
            'role' => 'faculty',
            'national_id' => 'F12345',
            'birth_date' => '1980-01-01',
            'birth_place' => 'طرابلس',
            'nationality' => 'تونس',
            'gender' => 'male',
            'academic_rank' => 'أستاذ',
            'qualification' => 'دكتوراه',
            'institution' => 'كلية التقنية',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::where('email', 'faculty@example.com')->value('nationality'))->toBe('تونس');
});
