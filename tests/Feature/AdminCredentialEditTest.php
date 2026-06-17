<?php

use App\Filament\Resources\Students\Pages\EditStudent;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use Livewire\Livewire;

/*
 * Part 3 — student edit exposes editable contact fields (email + phone) that
 * live on the related User row; Part 4 — the User edit form has no password field.
 * See _brain/project-context/password-management-guide.md.
 */

function fullStudentAttributes(array $overrides = []): array
{
    return array_merge([
        'mother_name' => 'فاطمة أحمد',
        'birth_date' => '1990-01-01',
        'birth_place' => 'طرابلس',
        'gender' => 'male',
        'marital_status' => 'single',
        'nationality' => 'ليبيا',
        'national_id' => '111111111111',
        'residence' => 'طرابلس',
        'permanent_address' => 'طرابلس',
        'study_type' => 'self_funded',
    ], $overrides);
}

it('pre-fills the student edit form with the related user email and phone', function () {
    ['student' => $student, 'user' => $user] = studentWithRecord(fullStudentAttributes());
    $user->update(['email' => 'old@example.com', 'phone' => '0911111111']);

    $this->actingAs(adminUser());

    Livewire::test(EditStudent::class, ['record' => $student->getRouteKey()])
        ->assertFormSet([
            'user_email' => 'old@example.com',
            'user_phone' => '0911111111',
        ]);
});

it('persists edited email and phone to the related user on save', function () {
    ['student' => $student, 'user' => $user] = studentWithRecord(fullStudentAttributes());

    $this->actingAs(adminUser());

    Livewire::test(EditStudent::class, ['record' => $student->getRouteKey()])
        ->fillForm([
            'user_email' => 'new@example.com',
            'user_phone' => '0922222222',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->fresh()->email)->toBe('new@example.com');
    expect($user->fresh()->phone)->toBe('0922222222');
});

it('rejects a duplicate email on student edit', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    ['student' => $student] = studentWithRecord(fullStudentAttributes());

    $this->actingAs(adminUser());

    Livewire::test(EditStudent::class, ['record' => $student->getRouteKey()])
        ->fillForm(['user_email' => 'taken@example.com'])
        ->call('save')
        ->assertHasFormErrors(['user_email']);
});

it('hides the password field on the user edit page', function () {
    $user = User::factory()->withRole('secretary')->create();

    $this->actingAs(adminUser());

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->assertFormFieldDoesNotExist('password');
});

it('still requires a password when creating a user', function () {
    $this->actingAs(adminUser());

    Livewire::test(CreateUser::class)
        ->assertFormFieldExists('password')
        ->fillForm([
            'name' => 'مستخدم جديد',
            'email' => 'created@example.com',
            'role' => 'secretary',
        ])
        ->call('create')
        ->assertHasFormErrors(['password']);
});
