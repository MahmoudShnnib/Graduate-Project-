<?php

use App\Enums\EnrollmentStatus;
use App\Enums\GradeStatus;
use App\Enums\StudentStatus;
use App\Filament\Student\Pages\CourseTreePage;
use App\Filament\Student\Pages\GradesPage;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;

/**
 * Enroll a student in a graded, published course under $semester and return the
 * enrollment. total = $cw + $final drives the letter/points via Grade::booted().
 */
function gradedEnrollment(Student $student, Semester $semester, int $cw, int $final, string $code): StudentEnrollment
{
    $course = Course::factory()->create(['credits' => 3, 'code' => $code]);
    $course->specializations()->attach($student->specialization_id, ['type' => 'general']);

    $offering = CourseOffering::factory()->create([
        'semester_id' => $semester->id,
        'course_id' => $course->id,
    ]);

    $enrollment = StudentEnrollment::factory()->create([
        'student_id' => $student->id,
        'course_offering_id' => $offering->id,
        'status' => EnrollmentStatus::Enrolled,
    ]);

    Grade::factory()->create([
        'enrollment_id' => $enrollment->id,
        'coursework_grade' => $cw,
        'final_grade' => $final,
        'status' => GradeStatus::Approved,
        'published_at' => now(),
    ]);

    return $enrollment;
}

/**
 * Student with one completed prior-semester course (A = 4.0) and one
 * current/active-semester course (D = 1.0). Initial GPA = 2.50.
 *
 * @return array{student: Student, prior: StudentEnrollment, current: StudentEnrollment, active: Semester}
 */
function flushFixture(): array
{
    ['student' => $student] = studentWithRecord();

    $prior = Semester::factory()->create(['is_active' => false]);
    $active = Semester::factory()->active()->create();
    Semester::flushActiveCache();

    $priorEnrollment = gradedEnrollment($student, $prior, 50, 40, 'PRIOR'); // 90 → A → 4.0
    $currentEnrollment = gradedEnrollment($student, $active, 20, 30, 'CURR'); // 50 → D → 1.0

    $student->refresh();
    expect((float) $student->gpa)->toBe(2.5);

    return ['student' => $student, 'prior' => $priorEnrollment, 'current' => $currentEnrollment, 'active' => $active];
}

it('flushes only the current semester on suspension, keeps prior work, and recalcs the GPA', function () {
    ['student' => $student, 'prior' => $prior, 'current' => $current] = flushFixture();

    $student->update(['status' => StudentStatus::OnHold]);

    expect($current->fresh()->status)->toBe(EnrollmentStatus::Withdrawn)
        ->and($prior->fresh()->status)->toBe(EnrollmentStatus::Enrolled)
        ->and((float) $student->fresh()->gpa)->toBe(4.0); // only the prior A remains
});

it('behaves the same way for the Inactive (منقطع) status', function () {
    ['student' => $student, 'prior' => $prior, 'current' => $current] = flushFixture();

    $student->update(['status' => StudentStatus::Inactive]);

    expect($current->fresh()->status)->toBe(EnrollmentStatus::Withdrawn)
        ->and($prior->fresh()->status)->toBe(EnrollmentStatus::Enrolled)
        ->and((float) $student->fresh()->gpa)->toBe(4.0);
});

it('keeps flushed courses out of the GPA after reactivation', function () {
    ['student' => $student, 'current' => $current] = flushFixture();

    $student->update(['status' => StudentStatus::OnHold]);
    expect((float) $student->fresh()->gpa)->toBe(4.0);

    $student->update(['status' => StudentStatus::Active]);

    expect($current->fresh()->status)->toBe(EnrollmentStatus::Withdrawn) // stays flushed
        ->and((float) $student->fresh()->gpa)->toBe(4.0);
});

it('drops a first-semester student with no prior work to 0.00 on suspension', function () {
    ['student' => $student] = studentWithRecord();
    $active = Semester::factory()->active()->create();
    Semester::flushActiveCache();

    gradedEnrollment($student, $active, 20, 30, 'ONLY'); // D → 1.0
    $student->refresh();
    expect((float) $student->gpa)->toBe(1.0);

    $student->update(['status' => StudentStatus::OnHold]);

    expect((float) $student->fresh()->gpa)->toBe(0.0);
});

it('lets a flushed course be re-studied later as a fresh attempt', function () {
    ['student' => $student, 'current' => $current] = flushFixture();
    $currentCourseId = $current->courseOffering->course_id;

    $student->update(['status' => StudentStatus::OnHold]);
    $student->update(['status' => StudentStatus::Active]);

    // New term; re-study the same flushed course and earn a B+ (3.5).
    $newTerm = Semester::factory()->create(['is_active' => false]);
    $newOffering = CourseOffering::factory()->create([
        'semester_id' => $newTerm->id,
        'course_id' => $currentCourseId,
    ]);
    $retake = StudentEnrollment::factory()->create([
        'student_id' => $student->id,
        'course_offering_id' => $newOffering->id,
        'status' => EnrollmentStatus::Enrolled,
    ]);
    Grade::factory()->create([
        'enrollment_id' => $retake->id,
        'coursework_grade' => 40,
        'final_grade' => 38, // 78 → B+ → 3.5
        'status' => GradeStatus::Approved,
        'published_at' => now(),
    ]);

    // GPA = (prior A 4.0 + retake B+ 3.5) / 2 = 3.75; the flushed D never counts.
    expect((float) $student->fresh()->gpa)->toBe(3.75);
});

it('still withdraws everything and preserves the final GPA on graduation', function () {
    ['student' => $student, 'prior' => $prior, 'current' => $current] = flushFixture();

    $student->update(['status' => StudentStatus::Graduated]);

    expect($current->fresh()->status)->toBe(EnrollmentStatus::Withdrawn)
        ->and($prior->fresh()->status)->toBe(EnrollmentStatus::Withdrawn)
        ->and((float) $student->fresh()->gpa)->toBe(2.5); // frozen at the final value
});

it('hides suspension-flushed current-semester courses from the transcript but keeps prior work', function () {
    ['student' => $student, 'prior' => $prior, 'current' => $current] = flushFixture();
    $student->update(['status' => StudentStatus::OnHold]);

    $this->actingAs($student->user);

    $enrollmentIds = (new GradesPage)->getGrades()->pluck('id');

    expect($enrollmentIds)->toContain($prior->id)
        ->and($enrollmentIds)->not->toContain($current->id);
});

it('excludes suspension-flushed current courses from the course tree but keeps prior passed courses', function () {
    ['student' => $student, 'prior' => $prior, 'current' => $current] = flushFixture();
    $priorCourseId = $prior->courseOffering->course_id;
    $currentCourseId = $current->courseOffering->course_id;

    $student->update(['status' => StudentStatus::OnHold]);

    $this->actingAs($student->user);

    $map = (new CourseTreePage)->getEnrollmentMap();

    expect($map)->toHaveKey($priorCourseId)
        ->and($map[$priorCourseId]['status'])->toBe('passed')
        ->and($map)->not->toHaveKey($currentCourseId);
});

it('keeps a graduated student full transcript visible even though all enrollments are withdrawn', function () {
    ['student' => $student, 'prior' => $prior, 'current' => $current] = flushFixture();
    $student->update(['status' => StudentStatus::Graduated]);

    $this->actingAs($student->user);

    // On graduation BOTH enrollments are withdrawn, but they are prior/non-current
    // relative to a graduated student, so the transcript must still show them.
    $enrollmentIds = (new GradesPage)->getGrades()->pluck('id');

    expect($enrollmentIds)->toContain($prior->id);
});
