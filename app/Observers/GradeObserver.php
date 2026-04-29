<?php

namespace App\Observers;

use App\Enums\EnrollmentStatus;
use App\Enums\GradeStatus;
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
        if ($grade->status !== GradeStatus::Approved) {
            return;
        }

        $this->handleApproval($grade);
    }

    public function updated(Grade $grade): void
    {
        if (! $grade->wasChanged('status') || $grade->status !== GradeStatus::Approved) {
            return;
        }

        $this->handleApproval($grade);
    }

    private function handleApproval(Grade $grade): void
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
