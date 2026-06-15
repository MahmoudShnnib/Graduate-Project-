<?php

use App\Enums\AddDropType;
use App\Enums\EnrollmentStatus;
use App\Filament\Pages\StudentAddDropView;
use App\Filament\Pages\StudentSurveyView;
use App\Filament\Resources\CourseOfferings\Pages\EnrollStudents;
use App\Models\AddDropRequest;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\StudentEnrollment;
use Livewire\Livewire;

/**
 * Builds a student actively enrolled in a course with a published passing
 * grade (B = 3.0), so the stored GPA is non-zero before we drop the course.
 * Removing the course must drive the GPA back to 0.00.
 *
 * @return array{student: \App\Models\Student, offering: \App\Models\CourseOffering, course: \App\Models\Course, semester: \App\Models\Semester, enrollment: \App\Models\StudentEnrollment}
 */
function gpaDropFixture(): array
{
    $semester = Semester::factory()->active()->withAddDrop()->create();
    $course = Course::factory()->create(['credits' => 3]);
    $offering = CourseOffering::factory()->create([
        'course_id' => $course->id,
        'semester_id' => $semester->id,
    ]);

    ['student' => $student] = studentWithRecord();
    $course->specializations()->attach($student->specialization_id, ['type' => 'general']);

    $enrollment = StudentEnrollment::factory()->create([
        'student_id' => $student->id,
        'course_offering_id' => $offering->id,
        'status' => EnrollmentStatus::Enrolled,
    ]);

    Grade::factory()->passing()->create(['enrollment_id' => $enrollment->id]);

    $student->refresh();
    expect((float) $student->gpa)->toBe(3.0); // sanity: GPA reflects the published grade

    return compact('student', 'offering', 'course', 'semester', 'enrollment');
}

it('recalculates GPA when an admin unenrolls a student from the enroll page', function () {
    ['student' => $student, 'offering' => $offering] = gpaDropFixture();

    $this->actingAs(adminUser());

    Livewire::test(EnrollStudents::class, ['record' => $offering])
        ->callTableAction('unenroll', $student);

    expect((float) $student->fresh()->gpa)->toBe(0.0);
});

it('recalculates GPA when an admin bulk-unenrolls students from the enroll page', function () {
    ['student' => $student, 'offering' => $offering] = gpaDropFixture();

    $this->actingAs(adminUser());

    Livewire::test(EnrollStudents::class, ['record' => $offering])
        ->callTableBulkAction('unenroll_selected', [$student]);

    expect((float) $student->fresh()->gpa)->toBe(0.0);
});

it('recalculates GPA when an admin approves a drop add/drop request', function () {
    ['student' => $student, 'offering' => $offering, 'semester' => $semester] = gpaDropFixture();

    $request = AddDropRequest::create([
        'student_id' => $student->id,
        'course_offering_id' => $offering->id,
        'type' => AddDropType::Drop,
        'status' => 'pending',
    ]);

    $this->actingAs(adminUser());

    Livewire::test(StudentAddDropView::class, ['student' => $student, 'semester' => $semester])
        ->callTableAction('approve', $request);

    expect((float) $student->fresh()->gpa)->toBe(0.0);
});

it('recalculates GPA when an admin unenrolls from the survey view page', function () {
    ['student' => $student, 'course' => $course, 'semester' => $semester] = gpaDropFixture();

    $this->actingAs(adminUser());

    Livewire::test(StudentSurveyView::class, ['student' => $student, 'semester' => $semester])
        ->call('unenrollStudent', $course->id);

    expect((float) $student->fresh()->gpa)->toBe(0.0);
});
