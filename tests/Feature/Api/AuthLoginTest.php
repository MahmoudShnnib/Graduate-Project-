<?php

use Illuminate\Support\Facades\Hash;

it('issues a sanctum token on valid credentials', function () {
    ['user' => $user] = studentWithRecord(['enrollment_no' => '26CS101']);
    $user->update(['password' => Hash::make('password'), 'is_active' => true, 'role' => 'student']);

    $response = $this->postJson('/api/v1/auth/login', [
        'enrollment_no' => '26CS101',
        'password' => 'password',
        'device_name' => 'pest-test',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'token',
            'student' => ['id', 'enrollment_no', 'name', 'gpa', 'status' => ['value', 'label']],
        ]);

    expect($user->fresh()->tokens()->count())->toBe(1);
});

it('rejects invalid password', function () {
    ['user' => $user] = studentWithRecord(['enrollment_no' => '26CS101']);
    $user->update(['password' => Hash::make('password')]);

    $this->postJson('/api/v1/auth/login', [
        'enrollment_no' => '26CS101',
        'password' => 'wrong',
    ])->assertStatus(422);
});

it('rejects unknown enrollment number', function () {
    $this->postJson('/api/v1/auth/login', [
        'enrollment_no' => 'NOPE',
        'password' => 'password',
    ])->assertStatus(422);
});

it('rejects inactive user', function () {
    ['user' => $user] = studentWithRecord(['enrollment_no' => '26CS101']);
    $user->update(['password' => Hash::make('password'), 'is_active' => false]);

    $this->postJson('/api/v1/auth/login', [
        'enrollment_no' => '26CS101',
        'password' => 'password',
    ])->assertStatus(422);
});

it('logs out and revokes the current token', function () {
    ['user' => $user] = studentWithRecord(['enrollment_no' => '26CS101']);
    $user->update(['password' => Hash::make('password')]);

    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    expect($user->fresh()->tokens()->count())->toBe(0);
});
