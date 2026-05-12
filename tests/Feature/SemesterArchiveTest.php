<?php

use App\Enums\ApplicationStatus;
use App\Enums\EnrollmentStatus;
use App\Filament\Resources\Semesters\SemesterResource;
use App\Models\Application;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Semester;
use App\Models\Specialization;
use App\Models\StudentEnrollment;

it('archive action sets archived_at, deactivates the semester, and closes the survey', function () {
    $semester = Semester::factory()->create([
        'is_active' => true,
        'survey_open' => true,
    ]);

    SemesterResource::archiveSemesterAction()
        ->record($semester)
        ->call();

    $semester->refresh();

    expect($semester->archived_at)->not()->toBeNull()
        ->and($semester->is_active)->toBeFalse()
        ->and($semester->survey_open)->toBeFalse()
        ->and($semester->isArchived())->toBeTrue();
});

it('archive action cascades is_open=false to every open course offering in the semester', function () {
    $semester = Semester::factory()->create(['is_active' => true]);
    $course = Course::factory()->create();
    $course->specializations()->attach(Specialization::factory()->create()->id, ['type' => 'general']);

    $openOffering = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'semester_id' => $semester->id,
        'is_open' => true,
    ]);

    $alreadyClosedOffering = CourseOffering::factory()->create([
        'course_id' => Course::factory()->create()->id,
        'semester_id' => $semester->id,
        'is_open' => false,
    ]);

    SemesterResource::archiveSemesterAction()
        ->record($semester)
        ->call();

    expect($openOffering->fresh()->is_open)->toBeFalse()
        ->and($alreadyClosedOffering->fresh()->is_open)->toBeFalse();
});

it('archive action preserves existing student enrollments (historical data)', function () {
    ['student' => $student] = studentWithRecord();
    $semester = Semester::factory()->create(['is_active' => true]);
    $course = Course::factory()->create();
    $course->specializations()->attach($student->specialization_id, ['type' => 'general']);

    $offering = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'semester_id' => $semester->id,
    ]);

    $enrollment = StudentEnrollment::create([
        'student_id' => $student->id,
        'course_offering_id' => $offering->id,
        'status' => EnrollmentStatus::Enrolled,
    ]);

    SemesterResource::archiveSemesterAction()
        ->record($semester)
        ->call();

    expect($enrollment->fresh()->status)->toBe(EnrollmentStatus::Enrolled);
});

it('archive action is hidden once the semester is already archived (one-way)', function () {
    $semester = Semester::factory()->create([
        'is_active' => false,
        'archived_at' => now(),
    ]);

    $isVisible = SemesterResource::archiveSemesterAction()
        ->record($semester)
        ->isVisible();

    expect($isVisible)->toBeFalse();
});

it('survey open/edit/close actions are hidden on archived semesters', function () {
    $semester = Semester::factory()->create([
        'survey_open' => false,
        'archived_at' => now(),
    ]);

    expect(SemesterResource::openSurveyAction()->record($semester)->isVisible())->toBeFalse();

    $semester->update(['survey_open' => true]);

    expect(SemesterResource::editSurveyCoursesAction()->record($semester)->isVisible())->toBeFalse()
        ->and(SemesterResource::closeSurveyAction()->record($semester)->isVisible())->toBeFalse();
});

it('application observer captures semester_id when status flips to Accepted', function () {
    $semester = Semester::factory()->create(['is_active' => true]);

    $application = Application::factory()->create([
        'status' => ApplicationStatus::Pending,
        'semester_id' => null,
    ]);

    expect($application->semester_id)->toBeNull();

    $application->update(['status' => ApplicationStatus::Accepted]);

    expect($application->fresh()->semester_id)->toBe($semester->id);
});

it('application observer captures semester_id when status flips to Rejected', function () {
    $semester = Semester::factory()->create(['is_active' => true]);

    $application = Application::factory()->create([
        'status' => ApplicationStatus::Pending,
        'semester_id' => null,
    ]);

    $application->update(['status' => ApplicationStatus::Rejected]);

    expect($application->fresh()->semester_id)->toBe($semester->id);
});

it('application observer does not set semester_id for Pending applications', function () {
    Semester::factory()->create(['is_active' => true]);

    $application = Application::factory()->create([
        'status' => ApplicationStatus::Pending,
        'semester_id' => null,
    ]);

    expect($application->fresh()->semester_id)->toBeNull();
});

it('application observer does not overwrite an existing semester_id', function () {
    $semesterA = Semester::factory()->create(['is_active' => false]);
    $semesterB = Semester::factory()->create(['is_active' => true]);

    $application = Application::factory()->create([
        'status' => ApplicationStatus::Pending,
        'semester_id' => $semesterA->id,
    ]);

    $application->update(['status' => ApplicationStatus::Accepted]);

    expect($application->fresh()->semester_id)->toBe($semesterA->id);
});

it('isArchived helper returns true only when archived_at is set', function () {
    $active = Semester::factory()->create(['archived_at' => null]);
    $archived = Semester::factory()->create(['archived_at' => now()]);

    expect($active->isArchived())->toBeFalse()
        ->and($archived->isArchived())->toBeTrue();
});

it('activating a semester auto-archives every other non-archived peer', function () {
    $oldActive = Semester::factory()->create(['is_active' => true]);
    $scheduled = Semester::factory()->create(['is_active' => false, 'archived_at' => null]);
    $alreadyArchived = Semester::factory()->create(['is_active' => false, 'archived_at' => now()->subDay()]);

    $newSemester = Semester::factory()->create(['is_active' => true]);

    expect($oldActive->fresh()->isArchived())->toBeTrue()
        ->and($scheduled->fresh()->isArchived())->toBeTrue()
        ->and($newSemester->fresh()->isArchived())->toBeFalse()
        ->and($alreadyArchived->fresh()->archived_at?->toDateString())->toBe(now()->subDay()->toDateString());
});

it('activating an existing semester (update) also archives non-archived peers', function () {
    $peerA = Semester::factory()->create(['is_active' => true]);
    $peerB = Semester::factory()->create(['is_active' => false, 'archived_at' => null]);
    $target = Semester::factory()->create(['is_active' => false, 'archived_at' => null]);

    $target->update(['is_active' => true]);

    expect($peerA->fresh()->isArchived())->toBeTrue()
        ->and($peerB->fresh()->isArchived())->toBeTrue()
        ->and($target->fresh()->isArchived())->toBeFalse()
        ->and($target->fresh()->is_active)->toBeTrue();
});
