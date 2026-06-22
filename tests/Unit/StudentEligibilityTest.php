<?php

use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\Specialization;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;

beforeEach(function () {
    $this->specialization = Specialization::factory()->create();
    $this->user = User::factory()->withRole('student')->create();
    $this->student = Student::factory()->create([
        'user_id' => $this->user->id,
        'specialization_id' => $this->specialization->id,
        'gpa' => 3.5,
    ]);
    $this->semester = Semester::factory()->active()->create();
});

function createCoursesForSpecialization(Specialization $spec, string $type, int $count): array
{
    $courses = [];
    for ($i = 0; $i < $count; $i++) {
        $course = Course::factory()->create();
        $course->specializations()->attach($spec->id, ['type' => $type]);
        $courses[] = $course;
    }

    return $courses;
}

function enrollAndPassCourses(Student $student, array $courses, Semester $semester): void
{
    foreach ($courses as $course) {
        $offering = CourseOffering::factory()->create([
            'course_id' => $course->id,
            'semester_id' => $semester->id,
        ]);
        $enrollment = StudentEnrollment::factory()->create([
            'student_id' => $student->id,
            'course_offering_id' => $offering->id,
        ]);
        Grade::factory()->passing()->create(['enrollment_id' => $enrollment->id]);
    }
}

test('student is eligible when all general, all specialized, and 2+ electives completed', function () {
    $general = createCoursesForSpecialization($this->specialization, 'general', 4);
    $specialized = createCoursesForSpecialization($this->specialization, 'specialized', 4);
    $electives = createCoursesForSpecialization($this->specialization, 'elective', 3);

    enrollAndPassCourses($this->student, $general, $this->semester);
    enrollAndPassCourses($this->student, $specialized, $this->semester);
    enrollAndPassCourses($this->student, array_slice($electives, 0, 2), $this->semester);

    expect($this->student->isEligibleForThesis())->toBeTrue();
});

test('student is ineligible with missing general courses', function () {
    $general = createCoursesForSpecialization($this->specialization, 'general', 4);
    $specialized = createCoursesForSpecialization($this->specialization, 'specialized', 4);
    $electives = createCoursesForSpecialization($this->specialization, 'elective', 3);

    enrollAndPassCourses($this->student, array_slice($general, 0, 3), $this->semester);
    enrollAndPassCourses($this->student, $specialized, $this->semester);
    enrollAndPassCourses($this->student, array_slice($electives, 0, 2), $this->semester);

    expect($this->student->isEligibleForThesis())->toBeFalse();
});

test('student is ineligible with missing specialized courses', function () {
    $general = createCoursesForSpecialization($this->specialization, 'general', 4);
    $specialized = createCoursesForSpecialization($this->specialization, 'specialized', 4);
    $electives = createCoursesForSpecialization($this->specialization, 'elective', 3);

    enrollAndPassCourses($this->student, $general, $this->semester);
    enrollAndPassCourses($this->student, array_slice($specialized, 0, 2), $this->semester);
    enrollAndPassCourses($this->student, array_slice($electives, 0, 2), $this->semester);

    expect($this->student->isEligibleForThesis())->toBeFalse();
});

test('student is ineligible with fewer than 2 electives', function () {
    $general = createCoursesForSpecialization($this->specialization, 'general', 4);
    $specialized = createCoursesForSpecialization($this->specialization, 'specialized', 4);
    $electives = createCoursesForSpecialization($this->specialization, 'elective', 3);

    enrollAndPassCourses($this->student, $general, $this->semester);
    enrollAndPassCourses($this->student, $specialized, $this->semester);
    enrollAndPassCourses($this->student, array_slice($electives, 0, 1), $this->semester);

    expect($this->student->isEligibleForThesis())->toBeFalse();
});

test('student is ineligible with low gpa even with all courses completed', function () {
    $this->student->update(['gpa' => 2.5]);

    $general = createCoursesForSpecialization($this->specialization, 'general', 4);
    $specialized = createCoursesForSpecialization($this->specialization, 'specialized', 4);
    $electives = createCoursesForSpecialization($this->specialization, 'elective', 3);

    enrollAndPassCourses($this->student, $general, $this->semester);
    enrollAndPassCourses($this->student, $specialized, $this->semester);
    enrollAndPassCourses($this->student, array_slice($electives, 0, 2), $this->semester);

    expect($this->student->isEligibleForThesis())->toBeFalse();
});

test('failed grades do not count toward completed courses', function () {
    $course = Course::factory()->create();
    $course->specializations()->attach($this->specialization->id, ['type' => 'general']);

    $offering = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'semester_id' => $this->semester->id,
    ]);
    $enrollment = StudentEnrollment::factory()->create([
        'student_id' => $this->student->id,
        'course_offering_id' => $offering->id,
    ]);
    Grade::factory()->failing()->create(['enrollment_id' => $enrollment->id]);

    expect($this->student->completedCoursesCount())->toBe(0);
});

test('a course passed both as a transfer equivalence and in-system counts once', function () {
    $course = Course::factory()->create();
    $course->specializations()->attach($this->specialization->id, ['type' => 'general']);

    // Transfer (معادلة) equivalence for the course.
    $this->student->transferCourses()->create([
        'course_id' => $course->id,
        'coursework_grade' => 50,
        'final_grade' => 38, // 88 → A
    ]);

    // Same course later studied and passed in-system.
    $offering = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'semester_id' => $this->semester->id,
    ]);
    $enrollment = StudentEnrollment::factory()->create([
        'student_id' => $this->student->id,
        'course_offering_id' => $offering->id,
    ]);
    Grade::factory()->passing()->create(['enrollment_id' => $enrollment->id]);

    // One distinct course passed — not double-counted across the two tables.
    expect($this->student->completedCoursesCount())->toBe(1);
});

test('breakdown returns correct counts per type', function () {
    $general = createCoursesForSpecialization($this->specialization, 'general', 4);
    $specialized = createCoursesForSpecialization($this->specialization, 'specialized', 4);
    createCoursesForSpecialization($this->specialization, 'elective', 3);

    enrollAndPassCourses($this->student, array_slice($general, 0, 2), $this->semester);
    enrollAndPassCourses($this->student, $specialized, $this->semester);

    $breakdown = $this->student->getThesisEligibilityBreakdown();

    expect($breakdown['general']['required'])->toBe(4)
        ->and($breakdown['general']['completed'])->toBe(2)
        ->and($breakdown['specialized']['required'])->toBe(4)
        ->and($breakdown['specialized']['completed'])->toBe(4)
        ->and($breakdown['elective']['required'])->toBe(2)
        ->and($breakdown['elective']['completed'])->toBe(0);
});
