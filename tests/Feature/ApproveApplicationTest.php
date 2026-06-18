<?php

use App\Actions\ApproveApplication;
use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Semester;
use App\Models\Specialization;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Notification::fake();
    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
});

it('creates a student with a generated enrollment number on approval', function () {
    $spec = Specialization::factory()->create(['code' => 42]);
    Semester::factory()->active()->create(['year' => 2026, 'season' => \App\Enums\Season::Autumn]);
    $application = Application::factory()->create([
        'specialization_id' => $spec->id,
        'status' => ApplicationStatus::Pending,
        'is_transfer' => false,
    ]);

    app(ApproveApplication::class)->handle($application);

    $student = Student::firstWhere('specialization_id', $spec->id);

    expect($application->fresh()->status)->toBe(ApplicationStatus::Accepted)
        ->and($student)->not->toBeNull()
        ->and($student->enrollment_no)->not->toBeNull()
        ->and($student->enrollment_no)->toStartWith('26421'); // YY=26, spec=42, autumn=1
});

it('propagates the application telegram chat id to the new user on approval', function () {
    $spec = Specialization::factory()->create(['code' => 9]);
    Semester::factory()->active()->create(['year' => 2026, 'season' => \App\Enums\Season::Autumn]);
    $application = Application::factory()->create([
        'specialization_id' => $spec->id,
        'status' => ApplicationStatus::Pending,
        'is_transfer' => false,
        'telegram_chat_id' => '556677',
    ]);

    app(ApproveApplication::class)->handle($application);

    $user = User::firstWhere('email', $application->email);

    expect($user->telegram_chat_id)->toBe('556677')
        ->and($user->telegram_linked_at)->not->toBeNull();
});

it('refuses approval when no semester is active (no NULL enrollment number)', function () {
    $spec = Specialization::factory()->create(['code' => 7]);
    $application = Application::factory()->create([
        'specialization_id' => $spec->id,
        'status' => ApplicationStatus::Pending,
    ]);

    expect(fn () => app(ApproveApplication::class)->handle($application))
        ->toThrow(Exception::class, 'لا يوجد فصل دراسي نشط');

    expect($application->fresh()->status)->toBe(ApplicationStatus::Pending)
        ->and(User::where('email', $application->email)->exists())->toBeFalse()
        ->and(Student::count())->toBe(0);
});
