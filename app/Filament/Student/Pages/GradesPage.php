<?php

namespace App\Filament\Student\Pages;

use App\Models\Semester;
use App\Models\StudentEnrollment;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class GradesPage extends Page
{
    protected string $view = 'filament.student.pages.grades';

    protected static ?string $navigationLabel = 'الدرجات';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 6;

    public ?int $selectedSemesterId = null;

    public function getSemesters(): Collection
    {
        $student = auth()->user()->student;

        if (! $student) {
            return collect();
        }

        return Semester::whereHas('courseOfferings.enrollments', fn ($q) => $q->where('student_id', $student->id))
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
            ->whereHas('grade');

        if ($this->selectedSemesterId) {
            $query->whereHas('courseOffering', fn ($q) => $q->where('semester_id', $this->selectedSemesterId));
        }

        return $query->get();
    }
}
