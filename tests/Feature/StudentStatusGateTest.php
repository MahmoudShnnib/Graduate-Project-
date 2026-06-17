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

it('returns false from canPerformActions for non-active students', function (StudentStatus $status) {
    $this->student->update(['status' => $status]);

    expect($this->student->fresh()->canPerformActions())->toBeFalse();
})->with([
    'on_hold' => StudentStatus::OnHold,
    'inactive' => StudentStatus::Inactive,
    'transferred' => StudentStatus::Transferred,
    'graduated' => StudentStatus::Graduated,
    'dismissed' => StudentStatus::Dismissed,
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
    'on_hold' => StudentStatus::OnHold,
    'inactive' => StudentStatus::Inactive,
    'transferred' => StudentStatus::Transferred,
    'graduated' => StudentStatus::Graduated,
    'dismissed' => StudentStatus::Dismissed,
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
    'on_hold' => StudentStatus::OnHold,
    'inactive' => StudentStatus::Inactive,
    'transferred' => StudentStatus::Transferred,
    'graduated' => StudentStatus::Graduated,
    'dismissed' => StudentStatus::Dismissed,
]);

it('keeps approved grades visible on GradesPage after a non-suspension cascade', function () {
    // A graduation cascade withdraws all enrollments, but the GPA stays frozen
    // WITH them, so the transcript must remain visible. (A suspension/inactive
    // cascade intentionally HIDES the flushed current term — that is covered in
    // SuspensionFlushesCurrentSemesterTest.)
    $enroll = app(EnrollStudentInOffering::class);
    $enrollment = $enroll($this->student, $this->offering);

    Grade::create([
        'enrollment_id' => $enrollment->id,
        'coursework_grade' => 35,
        'final_grade' => 50,
        'status' => GradeStatus::Approved,
        'published_at' => now(),
    ]);

    $this->student->update(['status' => StudentStatus::Graduated]);

    $this->actingAs($this->student->fresh()->user);

    $grades = (new App\Filament\Student\Pages\GradesPage)->tap(function ($page) {
        $page->selectedSemesterId = $this->semester->id;
    })->getGrades();

    expect($grades)->toHaveCount(1)
        ->and($grades->first()->grade->status)->toBe(GradeStatus::Approved);
});

it('keeps the GPA frozen for terminal non-active statuses', function (StudentStatus $status) {
    $enroll = app(EnrollStudentInOffering::class);
    $enrollment = $enroll($this->student, $this->offering);

    $this->student->update(['gpa' => 2.75]);
    $this->student->refresh();
    $this->student->update(['status' => $status]);

    Grade::create([
        'enrollment_id' => $enrollment->id,
        'coursework_grade' => 38,
        'final_grade' => 52,
        'status' => GradeStatus::Approved,
        'published_at' => now(),
    ]);

    $this->student->refresh();

    expect((float) $this->student->gpa)->toBe(2.75);
})->with([
    // Terminal states preserve the final GPA. OnHold/Inactive intentionally
    // recalc on suspension (current semester flushed) — covered separately
    // below and in SuspensionFlushesCurrentSemesterTest.
    'transferred' => StudentStatus::Transferred,
    'graduated' => StudentStatus::Graduated,
    'dismissed' => StudentStatus::Dismissed,
]);

it('flushes the current semester and recalculates the GPA on suspension/inactive', function (StudentStatus $status) {
    $enroll = app(EnrollStudentInOffering::class);
    $enrollment = $enroll($this->student, $this->offering);

    Grade::create([
        'enrollment_id' => $enrollment->id,
        'coursework_grade' => 30,
        'final_grade' => 30, // 60 → C → 2.0
        'status' => GradeStatus::Approved,
        'published_at' => now(),
    ]);

    $this->student->refresh();
    expect((float) $this->student->gpa)->toBe(2.0); // counts while Active

    $this->student->update(['status' => $status]);

    // Current-semester course flushed; no prior work → GPA 0.00.
    expect($enrollment->fresh()->status)->toBe(EnrollmentStatus::Withdrawn)
        ->and((float) $this->student->fresh()->gpa)->toBe(0.0);
})->with([
    'on_hold' => StudentStatus::OnHold,
    'inactive' => StudentStatus::Inactive,
]);

it('graduated students can still authenticate and access the portal', function () {
    $this->student->update(['status' => StudentStatus::Graduated]);
    $this->student->user->update(['is_active' => true]);

    $this->actingAs($this->student->fresh()->user);

    expect($this->student->fresh()->user->canAccessPanel(
        \Filament\Facades\Filament::getPanel('student')
    ))->toBeTrue();
});

it('returns correct display labels and colors for each status', function (StudentStatus $status, string $expectedLabel, string $expectedColor) {
    $this->student->update(['status' => $status]);

    $student = $this->student->fresh();

    expect($student->getDisplayStatusLabel())->toBe($expectedLabel)
        ->and($student->getDisplayStatusColor())->toBe($expectedColor);
})->with([
    'active' => [StudentStatus::Active, 'مستمر', 'success'],
    'on_hold' => [StudentStatus::OnHold, 'قيده موقوف', 'danger'],
    'inactive' => [StudentStatus::Inactive, 'منقطع', 'warning'],
    'transferred' => [StudentStatus::Transferred, 'منتقل', 'info'],
    'graduated' => [StudentStatus::Graduated, 'متخرج', 'info'],
    'dismissed' => [StudentStatus::Dismissed, 'مفصول', 'danger'],
]);

it('changeStatus action with target=Active rescinds an approved semester suspension', function () {
    \App\Models\SuspensionRequest::create([
        'student_id' => $this->student->id,
        'semester_id' => $this->semester->id,
        'reason' => 'student-filed suspension',
        'status' => \App\Enums\SuspensionStatus::Approved,
    ]);

    $this->student->update(['status' => StudentStatus::OnHold]);

    expect($this->student->fresh()->canPerformActions())->toBeFalse();

    $admin = adminUser();
    $this->actingAs($admin);

    Livewire\Livewire::test(
        \App\Filament\Resources\Students\Pages\EditStudent::class,
        ['record' => $this->student->getRouteKey()],
    )
        ->callAction('changeStatus', data: ['status' => StudentStatus::Active->value]);

    expect($this->student->fresh()->status)->toBe(StudentStatus::Active)
        ->and($this->student->fresh()->canPerformActions())->toBeTrue()
        ->and(\App\Models\SuspensionRequest::where('student_id', $this->student->id)
            ->where('semester_id', $this->semester->id)
            ->where('status', \App\Enums\SuspensionStatus::Rejected)
            ->exists())->toBeTrue();
});

it('changeStatus action with target=Transferred does NOT rescind the suspension', function () {
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
        ->callAction('changeStatus', data: ['status' => StudentStatus::Transferred->value]);

    expect($this->student->fresh()->status)->toBe(StudentStatus::Transferred)
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
    'on_hold' => [StudentStatus::OnHold, 'إيقاف قيد الطالب'],
    'inactive' => [StudentStatus::Inactive, 'تسجيل الطالب كمنقطع'],
    'transferred' => [StudentStatus::Transferred, 'تسجيل انتقال الطالب'],
    'graduated' => [StudentStatus::Graduated, 'تسجيل تخرج الطالب'],
    'dismissed' => [StudentStatus::Dismissed, 'فصل الطالب'],
    'active' => [StudentStatus::Active, '"مستمر"'],
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
