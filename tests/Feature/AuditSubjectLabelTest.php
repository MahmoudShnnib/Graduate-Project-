<?php

use App\Models\CourseOffering;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\StudentEnrollment;
use App\Services\AuditService;

it('labels a grade audit row with the student and course names', function () {
    ['student' => $student] = studentWithRecord();
    $offering = CourseOffering::factory()->create();
    $enrollment = StudentEnrollment::factory()->create([
        'student_id' => $student->id,
        'course_offering_id' => $offering->id,
    ]);
    $grade = Grade::factory()->create(['enrollment_id' => $enrollment->id]);

    $label = AuditService::subjectLabel($grade);

    expect($label)
        ->toContain($student->user->name)
        ->toContain($offering->course->name)
        ->toContain(' - ');
});

it('labels a course-offering audit row with the course and semester names', function () {
    $semester = Semester::factory()->create(['name' => 'خريف 2025']);
    $offering = CourseOffering::factory()->create(['semester_id' => $semester->id]);

    $label = AuditService::subjectLabel($offering);

    expect($label)
        ->toContain($offering->course->name)
        ->toContain('خريف 2025')
        ->toContain(' - ');
});
