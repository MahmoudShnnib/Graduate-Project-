<?php

use App\Enums\GradeStatus;
use App\Enums\StudentStatus;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\Specialization;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\GradeCalculationService;

beforeEach(function () {
    $this->service = new GradeCalculationService;
    $specialization = Specialization::factory()->create();
    $user = User::factory()->withRole('student')->create();
    $this->student = Student::factory()->create([
        'user_id' => $user->id,
        'specialization_id' => $specialization->id,
        'gpa' => 0.0,
    ]);
    $this->semester = Semester::factory()->active()->create();
});

test('calculates gpa correctly from approved grades', function () {
    $gradeData = [
        ['coursework' => 45, 'final' => 35, 'credits' => 3],  // 80 = B+ = 3.5
        ['coursework' => 50, 'final' => 38, 'credits' => 2],  // 88 = A  = 4.0
    ];

    foreach ($gradeData as $data) {
        $course = Course::factory()->create(['credits' => $data['credits']]);
        $course->specializations()->attach($this->student->specialization_id, ['type' => 'general']);
        $offering = CourseOffering::factory()->create([
            'course_id' => $course->id,
            'semester_id' => $this->semester->id,
        ]);
        $enrollment = StudentEnrollment::factory()->create([
            'student_id' => $this->student->id,
            'course_offering_id' => $offering->id,
        ]);
        Grade::factory()->create([
            'enrollment_id' => $enrollment->id,
            'coursework_grade' => $data['coursework'],
            'final_grade' => $data['final'],
            'status' => GradeStatus::Approved,
            'published_at' => now(),
        ]);
    }

    $this->service->recalculateStudentGPA($this->student);
    $this->student->refresh();

    // Expected: (3.5*3 + 4.0*2) / (3+2) = (10.5 + 8.0) / 5 = 18.5/5 = 3.70
    expect((float) $this->student->gpa)->toBeGreaterThanOrEqual(3.6)
        ->toBeLessThanOrEqual(3.8);
});

test('does not recalculate gpa for non-active students (unforced)', function () {
    // Suspending triggers the current-semester flush + a forced recalc, so set
    // a known GPA AFTER the status change (a gpa-only update doesn't re-fire the
    // status cascade). An unforced recalc must then leave it frozen.
    $this->student->update(['status' => StudentStatus::OnHold]);
    $this->student->update(['gpa' => 2.5]);

    $this->service->recalculateStudentGPA($this->student);
    $this->student->refresh();

    expect((float) $this->student->gpa)->toBe(2.5);
});

test('sets gpa to zero when student has no approved grades', function () {
    $this->student->update(['gpa' => 3.0]);

    $this->service->recalculateStudentGPA($this->student);
    $this->student->refresh();

    expect((float) $this->student->gpa)->toBe(0.0);
});

test('counts only the latest attempt when a course is retaken', function () {
    $course = Course::factory()->create(['credits' => 3]);
    $course->specializations()->attach($this->student->specialization_id, ['type' => 'general']);

    $priorSemester = Semester::factory()->create(['is_active' => false]);

    // First attempt: F (0.0).
    $firstOffering = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'semester_id' => $priorSemester->id,
    ]);
    $firstEnrollment = StudentEnrollment::factory()->create([
        'student_id' => $this->student->id,
        'course_offering_id' => $firstOffering->id,
    ]);
    Grade::factory()->create([
        'enrollment_id' => $firstEnrollment->id,
        'coursework_grade' => 10,
        'final_grade' => 15, // 25 → F → 0.0
        'status' => GradeStatus::Approved,
        'published_at' => now(),
    ]);

    // Retake: A (4.0).
    $retakeOffering = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'semester_id' => $this->semester->id,
    ]);
    $retakeEnrollment = StudentEnrollment::factory()->create([
        'student_id' => $this->student->id,
        'course_offering_id' => $retakeOffering->id,
    ]);
    Grade::factory()->create([
        'enrollment_id' => $retakeEnrollment->id,
        'coursework_grade' => 50,
        'final_grade' => 40, // 90 → A → 4.0
        'status' => GradeStatus::Approved,
        'published_at' => now(),
    ]);

    $this->service->recalculateStudentGPA($this->student);
    $this->student->refresh();

    // Only the latest attempt (A = 4.0) counts — NOT the average of both (2.0).
    expect((float) $this->student->gpa)->toBe(4.0);
});

test('a transfer course studied in-system is counted once, with the in-system grade winning', function () {
    $course = Course::factory()->create(['credits' => 3]);
    $course->specializations()->attach($this->student->specialization_id, ['type' => 'general']);

    // Transfer (معادلة) equivalence: 88 → A → 4.0.
    $this->student->transferCourses()->create([
        'course_id' => $course->id,
        'coursework_grade' => 50,
        'final_grade' => 38,
    ]);

    // Later studied the same course in-system: 70 → B → 3.0 (lower than the transfer).
    $offering = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'semester_id' => $this->semester->id,
    ]);
    $enrollment = StudentEnrollment::factory()->create([
        'student_id' => $this->student->id,
        'course_offering_id' => $offering->id,
    ]);
    Grade::factory()->create([
        'enrollment_id' => $enrollment->id,
        'coursework_grade' => 40,
        'final_grade' => 30, // 70 → B → 3.0
        'status' => GradeStatus::Approved,
        'published_at' => now(),
    ]);

    $this->service->recalculateStudentGPA($this->student);
    $this->student->refresh();

    // Counted ONCE with the in-system (latest) grade — 3.0 — not 4.0, and not the
    // double-counted average (3.5) the old code produced.
    expect((float) $this->student->gpa)->toBe(3.0);
});

test('a transfer course with no in-system attempt still counts toward gpa', function () {
    $course = Course::factory()->create(['credits' => 2]);
    $course->specializations()->attach($this->student->specialization_id, ['type' => 'general']);

    // Transfer (معادلة) equivalence: 88 → A → 4.0, never studied in-system.
    $this->student->transferCourses()->create([
        'course_id' => $course->id,
        'coursework_grade' => 50,
        'final_grade' => 38,
    ]);

    $this->service->recalculateStudentGPA($this->student);
    $this->student->refresh();

    expect((float) $this->student->gpa)->toBe(4.0);
});
