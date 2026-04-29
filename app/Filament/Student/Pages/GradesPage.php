<?php

namespace App\Filament\Student\Pages;

use App\Models\Semester;
use App\Models\StudentEnrollment;
use App\Models\TransferCourse;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class GradesPage extends Page
{
    protected string $view = 'filament.student.pages.grades';

    protected static ?string $navigationLabel = 'الدرجات';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'الأكاديمي';

    protected static ?int $navigationSort = 6;

    public ?int $selectedSemesterId = null;

    public function getSemesters(): Collection
    {
        $student = auth()->user()->student;

        if (! $student) {
            return collect();
        }

        // Show every semester the student has ever had an enrollment in with
        // an approved grade — regardless of current enrollment status. This
        // keeps transcript visibility intact after cascade withdrawal (when
        // the student is moved to Inactive / Withdrawn / Graduated).
        return Semester::whereHas(
            'courseOfferings.enrollments',
            fn ($q) => $q->where('student_id', $student->id)
                ->whereHas('grade', fn ($g) => $g->where('status', \App\Enums\GradeStatus::Approved))
        )
            ->orderByDesc('year')
            ->get();
    }

    public function getGrades(): Collection
    {
        $student = auth()->user()->student;

        if (! $student) {
            return collect();
        }

        // No ->active() filter — we want the transcript to include approved
        // grades even on enrollments that were withdrawn via cascade (status
        // change on the student). The whereHas('grade') below keeps this
        // from including enrollments that were dropped without grades.
        $query = StudentEnrollment::where('student_id', $student->id)
            ->with(['courseOffering.course', 'courseOffering.semester', 'grade'])
            ->whereHas('grade');

        if ($this->selectedSemesterId) {
            $query->whereHas('courseOffering', fn ($q) => $q->where('semester_id', $this->selectedSemesterId));
        }

        return $query->get();
    }

    public function getTransferCourses(): Collection
    {
        $student = auth()->user()->student;

        if (! $student) {
            return collect();
        }

        return TransferCourse::where('student_id', $student->id)
            ->with('course')
            ->get();
    }
}
