<?php

namespace App\Actions\Enrollment;

use App\Enums\EnrollmentStatus;
use App\Models\CourseOffering;
use App\Models\Student;
use App\Models\StudentEnrollment;

class DropStudentFromOffering
{
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

        return true;
    }
}
