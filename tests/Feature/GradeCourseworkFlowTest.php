<?php

use App\Enums\EnrollmentStatus;
use App\Enums\GradeStatus;
use App\Filament\Resources\Faculty\Pages\ViewMyCourse;
use App\Filament\Resources\Grades\Pages\ViewCourseGrades;
use App\Models\CourseOffering;
use App\Models\Grade;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Notifications\GradesPublishedNotification;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

function facultyOfferingWithStudent(): array
{
    $faculty = User::factory()->faculty()->create();
    foreach (['ViewAny:CourseOffering', 'View:CourseOffering'] as $perm) {
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
    $faculty->givePermissionTo(['ViewAny:CourseOffering', 'View:CourseOffering']);

    $offering = CourseOffering::factory()->create([
        'faculty_id' => $faculty->id,
        'coursework_max' => 40,
        'final_max' => 60,
    ]);
    ['student' => $student] = studentWithRecord();
    $enrollment = StudentEnrollment::factory()->create([
        'student_id' => $student->id,
        'course_offering_id' => $offering->id,
        'status' => EnrollmentStatus::Enrolled,
    ]);

    return compact('faculty', 'offering', 'student', 'enrollment');
}

it('lets faculty save a coursework-only grade as a draft', function () {
    ['faculty' => $faculty, 'offering' => $offering, 'enrollment' => $enrollment] = facultyOfferingWithStudent();

    Livewire::actingAs($faculty)
        ->test(ViewMyCourse::class, ['record' => $offering])
        ->callAction(TestAction::make('enter_grade')->table($enrollment), data: [
            'coursework_grade' => 35,
            'final_grade' => null,
        ])
        ->assertHasNoActionErrors();

    $grade = Grade::where('enrollment_id', $enrollment->id)->first();

    expect((float) $grade->coursework_grade)->toBe(35.0)
        ->and($grade->final_grade)->toBeNull()
        ->and($grade->total)->toBeNull()
        ->and($grade->status)->toBe(GradeStatus::Draft);
});

it('does not submit a coursework-only draft for approval', function () {
    ['faculty' => $faculty, 'offering' => $offering, 'enrollment' => $enrollment] = facultyOfferingWithStudent();

    Grade::factory()->create([
        'enrollment_id' => $enrollment->id,
        'coursework_grade' => 35,
        'final_grade' => null,
        'status' => GradeStatus::Draft,
    ]);

    Livewire::actingAs($faculty)
        ->test(ViewMyCourse::class, ['record' => $offering])
        ->callAction('submit_grades');

    expect(Grade::where('enrollment_id', $enrollment->id)->first()->status)->toBe(GradeStatus::Draft);
});

it('submits a complete draft (coursework + final) for approval', function () {
    ['faculty' => $faculty, 'offering' => $offering, 'enrollment' => $enrollment] = facultyOfferingWithStudent();

    Grade::factory()->create([
        'enrollment_id' => $enrollment->id,
        'coursework_grade' => 35,
        'final_grade' => 50,
        'status' => GradeStatus::Draft,
    ]);

    Livewire::actingAs($faculty)
        ->test(ViewMyCourse::class, ['record' => $offering])
        ->callAction('submit_grades')
        ->assertHasNoActionErrors();

    expect(Grade::where('enrollment_id', $enrollment->id)->first()->status)->toBe(GradeStatus::PendingApproval);
});

it('hides faculty grade entry while a grade awaits admin approval', function () {
    ['faculty' => $faculty, 'offering' => $offering, 'enrollment' => $enrollment] = facultyOfferingWithStudent();

    Grade::factory()->create([
        'enrollment_id' => $enrollment->id,
        'status' => GradeStatus::PendingApproval,
    ]);

    Livewire::actingAs($faculty)
        ->test(ViewMyCourse::class, ['record' => $offering])
        ->assertActionHidden(TestAction::make('enter_grade')->table($enrollment));
});

it('hides faculty grade entry once the grade is approved', function () {
    ['faculty' => $faculty, 'offering' => $offering, 'enrollment' => $enrollment] = facultyOfferingWithStudent();

    Grade::factory()->create([
        'enrollment_id' => $enrollment->id,
        'coursework_grade' => 35,
        'final_grade' => 50,
        'status' => GradeStatus::Approved,
    ]);

    Livewire::actingAs($faculty)
        ->test(ViewMyCourse::class, ['record' => $offering])
        ->assertActionHidden(TestAction::make('enter_grade')->table($enrollment));
});

it('publishes approved grades in a single final step and notifies the student', function () {
    Notification::fake();

    ['offering' => $offering, 'student' => $student, 'enrollment' => $enrollment] = facultyOfferingWithStudent();

    Grade::factory()->create([
        'enrollment_id' => $enrollment->id,
        'coursework_grade' => 38,
        'final_grade' => 50,
        'status' => GradeStatus::Approved,
        'published_at' => null,
    ]);

    Livewire::actingAs(adminUser())
        ->test(ViewCourseGrades::class, ['record' => $offering])
        ->callAction('publish_finals')
        ->assertHasNoActionErrors();

    Notification::assertSentTo($student->user, GradesPublishedNotification::class);

    expect(Grade::where('enrollment_id', $enrollment->id)->first()->published_at)->not->toBeNull();
});
