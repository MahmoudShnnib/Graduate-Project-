<?php

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Specialization;
use App\Models\User;

it('generates an exam-list PDF for an admin', function () {
    $spec = Specialization::factory()->create();
    Application::factory()
        ->count(2)
        ->sequence(
            ['tracking_code' => 'APP-TEST-0001'],
            ['tracking_code' => 'APP-TEST-0002'],
        )
        ->create([
            'specialization_id' => $spec->id,
            'status' => ApplicationStatus::InitiallyApproved,
        ]);

    $response = $this->actingAs(adminUser())
        ->get(route('print.exam-list', $spec));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

it('forbids non-admins from the exam-list PDF', function () {
    $spec = Specialization::factory()->create();
    $faculty = User::factory()->withRole('faculty')->create();

    $this->actingAs($faculty)
        ->get(route('print.exam-list', $spec))
        ->assertForbidden();
});
