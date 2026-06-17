<?php

namespace App\Observers;

use App\Enums\EnrollmentStatus;
use App\Models\Grade;
use App\Services\GradeCalculationService;
use App\Services\WarningService;

class GradeObserver
{
    public function __construct(
        private GradeCalculationService $gradeCalculationService,
        private WarningService $warningService,
    ) {}

    public function created(Grade $grade): void
    {
        if ($grade->published_at !== null) {
            $this->handlePublication($grade);
        }
    }

    public function updating(Grade $grade): void
    {
        // Any change to the marks un-publishes the grade until an admin
        // re-publishes it (the single final-publish step).
        if ($grade->isDirty('coursework_grade') || $grade->isDirty('final_grade')) {
            $grade->published_at = null;
        }
    }

    public function updated(Grade $grade): void
    {
        if ($grade->wasChanged('published_at') && $grade->published_at !== null) {
            $this->handlePublication($grade);
        }
    }

    private function handlePublication(Grade $grade): void
    {
        $enrollment = $grade->enrollment;

        if (! $enrollment || $enrollment->status !== EnrollmentStatus::Enrolled) {
            return;
        }

        $student = $enrollment->student;

        if (! $student) {
            return;
        }

        $this->gradeCalculationService->recalculateStudentGPA($student);

        $this->warningService->evaluateGradeApproval($student, $grade);
    }
}
