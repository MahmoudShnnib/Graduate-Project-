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

it('allows new suspension request for a different semester when another is approved', function () {
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

    expect(SuspensionRequest::where('student_id', $this->student->id)->where('semester_id', $otherSemester->id)->count())
        ->toBeOne();
});

it('allows re-submission after a prior rejection for the same semester', function () {
    SuspensionRequest::create([
        'student_id' => $this->student->id,
        'semester_id' => $this->semester->id,
        'reason' => 'reason for previously rejected suspension',
        'status' => SuspensionStatus::Rejected,
    ]);

    Livewire::test(SuspensionRequestPage::class)
        ->set('semesterId', $this->semester->id)
        ->set('reason', 'reason for the second attempt after rejection')
        ->call('submit');

    expect(SuspensionRequest::where('student_id', $this->student->id)
        ->where('status', SuspensionStatus::Pending)
        ->exists())->toBeTrue();
});

it('isSelectedSemesterAlreadyApproved returns true for approved semester', function () {
    SuspensionRequest::create([
        'student_id' => $this->student->id,
        'semester_id' => $this->semester->id,
        'reason' => 'reason for approved suspension',
        'status' => SuspensionStatus::Approved,
    ]);

    Livewire::test(SuspensionRequestPage::class)
        ->set('semesterId', $this->semester->id)
        ->tap(fn ($page) => expect($page->instance()->isSelectedSemesterAlreadyApproved())->toBeTrue());
});
