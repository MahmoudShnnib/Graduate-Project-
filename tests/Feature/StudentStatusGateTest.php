<?php

use App\Actions\Enrollment\EnrollStudentInOffering;
use App\Enums\EnrollmentStatus;
use App\Enums\GradeStatus;
use App\Enums\Season;
use App\Enums\StudentStatus;
use App\Exceptions\StudentNotActiveException;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\StudentEnrollment;

beforeEach(function () {
    $this->semester = Semester::factory()->create([
        'is_active' => true,
        'season' => Season::Autumn,
        'year' => 2025,
        'survey_open' => false,
    ]);

    ['student' => $this->student] = studentWithRecord(['status' => StudentStatus::Active]);

    $this->course = Course::factory()->create();
    $this->course->specializations()->attach($this->student->specialization_id, ['type' => 'general']);

    $this->offering = CourseOffering::factory()->create([
        'course_id' => $this->course->id,
        'semester_id' => $this->semester->id,
        'is_open' => true,
    ]);
});

it('returns true from canPerformActions for an active student', function () {
    expect($this->student->canPerformActions())->toBeTrue()
        ->and($this->student->canPerformActions($this->semester))->toBeTrue();
});

it('returns false from canPerformActions for inactive / withdrawn / graduated students', function (StudentStatus $status) {
    $this->student->update(['status' => $status]);

    expect($this->student->fresh()->canPerformActions())->toBeFalse();
})->with([
    'inactive' => StudentStatus::Inactive,
    'withdrawn' => StudentStatus::Withdrawn,
    'graduated' => StudentStatus::Graduated,
]);

it('throws StudentNotActiveException from EnrollStudentInOffering when student is not active', function (StudentStatus $status) {
    $this->student->update(['status' => $status]);

    $enroll = app(EnrollStudentInOffering::class);

    expect(fn () => $enroll($this->student->fresh(), $this->offering))
        ->toThrow(StudentNotActiveException::class);

    expect(StudentEnrollment::where('student_id', $this->student->id)
        ->where('course_offering_id', $this->offering->id)
        ->active()
        ->exists())->toBeFalse();
})->with([
    'inactive' => StudentStatus::Inactive,
    'withdrawn' => StudentStatus::Withdrawn,
    'graduated' => StudentStatus::Graduated,
]);

it('cascades active enrollments to Withdrawn when status flips from Active', function (StudentStatus $status) {
    $enroll = app(EnrollStudentInOffering::class);
    $enroll($this->student, $this->offering);

    expect(StudentEnrollment::where('student_id', $this->student->id)->active()->count())->toBe(1);

    $this->student->update(['status' => $status]);

    expect(StudentEnrollment::where('student_id', $this->student->id)->active()->count())->toBe(0)
        ->and(StudentEnrollment::where('student_id', $this->student->id)->first()->status)
        ->toBe(EnrollmentStatus::Withdrawn);
})->with([
    'inactive' => StudentStatus::Inactive,
    'withdrawn' => StudentStatus::Withdrawn,
    'graduated' => StudentStatus::Graduated,
]);

it('keeps approved grades visible on GradesPage after cascade', function () {
    $enroll = app(EnrollStudentInOffering::class);
    $enrollment = $enroll($this->student, $this->offering);

    Grade::create([
        'enrollment_id' => $enrollment->id,
        'coursework_grade' => 35,
        'final_grade' => 50,
        'status' => GradeStatus::Approved,
    ]);

    $this->student->update(['status' => StudentStatus::Inactive]);

    $this->actingAs($this->student->fresh()->user);

    $grades = (new App\Filament\Student\Pages\GradesPage)->tap(function ($page) {
        $page->selectedSemesterId = $this->semester->id;
    })->getGrades();

    expect($grades)->toHaveCount(1)
        ->and($grades->first()->grade->status)->toBe(GradeStatus::Approved);
});

it('GPA is not recalculated for non-active students', function (StudentStatus $status) {
    $enroll = app(EnrollStudentInOffering::class);
    $enrollment = $enroll($this->student, $this->offering);

    // Freeze a known GPA before flipping status.
    $this->student->update(['gpa' => 2.75]);
    $this->student->refresh();
    $this->student->update(['status' => $status]);

    // Approve a grade — the observer fires GradeCalculationService::recalculate,
    // which must early-return for non-active students so the GPA stays frozen.
    Grade::create([
        'enrollment_id' => $enrollment->id,
        'coursework_grade' => 38,
        'final_grade' => 52,
        'status' => GradeStatus::Approved,
    ]);

    $this->student->refresh();

    expect((float) $this->student->gpa)->toBe(2.75);
})->with([
    'inactive' => StudentStatus::Inactive,
    'withdrawn' => StudentStatus::Withdrawn,
    'graduated' => StudentStatus::Graduated,
]);

it('graduated students can still authenticate and access the portal', function () {
    $this->student->update(['status' => StudentStatus::Graduated]);
    $this->student->user->update(['is_active' => true]);

    $this->actingAs($this->student->fresh()->user);

    expect($this->student->fresh()->user->canAccessPanel(
        \Filament\Facades\Filament::getPanel('student')
    ))->toBeTrue();
});

it('displays "القيد موقوف مؤقتًا" for an active student with an approved semester suspension', function () {
    \App\Models\SuspensionRequest::create([
        'student_id' => $this->student->id,
        'semester_id' => $this->semester->id,
        'reason' => 'semester-scoped suspension',
        'status' => \App\Enums\SuspensionStatus::Approved,
    ]);

    $student = $this->student->fresh();

    expect($student->status)->toBe(StudentStatus::Active)
        ->and($student->getDisplayStatusLabel())->toBe('القيد موقوف مؤقتًا')
        ->and($student->getDisplayStatusColor())->toBe('danger');
});

