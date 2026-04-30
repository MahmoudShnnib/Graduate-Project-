<?php

use App\Enums\ExceptionalRequestStatus;
use App\Enums\GradeStatus;
use App\Enums\Season;
use App\Enums\StudentStatus;
use App\Enums\WarningType;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\ExceptionalRequest;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\StudentWarning;

beforeEach(function () {
    $this->semester = Semester::factory()->create([
        'is_active' => true,
        'season' => Season::Autumn,
        'year' => 2025,
        'survey_open' => false,
    ]);

    ['student' => $this->student, 'user' => $this->user] = studentWithRecord(['status' => StudentStatus::Active]);

    $this->course = Course::factory()->create();
    $this->course->specializations()->attach($this->student->specialization_id, ['type' => 'general']);

    $this->offering = CourseOffering::factory()->create([
        'course_id' => $this->course->id,
        'semester_id' => $this->semester->id,
        'is_open' => true,
    ]);
});

it('allows a dismissed student to submit an exceptional request', function () {
    $this->student->update([
        'status' => StudentStatus::Dismissed,
        'dismissed_at' => now(),
        'dismissal_type' => WarningType::CourseFailure,
        'dismissal_course_id' => $this->course->id,
    ]);

    $this->actingAs($this->user);

    Livewire\Livewire::test(\App\Filament\Student\Pages\ExceptionalRequestPage::class)
        ->set('reason', 'أرجو منحي فرصة استثنائية لأنني كنت مريضاً')
        ->call('submit');

    expect(ExceptionalRequest::where('student_id', $this->student->id)->count())->toBe(1)
        ->and(ExceptionalRequest::first()->status)->toBe(ExceptionalRequestStatus::Pending);
});

it('accepts an exceptional request and reactivates the student', function () {
    $this->student->update([
        'status' => StudentStatus::Dismissed,
        'dismissed_at' => now(),
        'dismissal_type' => WarningType::CourseFailure,
        'dismissal_course_id' => $this->course->id,
    ]);

    $request = ExceptionalRequest::create([
        'student_id' => $this->student->id,
        'reason' => 'أرجو منحي فرصة',
    ]);

    $admin = adminUser();
    $this->actingAs($admin);

    Livewire\Livewire::test(\App\Filament\Pages\ViewExceptionalStudent::class, ['student' => $this->student->id])
        ->callAction('accept');

    $student = $this->student->fresh();
    expect($student->status)->toBe(StudentStatus::Active)
        ->and($student->dismissed_at)->toBeNull()
        ->and($student->dismissal_type)->toBeNull();

    expect($request->fresh()->status)->toBe(ExceptionalRequestStatus::Accepted);

    $warning = StudentWarning::where('student_id', $this->student->id)
        ->where('has_exceptional_chance', true)
        ->first();
    expect($warning)->not->toBeNull()
        ->and($warning->course_id)->toBe($this->course->id);
});

it('rejects an exceptional request and student stays dismissed', function () {
    $this->student->update([
        'status' => StudentStatus::Dismissed,
        'dismissed_at' => now(),
        'dismissal_type' => WarningType::CourseFailure,
        'dismissal_course_id' => $this->course->id,
    ]);

    $request = ExceptionalRequest::create([
        'student_id' => $this->student->id,
        'reason' => 'أرجو منحي فرصة',
    ]);

    $admin = adminUser();
    $this->actingAs($admin);

    Livewire\Livewire::test(\App\Filament\Pages\ViewExceptionalStudent::class, ['student' => $this->student->id])
        ->callAction('reject');

    expect($this->student->fresh()->status)->toBe(StudentStatus::Dismissed)
        ->and($request->fresh()->status)->toBe(ExceptionalRequestStatus::Rejected);
});

