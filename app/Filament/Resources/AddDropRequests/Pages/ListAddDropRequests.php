<?php

namespace App\Filament\Resources\AddDropRequests\Pages;

use App\Filament\Resources\AddDropRequests\AddDropRequestResource;
use App\Models\Semester;
use App\Models\Student;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;

/**
 * Grouped-by-student listing for add/drop requests.
 *
 * One row per student with outstanding requests in the active semester.
 * Clicking a row opens {@see \App\Filament\Pages\StudentAddDropView} where
 * the admin decides each request individually. The overall row is marked
 * as "completed" when every request has been decided (approved/rejected).
 */
class ListAddDropRequests extends ListRecords
{
    protected static string $resource = AddDropRequestResource::class;

    protected string $view = 'filament.resources.add-drop-requests.pages.list-add-drop-requests';

    public function getActiveSemester(): ?Semester
    {
        return Semester::where('is_active', true)->first();
    }

    /**
     * Students with at least one add/drop request in the active semester,
     * hydrated with user/specialization and total + pending counts.
     */
    public function getStudentsWithRequests(): Collection
    {
        $semester = $this->getActiveSemester();

        if (! $semester) {
            return collect();
        }

        return Student::query()
            ->whereHas('addDropRequests', fn ($q) => $q->whereHas(
                'courseOffering',
                fn ($co) => $co->where('semester_id', $semester->id)
            ))
            ->with(['user:id,name', 'specialization:id,name'])
            ->withCount([
                'addDropRequests as total_requests_count' => fn ($q) => $q->whereHas(
                    'courseOffering',
                    fn ($co) => $co->where('semester_id', $semester->id)
                ),
                'addDropRequests as pending_requests_count' => fn ($q) => $q
                    ->where('status', 'pending')
                    ->whereHas('courseOffering', fn ($co) => $co->where('semester_id', $semester->id)),
            ])
            ->orderBy('id')
            ->get();
    }

    public function getDetailUrl(int $studentId): string
    {
        $semester = $this->getActiveSemester();

        return url("/admin/student-add-drop/{$studentId}/{$semester?->id}");
    }

    /**
     * Hide the default header actions (create) — this index is read-only;
     * admins act from the detail page.
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
