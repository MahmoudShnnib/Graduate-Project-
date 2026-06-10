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

    protected static ?int $navigationSort = 3;

    public ?int $selectedSemesterId = null;

    public function getSemesters(): Collection
    {
        $student = auth()->user()->student;

        if (! $student) {
            return collect();
        }

        return Semester::whereHas(
            'courseOfferings.enrollments',
            fn ($q) => $q->where('student_id', $student->id)
                ->whereHas('grade', fn ($g) => $g->where(fn ($q) => $q
                    ->whereNotNull('midterm_published_at')
                    ->orWhereNotNull('published_at')))
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

        $query = StudentEnrollment::where('student_id', $student->id)
            ->with(['courseOffering.course', 'courseOffering.semester', 'grade'])
            ->whereHas('grade', fn ($g) => $g->where(fn ($q) => $q
                ->whereNotNull('midterm_published_at')
                ->orWhereNotNull('published_at')));

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
