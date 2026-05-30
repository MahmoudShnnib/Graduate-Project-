<?php

use App\Enums\SuspensionStatus;
use App\Filament\Student\Pages\SuspensionRequestPage;
use App\Models\Semester;
use App\Models\SuspensionRequest;
use Livewire\Livewire;

beforeEach(function () {
    ['user' => $this->user, 'student' => $this->student] = studentWithRecord();
    $this->semester = Semester::factory()->create(['is_active' => true]);
    $this->actingAs($this->user);
});

it('blocks new suspension request when already approved for the same semester', function () {
    SuspensionRequest::create([
        'student_id' => $this->student->id,
        'semester_id' => $this->semester->id,
        'reason' => 'reason for approved suspension',
        'status' => SuspensionStatus::Approved,
    ]);

    Livewire::test(SuspensionRequestPage::class)
        ->set('reason', 'another very long reason for the second request')
        ->call('submit');

    expect(SuspensionRequest::where('student_id', $this->student->id)->count())
        ->toBe(1);
});

it('blocks any new suspension request once one has been approved', function () {
    // Per 2026-05-08 blocking rules: any approved suspension permanently
    // blocks future requests. Per the 2026-05-16 M-15 scope extension, the
    // student can no longer pick another semester — the form always targets
    // the active semester, so the only path is "approved → no resubmit".
    SuspensionRequest::create([
        'student_id' => $this->student->id,
        'semester_id' => $this->semester->id,
        'reason' => 'reason for approved suspension',
        'status' => SuspensionStatus::Approved,
    ]);

    Livewire::test(SuspensionRequestPage::class)
        ->set('reason', 'reason for the new attempt')
        ->call('submit');

    expect(SuspensionRequest::where('student_id', $this->student->id)->count())
        ->toBe(1);
});

it('blocks re-submission for the active semester after a rejection', function () {
    // Per 2026-05-08 blocking rules: rejection blocks that ONE semester.
    // Per the 2026-05-16 M-15 scope extension, the form is always scoped to
    // the active semester, so a rejection on the active semester is a hard
    // block (no semester switch is possible from the student side).
    SuspensionRequest::create([
        'student_id' => $this->student->id,
        'semester_id' => $this->semester->id,
        'reason' => 'reason for previously rejected suspension',
        'status' => SuspensionStatus::Rejected,
    ]);

    Livewire::test(SuspensionRequestPage::class)
        ->set('reason', 'reason for the second attempt after rejection')
        ->call('submit');

    expect(SuspensionRequest::where('student_id', $this->student->id)
        ->where('semester_id', $this->semester->id)
        ->where('status', SuspensionStatus::Pending)
        ->exists())->toBeFalse();
});

it('hasAnyApprovedRequest and isCurrentSemesterBlocked report correctly', function () {
    SuspensionRequest::create([
        'student_id' => $this->student->id,
        'semester_id' => $this->semester->id,
        'reason' => 'reason for approved suspension',
        'status' => SuspensionStatus::Approved,
    ]);

    Livewire::test(SuspensionRequestPage::class)
        ->tap(function ($page): void {
            $instance = $page->instance();
            expect($instance->hasAnyApprovedRequest())->toBeTrue()
                ->and($instance->isCurrentSemesterBlocked())->toBeTrue();
        });
});
