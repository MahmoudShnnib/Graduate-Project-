<?php

namespace App\Services;

use App\Enums\StudentStatus;
use App\Models\Student;

class GradeCalculationService
{
    public function recalculateStudentGPA(Student $student, bool $force = false): void
    {
        if (! $force && $student->status !== StudentStatus::Active) {
            return;
        }

        $specializationId = $student->specialization_id;

        $enrollments = $student->activeEnrollments()
            ->whereHas('grade', function ($query) {
                $query->whereNotNull('published_at')
                    ->whereNotNull('letter');
            })
            ->where(function ($query) use ($specializationId) {
                $query->whereHas('courseOffering.course.specializations', function ($q) use ($specializationId) {
                    $q->where('specialization_id', $specializationId);
                })->orWhereDoesntHave('courseOffering.course.specializations');
            })
            ->with(['grade', 'courseOffering.course'])
            ->get();

        // When a student retakes a course, only the latest grade counts toward GPA.
        $latestPerCourse = $enrollments
            ->groupBy(fn ($e) => $e->courseOffering->course_id)
            ->map(fn ($group) => $group->sortByDesc('id')->first());

        $totalPoints = 0;
        $totalCredits = 0;

        foreach ($latestPerCourse as $enrollment) {
            $credits = $enrollment->courseOffering->course->credits;
            $points = (float) $enrollment->grade->points;
            $totalPoints += $points * $credits;
            $totalCredits += $credits;
        }

        $transferCourses = $student->transferCourses()
            ->whereNotNull('points')
            ->where(function ($query) use ($specializationId) {
                $query->whereHas('course.specializations', function ($q) use ($specializationId) {
                    $q->where('specialization_id', $specializationId);
                })->orWhereDoesntHave('course.specializations');
            })
            ->with('course')
            ->get();

        foreach ($transferCourses as $transferCourse) {
            // A transfer (معادلة) course is a passed course. If the student later
            // studied the same course in-system, that latest grade already counted
            // above and supersedes the equivalence — skip the transfer record so
            // the course is never counted twice.
            if ($latestPerCourse->has($transferCourse->course_id)) {
                continue;
            }

            $credits = $transferCourse->course->credits;
            $points = (float) $transferCourse->points;
            $totalPoints += $points * $credits;
            $totalCredits += $credits;
        }

        $student->gpa = $totalCredits > 0
            ? round($totalPoints / $totalCredits, 2)
            : 0.00;

        $student->save();
    }
}
