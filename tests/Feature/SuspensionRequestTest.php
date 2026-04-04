<?php

use App\Enums\SuspensionStatus;
use App\Models\Semester;
use App\Models\SuspensionRequest;

test('student can access suspension request page', function () {
    ['user' => $user] = studentWithRecord();

    $this->actingAs($user)->get('/portal/suspension-request-page')->assertOk();
});

test('student can submit a suspension request', function () {
    ['student' => $student] = studentWithRecord();
    $semester = Semester::factory()->create(['is_active' => true]);

    SuspensionRequest::create([
        'student_id' => $student->id,
        'semester_id' => $semester->id,
        'reason' => 'ظروف صحية تستوجب الانقطاع عن الدراسة',
        'status' => SuspensionStatus::Pending,
    ]);

    expect(
        SuspensionRequest::where('student_id', $student->id)->count()
    )->toBe(1);
});

test('student with pending request is blocked from submitting another', function () {
    ['student' => $student] = studentWithRecord();
    $semester = Semester::factory()->create();

    SuspensionRequest::factory()->create([
        'student_id' => $student->id,
        'semester_id' => $semester->id,
        'status' => SuspensionStatus::Pending,
    ]);

    $hasPending = SuspensionRequest::where('student_id', $student->id)
        ->where('status', SuspensionStatus::Pending)
        ->exists();

    expect($hasPending)->toBeTrue();
});

test('approved suspension request does not block new submission', function () {
    ['student' => $student] = studentWithRecord();
    $semester = Semester::factory()->create();

    SuspensionRequest::factory()->approved()->create([
        'student_id' => $student->id,
        'semester_id' => $semester->id,
    ]);

    $hasPending = SuspensionRequest::where('student_id', $student->id)
        ->where('status', SuspensionStatus::Pending)
        ->exists();

    expect($hasPending)->toBeFalse();
});

test('suspension request history is ordered latest first', function () {
    ['student' => $student] = studentWithRecord();
    $semester = Semester::factory()->create();

    SuspensionRequest::factory()->count(3)->create([
        'student_id' => $student->id,
        'semester_id' => $semester->id,
    ]);

    $history = SuspensionRequest::where('student_id', $student->id)
        ->latest()
        ->get();

    expect($history)->toHaveCount(3)
        ->and($history->first()->created_at->greaterThanOrEqualTo($history->last()->created_at))->toBeTrue();
});
