<?php

namespace App\Exceptions;

use App\Models\CourseOffering;
use App\Models\Student;
use RuntimeException;

/**
 * Thrown when attempting to enroll a student in a course offering whose
 * course is not part of the student's specialization.
 *
 * Domain invariant: a student can only be enrolled in courses that belong
 * to their specialization (via the course_specialization pivot). The check
 * lives on {@see \App\Actions\Enrollment\EnrollStudentInOffering} so every
 * enrollment path (staff manual, survey-batch, add/drop approval) respects it.
 */
class CourseOutsideSpecializationException extends RuntimeException
{
    public function __construct(
        public readonly Student $student,
        public readonly CourseOffering $offering,
    ) {
        parent::__construct(
            sprintf(
                'Student %d (specialization %s) cannot enroll in course %d — course is not part of their specialization.',
                $student->id,
                $student->specialization_id ?? 'NULL',
                $offering->course_id,
            )
        );
    }
}
