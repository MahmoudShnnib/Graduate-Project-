<?php

use App\Enums\DiscussionStatus;
use App\Enums\ThesisStatus;
use App\Models\DiscussionRequest;
use App\Models\ThesisRegistration;

function clearanceReadyStudent(): array
{
    ['user' => $user, 'student' => $student] = studentWithRecord(['enrollment_no' => 'C-2026-0001']);

    $thesis = ThesisRegistration::factory()->approved()->create(['student_id' => $student->id]);

    DiscussionRequest::create([
        'student_id' => $student->id,
        'thesis_registration_id' => $thesis->id,
        'status' => DiscussionStatus::Completed,
        'completed_at' => now(),
    ]);

    return compact('user', 'student');
}

it('generates a clearance PDF for the owning student once the discussion is completed', function () {
    ['user' => $user, 'student' => $student] = clearanceReadyStudent();

    $response = $this->actingAs($user)->get(route('print.clearance-form', $student));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

it('forbids the student from printing clearance before the discussion is completed', function () {
    ['user' => $user, 'student' => $student] = studentWithRecord();

    ThesisRegistration::factory()->create([
        'student_id' => $student->id,
        'status' => ThesisStatus::Approved,
    ]);

    $this->actingAs($user)
        ->get(route('print.clearance-form', $student))
        ->assertForbidden();
});

it('forbids a different student from printing someone else clearance', function () {
    ['student' => $student] = clearanceReadyStudent();
    ['user' => $otherUser] = studentWithRecord();

    $this->actingAs($otherUser)
        ->get(route('print.clearance-form', $student))
        ->assertForbidden();
});
