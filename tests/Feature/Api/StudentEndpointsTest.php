<?php

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\Specialization;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->spec = Specialization::factory()->create(['name' => 'علوم الحاسوب', 'code' => 99]);
    $this->user = User::factory()->withRole('student')->create([
        'password' => Hash::make('password'),
        'specialization_id' => $this->spec->id,
        'is_active' => true,
    ]);
    $this->student = Student::factory()->create([
        'user_id' => $this->user->id,
        'specialization_id' => $this->spec->id,
        'enrollment_no' => '26CS101',
    ]);
    Sanctum::actingAs($this->user);
});

it('returns the authenticated student profile via /me', function () {
    $this->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.enrollment_no', '26CS101')
        ->assertJsonPath('data.specialization.name', 'علوم الحاسوب');
});

it('returns the active semester', function () {
    Semester::factory()->create(['is_active' => false]);
    Semester::factory()->create(['is_active' => true, 'name' => 'الفصل الدراسي الأول 2026']);

    $this->getJson('/api/v1/semesters/active')
        ->assertOk()
        ->assertJsonPath('data.name', 'الفصل الدراسي الأول 2026')
        ->assertJsonPath('data.is_active', true);
});

it('returns enrollments filtered to the active semester', function () {
    $semester = Semester::factory()->create(['is_active' => true]);
    $course = Course::factory()->create();
    $course->specializations()->attach($this->spec, ['type' => 'general']);
    $offering = CourseOffering::factory()->create(['course_id' => $course->id, 'semester_id' => $semester->id]);
    $enrollment = StudentEnrollment::factory()->create([
        'student_id' => $this->student->id,
        'course_offering_id' => $offering->id,
        'status' => EnrollmentStatus::Enrolled,
    ]);

    $this->getJson('/api/v1/enrollments')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $enrollment->id)
        ->assertJsonPath('data.0.status.value', 'enrolled');
});

it('hides unpublished grade fields', function () {
    $semester = Semester::factory()->create(['is_active' => true]);
    $course = Course::factory()->create();
    $course->specializations()->attach($this->spec, ['type' => 'general']);
    $offering = CourseOffering::factory()->create(['course_id' => $course->id, 'semester_id' => $semester->id]);
    $enrollment = StudentEnrollment::factory()->create([
        'student_id' => $this->student->id,
        'course_offering_id' => $offering->id,
        'status' => EnrollmentStatus::Enrolled,
    ]);
    Grade::create([
        'enrollment_id' => $enrollment->id,
        'coursework_grade' => 50,
        'final_grade' => 30,
        'midterm_published_at' => null,
        'published_at' => null,
    ]);

    $this->getJson('/api/v1/grades')
        ->assertOk()
        ->assertJsonPath('data.0.grade.publish_state', 'unpublished')
        ->assertJsonPath('data.0.grade.coursework_grade', null)
        ->assertJsonPath('data.0.grade.final_grade', null)
        ->assertJsonPath('data.0.grade.letter', null);
});
