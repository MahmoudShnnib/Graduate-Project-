<?php

namespace App\Observers;

use App\Actions\Enrollment\DropStudentFromOffering;
use App\Enums\EnrollmentStatus;
use App\Models\CourseOffering;
use App\Services\GradeCalculationService;

/**
 * Cascades side effects on CourseOffering state changes.
 *
 * Current scope: when is_open transitions true → false, withdraw every
 * currently-active enrollment in that offering and trigger per-student GPA
 * recalculation. The write-path counterpart (blocking new enrollments into
 * a closed offering) lives in the EnrollStudentInOffering action via the
 * CourseOfferingClosedException guard.
 */
class CourseOfferingObserver
{
    public function __construct(
        private DropStudentFromOffering $dropStudentFromOffering,
        private GradeCalculationService $gradeCalculationService,
    ) {}

    public function updated(CourseOffering $offering): void
    {
        if (! $offering->wasChanged('is_open')) {
            return;
        }

        // Only cascade on the true → false transition. No-op when opening
        // an offering, reopening, or toggling the same value repeatedly.
        $previousIsOpen = (bool) $offering->getOriginal('is_open');
        $currentIsOpen = (bool) $offering->is_open;

        if (! $previousIsOpen || $currentIsOpen) {
            return;
        }

        $this->cascadeWithdraw($offering);
    }

    private function cascadeWithdraw(CourseOffering $offering): void
    {
        $activeEnrollments = $offering->activeEnrollments()->with('student')->get();

        // Track affected students to recalc GPA once per student (not once
        // per enrollment — de-duplicates if a student had multiple rows
        // which can't happen today but is defensive).
        $affectedStudents = [];

        foreach ($activeEnrollments as $enrollment) {
            $student = $enrollment->student;

            if (! $student) {
                continue;
            }

            $this->dropStudentFromOffering->__invoke(
                $student,
                $offering,
                EnrollmentStatus::Withdrawn,
            );

            $affectedStudents[$student->id] = $student;
        }

        foreach ($affectedStudents as $student) {
            $this->gradeCalculationService->recalculateStudentGPA($student);
        }
    }
}
