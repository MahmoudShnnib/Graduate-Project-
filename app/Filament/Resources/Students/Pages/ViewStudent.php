<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Models\Semester;
use App\Models\StudentEnrollment;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Collection;

class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;

    protected string $view = 'filament.resources.students.pages.view-student';

    public ?int $selectedSemesterId = null;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function getStudentSemesters(): Collection
    {
        return Semester::query()
            ->whereHas('courseOfferings.enrollments', function ($query) {
                $query->where('student_id', $this->record->id);
            })
            ->orderByDesc('year')
            ->orderByDesc('id')
            ->get();
    }

    public function getSemesterGrades(): Collection
    {
        if (! $this->selectedSemesterId) {
            return collect();
        }

        return StudentEnrollment::query()
            ->where('student_id', $this->record->id)
            ->whereHas('courseOffering', function ($query) {
                $query->where('semester_id', $this->selectedSemesterId);
            })
            ->with([
                'courseOffering.course',
                'grade',
            ])
            ->get();
    }
}
