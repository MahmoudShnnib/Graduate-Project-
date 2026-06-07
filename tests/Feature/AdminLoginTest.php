<?php

use App\Filament\Auth\Login;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

it('implements FilamentUser and allows super admin panel access', function () {
    $admin = adminUser();

    expect($admin)->toBeInstanceOf(\Filament\Models\Contracts\FilamentUser::class)
        ->and($admin->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
});

it('allows legacy secretary role column to access the admin panel', function () {
    $user = User::factory()->create([
        'role' => 'secretary',
        'is_active' => true,
    ]);
    $user->assignRole(Role::firstOrCreate(['name' => 'manager_assistant', 'guard_name' => 'web']));

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
});

it('authenticates a super admin through the Filament login page', function () {
    $admin = adminUser();

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $admin->email,
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertRedirect(Filament::getPanel('admin')->getUrl());

    $this->assertAuthenticatedAs($admin);
});