it('sends a summon message that shows on student dashboard', function () {
    $this->student->update([
        'status' => StudentStatus::Dismissed,
        'dismissed_at' => now(),
        'dismissal_type' => WarningType::CourseFailure,
        'dismissal_course_id' => $this->course->id,
    ]);

    $request = ExceptionalRequest::create([
        'student_id' => $this->student->id,
        'reason' => 'أرجو منحي فرصة',
    ]);

    $admin = adminUser();
    $this->actingAs($admin);

    Livewire\Livewire::test(\App\Filament\Pages\ViewExceptionalStudent::class, ['student' => $this->student->id])
        ->callAction('summon', data: ['summon_message' => 'يرجى الحضور للإدارة يوم الأحد']);

    $request->refresh();
    expect($request->status)->toBe(ExceptionalRequestStatus::Summoned)
        ->and($request->summon_message)->toBe('يرجى الحضور للإدارة يوم الأحد')
        ->and($request->summon_sent_at)->not->toBeNull();

    $this->actingAs($this->user);

    $dashboard = new \App\Filament\Student\Pages\StudentDashboard;
    $summon = $dashboard->getActiveSummon();
    expect($summon)->not->toBeNull()
        ->and($summon->summon_message)->toBe('يرجى الحضور للإدارة يوم الأحد');
});

it('bans reapply when student fails course again after exceptional acceptance', function () {
    $enroll = app(\App\Actions\Enrollment\EnrollStudentInOffering::class);

    $enrollment = $enroll($this->student, $this->offering);
    Grade::create([
        'enrollment_id' => $enrollment->id,
        'coursework_grade' => 10,
        'final_grade' => 15,
        'status' => GradeStatus::Approved,
    ]);

    $semester2 = Semester::factory()->create(['season' => Season::Spring, 'year' => 2026]);
    $offering2 = CourseOffering::factory()->create([
        'course_id' => $this->course->id,
        'semester_id' => $semester2->id,
        'is_open' => true,
    ]);

    $this->student->refresh();
    $enrollment2 = $enroll($this->student, $offering2);
    Grade::create([
        'enrollment_id' => $enrollment2->id,
        'coursework_grade' => 10,
        'final_grade' => 10,
        'status' => GradeStatus::Approved,
    ]);

    expect($this->student->fresh()->status)->toBe(StudentStatus::Dismissed);

    $request = ExceptionalRequest::create([
        'student_id' => $this->student->id,
        'reason' => 'فرصة استثنائية',
    ]);

    $admin = adminUser();
    $this->actingAs($admin);

    Livewire\Livewire::test(\App\Filament\Pages\ViewExceptionalStudent::class, ['student' => $this->student->id])
        ->callAction('accept');

    $student = $this->student->fresh();
    expect($student->status)->toBe(StudentStatus::Active);

    $semester3 = Semester::factory()->create(['season' => Season::Autumn, 'year' => 2026]);
    $offering3 = CourseOffering::factory()->create([
        'course_id' => $this->course->id,
        'semester_id' => $semester3->id,
        'is_open' => true,
    ]);

    $student->refresh();
    $enrollment3 = $enroll($student, $offering3);
    Grade::create([
        'enrollment_id' => $enrollment3->id,
        'coursework_grade' => 10,
        'final_grade' => 5,
        'status' => GradeStatus::Approved,
    ]);

    $student->refresh();
    expect($student->status)->toBe(StudentStatus::Dismissed);

    $latestRequest = ExceptionalRequest::where('student_id', $student->id)
        ->where('status', ExceptionalRequestStatus::Accepted)
        ->latest()
        ->first();
    expect($latestRequest->can_reapply)->toBeFalse();
});

it('accepts inactive dismissal and gives fresh start with zero warnings', function () {
    $this->student->update(['status' => StudentStatus::Inactive]);

    $semester2 = Semester::factory()->create([
        'is_active' => false,
        'season' => Season::Spring,
        'year' => 2026,
    ]);
    $semester2->update(['is_active' => true]);
    $this->semester->update(['is_active' => false]);

    expect($this->student->fresh()->status)->toBe(StudentStatus::Dismissed);

    $request = ExceptionalRequest::create([
        'student_id' => $this->student->id,
        'reason' => 'فرصة استثنائية بعد انقطاع',
    ]);

    $admin = adminUser();
    $this->actingAs($admin);

    Livewire\Livewire::test(\App\Filament\Pages\ViewExceptionalStudent::class, ['student' => $this->student->id])
        ->callAction('accept');

    $student = $this->student->fresh();
    expect($student->status)->toBe(StudentStatus::Active)
        ->and($student->warnings_count)->toBe(0)
        ->and(StudentWarning::where('student_id', $student->id)->where('is_active', true)->count())->toBe(0);
});
