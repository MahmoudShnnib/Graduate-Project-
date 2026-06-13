<?php

use App\Enums\EnrollmentStatus;
use App\Enums\GradeStatus;
use App\Filament\Resources\Faculty\Pages\ViewMyCourse;
use App\Filament\Resources\Grades\Pages\ViewCourseGrades;
use App\Models\CourseOffering;
use App\Models\Grade;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Notifications\CourseworkGradePublishedNotification;
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

it('lets faculty save a coursework grade alone (no final required)', function () {
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

it('lets faculty re-open an approved coursework-only grade to add the final', function () {
    ['faculty' => $faculty, 'offering' => $offering, 'enrollment' => $enrollment] = facultyOfferingWithStudent();

    $grade = Grade::factory()->create([
        'enrollment_id' => $enrollment->id,
        'coursework_grade' => 35,
        'final_grade' => null,
        'status' => GradeStatus::Approved,
        'midterm_published_at' => now(),
    ]);

    Livewire::actingAs($faculty)
        ->test(ViewMyCourse::class, ['record' => $offering])
        ->callAction(TestAction::make('enter_grade')->table($enrollment), data: [
            'coursework_grade' => 35,
            'final_grade' => 50,
        ])
        ->assertHasNoActionErrors();

    $grade->refresh();

    expect((float) $grade->final_grade)->toBe(50.0)
        ->and((float) $grade->total)->toBe(85.0)
        ->and($grade->status)->toBe(GradeStatus::Draft);
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

it('notifies the student when coursework grades are published', function () {
    Notification::fake();

    ['offering' => $offering, 'student' => $student, 'enrollment' => $enrollment] = facultyOfferingWithStudent();

    Grade::factory()->create([
        'enrollment_id' => $enrollment->id,
        'coursework_grade' => 38,
        'final_grade' => null,
        'status' => GradeStatus::Approved,
        'midterm_published_at' => null,
    ]);

    Livewire::actingAs(adminUser())
        ->test(ViewCourseGrades::class, ['record' => $offering])
        ->callAction('publish_midterms')
        ->assertHasNoActionErrors();

    Notification::assertSentTo($student->user, CourseworkGradePublishedNotification::class);

    expect(Grade::where('enrollment_id', $enrollment->id)->first()->midterm_published_at)->not->toBeNull();
});
