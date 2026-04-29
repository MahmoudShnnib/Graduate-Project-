<?php

namespace App\Services;

use App\Enums\ExceptionalRequestStatus;
use App\Enums\StudentStatus;
use App\Enums\WarningType;
use App\Models\ExceptionalRequest;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentWarning;

class WarningService
{
    public function evaluateGradeApproval(Student $student, Grade $grade): void
    {
        $courseId = $grade->enrollment->courseOffering->course_id;
        $semester = $grade->enrollment->courseOffering->semester;

        if ($grade->letter === 'F') {
            $this->handleCourseFailure($student, $courseId, $semester);
        } else {
            $this->handleCoursePass($student, $courseId);
        }
    }

    public function processInactiveWarning(Student $student, ?Semester $semester = null): void
    {
        $semester ??= Semester::where('is_active', true)->first();

        if (! $semester) {
            return;
        }

        $alreadyWarned = StudentWarning::where('student_id', $student->id)
            ->where('type', WarningType::ConsecutiveInactive)
            ->where('semester_id', $semester->id)
            ->where('is_active', true)
            ->exists();

        if ($alreadyWarned) {
            return;
        }

        StudentWarning::create([
            'student_id' => $student->id,
            'type' => WarningType::ConsecutiveInactive,
            'semester_id' => $semester->id,
        ]);

        $activeInactiveCount = StudentWarning::where('student_id', $student->id)
            ->where('type', WarningType::ConsecutiveInactive)
            ->where('is_active', true)
            ->count();

        if ($activeInactiveCount >= 2) {
            $this->dismissStudent($student, WarningType::ConsecutiveInactive);

            return;
        }

        $this->syncWarningsCount($student);
    }

    public function clearInactiveWarnings(Student $student): void
    {
        StudentWarning::where('student_id', $student->id)
            ->where('type', WarningType::ConsecutiveInactive)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'resolved_at' => now(),
            ]);

        $this->syncWarningsCount($student);
    }

    public function syncWarningsCount(Student $student): void
    {
        $count = StudentWarning::where('student_id', $student->id)
            ->where('is_active', true)
            ->count();

        $student->updateQuietly(['warnings_count' => $count]);
    }

    private function handleCourseFailure(Student $student, int $courseId, Semester $semester): void
    {
        $existingWarning = StudentWarning::where('student_id', $student->id)
            ->where('type', WarningType::CourseFailure)
            ->where('course_id', $courseId)
            ->where('is_active', true)
            ->first();

        if ($existingWarning) {
            $this->dismissStudent($student, WarningType::CourseFailure, $courseId);

            if ($existingWarning->has_exceptional_chance) {
                $lastAccepted = ExceptionalRequest::where('student_id', $student->id)
                    ->where('status', ExceptionalRequestStatus::Accepted)
                    ->latest()
                    ->first();

                $lastAccepted?->update(['can_reapply' => false]);
            }

            return;
        }

        StudentWarning::create([
            'student_id' => $student->id,
            'type' => WarningType::CourseFailure,
            'course_id' => $courseId,
            'semester_id' => $semester->id,
        ]);

        $this->syncWarningsCount($student);
    }

    private function handleCoursePass(Student $student, int $courseId): void
    {
        $resolved = StudentWarning::where('student_id', $student->id)
            ->where('type', WarningType::CourseFailure)
            ->where('course_id', $courseId)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'resolved_at' => now(),
            ]);

        if ($resolved > 0) {
            $this->syncWarningsCount($student);
        }
    }

    private function dismissStudent(Student $student, WarningType $type, ?int $courseId = null): void
    {
        $student->update([
            'status' => StudentStatus::Dismissed,
            'dismissed_at' => now(),
            'dismissal_type' => $type,
            'dismissal_course_id' => $courseId,
        ]);
    }
}
