<?php

namespace App\Exceptions;

use App\Models\Semester;
use App\Models\Student;
use RuntimeException;

/**
 * Thrown when attempting to enroll a student who has an approved suspension
 * request for the target semester.
 *
 * Semester-scoped suspension: blocks enrollment / surveys / add-drop only
 * for the specific semester, while keeping the student active overall.
 * Check performed inside {@see \App\Actions\Enrollment\EnrollStudentInOffering}.
 */
class StudentSuspendedInSemesterException extends RuntimeException
{
    public function __construct(
        public readonly Student $student,
        public readonly Semester $semester,
    ) {
        parent::__construct(
            sprintf(
                'Student %d has an approved suspension for semester %d — cannot enroll.',
                $student->id,
                $semester->id,
            )
        );
    }
}
