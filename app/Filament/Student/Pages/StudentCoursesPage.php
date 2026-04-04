<?php

namespace App\Filament\Student\Pages;

use App\Models\Semester;
use App\Models\StudentEnrollment;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class StudentCoursesPage extends Page
{
    protected string $view = 'filament.student.pages.student-courses';

    protected static ?string $navigationLabel = 'مقرراتي';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 4;

    public function getActiveSemester(): ?Semester
    {
        return Semester::where('is_active', true)->first();
    }

    public function getEnrollments(): Collection
    {
        $student = auth()->user()->student;
        $activeSemester = $this->getActiveSemester();

        if (! $student || ! $activeSemester) {
            return collect();
        }

        return StudentEnrollment::where('student_id', $student->id)
            ->whereHas('courseOffering', fn ($q) => $q->where('semester_id', $activeSemester->id))
            ->with(['courseOffering.course', 'courseOffering.faculty', 'grade'])
            ->get();
    }
}
