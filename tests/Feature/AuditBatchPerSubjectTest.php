<?php

use App\Enums\ApplicationStatus;
use App\Filament\Pages\ExamListPage;
use App\Filament\Resources\CourseOfferings\Pages\EnrollStudents;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Semester;
use App\Models\Specialization;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * A bulk action processes many applicants in one HTTP request. Each applicant's
 * fan-out (application edit + user/student creation + exam result) must be its OWN
 * audit operation — never glued to the next applicant's changes via a shared batch_id.
 */
it('gives each applicant its own audit batch when bulk-passing the exam', function () {
    Notification::fake();
    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

    $semester = Semester::factory()->create(['is_active' => true]);
    $spec = Specialization::factory()->create();
    $apps = collect(['APP-T-1', 'APP-T-2'])->map(fn (string $code) => Application::factory()->create([
        'specialization_id' => $spec->id,
        'semester_id' => $semester->id,
        'status' => ApplicationStatus::InitiallyApproved,
        'tracking_code' => $code,
    ]));

    Livewire::actingAs(adminUser())
        ->test(ExamListPage::class)
        ->set('selected', $apps->pluck('id')->all())
        ->set('scores', $apps->mapWithKeys(fn ($app) => [$app->id => 15])->all())
        ->callAction('markPassed')
        ->assertHasNoActionErrors();

    $batchA = AuditLog::where('action', 'exam_passed')->where('model_id', $apps[0]->id)->value('batch_id');
    $batchB = AuditLog::where('action', 'exam_passed')->where('model_id', $apps[1]->id)->value('batch_id');

    expect($batchA)->not->toBeNull()
        ->and($batchB)->not->toBeNull()
        ->and($batchA)->not->toBe($batchB);
});

it('gives each applicant its own audit batch when bulk-failing the exam', function () {
    Notification::fake();

    $semester = Semester::factory()->create(['is_active' => true]);
    $spec = Specialization::factory()->create();
    $apps = collect(['APP-T-3', 'APP-T-4'])->map(fn (string $code) => Application::factory()->create([
        'specialization_id' => $spec->id,
        'semester_id' => $semester->id,
        'status' => ApplicationStatus::InitiallyApproved,
        'tracking_code' => $code,
    ]));

    Livewire::actingAs(adminUser())
        ->test(ExamListPage::class)
        ->set('selected', $apps->pluck('id')->all())
        ->set('scores', $apps->mapWithKeys(fn ($app) => [$app->id => 5])->all())
        ->callAction('markFailed')
        ->assertHasNoActionErrors();

    $batchA = AuditLog::where('action', 'exam_failed')->where('model_id', $apps[0]->id)->value('batch_id');
    $batchB = AuditLog::where('action', 'exam_failed')->where('model_id', $apps[1]->id)->value('batch_id');

    expect($batchA)->not->toBeNull()
        ->and($batchB)->not->toBeNull()
        ->and($batchA)->not->toBe($batchB);
});

it('gives each student its own audit batch when bulk-enrolling into an offering', function () {
    Notification::fake();

    $semester = Semester::factory()->active()->create();
    $course = Course::factory()->create();
    $offering = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'semester_id' => $semester->id,
        'is_open' => true,
    ]);

    $students = collect(range(1, 2))->map(function () use ($course) {
        ['student' => $student] = studentWithRecord();
        $course->specializations()->syncWithoutDetaching([$student->specialization_id => ['type' => 'general']]);

        return $student;
    });

    Livewire::actingAs(adminUser())
        ->test(EnrollStudents::class, ['record' => $offering])
        ->callAction('enroll_manual', ['student_ids' => $students->pluck('id')->all()])
        ->assertHasNoActionErrors();

    $batches = $students->map(fn ($student) => AuditLog::query()
        ->where('action', 'StudentEnrollment_created')
        ->whereIn('model_id', StudentEnrollment::where('student_id', $student->id)->pluck('id'))
        ->value('batch_id'));

    expect($batches)->toHaveCount(2)
        ->and($batches[0])->not->toBeNull()
        ->and($batches[1])->not->toBeNull()
        ->and($batches[0])->not->toBe($batches[1]);
});
