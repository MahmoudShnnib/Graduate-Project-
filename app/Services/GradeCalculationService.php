<?php

namespace App\Services;

use App\Enums\GradeStatus;
use App\Enums\StudentStatus;
use App\Models\Student;

class GradeCalculationService
{
    public function recalculateStudentGPA(Student $student): void
    {
        if ($student->status === StudentStatus::Suspended) {
            return;
        }

        $enrollments = $student->enrollments()
            ->whereHas('grade', function ($query) {
                $query->where('status', GradeStatus::Approved)
                    ->whereNotNull('letter');
            })
            ->with(['grade', 'courseOffering.course'])
            ->get();

        $totalPoints = 0;
        $totalCredits = 0;

        foreach ($enrollments as $enrollment) {
            $credits = $enrollment->courseOffering->course->credits;
            $points = (float) $enrollment->grade->points;
            $totalPoints += $points * $credits;
            $totalCredits += $credits;
        }

        $student->gpa = $totalCredits > 0
            ? round($totalPoints / $totalCredits, 2)
            : 0.00;

        $student->save();
    }
}
