<?php

use App\Enums\GradeStatus;
use App\Enums\Season;
use App\Enums\StudentStatus;
use App\Enums\WarningType;
use App\Models\Course;
use App\Models\CourseOffering;
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

    ['student' => $this->student] = studentWithRecord(['status' => StudentStatus::Active]);

    $this->course = Course::factory()->create();
    $this->course->specializations()->attach($this->student->specialization_id, ['type' => 'general']);

    $this->offering = CourseOffering::factory()->create([
        'course_id' => $this->course->id,
        'semester_id' => $this->semester->id,
        'is_open' => true,
    ]);
});

// --- Type 1: Course Failure Warnings ---

it('creates a warning when a student gets an F', function () {
    $enroll = app(\App\Actions\Enrollment\EnrollStudentInOffering::class);
    $enrollment = $enroll($this->student, $this->offering);

    Grade::create([
        'enrollment_id' => $enrollment->id,
        'coursework_grade' => 10,
        'final_grade' => 15,
        'status' => GradeStatus::Approved,
        'published_at' => now(),
    ]);

    expect(StudentWarning::where('student_id', $this->student->id)->where('is_active', true)->count())->toBe(1)
        ->and($this->student->fresh()->warnings_count)->toBe(1);

    $warning = StudentWarning::where('student_id', $this->student->id)->first();
    expect($warning->type)->toBe(WarningType::CourseFailure)
        ->and($warning->course_id)->toBe($this->course->id);
});

it('resolves course failure warning when student passes the course', function () {
    $enroll = app(\App\Actions\Enrollment\EnrollStudentInOffering::class);
    $enrollment = $enroll($this->student, $this->offering);

    Grade::create([
        'enrollment_id' => $enrollment->id,
        'coursework_grade' => 10,
        'final_grade' => 15,
        'status' => GradeStatus::Approved,
        'published_at' => now(),
    ]);

    expect($this->student->fresh()->warnings_count)->toBe(1);

    $semester2 = Semester::factory()->create(['season' => Season::Spring, 'year' => 2026]);
    $offering2 = CourseOffering::factory()->create([
        'course_id' => $this->course->id,
        'semester_id' => $semester2->id,
        'is_open' => true,
    ]);

    $enrollment2 = $enroll($this->student->fresh(), $offering2);

    Grade::create([
        'enrollment_id' => $enrollment2->id,
        'coursework_grade' => 40,
        'final_grade' => 50,
        'status' => GradeStatus::Approved,
        'published_at' => now(),
    ]);

    expect($this->student->fresh()->warnings_count)->toBe(0)
        ->and(StudentWarning::where('student_id', $this->student->id)->where('is_active', true)->count())->toBe(0)
        ->and(StudentWarning::where('student_id', $this->student->id)->where('is_active', false)->count())->toBe(1);
});

it('dismisses student who fails the same course twice', function () {
    $enroll = app(\App\Actions\Enrollment\EnrollStudentInOffering::class);
    $enrollment = $enroll($this->student, $this->offering);

    Grade::create([
        'enrollment_id' => $enrollment->id,
        'coursework_grade' => 10,
        'final_grade' => 15,
        'status' => GradeStatus::Approved,
        'published_at' => now(),
    ]);

    expect($this->student->fresh()->status)->toBe(StudentStatus::Active);

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
        'published_at' => now(),
    ]);

    $student = $this->student->fresh();
    expect($student->status)->toBe(StudentStatus::Dismissed)
        ->and($student->dismissed_at)->not->toBeNull()
        ->and($student->dismissal_type)->toBe(WarningType::CourseFailure)
        ->and($student->dismissal_course_id)->toBe($this->course->id);
});

it('does not dismiss when failing different courses', function () {
    $course2 = Course::factory()->create();
    $course2->specializations()->attach($this->student->specialization_id, ['type' => 'general']);
    $offering2 = CourseOffering::factory()->create([
        'course_id' => $course2->id,
        'semester_id' => $this->semester->id,
        'is_open' => true,
    ]);

    $enroll = app(\App\Actions\Enrollment\EnrollStudentInOffering::class);

    $e1 = $enroll($this->student, $this->offering);
    Grade::create([
        'enrollment_id' => $e1->id,
        'coursework_grade' => 10,
        'final_grade' => 15,
        'status' => GradeStatus::Approved,
        'published_at' => now(),
    ]);

    $e2 = $enroll($this->student->fresh(), $offering2);
    Grade::create([
        'enrollment_id' => $e2->id,
        'coursework_grade' => 10,
        'final_grade' => 15,
        'status' => GradeStatus::Approved,
        'published_at' => now(),
    ]);

    $student = $this->student->fresh();
    expect($student->status)->toBe(StudentStatus::Active)
        ->and($student->warnings_count)->toBe(2);
});

// --- Type 2: Consecutive Inactive Warnings ---

it('creates inactive warning when student status set to inactive', function () {
    $this->student->update(['status' => StudentStatus::Inactive]);

    expect(StudentWarning::where('student_id', $this->student->id)
        ->where('type', WarningType::ConsecutiveInactive)
        ->where('is_active', true)
        ->count())->toBe(1)
        ->and($this->student->fresh()->warnings_count)->toBe(1);
});

it('clears inactive warnings when student becomes active again', function () {
    $this->student->update(['status' => StudentStatus::Inactive]);
    expect($this->student->fresh()->warnings_count)->toBe(1);

    $this->student->refresh();
    $this->student->update(['status' => StudentStatus::Active]);

    expect($this->student->fresh()->warnings_count)->toBe(0)
        ->and(StudentWarning::where('student_id', $this->student->id)->where('is_active', true)->count())->toBe(0);
});

it('dismisses student inactive for 2 consecutive semesters', function () {
    $this->student->update(['status' => StudentStatus::Inactive]);
    expect($this->student->fresh()->status)->toBe(StudentStatus::Inactive);

    $semester2 = Semester::factory()->create([
        'is_active' => false,
        'season' => Season::Spring,
        'year' => 2026,
    ]);

    $this->student->refresh();

    $semester2->update(['is_active' => true]);
    $this->semester->update(['is_active' => false]);

    $student = $this->student->fresh();
    expect($student->status)->toBe(StudentStatus::Dismissed)
        ->and($student->dismissal_type)->toBe(WarningType::ConsecutiveInactive)
        ->and($student->dismissal_course_id)->toBeNull();
});

it('does not dismiss when inactive semesters are not consecutive', function () {
    $this->student->update(['status' => StudentStatus::Inactive]);

    $this->student->refresh();
    $this->student->update(['status' => StudentStatus::Active]);

    expect($this->student->fresh()->warnings_count)->toBe(0);

    $semester2 = Semester::factory()->create([
        'is_active' => false,
        'season' => Season::Spring,
        'year' => 2026,
    ]);

    $this->student->refresh();
    $this->student->update(['status' => StudentStatus::Inactive]);

    $student = $this->student->fresh();
    expect($student->status)->toBe(StudentStatus::Inactive)
        ->and($student->warnings_count)->toBe(1);
});
