<?php

namespace App\Actions\Enrollment;

use App\Enums\EnrollmentStatus;
use App\Models\CourseOffering;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\AuditService;

class DropStudentFromOffering
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function __invoke(
        Student $student,
        CourseOffering $offering,
        EnrollmentStatus $to = EnrollmentStatus::Dropped,
    ): bool {
        $enrollment = StudentEnrollment::query()
            ->where('student_id', $student->id)
            ->where('course_offering_id', $offering->id)
            ->active()
            ->first();

        if (! $enrollment) {
            return false;
        }

        $enrollment->update(['status' => $to]);

        $this->audit->log('student_dropped', $enrollment);

        return true;
    }
}
