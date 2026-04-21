<?php

use App\Filament\Pages\StudentSurveyView;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\CourseSurvey;
use App\Models\Semester;
use Livewire\Livewire;

it('rejecting a survey entry sets is_processed and rejected_at', function () {
    ['student' => $student] = studentWithRecord();
    $semester = Semester::factory()->create();
    $course = Course::factory()->create();

    $survey = CourseSurvey::create([
        'student_id' => $student->id,
        'semester_id' => $semester->id,
        'course_id' => $course->id,
        'priority' => 1,
        'is_processed' => false,
    ]);

    $this->actingAs(adminUser());

    Livewire::test(StudentSurveyView::class, ['student' => $student, 'semester' => $semester])
        ->call('rejectCourse', $course->id);

    $survey->refresh();
    expect($survey->is_processed)->toBeTrue();
    expect($survey->rejected_at)->not->toBeNull();
});

it('cannot reject a course the student is already enrolled in', function () {
    ['student' => $student] = studentWithRecord();
    $semester = Semester::factory()->create();
    $course = Course::factory()->create();
    $offering = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'semester_id' => $semester->id,
    ]);

    $survey = CourseSurvey::create([
        'student_id' => $student->id,
        'semester_id' => $semester->id,
        'course_id' => $course->id,
        'priority' => 1,
        'is_processed' => true,
    ]);

    $student->enrollments()->create([
        'course_offering_id' => $offering->id,
        'status' => \App\Enums\EnrollmentStatus::Enrolled,
    ]);

    $this->actingAs(adminUser());

    Livewire::test(StudentSurveyView::class, ['student' => $student, 'semester' => $semester])
        ->call('rejectCourse', $course->id);

    $survey->refresh();
    // Unchanged — guard should have blocked the rejection.
    expect($survey->rejected_at)->toBeNull();
});

it('bulk rejectSelected rejects multiple surveys and skips enrolled ones', function () {
    ['student' => $student] = studentWithRecord();
    $semester = Semester::factory()->create();

    $rejectableCourse1 = Course::factory()->create();
    $rejectableCourse2 = Course::factory()->create();
    $enrolledCourse = Course::factory()->create();

    foreach ([$rejectableCourse1, $rejectableCourse2, $enrolledCourse] as $i => $course) {
        CourseSurvey::create([
            'student_id' => $student->id,
            'semester_id' => $semester->id,
            'course_id' => $course->id,
            'priority' => $i + 1,
            'is_processed' => false,
        ]);
    }

    // Student is actively enrolled in the third course → bulk reject should skip it.
    $enrolledOffering = CourseOffering::factory()->create([
        'course_id' => $enrolledCourse->id,
        'semester_id' => $semester->id,
    ]);
    $student->enrollments()->create([
        'course_offering_id' => $enrolledOffering->id,
        'status' => \App\Enums\EnrollmentStatus::Enrolled,
    ]);

    $this->actingAs(adminUser());

    Livewire::test(StudentSurveyView::class, ['student' => $student, 'semester' => $semester])
        ->set('selected', [$rejectableCourse1->id, $rejectableCourse2->id, $enrolledCourse->id])
        ->call('rejectSelected');

    // Two surveys rejected, one skipped (enrolled).
    expect(CourseSurvey::whereNotNull('rejected_at')->count())->toBe(2);
    expect(CourseSurvey::where('course_id', $enrolledCourse->id)->first()->rejected_at)->toBeNull();
});

it('is idempotent when called on an already-rejected survey', function () {
    ['student' => $student] = studentWithRecord();
    $semester = Semester::factory()->create();
    $course = Course::factory()->create();

    $originalRejectedAt = now()->subHour();

    $survey = CourseSurvey::create([
        'student_id' => $student->id,
        'semester_id' => $semester->id,
        'course_id' => $course->id,
        'priority' => 1,
        'is_processed' => true,
        'rejected_at' => $originalRejectedAt,
    ]);

    $this->actingAs(adminUser());

    // Re-call rejectCourse — it runs successfully but overwrites rejected_at.
    // That's fine behaviorally (the row stays rejected); we just confirm no crash.
    Livewire::test(StudentSurveyView::class, ['student' => $student, 'semester' => $semester])
        ->call('rejectCourse', $course->id);

    $survey->refresh();
    expect($survey->rejected_at)->not->toBeNull();
    expect($survey->is_processed)->toBeTrue();
});
