<?php

namespace App\Filament\Student\Pages;

use App\Enums\EnrollmentStatus;
use App\Enums\GradeStatus;
use App\Models\Course;
use App\Models\Semester;
use App\Models\Student;
use App\Models\TransferCourse;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class CourseTreePage extends Page
{
    protected string $view = 'filament.student.pages.course-tree';

    protected static ?string $navigationLabel = 'الخطة الدراسية';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static string|\UnitEnum|null $navigationGroup = 'الأكاديمي';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'الخطة الدراسية';

    /** @var array<int, bool> */
    public array $expandedCourses = [];

    public function toggleCourseDetail(int $courseId): void
    {
        if (isset($this->expandedCourses[$courseId])) {
            unset($this->expandedCourses[$courseId]);
        } else {
            $this->expandedCourses[$courseId] = true;
        }
    }

    public function getStudent(): ?Student
    {
        return auth()->user()->student;
    }

    public function getActiveSemester(): ?Semester
    {
        return Semester::where('is_active', true)->first();
    }

    /**
     * Get all courses for the student's specialization, grouped by type.
     *
     * @return Collection<string, Collection<int, Course>>
     */
    public function getCoursesByType(): Collection
    {
        $student = $this->getStudent();

        if (! $student?->specialization_id) {
            return collect();
        }

        return Course::query()
            ->whereHas('specializations', fn ($q) => $q->where('specialization_id', $student->specialization_id))
            ->with(['specializations' => fn ($q) => $q->where('specialization_id', $student->specialization_id)])
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->groupBy(fn (Course $course) => $course->specializations->first()?->pivot?->type ?? 'specialized');
    }

    /**
     * Build a map of course_id => enrollment data for the student.
     *
     * @return array<int, array{status: string, grade: array|null, semester_name: string|null}>
     */
    public function getEnrollmentMap(): array
    {
        $student = $this->getStudent();

        if (! $student) {
            return [];
        }

        $activeSemester = $this->getActiveSemester();

        $enrollments = $student->enrollments()
            ->with(['courseOffering.course', 'courseOffering.semester', 'grade'])
            ->get();

        $map = [];

        foreach ($enrollments as $enrollment) {
            $courseId = $enrollment->courseOffering->course_id;
            $isCurrentSemester = $activeSemester && $enrollment->courseOffering->semester_id === $activeSemester->id;
            $grade = $enrollment->grade;

            $isPassed = $grade
                && $grade->status === GradeStatus::Approved
                && $grade->letter !== 'F';

            $isCurrentlyTaking = $enrollment->status === EnrollmentStatus::Enrolled
                && $isCurrentSemester
                && ! $isPassed;

            if ($isPassed) {
                $courseStatus = 'passed';
            } elseif ($isCurrentlyTaking) {
                $courseStatus = 'current';
            } else {
                continue;
            }

            $existingEntry = $map[$courseId] ?? null;
            if ($existingEntry && $existingEntry['status'] === 'passed') {
                continue;
            }

            $map[$courseId] = [
                'status' => $courseStatus,
                'grade' => $grade ? [
                    'coursework_grade' => $grade->coursework_grade,
                    'final_grade' => $grade->final_grade,
                    'total' => $grade->total,
                    'letter' => $grade->letter,
                    'points' => $grade->points,
                ] : null,
                'semester_name' => $enrollment->courseOffering->semester->name,
            ];
        }

        // Include transfer (equated) courses as passed
        if ($student->is_transfer) {
            $transferCourses = TransferCourse::where('student_id', $student->id)
                ->whereNotNull('letter')
                ->where('letter', '!=', 'F')
                ->get();

            foreach ($transferCourses as $transfer) {
                if (isset($map[$transfer->course_id]) && $map[$transfer->course_id]['status'] === 'passed') {
                    continue;
                }

                $map[$transfer->course_id] = [
                    'status' => 'passed',
                    'grade' => [
                        'coursework_grade' => $transfer->coursework_grade,
                        'final_grade' => $transfer->final_grade,
                        'total' => $transfer->total,
                        'letter' => $transfer->letter,
                        'points' => $transfer->points,
                    ],
                    'semester_name' => 'معادل',
                ];
            }
        }

        return $map;
    }
}