it('falls back to the global status label when no semester suspension exists', function () {
    expect($this->student->getDisplayStatusLabel())->toBe('نشط')
        ->and($this->student->getDisplayStatusColor())->toBe('success');

    $this->student->update(['status' => StudentStatus::Inactive]);

    expect($this->student->fresh()->getDisplayStatusLabel())->toBe('القيد موقوف مؤقتًا')
        ->and($this->student->fresh()->getDisplayStatusColor())->toBe('danger');
});

it('global Withdrawn overrides an approved semester suspension in display', function () {
    \App\Models\SuspensionRequest::create([
        'student_id' => $this->student->id,
        'semester_id' => $this->semester->id,
        'reason' => 'prior suspension',
        'status' => \App\Enums\SuspensionStatus::Approved,
    ]);

    $this->student->update(['status' => StudentStatus::Withdrawn]);

    expect($this->student->fresh()->getDisplayStatusLabel())->toBe('منسحب')
        ->and($this->student->fresh()->getDisplayStatusColor())->toBe('danger');
});

it('global Graduated overrides an approved semester suspension in display', function () {
    \App\Models\SuspensionRequest::create([
        'student_id' => $this->student->id,
        'semester_id' => $this->semester->id,
        'reason' => 'prior suspension',
        'status' => \App\Enums\SuspensionStatus::Approved,
    ]);

    $this->student->update(['status' => StudentStatus::Graduated]);

    expect($this->student->fresh()->getDisplayStatusLabel())->toBe('متخرج')
        ->and($this->student->fresh()->getDisplayStatusColor())->toBe('info');
});

it('changeStatus action with target=Active rescinds an approved semester suspension', function () {
    \App\Models\SuspensionRequest::create([
        'student_id' => $this->student->id,
        'semester_id' => $this->semester->id,
        'reason' => 'student-filed suspension',
        'status' => \App\Enums\SuspensionStatus::Approved,
    ]);

    expect($this->student->fresh()->canPerformActions($this->semester))->toBeFalse();

    $admin = adminUser();
    $this->actingAs($admin);

    // Toggle the student to Inactive first so the action actually moves state.
    $this->student->update(['status' => StudentStatus::Inactive]);

    Livewire\Livewire::test(
        \App\Filament\Resources\Students\Pages\EditStudent::class,
        ['record' => $this->student->getRouteKey()],
    )
        ->callAction('changeStatus', data: ['status' => StudentStatus::Active->value]);

    expect($this->student->fresh()->status)->toBe(StudentStatus::Active)
        ->and($this->student->fresh()->canPerformActions($this->semester))->toBeTrue()
        ->and(\App\Models\SuspensionRequest::where('student_id', $this->student->id)
            ->where('semester_id', $this->semester->id)
            ->where('status', \App\Enums\SuspensionStatus::Rejected)
            ->exists())->toBeTrue();
});

it('changeStatus action with target=Withdrawn does NOT rescind the suspension (admin says withdrawn, not active)', function () {
    \App\Models\SuspensionRequest::create([
        'student_id' => $this->student->id,
        'semester_id' => $this->semester->id,
        'reason' => 'student-filed suspension',
        'status' => \App\Enums\SuspensionStatus::Approved,
    ]);

    $admin = adminUser();
    $this->actingAs($admin);

    Livewire\Livewire::test(
        \App\Filament\Resources\Students\Pages\EditStudent::class,
        ['record' => $this->student->getRouteKey()],
    )
        ->callAction('changeStatus', data: ['status' => StudentStatus::Withdrawn->value]);

    expect($this->student->fresh()->status)->toBe(StudentStatus::Withdrawn)
        ->and(\App\Models\SuspensionRequest::where('student_id', $this->student->id)
            ->where('status', \App\Enums\SuspensionStatus::Approved)
            ->exists())->toBeTrue();
});

it('changeStatus modal shows different consequence warnings per target status', function (StudentStatus $target, string $needle) {
    $admin = adminUser();
    $this->actingAs($admin);

    $page = Livewire\Livewire::test(
        \App\Filament\Resources\Students\Pages\EditStudent::class,
        ['record' => $this->student->getRouteKey()],
    );

    $reflection = new ReflectionMethod($page->instance(), 'getStatusChangeWarning');
    $reflection->setAccessible(true);
    $warning = $reflection->invoke($page->instance(), $target->value);

    expect($warning)->toContain($needle);
})->with([
    'inactive' => [StudentStatus::Inactive, 'إيقاف قيد الطالب مؤقتًا'],
    'withdrawn' => [StudentStatus::Withdrawn, 'سحب قيد الطالب'],
    'graduated' => [StudentStatus::Graduated, 'تسجيل تخرج الطالب'],
    'active' => [StudentStatus::Active, '"نشط"'],
]);

it('changeStatus action no-ops when the same status is submitted', function () {
    $admin = adminUser();
    $this->actingAs($admin);

    Livewire\Livewire::test(
        \App\Filament\Resources\Students\Pages\EditStudent::class,
        ['record' => $this->student->getRouteKey()],
    )
        ->callAction('changeStatus', data: ['status' => StudentStatus::Active->value]);

    expect($this->student->fresh()->status)->toBe(StudentStatus::Active);
});
