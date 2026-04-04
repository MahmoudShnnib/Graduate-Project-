<?php

namespace App\Observers;

use App\Enums\GradeStatus;
use App\Models\Grade;
use App\Services\GradeCalculationService;

class GradeObserver
{
    public function __construct(
        private GradeCalculationService $gradeCalculationService,
    ) {}

    public function updated(Grade $grade): void
    {
        if ($grade->wasChanged('status') && $grade->status === GradeStatus::Approved) {
            $student = $grade->enrollment->student;
            $this->gradeCalculationService->recalculateStudentGPA($student);
        }
    }
}
