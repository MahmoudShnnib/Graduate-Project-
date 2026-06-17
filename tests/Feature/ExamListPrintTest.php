<?php

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Semester;
use App\Models\Specialization;

beforeEach(function () {
    $this->actingAs(adminUser());
    $this->semester = Semester::factory()->active()->create();
    $this->specA = Specialization::factory()->create();
    $this->specB = Specialization::factory()->create();

    // Pending exam-list candidate (initially approved, not yet processed).
    Application::factory()->create([
        'specialization_id' => $this->specA->id,
        'semester_id' => $this->semester->id,
        'status' => ApplicationStatus::InitiallyApproved,
    ]);

    // Processed results — one accepted, one rejected, each with a score.
    Application::factory()->create([
        'specialization_id' => $this->specA->id,
        'semester_id' => $this->semester->id,
        'status' => ApplicationStatus::Accepted,
        'exam_score' => 85,
        'exam_passed' => true,
        'exam_result_date' => now(),
    ]);
    Application::factory()->create([
        'specialization_id' => $this->specB->id,
        'semester_id' => $this->semester->id,
        'status' => ApplicationStatus::Rejected,
        'exam_score' => 40,
        'exam_passed' => false,
        'exam_result_date' => now(),
    ]);
});

it('prints the exam call list for all specializations', function () {
    $response = $this->get(route('print.exam-list', ['semester' => $this->semester->id]));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

it('prints the exam call list filtered to one specialization', function () {
    $this->get(route('print.exam-list', ['specialization' => $this->specA->id, 'semester' => $this->semester->id]))
        ->assertOk();
});

it('prints the exam results for all specializations', function () {
    $response = $this->get(route('print.exam-results', ['semester' => $this->semester->id]));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

it('prints the exam results filtered to one specialization', function () {
    $this->get(route('print.exam-results', ['specialization' => $this->specB->id, 'semester' => $this->semester->id]))
        ->assertOk();
});

it('forbids non-admins from the exam prints', function () {
    $this->actingAs(facultyUser());

    $this->get(route('print.exam-list'))->assertForbidden();
    $this->get(route('print.exam-results'))->assertForbidden();
});
