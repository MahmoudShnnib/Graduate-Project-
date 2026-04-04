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
        ]);
    }

    $this->service->recalculateStudentGPA($this->student);
    $this->student->refresh();

    // Expected: (3.5*3 + 4.0*2) / (3+2) = (10.5 + 8.0) / 5 = 18.5/5 = 3.70
    expect((float) $this->student->gpa)->toBeGreaterThanOrEqual(3.6)
        ->toBeLessThanOrEqual(3.8);
});

test('does not recalculate gpa for suspended students', function () {
    $this->student->update(['status' => StudentStatus::Suspended, 'gpa' => 2.5]);

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
