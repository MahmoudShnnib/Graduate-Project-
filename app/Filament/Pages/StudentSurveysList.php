<?php

namespace App\Filament\Pages;

use App\Models\Semester;
use App\Models\Student;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class StudentSurveysList extends Page
{
    protected string $view = 'filament.pages.student-surveys-list';

    protected static ?string $navigationLabel = 'استطلاعات الطلاب';

    protected static ?string $title = 'استطلاعات الطلاب';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'الإعدادات الأكاديمية';

    protected static ?int $navigationSort = 11;

    public function getActiveSemester(): ?Semester
    {
        return Semester::where('is_active', true)->first();
    }

    public function getStudentsWithSurveys(): Collection
    {
        $semester = $this->getActiveSemester();

        if (! $semester) {
            return collect();
        }

        return Student::whereHas('courseSurveys', fn ($q) => $q->where('semester_id', $semester->id))
            ->with(['user:id,name', 'specialization:id,name'])
            ->withCount(['courseSurveys' => fn ($q) => $q->where('semester_id', $semester->id)])
            ->orderBy('id')
            ->get();
    }

    public function getSurveyUrl(int $studentId): string
    {
        $semester = $this->getActiveSemester();

        return url("/admin/student-survey/{$studentId}/{$semester?->id}");
    }
}
