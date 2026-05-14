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
        ->set('semesterId', $this->semester->id)
        ->set('reason', 'another very long reason for the second request')
        ->call('submit');

    expect(SuspensionRequest::where('student_id', $this->student->id)->count())
        ->toBe(1);
});

it('blocks any new suspension request once one has been approved (different semester too)', function () {
    // Per 2026-05-08 blocking rules: any approved suspension permanently
    // blocks future requests regardless of semester.
    $otherSemester = Semester::factory()->create(['is_active' => false]);

    SuspensionRequest::create([
        'student_id' => $this->student->id,
        'semester_id' => $this->semester->id,
        'reason' => 'reason for approved suspension',
        'status' => SuspensionStatus::Approved,
    ]);

    Livewire::test(SuspensionRequestPage::class)
        ->set('semesterId', $otherSemester->id)
        ->set('reason', 'reason for the new request in a different semester')
        ->call('submit');

    expect(SuspensionRequest::where('student_id', $this->student->id)->count())
        ->toBe(1);
});

it('blocks re-submission for the same semester after a rejection, but allows for a different semester', function () {
    // Per 2026-05-08 blocking rules: rejection blocks that ONE semester only.
    $otherSemester = Semester::factory()->create(['is_active' => false]);

    SuspensionRequest::create([
        'student_id' => $this->student->id,
        'semester_id' => $this->semester->id,
        'reason' => 'reason for previously rejected suspension',
        'status' => SuspensionStatus::Rejected,
    ]);

    // Same-semester re-submission is blocked.
    Livewire::test(SuspensionRequestPage::class)
        ->set('semesterId', $this->semester->id)
        ->set('reason', 'reason for the second attempt after rejection')
        ->call('submit');

    expect(SuspensionRequest::where('student_id', $this->student->id)
        ->where('semester_id', $this->semester->id)
        ->where('status', SuspensionStatus::Pending)
        ->exists())->toBeFalse();

    // Different-semester submission is allowed.
    Livewire::test(SuspensionRequestPage::class)
        ->set('semesterId', $otherSemester->id)
        ->set('reason', 'reason for a new attempt in a different semester')
        ->call('submit');

    expect(SuspensionRequest::where('student_id', $this->student->id)
        ->where('semester_id', $otherSemester->id)
        ->where('status', SuspensionStatus::Pending)
        ->exists())->toBeTrue();
});

it('hasAnyApprovedRequest and isSelectedSemesterBlocked report correctly', function () {
    // The old isSelectedSemesterAlreadyApproved() method was retired during the
    // 2026-05-08 redesign. The replacement API is hasAnyApprovedRequest() +
    // getBlockedSemesterIds() + isSelectedSemesterBlocked().
    SuspensionRequest::create([
        'student_id' => $this->student->id,
        'semester_id' => $this->semester->id,
        'reason' => 'reason for approved suspension',
        'status' => SuspensionStatus::Approved,
    ]);

    Livewire::test(SuspensionRequestPage::class)
        ->set('semesterId', $this->semester->id)
        ->tap(function ($page): void {
            $instance = $page->instance();
            expect($instance->hasAnyApprovedRequest())->toBeTrue()
                ->and($instance->isSelectedSemesterBlocked())->toBeTrue()
                ->and($instance->getBlockedSemesterIds())->toContain($this->semester->id);
        });
});
